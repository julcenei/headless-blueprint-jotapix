<?php
/**
 * HTML dos resultados. O mesmo markup é usado na primeira carga (shortcode)
 * e nas atualizações automáticas (REST), então só existe um caminho de renderização.
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Render {

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
			'/\b(Sc|Br|Pt|Pl|Psd|Mdb|Pp|Psb|Pdt|Psdb|Psol|Pv|Pcdob|Fe|Ii|Iii|Iv)\b/u',
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
			return '<span class="jpxe-foto jpxe-foto--ini" aria-hidden="true">' . $ini . '</span>';
		}
		return '<img class="jpxe-foto" src="' . esc_url( $base . rawurlencode( $c['sq'] ) . '.jpeg' ) . '" alt="" loading="lazy" decoding="async" width="160" height="214" data-ini="' . $ini . '">';
	}

	private static function tag_situacao( $c ) {
		$st = trim( $c['situacao'] );
		$h  = '';
		if ( '' !== $st && 'não eleito' !== mb_strtolower( $st ) ) {
			$cls = 'jpxe-tag--info';
			if ( $c['eleito'] ) {
				$cls = 'jpxe-tag--eleito';
			} elseif ( false !== stripos( $st, 'turno' ) ) {
				$cls = 'jpxe-tag--turno';
			} elseif ( false !== stripos( $st, 'suplente' ) ) {
				$cls = 'jpxe-tag--suplente';
			}
			$h .= '<span class="jpxe-tag ' . $cls . '">' . esc_html( $st ) . '</span>';
		}
		if ( '' !== $c['validade'] && 0 !== stripos( $c['validade'], 'válido' ) ) {
			$h .= '<span class="jpxe-tag jpxe-tag--alerta">' . esc_html( $c['validade'] ) . '</span>';
		}
		return $h;
	}

	/**
	 * @param array|WP_Error $r    Resultado de JPXE_TSE::resultado().
	 * @param array          $opts layout (completo|compacto), limite, fotos, titulo, link.
	 */
	public static function resultado( $r, $opts = array() ) {
		$opts = wp_parse_args(
			$opts,
			array(
				'layout' => 'completo',
				'limite' => 0,
				'fotos'  => (bool) JPXE_Options::get( 'fotos' ),
				'titulo' => '',
				'link'   => '',
			)
		);

		if ( is_wp_error( $r ) && 'jpxe_frio' === $r->get_error_code() ) {
			return self::esqueleto();
		}
		if ( is_wp_error( $r ) ) {
			$code = $r->get_error_code();
			if ( 'jpxe_404' === $code ) {
				$msg = 'Os resultados ainda não foram publicados pelo TSE. A página será atualizada automaticamente.';
			} elseif ( in_array( $code, array( 'jpxe_sem_t2', 'jpxe_local', 'jpxe_cargo', 'jpxe_uf' ), true ) ) {
				$msg = $r->get_error_message(); // Erro de configuração do shortcode: mostra o motivo.
			} else {
				$msg = 'Não foi possível carregar os resultados agora. Tentaremos de novo em instantes.';
			}
			return '<div class="jpxe-res jpxe-res--vazio"><p class="jpxe-aviso">' . esc_html( $msg ) . '</p>'
				. '<footer class="jpxe-foot"><span>Fonte: TSE</span><span class="jpxe-countdown" aria-hidden="true"></span></footer></div>';
		}

		$compacto = 'compacto' === $opts['layout'];
		$limite   = (int) $opts['limite'];
		if ( $limite <= 0 ) {
			$limite = $compacto ? ( $r['proporcional'] ? 5 : 3 ) : ( $r['proporcional'] ? 20 : 0 );
		}

		ob_start();
		$classes = 'jpxe-res jpxe-res--' . ( $r['proporcional'] ? 'prop' : 'maj' ) . ( $compacto ? ' jpxe-res--compacto' : '' );
		echo '<div class="' . esc_attr( $classes ) . '" data-finalizada="' . ( $r['finalizada'] ? '1' : '0' ) . '" data-compartilhar="' . esc_attr( self::texto_compartilhar( $r ) ) . '">';

		self::cabecalho( $r, $opts['titulo'], $compacto );

		if ( ! empty( $r['aviso'] ) ) {
			echo '<p class="jpxe-aviso">' . esc_html( $r['aviso'] ) . '</p>';
		}
		if ( ! empty( $r['_stale'] ) ) {
			echo '<p class="jpxe-aviso">Falha temporária ao consultar o TSE: exibindo os últimos dados recebidos.</p>';
		}
		if ( 0 === $r['secoes']['totalizadas'] && ! $r['finalizada'] ) {
			echo '<p class="jpxe-aviso jpxe-aviso--neutro">Aguardando o início da divulgação dos resultados pelo TSE (a partir das 17h, horário de Brasília).</p>';
		}

		if ( $r['proporcional'] ) {
			self::proporcional( $r, $limite, $opts['fotos'], $compacto );
		} else {
			self::majoritario( $r, $limite, $opts['fotos'], $compacto );
		}

		if ( ! $compacto ) {
			self::estatisticas( $r );
		}

		echo '<footer class="jpxe-foot">';
		echo '<span>Fonte: <a href="https://resultados.tse.jus.br" target="_blank" rel="noopener">TSE</a></span>';
		if ( $compacto && $opts['link'] ) {
			echo '<a class="jpxe-link" href="' . esc_url( $opts['link'] ) . '">Ver apuração completa →</a>';
		}
		echo '<span class="jpxe-foot__dir"><span class="jpxe-countdown" aria-hidden="true"></span>' . self::botao_compartilhar() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</footer></div>';

		return ob_get_clean();
	}

	private static function cabecalho( $r, $titulo, $compacto ) {
		$ano = substr( $r['data'], -4 );
		if ( '' === $titulo ) {
			$titulo = $r['cargo'] . ' · ' . $r['local']['nome'];
		}
		$pct = $r['secoes']['pct'];

		echo '<header class="jpxe-head">';
		echo '<div><span class="jpxe-eyebrow">Eleições ' . esc_html( $ano ) . ' · ' . (int) $r['turno'] . 'º turno</span>';
		echo '<h3 class="jpxe-title">' . esc_html( $titulo ) . '</h3></div>';
		echo '<div class="jpxe-pills">';
		if ( $r['finalizada'] ) {
			echo '<span class="jpxe-pill jpxe-pill--ok">Totalização encerrada</span>';
		} elseif ( $r['definida'] ) {
			echo '<span class="jpxe-pill jpxe-pill--ok">Matematicamente definido</span>';
		} elseif ( $pct >= 100 ) {
			echo '<span class="jpxe-pill jpxe-pill--info">Seções 100% apuradas</span>';
		} elseif ( $r['secoes']['totalizadas'] > 0 ) {
			echo '<span class="jpxe-pill jpxe-pill--live">Ao vivo</span>';
		}
		echo '</div></header>';

		echo '<div class="jpxe-progress" role="progressbar" aria-label="Seções totalizadas" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( round( $pct, 2 ) ) . '"><span style="width:' . esc_attr( min( 100, max( 0, $pct ) ) ) . '%"></span></div>';

		echo '<p class="jpxe-meta"><strong>' . esc_html( self::pct( $pct ) ) . '</strong> das seções totalizadas';
		if ( ! $compacto ) {
			echo ' (' . esc_html( self::n( $r['secoes']['totalizadas'] ) ) . ' de ' . esc_html( self::n( $r['secoes']['total'] ) ) . ')';
		}
		if ( $r['atualizado'] ) {
			$p = explode( ' ', $r['atualizado'] );
			echo ' <span class="jpxe-sep">·</span> <span aria-live="polite">Atualizado ' . esc_html( isset( $p[1] ) ? 'em ' . substr( $p[0], 0, 5 ) . ' às ' . $p[1] : $p[0] ) . '</span>';
		}
		if ( ! $compacto && $r['proporcional'] ) {
			echo ' <span class="jpxe-sep">·</span> ' . (int) $r['vagas'] . ' vagas';
			if ( $r['qe'] ) {
				echo ' <span class="jpxe-sep">·</span> Quociente eleitoral: ' . esc_html( self::n( $r['qe'] ) );
			}
		}
		echo '</p>';
	}

	private static function majoritario( $r, $limite, $fotos, $compacto ) {
		$cands = $r['candidatos'];
		$total = count( $cands );
		$ini   = 0;

		// Cargo de uma vaga com votos: 1º colocado em destaque (ou duelo, no 2º turno).
		if ( ! $compacto && 1 === $r['vagas'] && $total > 1 && $cands[0]['votos'] > 0 ) {
			if ( 2 === $total ) {
				self::duelo( $r, $cands, $fotos );
				return;
			}
			self::lider( $r, $cands, $fotos );
			$ini = 1;
		}

		echo '<ol class="jpxe-cands"' . ( $ini ? ' start="2"' : '' ) . '>';
		foreach ( $cands as $i => $c ) {
			if ( $i < $ini ) {
				continue;
			}
			if ( $limite > 0 && $i >= $limite ) {
				break;
			}
			$cls = 'jpxe-cand' . ( $c['eleito'] ? ' is-eleito' : '' ) . ( 0 === $i && $c['votos'] > 0 ? ' is-lider' : '' );
			echo '<li class="' . esc_attr( $cls ) . '">';
			echo self::foto( $c, $fotos, $r['fotos'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em foto().
			echo '<div class="jpxe-cand__body">';
			echo '<div class="jpxe-cand__top"><span class="jpxe-cand__nome">' . esc_html( $c['nome'] ) . '</span>';
			echo self::tag_situacao( $c ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="jpxe-cand__pct">' . esc_html( self::pct( $c['pct'] ) ) . '</span></div>';
			echo '<div class="jpxe-bar"><span style="width:' . esc_attr( min( 100, $c['pct'] ) ) . '%"></span></div>';
			echo '<div class="jpxe-cand__sub"><span>' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span>';
			if ( ! $compacto ) {
				if ( $c['agrup'] ) {
					echo '<span class="jpxe-cand__agr" title="' . esc_attr( $c['agrup_com'] ) . '">' . esc_html( $c['agrup'] ) . '</span>';
				}
				foreach ( $c['vices'] as $v ) {
					$rot = 'v' === $v['tipo'] ? 'Vice' : ( 's1' === $v['tipo'] ? '1º suplente' : ( 's2' === $v['tipo'] ? '2º suplente' : 'Suplente' ) );
					echo '<span>' . esc_html( $rot . ': ' . $v['nome'] . ( $v['partido'] ? ' (' . $v['partido'] . ')' : '' ) ) . '</span>';
				}
			}
			echo '<span class="jpxe-cand__votos">' . esc_html( self::n( $c['votos'] ) ) . ' votos</span></div>';
			echo '</div></li>';
		}
		echo '</ol>';
		if ( $compacto && $limite > 0 && $total > $limite ) {
			echo '<p class="jpxe-mais">+ ' . (int) ( $total - $limite ) . ' candidatos</p>';
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
				self::hemiciclo( array_values( $com_vaga ), $r['agrupamentos'], $r['vagas'] );
			}
		}

		if ( ! $compacto ) {
			echo '<div class="jpxe-tools">';
			echo '<label class="jpxe-search"><span class="screen-reader-text">Buscar candidato</span><input type="search" placeholder="Buscar por nome, número ou partido" autocomplete="off"></label>';
			if ( array_filter( wp_list_pluck( $cands, 'eleito' ) ) ) {
				echo '<label class="jpxe-check"><input type="checkbox" class="jpxe-only-eleitos"> Só eleitos</label>';
			}
			echo '</div>';
		}

		echo '<ol class="jpxe-rows" data-limite="' . (int) $limite . '" data-total="' . (int) $total . '">';
		foreach ( $cands as $i => $c ) {
			if ( $compacto && $i >= $limite ) {
				break;
			}
			$busca = strtolower( remove_accents( $c['nome'] . ' ' . $c['completo'] . ' ' . $c['numero'] . ' ' . $c['partido'] . ' ' . $c['agrup_com'] ) );
			$cls   = 'jpxe-row' . ( $c['eleito'] ? ' is-eleito' : '' ) . ( $limite > 0 && $i >= $limite ? ' is-extra' : '' );
			echo '<li class="' . esc_attr( $cls ) . '" data-busca="' . esc_attr( $busca ) . '"' . ( $c['eleito'] ? ' data-eleito="1"' : '' ) . '>';
			echo '<span class="jpxe-row__pos">' . (int) ( $i + 1 ) . 'º</span>';
			echo self::foto( $c, $fotos, $r['fotos'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="jpxe-row__main"><span class="jpxe-cand__nome">' . esc_html( $c['nome'] ) . '</span>';
			echo '<span class="jpxe-row__sub">' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span></span>';
			echo '<span class="jpxe-row__sit">' . self::tag_situacao( $c ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="jpxe-row__num"><strong>' . esc_html( self::n( $c['votos'] ) ) . '</strong><small>' . esc_html( self::pct( $c['pct'] ) ) . '</small></span>';
			echo '</li>';
		}
		echo '</ol>';

		if ( ! $compacto ) {
			echo '<p class="jpxe-vazio" hidden>Nenhum candidato encontrado.</p>';
			if ( $limite > 0 && $total > $limite ) {
				echo '<button type="button" class="jpxe-more" data-total="' . (int) $total . '">Mostrar todos os ' . (int) $total . ' candidatos</button>';
			}
		} elseif ( $total > $limite ) {
			echo '<p class="jpxe-mais">+ ' . (int) ( $total - $limite ) . ' candidatos</p>';
		}
	}

	/* ------------------------------------------------------------------ *
	 * Hemiciclo (cadeiras por partido/federação)
	 * ------------------------------------------------------------------ */

	/** Cores por sigla; federações usam a do partido que as lidera. */
	const CORES = array(
		'PL' => '#1e3a8a', 'PT' => '#dc2626', 'PCDOB' => '#991b1b', 'PV' => '#15803d', 'MDB' => '#059669',
		'PSD' => '#eab308', 'PP' => '#2563eb', 'UNIAO' => '#0e7490', 'REPUBLICANOS' => '#0ea5e9', 'NOVO' => '#f97316',
		'PODE' => '#65a30d', 'PSDB' => '#6366f1', 'CIDADANIA' => '#db2777', 'PSOL' => '#9333ea', 'REDE' => '#14b8a6',
		'PDT' => '#be123c', 'PSB' => '#f59e0b', 'AVANTE' => '#0891b2', 'SOLIDARIEDADE' => '#ea580c', 'PRD' => '#a16207',
		'MISSAO' => '#7c3aed', 'DC' => '#78716c', 'AGIR' => '#4d7c0f', 'MOBILIZA' => '#0f766e', 'PMB' => '#c026d3',
		'PCB' => '#7f1d1d', 'PCO' => '#b91c1c', 'PSTU' => '#9f1239', 'UP' => '#e11d48',
	);

	/** Partido que dá a cor de uma federação/coligação. */
	const LIDERES = array( 'PT', 'PSDB', 'UNIAO', 'PSOL', 'PRD', 'PL', 'MDB', 'PSD', 'PP' );

	const RESERVA = array( '#334155', '#0d9488', '#b45309', '#7e22ce', '#be185d', '#4338ca', '#047857', '#c2410c', '#475569', '#a21caf' );

	private static function cores( $grupos ) {
		$usadas = array();
		$out    = array();
		foreach ( $grupos as $i => $g ) {
			$tokens = array_map(
				function ( $t ) {
					return strtoupper( remove_accents( trim( $t ) ) );
				},
				preg_split( '#[/,]#', $g['sigla'] )
			);
			$cor = '';
			foreach ( array_merge( array_intersect( self::LIDERES, $tokens ), $tokens ) as $t ) {
				$t = str_replace( ' ', '', $t );
				if ( isset( self::CORES[ $t ] ) && ! isset( $usadas[ self::CORES[ $t ] ] ) ) {
					$cor = self::CORES[ $t ];
					break;
				}
			}
			// Sem cor conhecida (ou já usada neste gráfico): próxima da reserva.
			foreach ( $cor ? array() : self::RESERVA as $rc ) {
				if ( ! isset( $usadas[ $rc ] ) ) {
					$cor = $rc;
					break;
				}
			}
			$cor            = $cor ? $cor : '#64748b';
			$usadas[ $cor ] = true;
			$out[ $i ]      = $cor;
		}
		return $out;
	}

	/** Posições das cadeiras em semicírculo, da esquerda para a direita. */
	private static function assentos( $n ) {
		$filas = max( 1, min( 8, (int) round( sqrt( $n / 1.6 ) ) ) );
		$r0    = $filas > 1 ? 0.5 : 0.75;
		$raios = array();
		for ( $i = 0; $i < $filas; $i++ ) {
			$raios[] = $filas > 1 ? $r0 + ( 1 - $r0 ) * $i / ( $filas - 1 ) : $r0;
		}
		// Cadeiras por fila proporcionais ao comprimento do arco.
		$soma = array_sum( $raios );
		$qtd  = array();
		$tot  = 0;
		foreach ( $raios as $i => $r ) {
			$qtd[ $i ] = max( 1, (int) floor( $n * $r / $soma ) );
			$tot      += $qtd[ $i ];
		}
		for ( $i = $filas - 1; $tot < $n; $i = ( $i - 1 + $filas ) % $filas ) {
			$qtd[ $i ]++;
			$tot++;
		}
		for ( $i = 0; $tot > $n; $i = ( $i + 1 ) % $filas ) {
			if ( $qtd[ $i ] > 1 ) {
				$qtd[ $i ]--;
				$tot--;
			}
		}

		$pos   = array();
		$passo = PHP_INT_MAX;
		foreach ( $raios as $i => $r ) {
			$k = $qtd[ $i ];
			for ( $j = 0; $j < $k; $j++ ) {
				$a     = $k > 1 ? M_PI * ( 1 - $j / ( $k - 1 ) ) : M_PI / 2;
				$pos[] = array( $a, $r, cos( $a ) * $r, sin( $a ) * $r );
			}
			if ( $k > 1 ) {
				$passo = min( $passo, M_PI * $r / ( $k - 1 ) );
			}
		}
		if ( $filas > 1 ) {
			$passo = min( $passo, ( 1 - $r0 ) / ( $filas - 1 ) );
		}
		// Varre do lado esquerdo para o direito: cada partido ocupa uma "fatia".
		usort(
			$pos,
			function ( $p, $q ) {
				return abs( $p[0] - $q[0] ) > 1e-9 ? $q[0] <=> $p[0] : $p[1] <=> $q[1];
			}
		);
		$raio = min( $n <= 20 ? 0.1 : 0.08, ( PHP_INT_MAX === $passo ? 0.2 : $passo ) * 0.44 );
		return array( $pos, $raio );
	}

	private static function hemiciclo( $grupos, $todos, $vagas ) {
		$total_vagas = array_sum( wp_list_pluck( $grupos, 'vagas' ) );
		$total_votos = max( 1, array_sum( wp_list_pluck( $todos, 'votos' ) ) );
		$cores       = self::cores( $grupos );
		list( $pos, $raio ) = self::assentos( $total_vagas );

		$w  = 220;
		$cx = 110;
		$cy = 104;
		$R  = 100;

		echo '<div class="jpxe-vagas">';
		echo '<h4 class="jpxe-sub">Cadeiras por partido / federação</h4>';
		echo '<div class="jpxe-hemi">';

		echo '<figure class="jpxe-hemi__fig">';
		echo '<svg viewBox="0 0 ' . (int) $w . ' ' . ( $cy + 14 ) . '" role="img" aria-label="' . esc_attr( $total_vagas . ' cadeiras distribuídas entre ' . count( $grupos ) . ' partidos e federações' ) . '">';
		$i = 0;
		foreach ( $grupos as $gi => $g ) {
			echo '<g class="jpxe-hemi__p" data-p="' . (int) $gi . '" fill="' . esc_attr( $cores[ $gi ] ) . '"><title>' . esc_html( $g['sigla'] . ': ' . $g['vagas'] . ( 1 === $g['vagas'] ? ' cadeira' : ' cadeiras' ) ) . '</title>';
			for ( $k = 0; $k < $g['vagas']; $k++, $i++ ) {
				if ( ! isset( $pos[ $i ] ) ) {
					break;
				}
				printf( '<circle cx="%.2f" cy="%.2f" r="%.2f"/>', $cx + $pos[ $i ][2] * $R, $cy - $pos[ $i ][3] * $R, $raio * $R );
			}
			echo '</g>';
		}
		echo '<text x="' . (int) $cx . '" y="' . ( $cy - 6 ) . '" class="jpxe-hemi__n" text-anchor="middle">' . (int) $total_vagas . '</text>';
		echo '<text x="' . (int) $cx . '" y="' . ( $cy + 7 ) . '" class="jpxe-hemi__l" text-anchor="middle">vagas</text>';
		echo '</svg></figure>';

		echo '<ol class="jpxe-hemi__leg">';
		foreach ( $grupos as $gi => $g ) {
			$pct = 100 * $g['votos'] / $total_votos;
			$ext = isset( $g['extenso'] ) && $g['extenso'] && strtoupper( remove_accents( $g['extenso'] ) ) !== strtoupper( remove_accents( $g['sigla'] ) ) ? $g['extenso'] : '';
			echo '<li data-p="' . (int) $gi . '" tabindex="0" style="--jpxe-c:' . esc_attr( $cores[ $gi ] ) . '">';
			echo '<span class="jpxe-hemi__sw" aria-hidden="true"></span>';
			echo '<span class="jpxe-hemi__nm"><strong>' . esc_html( str_replace( ' / ', '/', $g['sigla'] ) ) . '</strong>';
			if ( $ext ) {
				echo '<small>' . esc_html( $ext ) . '</small>';
			}
			echo '</span>';
			echo '<span class="jpxe-hemi__v"><b>' . (int) $g['vagas'] . '</b><small>' . esc_html( self::pct( $pct, 1 ) ) . ' dos votos</small></span>';
			echo '<span class="jpxe-hemi__bar" aria-hidden="true"><span style="width:' . esc_attr( round( 100 * $g['vagas'] / max( 1, $total_vagas ), 2 ) ) . '%"></span></span>';
			echo '</li>';
		}
		echo '</ol></div></div>';
	}

	/* ------------------------------------------------------------------ *
	 * Compartilhar, destaque do líder e duelo
	 * ------------------------------------------------------------------ */

	/** Sem cache na montagem da página: esqueleto, e o navegador busca logo (data-gerado=0). */
	public static function esqueleto() {
		JPXE_TSE::$usou_reserva = true;
		$l = '';
		for ( $i = 0; $i < 4; $i++ ) {
			$l .= '<div class="jpxe-skel__row"><span class="jpxe-skel__c"></span><span class="jpxe-skel__l"><i style="width:' . ( 70 - $i * 9 ) . '%"></i><i style="width:' . ( 90 - $i * 12 ) . '%"></i></span></div>';
		}
		return '<div class="jpxe-res jpxe-skel" aria-busy="true"><span class="screen-reader-text">Carregando resultados…</span><i class="jpxe-skel__t"></i><i class="jpxe-skel__h"></i><i class="jpxe-skel__p"></i>' . $l . '</div>';
	}

	public static function foto_publica( $c, $base ) {
		return self::foto( $c, (bool) JPXE_Options::get( 'fotos' ), $base );
	}

	public static function tag_publica( $c ) {
		return self::tag_situacao( $c );
	}

	public static function botao_compartilhar() {
		return '<button type="button" class="jpxe-share" aria-haspopup="menu"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18 16.1c-.8 0-1.5.3-2 .8l-7.1-4.2c.1-.2.1-.5.1-.7s0-.5-.1-.7L16 7.2c.5.5 1.2.8 2 .8 1.7 0 3-1.3 3-3s-1.3-3-3-3-3 1.3-3 3c0 .2 0 .5.1.7L8 9.8C7.5 9.3 6.8 9 6 9c-1.7 0-3 1.3-3 3s1.3 3 3 3c.8 0 1.5-.3 2-.8l7.1 4.2c-.1.2-.1.4-.1.6 0 1.6 1.3 2.9 2.9 2.9s2.9-1.3 2.9-2.9-1.2-2.9-2.8-2.9z"/></svg><span>Compartilhar</span></button>';
	}

	/** "Governador · Pinhalzinho (100% das seções): Jorginho Mello 44,04% · João Rodrigues 40,73% …" */
	public static function texto_compartilhar( $r ) {
		$top = array();
		foreach ( array_slice( $r['candidatos'], 0, $r['proporcional'] ? 5 : 3 ) as $c ) {
			$top[] = $c['nome'] . ' (' . $c['partido'] . ') ' . self::pct( $c['pct'] );
		}
		$t = $r['turno'] . 'º turno · ' . $r['cargo'] . ' · ' . $r['local']['nome'] . ' (' . self::pct( $r['secoes']['pct'] ) . ' das seções): ' . implode( ' · ', $top );
		return $t . ' — ' . get_bloginfo( 'name' );
	}

	public static function cor_partido( $sigla ) {
		$k = str_replace( ' ', '', strtoupper( remove_accents( (string) $sigla ) ) );
		return isset( self::CORES[ $k ] ) ? self::CORES[ $k ] : '#64748b';
	}

	private static function foto_g( $c, $fotos, $base, $classe ) {
		return str_replace( 'class="jpxe-foto', 'class="jpxe-foto ' . $classe, self::foto( $c, $fotos, $base ) );
	}

	private static function lider( $r, $cands, $fotos ) {
		$c   = $cands[0];
		$seg = $cands[1];
		$dif = $c['votos'] - $seg['votos'];
		$rot = $c['eleito'] ? 'Eleito' : ( false !== stripos( $c['situacao'], 'turno' ) ? 'Mais votado · vai ao 2º turno' : 'Lidera' );

		echo '<div class="jpxe-lider' . ( $c['eleito'] ? ' is-eleito' : '' ) . '" style="--jpxe-c:' . esc_attr( self::cor_partido( $c['partido'] ) ) . '">';
		echo self::foto_g( $c, $fotos, $r['fotos'], 'jpxe-foto--g' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<div class="jpxe-lider__info">';
		echo '<span class="jpxe-lider__rot">' . esc_html( $rot ) . '</span>';
		echo '<span class="jpxe-lider__nome">' . esc_html( $c['nome'] ) . '</span>';
		echo '<span class="jpxe-lider__sub">' . esc_html( $c['numero'] . ' · ' . $c['partido'] . ( $c['agrup'] ? ' · ' . $c['agrup'] : '' ) ) . '</span>';
		foreach ( $c['vices'] as $v ) {
			echo '<span class="jpxe-lider__sub">' . esc_html( ( 'v' === $v['tipo'] ? 'Vice' : 'Suplente' ) . ': ' . $v['nome'] . ( $v['partido'] ? ' (' . $v['partido'] . ')' : '' ) ) . '</span>';
		}
		echo '</div>';
		echo '<div class="jpxe-lider__num"><span class="jpxe-lider__pct">' . esc_html( self::pct( $c['pct'] ) ) . '</span>';
		echo '<span class="jpxe-lider__votos">' . esc_html( self::n( $c['votos'] ) ) . ' votos</span></div>';
		echo '<div class="jpxe-bar jpxe-lider__bar"><span style="width:' . esc_attr( min( 100, $c['pct'] ) ) . '%"></span></div>';
		echo '<p class="jpxe-lider__vant">Vantagem de <strong>' . esc_html( self::n( $dif ) ) . ' votos</strong> (' . esc_html( number_format( $c['pct'] - $seg['pct'], 2, ',', '.' ) ) . ' p.p.) sobre ' . esc_html( $seg['nome'] ) . '</p>';
		echo '</div>';
	}

	private static function duelo( $r, $cands, $fotos ) {
		$a   = $cands[0];
		$b   = $cands[1];
		$ca  = self::cor_partido( $a['partido'] );
		$cb  = self::cor_partido( $b['partido'] );
		if ( $ca === $cb ) {
			$cb = '#94a3b8';
		}
		$tot = max( 1, $a['votos'] + $b['votos'] );
		echo '<div class="jpxe-duelo">';
		foreach ( array( array( $a, $ca ), array( $b, $cb ) ) as $i => $par ) {
			list( $c, $cor ) = $par;
			echo '<div class="jpxe-duelo__lado' . ( 0 === $i ? ' is-lider' : '' ) . ( $c['eleito'] ? ' is-eleito' : '' ) . '" style="--jpxe-c:' . esc_attr( $cor ) . '">';
			echo self::foto_g( $c, $fotos, $r['fotos'], 'jpxe-foto--g' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="jpxe-lider__nome">' . esc_html( $c['nome'] ) . '</span>';
			echo '<span class="jpxe-lider__sub">' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span>';
			echo self::tag_situacao( $c ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="jpxe-lider__pct">' . esc_html( self::pct( $c['pct'] ) ) . '</span>';
			echo '<span class="jpxe-lider__votos">' . esc_html( self::n( $c['votos'] ) ) . ' votos</span>';
			echo '</div>';
			if ( 0 === $i ) {
				echo '<span class="jpxe-duelo__x" aria-hidden="true">×</span>';
			}
		}
		echo '<div class="jpxe-duelo__bar" role="img" aria-label="' . esc_attr( $a['nome'] . ' ' . self::pct( $a['pct'] ) . ', ' . $b['nome'] . ' ' . self::pct( $b['pct'] ) ) . '">';
		echo '<span style="width:' . esc_attr( round( 100 * $a['votos'] / $tot, 3 ) ) . '%;background:' . esc_attr( $ca ) . '"></span>';
		echo '<span style="width:' . esc_attr( round( 100 * $b['votos'] / $tot, 3 ) ) . '%;background:' . esc_attr( $cb ) . '"></span>';
		echo '<i aria-hidden="true"></i></div>';
		if ( $a['votos'] > 0 ) {
			echo '<p class="jpxe-lider__vant">Diferença de <strong>' . esc_html( self::n( $a['votos'] - $b['votos'] ) ) . ' votos</strong> (' . esc_html( number_format( $a['pct'] - $b['pct'], 2, ',', '.' ) ) . ' p.p.)</p>';
		}
		echo '</div>';
	}

	private static function estatisticas( $r ) {
		$itens = array(
			'Comparecimento' => $r['comparec'],
			'Abstenção'      => $r['abstencao'],
			'Válidos'        => $r['validos'],
			'Brancos'        => $r['brancos'],
			'Nulos'          => $r['nulos'],
		);
		echo '<dl class="jpxe-stats">';
		foreach ( $itens as $rot => $v ) {
			echo '<div><dt>' . esc_html( $rot ) . '</dt><dd><strong>' . esc_html( self::pct( $v[1] ) ) . '</strong><span>' . esc_html( self::n( $v[0] ) ) . '</span></dd></div>';
		}
		echo '</dl>';
		if ( ! empty( $r['situacao_de'] ) && ( $r['finalizada'] || array_filter( wp_list_pluck( $r['candidatos'], 'eleito' ) ) ) ) {
			echo '<p class="jpxe-nota">Votos no recorte de ' . esc_html( $r['local']['nome'] ) . '. A situação (eleito, 2º turno…) refere-se ao resultado em ' . esc_html( $r['situacao_de'] ) . '.</p>';
		}
	}
}
