<?php
/**
 * HTML dos resultados. O mesmo markup é usado na primeira carga (shortcode)
 * e nas atualizações automáticas (REST), então só existe um caminho de renderização.
 */

defined( 'ABSPATH' ) || exit;

class NFE_Render {

	/** "SÃO LOURENÇO DO OESTE" → "São Lourenço do Oeste". */
	public static function titulo( $s ) {
		$s = trim( (string) $s );
		if ( '' === $s ) {
			return '';
		}
		$s = function_exists( 'mb_convert_case' ) ? mb_convert_case( mb_strtolower( $s, 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' ) : ucwords( strtolower( $s ) );
		$s = preg_replace_callback(
			'/(?<=\s)(De|Da|Do|Das|Dos|E|Di|Du|No|Na|Nos|Nas|Em|Pra|Para|Por|Com)(?=\s)/u',
			function ( $m ) {
				return strtolower( $m[1] );
			},
			$s
		);
		// Siglas comuns em nomes de urna e de coligações.
		return preg_replace_callback(
			'/\b(Sc|Br|Pt|Pl|Psd|Mdb|Pp|Psb|Pdt|Psdb|Psol|Pv|Pcdob|Ii|Iii|Iv)\b/u',
			function ( $m ) {
				return strtoupper( $m[1] );
			},
			$s
		);
	}

	public static function n( $v ) {
		return number_format( (float) $v, 0, ',', '.' );
	}

	public static function pct( $v, $dec = 2 ) {
		return number_format( (float) $v, $dec, ',', '.' ) . '%';
	}

	private static function iniciais( $nome ) {
		$p = preg_split( '/\s+/', trim( $nome ) );
		$i = mb_substr( $p[0], 0, 1 );
		if ( count( $p ) > 1 ) {
			$i .= mb_substr( end( $p ), 0, 1 );
		}
		return mb_strtoupper( $i );
	}

	private static function foto( $c, $mostrar, $base ) {
		$ini = esc_html( self::iniciais( $c['nome'] ) );
		if ( ! $mostrar ) {
			return '<span class="nfe-foto nfe-foto--ini" aria-hidden="true">' . $ini . '</span>';
		}
		return '<img class="nfe-foto" src="' . esc_url( $base . rawurlencode( $c['sq'] ) . '.jpeg' ) . '" alt="" loading="lazy" decoding="async" width="160" height="214" data-ini="' . $ini . '">';
	}

	private static function tag_situacao( $c ) {
		$st = trim( $c['situacao'] );
		$h  = '';
		if ( '' !== $st && 'não eleito' !== mb_strtolower( $st ) ) {
			$cls = 'nfe-tag--info';
			if ( $c['eleito'] ) {
				$cls = 'nfe-tag--eleito';
			} elseif ( false !== stripos( $st, 'turno' ) ) {
				$cls = 'nfe-tag--turno';
			} elseif ( false !== stripos( $st, 'suplente' ) ) {
				$cls = 'nfe-tag--suplente';
			}
			$h .= '<span class="nfe-tag ' . $cls . '">' . esc_html( $st ) . '</span>';
		}
		if ( '' !== $c['validade'] && 0 !== stripos( $c['validade'], 'válido' ) ) {
			$h .= '<span class="nfe-tag nfe-tag--alerta">' . esc_html( $c['validade'] ) . '</span>';
		}
		return $h;
	}

	/**
	 * @param array|WP_Error $r    Resultado de NFE_TSE::resultado().
	 * @param array          $opts layout (completo|compacto), limite, fotos, titulo, link.
	 */
	public static function resultado( $r, $opts = array() ) {
		$opts = wp_parse_args(
			$opts,
			array(
				'layout' => 'completo',
				'limite' => 0,
				'fotos'  => (bool) NFE_Options::get( 'fotos' ),
				'titulo' => '',
				'link'   => '',
			)
		);

		if ( is_wp_error( $r ) ) {
			$code = $r->get_error_code();
			if ( 'nfe_404' === $code ) {
				$msg = 'Os resultados ainda não foram publicados pelo TSE. A página será atualizada automaticamente.';
			} elseif ( in_array( $code, array( 'nfe_sem_t2', 'nfe_local', 'nfe_cargo', 'nfe_uf' ), true ) ) {
				$msg = $r->get_error_message(); // Erro de configuração do shortcode: mostra o motivo.
			} else {
				$msg = 'Não foi possível carregar os resultados agora. Tentaremos de novo em instantes.';
			}
			return '<div class="nfe-res nfe-res--vazio"><p class="nfe-aviso">' . esc_html( $msg ) . '</p>'
				. '<footer class="nfe-foot"><span>Fonte: TSE</span><span class="nfe-countdown" aria-hidden="true"></span></footer></div>';
		}

		$compacto = 'compacto' === $opts['layout'];
		$limite   = (int) $opts['limite'];
		if ( $limite <= 0 ) {
			$limite = $compacto ? ( $r['proporcional'] ? 5 : 3 ) : ( $r['proporcional'] ? 20 : 0 );
		}

		ob_start();
		$classes = 'nfe-res nfe-res--' . ( $r['proporcional'] ? 'prop' : 'maj' ) . ( $compacto ? ' nfe-res--compacto' : '' );
		echo '<div class="' . esc_attr( $classes ) . '" data-finalizada="' . ( $r['finalizada'] ? '1' : '0' ) . '">';

		self::cabecalho( $r, $opts['titulo'], $compacto );

		if ( ! empty( $r['aviso'] ) ) {
			echo '<p class="nfe-aviso">' . esc_html( $r['aviso'] ) . '</p>';
		}
		if ( ! empty( $r['_stale'] ) ) {
			echo '<p class="nfe-aviso">Falha temporária ao consultar o TSE: exibindo os últimos dados recebidos.</p>';
		}
		if ( 0 === $r['secoes']['totalizadas'] && ! $r['finalizada'] ) {
			echo '<p class="nfe-aviso nfe-aviso--neutro">Aguardando o início da divulgação dos resultados pelo TSE (a partir das 17h, horário de Brasília).</p>';
		}

		if ( $r['proporcional'] ) {
			self::proporcional( $r, $limite, $opts['fotos'], $compacto );
		} else {
			self::majoritario( $r, $limite, $opts['fotos'], $compacto );
		}

		if ( ! $compacto ) {
			self::estatisticas( $r );
		}

		echo '<footer class="nfe-foot">';
		echo '<span>Fonte: <a href="https://resultados.tse.jus.br" target="_blank" rel="noopener">TSE</a></span>';
		if ( $compacto && $opts['link'] ) {
			echo '<a class="nfe-link" href="' . esc_url( $opts['link'] ) . '">Ver apuração completa →</a>';
		}
		echo '<span class="nfe-countdown" aria-hidden="true"></span>';
		echo '</footer></div>';

		return ob_get_clean();
	}

	private static function cabecalho( $r, $titulo, $compacto ) {
		$ano = substr( $r['data'], -4 );
		if ( '' === $titulo ) {
			$titulo = $r['cargo'] . ' · ' . $r['local']['nome'];
		}
		$pct = $r['secoes']['pct'];

		echo '<header class="nfe-head">';
		echo '<div><span class="nfe-eyebrow">Eleições ' . esc_html( $ano ) . ' · ' . (int) $r['turno'] . 'º turno</span>';
		echo '<h3 class="nfe-title">' . esc_html( $titulo ) . '</h3></div>';
		echo '<div class="nfe-pills">';
		if ( $r['finalizada'] ) {
			echo '<span class="nfe-pill nfe-pill--ok">Totalização encerrada</span>';
		} elseif ( $r['definida'] ) {
			echo '<span class="nfe-pill nfe-pill--ok">Matematicamente definido</span>';
		} elseif ( $pct >= 100 ) {
			echo '<span class="nfe-pill nfe-pill--info">Seções 100% apuradas</span>';
		} elseif ( $r['secoes']['totalizadas'] > 0 ) {
			echo '<span class="nfe-pill nfe-pill--live">Ao vivo</span>';
		}
		echo '</div></header>';

		echo '<div class="nfe-progress" role="progressbar" aria-label="Seções totalizadas" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( round( $pct, 2 ) ) . '"><span style="width:' . esc_attr( min( 100, max( 0, $pct ) ) ) . '%"></span></div>';

		echo '<p class="nfe-meta"><strong>' . esc_html( self::pct( $pct ) ) . '</strong> das seções totalizadas';
		if ( ! $compacto ) {
			echo ' (' . esc_html( self::n( $r['secoes']['totalizadas'] ) ) . ' de ' . esc_html( self::n( $r['secoes']['total'] ) ) . ')';
		}
		if ( $r['atualizado'] ) {
			$p = explode( ' ', $r['atualizado'] );
			echo ' <span class="nfe-sep">·</span> <span aria-live="polite">Atualizado ' . esc_html( isset( $p[1] ) ? 'em ' . substr( $p[0], 0, 5 ) . ' às ' . $p[1] : $p[0] ) . '</span>';
		}
		if ( ! $compacto && $r['proporcional'] ) {
			echo ' <span class="nfe-sep">·</span> ' . (int) $r['vagas'] . ' vagas';
			if ( $r['qe'] ) {
				echo ' <span class="nfe-sep">·</span> Quociente eleitoral: ' . esc_html( self::n( $r['qe'] ) );
			}
		}
		echo '</p>';
	}

	private static function majoritario( $r, $limite, $fotos, $compacto ) {
		$cands = $r['candidatos'];
		$total = count( $cands );
		echo '<ol class="nfe-cands">';
		foreach ( $cands as $i => $c ) {
			if ( $limite > 0 && $i >= $limite ) {
				break;
			}
			$cls = 'nfe-cand' . ( $c['eleito'] ? ' is-eleito' : '' ) . ( 0 === $i && $c['votos'] > 0 ? ' is-lider' : '' );
			echo '<li class="' . esc_attr( $cls ) . '">';
			echo self::foto( $c, $fotos, $r['fotos'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em foto().
			echo '<div class="nfe-cand__body">';
			echo '<div class="nfe-cand__top"><span class="nfe-cand__nome">' . esc_html( $c['nome'] ) . '</span>';
			echo self::tag_situacao( $c ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="nfe-cand__pct">' . esc_html( self::pct( $c['pct'] ) ) . '</span></div>';
			echo '<div class="nfe-bar"><span style="width:' . esc_attr( min( 100, $c['pct'] ) ) . '%"></span></div>';
			echo '<div class="nfe-cand__sub"><span>' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span>';
			if ( ! $compacto ) {
				if ( $c['agrup'] ) {
					echo '<span class="nfe-cand__agr" title="' . esc_attr( $c['agrup_com'] ) . '">' . esc_html( $c['agrup'] ) . '</span>';
				}
				foreach ( $c['vices'] as $v ) {
					$rot = 'v' === $v['tipo'] ? 'Vice' : ( 's1' === $v['tipo'] ? '1º suplente' : ( 's2' === $v['tipo'] ? '2º suplente' : 'Suplente' ) );
					echo '<span>' . esc_html( $rot . ': ' . $v['nome'] . ( $v['partido'] ? ' (' . $v['partido'] . ')' : '' ) ) . '</span>';
				}
			}
			echo '<span class="nfe-cand__votos">' . esc_html( self::n( $c['votos'] ) ) . ' votos</span></div>';
			echo '</div></li>';
		}
		echo '</ol>';
		if ( $compacto && $limite > 0 && $total > $limite ) {
			echo '<p class="nfe-mais">+ ' . (int) ( $total - $limite ) . ' candidatos</p>';
		}
	}

	private static function proporcional( $r, $limite, $fotos, $compacto ) {
		$cands = $r['candidatos'];
		$total = count( $cands );

		if ( ! $compacto && 'mun' !== $r['local']['tipo'] ) {
			$com_vaga = array_filter(
				$r['agrupamentos'],
				function ( $a ) {
					return $a['vagas'] > 0;
				}
			);
			if ( $com_vaga ) {
				echo '<div class="nfe-vagas"><h4 class="nfe-sub">Cadeiras por partido / federação</h4><ul>';
				foreach ( $com_vaga as $a ) {
					echo '<li title="' . esc_attr( $a['nome'] . ' — ' . self::n( $a['votos'] ) . ' votos' ) . '"><strong>' . (int) $a['vagas'] . '</strong> <span>' . esc_html( $a['sigla'] ) . '</span></li>';
				}
				echo '</ul></div>';
			}
		}

		if ( ! $compacto ) {
			echo '<div class="nfe-tools">';
			echo '<label class="nfe-search"><span class="screen-reader-text">Buscar candidato</span><input type="search" placeholder="Buscar por nome, número ou partido" autocomplete="off"></label>';
			if ( array_filter( wp_list_pluck( $cands, 'eleito' ) ) ) {
				echo '<label class="nfe-check"><input type="checkbox" class="nfe-only-eleitos"> Só eleitos</label>';
			}
			echo '</div>';
		}

		echo '<ol class="nfe-rows" data-limite="' . (int) $limite . '" data-total="' . (int) $total . '">';
		foreach ( $cands as $i => $c ) {
			if ( $compacto && $i >= $limite ) {
				break;
			}
			$busca = strtolower( remove_accents( $c['nome'] . ' ' . $c['completo'] . ' ' . $c['numero'] . ' ' . $c['partido'] . ' ' . $c['agrup_com'] ) );
			$cls   = 'nfe-row' . ( $c['eleito'] ? ' is-eleito' : '' ) . ( $limite > 0 && $i >= $limite ? ' is-extra' : '' );
			echo '<li class="' . esc_attr( $cls ) . '" data-busca="' . esc_attr( $busca ) . '"' . ( $c['eleito'] ? ' data-eleito="1"' : '' ) . '>';
			echo '<span class="nfe-row__pos">' . (int) ( $i + 1 ) . 'º</span>';
			echo self::foto( $c, $fotos, $r['fotos'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="nfe-row__main"><span class="nfe-cand__nome">' . esc_html( $c['nome'] ) . '</span>';
			echo '<span class="nfe-row__sub">' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span></span>';
			echo '<span class="nfe-row__sit">' . self::tag_situacao( $c ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="nfe-row__num"><strong>' . esc_html( self::n( $c['votos'] ) ) . '</strong><small>' . esc_html( self::pct( $c['pct'] ) ) . '</small></span>';
			echo '</li>';
		}
		echo '</ol>';

		if ( ! $compacto ) {
			echo '<p class="nfe-vazio" hidden>Nenhum candidato encontrado.</p>';
			if ( $limite > 0 && $total > $limite ) {
				echo '<button type="button" class="nfe-more" data-total="' . (int) $total . '">Mostrar todos os ' . (int) $total . ' candidatos</button>';
			}
		} elseif ( $total > $limite ) {
			echo '<p class="nfe-mais">+ ' . (int) ( $total - $limite ) . ' candidatos</p>';
		}
	}

	private static function estatisticas( $r ) {
		$itens = array(
			'Comparecimento' => $r['comparec'],
			'Abstenção'      => $r['abstencao'],
			'Válidos'        => $r['validos'],
			'Brancos'        => $r['brancos'],
			'Nulos'          => $r['nulos'],
		);
		echo '<dl class="nfe-stats">';
		foreach ( $itens as $rot => $v ) {
			echo '<div><dt>' . esc_html( $rot ) . '</dt><dd><strong>' . esc_html( self::pct( $v[1] ) ) . '</strong><span>' . esc_html( self::n( $v[0] ) ) . '</span></dd></div>';
		}
		echo '</dl>';
		if ( ! empty( $r['situacao_de'] ) && ( $r['finalizada'] || array_filter( wp_list_pluck( $r['candidatos'], 'eleito' ) ) ) ) {
			echo '<p class="nfe-nota">Votos no recorte de ' . esc_html( $r['local']['nome'] ) . '. A situação (eleito, 2º turno…) refere-se ao resultado em ' . esc_html( $r['situacao_de'] ) . '.</p>';
		}
	}
}
