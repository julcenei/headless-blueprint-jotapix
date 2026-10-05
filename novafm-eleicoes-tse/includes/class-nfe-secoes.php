<?php
/**
 * Resultados por seção eleitoral, a partir dos Boletins de Urna publicados pelo TSE:
 *   <ciclo>/arquivo-urna/<pleito>/config/<uf>/<uf>-p<pleito>-cs.json          seções por município/zona
 *   <ciclo>/arquivo-urna/<pleito>/dados/<uf>/<mun>/<zona>/<secao>/p<pleito>-<uf>-m<mun>-z<zona>-s<secao>-aux.json
 *   <ciclo>/arquivo-urna/<pleito>/dados/<uf>/<mun>/<zona>/<secao>/<hash>/<arquivo>-bu.dat
 *
 * Nomes e endereços dos locais de votação vêm de data/locais-<uf>-<ano>.json
 * (gerado de dadosabertos.tse.jus.br com bin/gerar-locais.py).
 */

defined( 'ABSPATH' ) || exit;

class NFE_Secoes {

	/** Ordem de exibição dos cargos no boletim. */
	const ORDEM = array( 1 => 'presidente', 3 => 'governador', 5 => 'senador', 6 => 'deputado-federal', 7 => 'deputado-estadual' );

	/* ------------------------------------------------------------------ *
	 * Dados
	 * ------------------------------------------------------------------ */

	/** Pleito (código do arquivo-urna) e turno em uso. */
	private static function pleito( $uf, $turno ) {
		// Presidente existe em todo turno de eleição geral, então serve de referência para o pleito.
		$el = NFE_TSE::eleicao_para( 1, $uf, $turno );
		if ( is_wp_error( $el ) ) {
			$el = NFE_TSE::eleicao_para( 3, $uf, $turno );
		}
		return $el;
	}

	/** Seções de um município: [ [zona, secao, agregadas[], principal, totalizada] ]. */
	public static function config( $pleito, $uf, $mun ) {
		$ciclo = NFE_TSE::ciclo();
		return NFE_TSE::cached(
			'cs_' . $ciclo . '_' . $pleito . '_' . $uf . '_' . $mun,
			function ( $v ) {
				return $v['pendentes'] > 0 ? 5 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS;
			},
			function () use ( $ciclo, $pleito, $uf, $mun ) {
				$d = self::cs_uf( $ciclo, $pleito, $uf );
				if ( is_wp_error( $d ) ) {
					return $d;
				}
				$secs = array();
				foreach ( isset( $d['abr'] ) ? $d['abr'] : array() as $a ) {
					if ( strtolower( $a['cd'] ) !== $uf ) {
						continue;
					}
					foreach ( $a['mu'] as $m ) {
						if ( (string) $m['cd'] !== (string) $mun ) {
							continue;
						}
						foreach ( $m['zon'] as $z ) {
							foreach ( $z['sec'] as $s ) {
								$secs[] = array(
									'zona'       => (int) $z['cd'],
									'secao'      => (int) $s['ns'],
									'agregadas'  => array_map( 'intval', isset( $s['nsa'] ) ? (array) $s['nsa'] : array() ),
									'principal'  => isset( $s['nsp'] ) ? (int) $s['nsp'] : 0,
									'totalizada' => ! empty( $s['da'] ) ? trim( $s['da'] . ' ' . ( isset( $s['ha'] ) ? $s['ha'] : '' ) ) : '',
								);
							}
						}
					}
				}
				if ( ! $secs ) {
					return new WP_Error( 'nfe_404', 'Seções ainda não publicadas pelo TSE para este município.' );
				}
				$pend = 0;
				foreach ( $secs as $s ) {
					if ( ! $s['principal'] && '' === $s['totalizada'] ) {
						$pend++;
					}
				}
				return array(
					'secoes'     => $secs,
					'pendentes'  => $pend,
					'atualizado' => trim( ( isset( $d['dg'] ) ? $d['dg'] : '' ) . ' ' . ( isset( $d['hg'] ) ? $d['hg'] : '' ) ),
				);
			}
		);
	}

	/** O arquivo -cs.json da UF (~1 MB em SC) é baixado uma vez por requisição, mesmo para vários municípios. */
	private static function cs_uf( $ciclo, $pleito, $uf ) {
		static $mem = array();
		$k = "$ciclo/$pleito/$uf";
		if ( ! isset( $mem[ $k ] ) ) {
			$mem[ $k ] = NFE_TSE::http_json( sprintf( '%s/arquivo-urna/%s/config/%s/%s-p%06d-cs.json', $ciclo, $pleito, $uf, $uf, $pleito ) );
		}
		return $mem[ $k ];
	}

	/** Locais de votação do município: [ 'l' => [local => [nome, endereço, bairro]], 's' => ["zona-secao" => [local, eleitores, principal]] ]. */
	public static function locais( $uf, $mun ) {
		$ano = substr( NFE_TSE::ciclo(), 3 );
		$arq = apply_filters( 'nfe_tse_arquivo_locais', NFE_DIR . 'data/locais-' . $uf . '-' . $ano . '.json', $uf, $ano );
		$vazio = array( 'l' => array(), 's' => array() );
		if ( ! is_readable( $arq ) ) {
			return $vazio;
		}
		$r = NFE_TSE::cached(
			'loc_' . $arq . '_' . filemtime( $arq ) . '_' . $mun,
			DAY_IN_SECONDS,
			function () use ( $arq, $mun, $vazio ) {
				$d = json_decode( (string) file_get_contents( $arq ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				return isset( $d['mun'][ $mun ] ) ? $d['mun'][ $mun ] : $vazio;
			}
		);
		return is_wp_error( $r ) ? $vazio : $r;
	}

	private static function base( $pleito, $uf, $mun, $zona, $secao ) {
		return sprintf( '%s/arquivo-urna/%s/dados/%s/%s/%04d/%04d/', NFE_TSE::ciclo(), $pleito, $uf, $mun, $zona, $secao );
	}

	private static function aux_path( $base, $pleito, $uf, $mun, $zona, $secao ) {
		return $base . sprintf( 'p%06d-%s-m%s-z%04d-s%04d-aux.json', $pleito, $uf, $mun, $zona, $secao );
	}

	/** Do -aux.json: situação e caminho do -bu.dat. Pode haver mais de um envio: vale o totalizado; senão, o mais recente. */
	private static function aux_de( $d, $base ) {
		$escolhido = null;
		foreach ( isset( $d['hashes'] ) ? $d['hashes'] : array() as $h ) {
			if ( null === $escolhido || ( isset( $h['st'] ) && 'Totalizado' === $h['st'] ) ) {
				$escolhido = $h;
			}
		}
		$bu = '';
		foreach ( $escolhido && isset( $escolhido['arq'] ) ? $escolhido['arq'] : array() as $a ) {
			if ( 'bu' === $a['tp'] ) {
				$bu = $base . $escolhido['hash'] . '/' . $a['nm'];
			}
		}
		return array(
			'situacao' => isset( $d['st'] ) ? (string) $d['st'] : '',
			'recebido' => $escolhido ? trim( $escolhido['dr'] . ' ' . $escolhido['hr'] ) : '',
			'bu'       => $bu,
		);
	}

	public static function aux_ttl( $v ) {
		return '' !== $v['bu'] ? 6 * HOUR_IN_SECONDS : 2 * MINUTE_IN_SECONDS;
	}

	/** Boletim de urna de uma seção (lido e normalizado), ou ['pendente' => true]. */
	public static function boletim( $pleito, $uf, $mun, $zona, $secao ) {
		$base = self::base( $pleito, $uf, $mun, $zona, $secao );
		$aux  = NFE_TSE::cached(
			'aux_' . $base,
			array( __CLASS__, 'aux_ttl' ),
			function () use ( $base, $pleito, $uf, $mun, $zona, $secao ) {
				$d = NFE_TSE::http_json( self::aux_path( $base, $pleito, $uf, $mun, $zona, $secao ) );
				return is_wp_error( $d ) ? $d : self::aux_de( $d, $base );
			}
		);
		if ( is_wp_error( $aux ) ) {
			return $aux;
		}
		if ( '' === $aux['bu'] ) {
			return array( 'pendente' => true, 'situacao' => $aux['situacao'] );
		}

		$bu = NFE_TSE::cached(
			'bu_' . $aux['bu'],
			12 * HOUR_IN_SECONDS,
			function () use ( $aux ) {
				$bin = NFE_TSE::http_get( $aux['bu'] );
				return is_wp_error( $bin ) ? $bin : NFE_BU::ler( $bin );
			}
		);
		if ( is_wp_error( $bu ) ) {
			return $bu;
		}
		$bu['situacao'] = $aux['situacao'];
		$bu['recebido'] = $aux['recebido'];
		$bu['arquivo']  = NFE_TSE::url( $aux['bu'] );
		return $bu;
	}

	/**
	 * Boletins de várias seções, baixados em paralelo. Limita os downloads por requisição;
	 * o que faltar vem nas próximas atualizações automáticas.
	 *
	 * @return array { bus: ["zona-secao" => boletim|null], faltam: int }
	 */
	public static function boletins( $pleito, $uf, $mun, $secs, $orcamento = 160, $segundos = 4.0 ) {
		$prazo  = microtime( true ) + $segundos;
		$aux    = array();
		$baixar = array();
		foreach ( $secs as $s ) {
			$k    = $s['zona'] . '-' . $s['secao'];
			$base = self::base( $pleito, $uf, $mun, $s['zona'], $s['secao'] );
			$v    = NFE_TSE::peek( 'aux_' . $base );
			if ( null !== $v ) {
				$aux[ $k ] = $v;
			} elseif ( count( $baixar ) < $orcamento / 2 ) {
				$baixar[ $k ] = array( $base, self::aux_path( $base, $pleito, $uf, $mun, $s['zona'], $s['secao'] ) );
			}
		}
		if ( $baixar ) {
			$corpos = NFE_TSE::http_multi( wp_list_pluck( $baixar, 1 ), $prazo );
			foreach ( $baixar as $k => $b ) {
				if ( ! isset( $corpos[ $b[1] ] ) ) {
					continue; // Fora do prazo: fica para a próxima atualização.
				}
				$c = $corpos[ $b[1] ];
				if ( is_wp_error( $c ) && 'nfe_404' !== $c->get_error_code() ) {
					continue; // Falha de rede: não grava, tenta de novo depois.
				}
				$d = is_wp_error( $c ) ? null : json_decode( $c, true );
				$v = is_array( $d ) ? self::aux_de( $d, $b[0] ) : array( 'situacao' => '', 'recebido' => '', 'bu' => '' );
				NFE_TSE::put( 'aux_' . $b[0], $v, self::aux_ttl( $v ) );
				$aux[ $k ] = $v;
			}
		}

		$bus  = array();
		$fila = array();
		foreach ( $aux as $k => $a ) {
			if ( '' === $a['bu'] ) {
				$bus[ $k ] = null;
				continue;
			}
			$v = NFE_TSE::peek( 'bu_' . $a['bu'] );
			if ( null !== $v ) {
				$bus[ $k ] = $v;
			} elseif ( count( $fila ) < $orcamento - count( $baixar ) ) {
				$fila[ $k ] = $a['bu'];
			}
		}
		if ( $fila ) {
			$corpos = NFE_TSE::http_multi( array_values( $fila ), $prazo );
			foreach ( $fila as $k => $path ) {
				if ( ! isset( $corpos[ $path ] ) ) {
					continue;
				}
				$v = is_wp_error( $corpos[ $path ] ) ? $corpos[ $path ] : NFE_BU::ler( $corpos[ $path ] );
				if ( ! is_wp_error( $v ) ) {
					NFE_TSE::put( 'bu_' . $path, $v, 12 * HOUR_IN_SECONDS );
					$bus[ $k ] = $v;
				}
			}
		}
		$faltam = 0;
		foreach ( $secs as $s ) {
			if ( ! array_key_exists( $s['zona'] . '-' . $s['secao'], $bus ) ) {
				$faltam++;
			}
		}
		return array( 'bus' => $bus, 'faltam' => $faltam );
	}

	/** Votos válidos de um cargo no boletim: [numero => votos] (nominais) + legenda somada à parte. */
	private static function votos_cargo( $bu, $cd ) {
		foreach ( $bu['eleicoes'] as $e ) {
			if ( isset( $e['cargos'][ $cd ] ) ) {
				$out = array();
				foreach ( $e['cargos'][ $cd ]['votos'] as $v ) {
					if ( 1 === $v['tipo'] ) {
						$out[ (string) $v['numero'] ] = ( isset( $out[ (string) $v['numero'] ] ) ? $out[ (string) $v['numero'] ] : 0 ) + $v['qtd'];
					} elseif ( 4 === $v['tipo'] ) {
						$out['_leg'] = ( isset( $out['_leg'] ) ? $out['_leg'] : 0 ) + $v['qtd'];
					}
				}
				return $out;
			}
		}
		return null;
	}

	/**
	 * Mais votados por seção e por local de votação, para cada cargo.
	 *
	 * @return array { secoes: [k => [slug => [numero, pct]]], locais: [lid => [slug => [[numero, votos, pct], ...]]], faltam, total }
	 */
	public static function vencedores( $el, $loc, $cfg, $locais ) {
		$pleito = $el['eleicao']['pleito'];
		$chave  = 'venc_' . NFE_TSE::ciclo() . '_' . $pleito . '_' . $loc['chave'];
		$pronto = NFE_TSE::peek( $chave );
		if ( null !== $pronto ) {
			return $pronto;
		}

		$secs = array_filter(
			$cfg['secoes'],
			function ( $s ) {
				return ! $s['principal'];
			}
		);
		$lote = self::boletins( $pleito, $loc['uf'], $loc['mun'], $secs );

		$por_sec = array();
		$por_loc = array();
		foreach ( $secs as $s ) {
			$k  = $s['zona'] . '-' . $s['secao'];
			$bu = isset( $lote['bus'][ $k ] ) ? $lote['bus'][ $k ] : null;
			if ( ! $bu ) {
				continue;
			}
			$lid = isset( $locais['s'][ $k ] ) ? (string) $locais['s'][ $k ][0] : 'z' . $s['zona'];
			foreach ( self::ORDEM as $cd => $slug ) {
				$vs = self::votos_cargo( $bu, $cd );
				if ( null === $vs ) {
					continue;
				}
				$validos = array_sum( $vs );
				unset( $vs['_leg'] );
				arsort( $vs );
				$top = key( $vs );
				if ( null !== $top && $validos > 0 ) {
					$por_sec[ $k ][ $slug ] = array( (string) $top, round( 100 * $vs[ $top ] / $validos, 1 ) );
				}
				foreach ( $vs as $n => $q ) {
					$por_loc[ $lid ][ $slug ]['v'][ $n ] = ( isset( $por_loc[ $lid ][ $slug ]['v'][ $n ] ) ? $por_loc[ $lid ][ $slug ]['v'][ $n ] : 0 ) + $q;
				}
				$por_loc[ $lid ][ $slug ]['t'] = ( isset( $por_loc[ $lid ][ $slug ]['t'] ) ? $por_loc[ $lid ][ $slug ]['t'] : 0 ) + $validos;
			}
		}
		$locs = array();
		foreach ( $por_loc as $lid => $cargos ) {
			foreach ( $cargos as $slug => $d ) {
				arsort( $d['v'] );
				$locs[ $lid ][ $slug ] = array();
				foreach ( array_slice( $d['v'], 0, 2, true ) as $n => $q ) {
					$locs[ $lid ][ $slug ][] = array( (string) $n, $q, $d['t'] ? round( 100 * $q / $d['t'], 1 ) : 0 );
				}
			}
		}

		$res = array(
			'secoes' => $por_sec,
			'locais' => $locs,
			'faltam' => $lote['faltam'],
			'total'  => count( $secs ),
		);
		// Completo e sem seções pendentes no TSE: guarda por horas; senão, por pouco tempo.
		$completo = 0 === $lote['faltam'] && 0 === $cfg['pendentes'] && count( $por_sec ) === count( $secs );
		if ( 0 === $lote['faltam'] ) {
			NFE_TSE::put( $chave, $res, $completo ? 6 * HOUR_IN_SECONDS : 60 );
		}
		return $res;
	}

	/** numero => [nome curto, partido] de cada cargo, do resultado do município. */
	private static function nomes( $loc, $turno ) {
		$out = array();
		foreach ( self::ORDEM as $slug ) {
			$r = NFE_TSE::resultado( $slug, $loc['chave'], $turno );
			if ( is_wp_error( $r ) ) {
				continue;
			}
			foreach ( $r['candidatos'] as $c ) {
				$out[ $slug ][ $c['numero'] ] = array( $c['nome'], $c['partido'] );
			}
		}
		return $out;
	}

	private static function curto( $nome ) {
		if ( mb_strlen( $nome ) <= 16 ) {
			return $nome;
		}
		$p = preg_split( '/\s+/', $nome );
		return count( $p ) > 1 ? $p[0] . ' ' . end( $p ) : $nome;
	}

	/* ------------------------------------------------------------------ *
	 * Saída
	 * ------------------------------------------------------------------ */

	/**
	 * @return array { html, intervalo }
	 */
	public static function render( $local, $secao, $turno = 'auto' ) {
		$base = (int) NFE_Options::get( 'intervalo' );
		$loc  = NFE_TSE::local( $local );
		if ( ! is_wp_error( $loc ) && 'mun' !== $loc['tipo'] ) {
			$loc = new WP_Error( 'nfe_local', 'Escolha um município para ver os resultados por seção.' );
		}
		if ( is_wp_error( $loc ) ) {
			return array( 'html' => NFE_Render::resultado( $loc ), 'intervalo' => $base );
		}

		$el = self::pleito( $loc['uf'], $turno );
		if ( is_wp_error( $el ) ) {
			return array( 'html' => NFE_Render::resultado( $el ), 'intervalo' => $base );
		}
		$aviso = '';
		$cfg   = self::config( $el['eleicao']['pleito'], $loc['uf'], $loc['mun'] );
		if ( is_wp_error( $cfg ) && 2 === $el['turno'] && 'auto' === $turno ) {
			$el['eleicao'] = $el['e1'];
			$el['turno']   = 1;
			$aviso         = 'Os boletins do 2º turno ainda não foram publicados pelo TSE. Exibindo o 1º turno.';
			$cfg           = self::config( $el['eleicao']['pleito'], $loc['uf'], $loc['mun'] );
		}
		if ( is_wp_error( $cfg ) ) {
			return array( 'html' => NFE_Render::resultado( $cfg ), 'intervalo' => $base );
		}
		$locais = self::locais( $loc['uf'], $loc['mun'] );

		if ( preg_match( '/^(\d{1,4})-(\d{1,4})$/', (string) $secao, $m ) ) {
			return self::detalhe( $loc, $el, $cfg, $locais, (int) $m[1], (int) $m[2], $aviso );
		}
		$venc = self::vencedores( $el, $loc, $cfg, $locais );
		return array(
			'html'      => self::lista( $loc, $el, $cfg, $locais, $aviso, $venc, self::nomes( $loc, $el['turno'] ) ),
			// Enquanto lê os boletins em lotes, atualiza logo para completar o "mais votado por local".
			'intervalo' => $venc['faltam'] > 0 ? 15 : ( $cfg['pendentes'] > 0 ? $base : 15 * MINUTE_IN_SECONDS ),
		);
	}

	private static function link( $loc, $zona, $secao ) {
		return add_query_arg(
			array(
				'nfe_cargo' => 'secoes',
				'nfe_local' => $loc['chave'],
				'nfe_secao' => $zona . '-' . $secao,
			),
			''
		);
	}

	private static function hora( $dt ) {
		$p = explode( ' ', (string) $dt );
		return isset( $p[1] ) ? substr( $p[1], 0, 5 ) : '';
	}

	/** Mais votado(s) de um cargo, para a lista: "Nome" + partido + cor. */
	private static function quem( $nomes, $slug, $num ) {
		$n = isset( $nomes[ $slug ][ $num ] ) ? $nomes[ $slug ][ $num ] : array( 'Nº ' . $num, '' );
		return array( $n[0], $n[1], NFE_Render::cor_partido( $n[1] ) );
	}

	private static function lista( $loc, $el, $cfg, $locais, $aviso, $venc, $nomes ) {
		$total = 0;
		$feitas = 0;
		$grupos = array();
		$zonas  = array();
		foreach ( $cfg['secoes'] as $s ) {
			$k    = $s['zona'] . '-' . $s['secao'];
			$info = isset( $locais['s'][ $k ] ) ? $locais['s'][ $k ] : null;
			$lid  = $info ? (string) $info[0] : 'z' . $s['zona'];
			if ( ! isset( $grupos[ $lid ] ) ) {
				$l              = $info && isset( $locais['l'][ $lid ] ) ? $locais['l'][ $lid ] : null;
				$grupos[ $lid ] = array(
					'nome'     => $l ? NFE_Render::titulo( $l[0] ) : 'Zona ' . $s['zona'],
					'endereco' => $l ? NFE_Render::titulo( $l[1] ) . ( $l[2] ? ' · ' . NFE_Render::titulo( $l[2] ) : '' ) : '',
					'secoes'   => array(),
				);
			}
			$s['eleitores']             = $info ? (int) $info[1] : 0;
			$grupos[ $lid ]['secoes'][] = $s;
			$zonas[ $s['zona'] ]        = true;
			if ( ! $s['principal'] ) {
				$total++;
				if ( '' !== $s['totalizada'] ) {
					$feitas++;
				}
			}
		}
		$pct = $total ? 100 * $feitas / $total : 0;

		ob_start();
		$cargos_v = array();
		foreach ( self::ORDEM as $slug ) {
			foreach ( $venc['secoes'] as $sv ) {
				if ( isset( $sv[ $slug ] ) ) {
					$cargos_v[] = $slug;
					break;
				}
			}
		}
		$compart = 'Resultados por seção e local de votação · ' . $loc['nome'] . ' — ' . get_bloginfo( 'name' );
		echo '<div class="nfe-res nfe-secoes"' . ( $cargos_v ? ' data-venc="' . esc_attr( $cargos_v[0] ) . '"' : '' ) . ' data-compartilhar="' . esc_attr( $compart ) . '">';
		echo '<header class="nfe-head"><div><span class="nfe-eyebrow">Eleições ' . esc_html( substr( $el['eleicao']['data'], -4 ) ) . ' · ' . (int) $el['turno'] . 'º turno · Boletins de urna</span>';
		echo '<h3 class="nfe-title">Seções · ' . esc_html( $loc['nome'] ) . '</h3></div>';
		echo '<div class="nfe-pills">' . ( $feitas === $total ? '<span class="nfe-pill nfe-pill--ok">Todas totalizadas</span>' : ( $feitas ? '<span class="nfe-pill nfe-pill--live">Ao vivo</span>' : '' ) ) . '</div></header>';
		echo '<div class="nfe-progress" role="progressbar" aria-label="Seções totalizadas" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( round( $pct, 2 ) ) . '"><span style="width:' . esc_attr( $pct ) . '%"></span></div>';
		echo '<p class="nfe-meta"><strong>' . (int) $feitas . ' de ' . (int) $total . '</strong> seções totalizadas <span class="nfe-sep">·</span> ' . esc_html( ( count( $zonas ) > 1 ? 'Zonas ' : 'Zona ' ) . implode( ', ', array_keys( $zonas ) ) ) . ' <span class="nfe-sep">·</span> ' . count( $grupos ) . ' locais de votação</p>';
		if ( $aviso ) {
			echo '<p class="nfe-aviso">' . esc_html( $aviso ) . '</p>';
		}

		echo '<div class="nfe-tools"><label class="nfe-search"><span class="screen-reader-text">Buscar seção ou local</span><input type="search" placeholder="Buscar seção, escola ou bairro" autocomplete="off"></label></div>';
		if ( $cargos_v ) {
			echo '<div class="nfe-venc-tabs" role="group" aria-label="Mais votado por local"><span class="nfe-venc-tabs__rot">Mais votado em cada local:</span>';
			foreach ( $cargos_v as $i => $slug ) {
				echo '<button type="button" class="nfe-venc-tab" data-cargo="' . esc_attr( $slug ) . '" aria-pressed="' . ( 0 === $i ? 'true' : 'false' ) . '">' . esc_html( NFE_TSE::CARGOS[ $slug ]['nome'] ) . '</button>';
			}
			echo '</div>';
		}
		if ( $venc['faltam'] > 0 ) {
			echo '<p class="nfe-aviso nfe-aviso--neutro">Lendo os boletins de urna: ' . (int) ( $venc['total'] - $venc['faltam'] ) . ' de ' . (int) $venc['total'] . ' seções. Os mais votados por local se completam nas próximas atualizações.</p>';
		}
		echo '<div class="nfe-locais-lista">';
		foreach ( $grupos as $lid => $g ) {
			$nums  = wp_list_pluck( $g['secoes'], 'secao' );
			$busca = strtolower( remove_accents( $g['nome'] . ' ' . $g['endereco'] . ' s' . implode( ' s', $nums ) . ' ' ) );
			echo '<section class="nfe-local" data-busca="' . esc_attr( $busca ) . '">';
			echo '<h4 class="nfe-local__nome">' . esc_html( $g['nome'] ) . '</h4>';
			if ( $g['endereco'] ) {
				echo '<p class="nfe-local__end">' . esc_html( $g['endereco'] ) . '</p>';
			}
			foreach ( isset( $venc['locais'][ (string) $lid ] ) ? $venc['locais'][ (string) $lid ] : array() as $slug => $top ) {
				echo '<div class="nfe-venc" data-cargo="' . esc_attr( $slug ) . '">';
				foreach ( $top as $i => $t ) {
					list( $nome, $part, $cor ) = self::quem( $nomes, $slug, $t[0] );
					echo '<div class="nfe-venc__c' . ( 0 === $i ? ' is-1' : '' ) . '" style="--nfe-c:' . esc_attr( $cor ) . '">';
					echo '<span class="nfe-venc__n"><b>' . esc_html( $nome ) . '</b> <small>' . esc_html( $part ) . '</small></span>';
					echo '<span class="nfe-venc__p">' . esc_html( NFE_Render::pct( $t[2], 1 ) ) . ' <small>' . esc_html( NFE_Render::n( $t[1] ) ) . '</small></span>';
					echo '<span class="nfe-venc__bar"><span style="width:' . esc_attr( min( 100, $t[2] ) ) . '%"></span></span></div>';
				}
				echo '</div>';
			}
			echo '<ul class="nfe-secs">';
			foreach ( $g['secoes'] as $s ) {
				$alvo = $s['principal'] ? $s['principal'] : $s['secao'];
				echo '<li><a class="nfe-sec' . ( '' !== $s['totalizada'] || $s['principal'] ? ' is-ok' : '' ) . '" href="' . esc_url( self::link( $loc, $s['zona'], $alvo ) ) . '" data-secao="' . esc_attr( $s['zona'] . '-' . $alvo ) . '" data-num="' . (int) $s['secao'] . '">';
				echo '<strong>Seção ' . (int) $s['secao'] . '</strong>';
				if ( $s['eleitores'] ) {
					echo '<span>' . esc_html( NFE_Render::n( $s['eleitores'] ) ) . ' eleitores</span>';
				}
				if ( $s['principal'] ) {
					echo '<span class="nfe-sec__st">Agregada à ' . (int) $s['principal'] . '</span>';
				} elseif ( '' !== $s['totalizada'] ) {
					echo '<span class="nfe-sec__st">✓ Totalizada</span>';
				} else {
					echo '<span class="nfe-sec__st">Aguardando</span>';
				}
				$sv = isset( $venc['secoes'][ $s['zona'] . '-' . $alvo ] ) ? $venc['secoes'][ $s['zona'] . '-' . $alvo ] : array();
				foreach ( $s['principal'] ? array() : $sv as $slug => $t ) {
					list( $nome, $part, $cor ) = self::quem( $nomes, $slug, $t[0] );
					echo '<span class="nfe-sec__venc" data-cargo="' . esc_attr( $slug ) . '" style="--nfe-c:' . esc_attr( $cor ) . '" title="' . esc_attr( 'Mais votado: ' . $nome . ' (' . $part . ')' ) . '"><i aria-hidden="true"></i><span>' . esc_html( self::curto( $nome ) ) . '</span><b>' . esc_html( NFE_Render::pct( $t[1], 0 ) ) . '</b></span>';
				}
				echo '</a></li>';
			}
			echo '</ul></section>';
		}
		echo '</div><p class="nfe-vazio" hidden>Nenhuma seção encontrada.</p>';
		if ( ! $locais['l'] ) {
			echo '<p class="nfe-nota">Nomes dos locais de votação indisponíveis para esta UF.</p>';
		}
		echo '<footer class="nfe-foot"><span>Fonte: <a href="https://resultados.tse.jus.br" target="_blank" rel="noopener">TSE</a> – boletins de urna</span><span class="nfe-foot__dir"><span class="nfe-countdown" aria-hidden="true"></span>' . NFE_Render::botao_compartilhar() . '</span></footer>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		return ob_get_clean();
	}

	private static function detalhe( $loc, $el, $cfg, $locais, $zona, $secao, $aviso ) {
		$base   = (int) NFE_Options::get( 'intervalo' );
		$achada = null;
		foreach ( $cfg['secoes'] as $s ) {
			if ( $s['zona'] === $zona && $s['secao'] === $secao ) {
				$achada = $s;
			}
		}
		$voltar = '<a class="nfe-voltar" href="' . esc_url( add_query_arg( array( 'nfe_cargo' => 'secoes', 'nfe_local' => $loc['chave'] ), '' ) ) . '" data-secao="">← Todas as seções de ' . esc_html( $loc['nome'] ) . '</a>';
		if ( ! $achada ) {
			return array(
				'html'      => '<div class="nfe-res">' . $voltar . '<p class="nfe-aviso">Seção ' . (int) $secao . ' da zona ' . (int) $zona . ' não encontrada em ' . esc_html( $loc['nome'] ) . '.</p></div>',
				'intervalo' => 15 * MINUTE_IN_SECONDS,
			);
		}

		// Seção agregada não tem boletim próprio: os votos estão no da seção principal.
		$agregada = 0;
		if ( $achada['principal'] ) {
			$agregada = $secao;
			$secao    = $achada['principal'];
			foreach ( $cfg['secoes'] as $s ) {
				if ( $s['zona'] === $zona && $s['secao'] === $secao ) {
					$achada = $s;
				}
			}
			$aviso = trim( $aviso . ' A seção ' . $agregada . ' foi agregada à seção ' . $secao . ': os votos dela estão neste boletim.' );
		}

		$k    = $zona . '-' . $secao;
		$info = isset( $locais['s'][ $k ] ) ? $locais['s'][ $k ] : null;
		$l    = $info && isset( $locais['l'][ (string) $info[0] ] ) ? $locais['l'][ (string) $info[0] ] : null;
		$bu   = self::boletim( $el['eleicao']['pleito'], $loc['uf'], $loc['mun'], $zona, $secao );

		ob_start();
		echo '<div class="nfe-res nfe-secao" data-compartilhar="%%NFE_COMPARTILHAR%%">' . $voltar; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<header class="nfe-head"><div><span class="nfe-eyebrow">Boletim de urna · ' . (int) $el['turno'] . 'º turno · ' . esc_html( $loc['nome'] ) . '</span>';
		echo '<h3 class="nfe-title">Seção ' . (int) $secao . ' · Zona ' . (int) $zona . '</h3>';
		if ( $l ) {
			echo '<p class="nfe-local__end"><strong>' . esc_html( NFE_Render::titulo( $l[0] ) ) . '</strong> — ' . esc_html( NFE_Render::titulo( $l[1] ) . ( $l[2] ? ', ' . NFE_Render::titulo( $l[2] ) : '' ) ) . '</p>';
		}
		echo '</div></header>';
		if ( $aviso ) {
			echo '<p class="nfe-aviso">' . esc_html( $aviso ) . '</p>';
		}
		if ( $achada['agregadas'] && ! $agregada ) {
			echo '<p class="nfe-aviso nfe-aviso--neutro">Inclui os eleitores da(s) seção(ões) agregada(s) ' . esc_html( implode( ', ', $achada['agregadas'] ) ) . '.</p>';
		}

		if ( is_wp_error( $bu ) || ! empty( $bu['pendente'] ) ) {
			$msg = is_wp_error( $bu ) && 'nfe_404' !== $bu->get_error_code()
				? 'Não foi possível ler o boletim desta seção agora. Tentaremos de novo em instantes.'
				: 'O boletim de urna desta seção ainda não foi publicado pelo TSE.';
			echo '<p class="nfe-aviso nfe-aviso--neutro">' . esc_html( $msg ) . '</p>';
			echo '<footer class="nfe-foot"><span>Fonte: TSE – boletim de urna</span><span class="nfe-countdown" aria-hidden="true"></span></footer></div>';
			return array( 'html' => ob_get_clean(), 'intervalo' => $base );
		}

		// Comparecimento/aptos da primeira eleição do boletim (iguais em todas).
		$primeira = reset( $bu['eleicoes'] );
		$aptos    = $primeira['aptos'];
		$prim_c   = reset( $primeira['cargos'] );
		$comp     = $prim_c ? $prim_c['comparecimento'] : 0;
		echo '<dl class="nfe-stats">';
		$itens = array(
			'Eleitores aptos' => array( NFE_Render::n( $aptos ), '' ),
			'Comparecimento'  => array( NFE_Render::n( $comp ), $aptos ? NFE_Render::pct( 100 * $comp / $aptos ) : '' ),
			'Abstenção'       => array( NFE_Render::n( max( 0, $aptos - $comp ) ), $aptos ? NFE_Render::pct( 100 * ( $aptos - $comp ) / $aptos ) : '' ),
			'Urna aberta'     => array( self::hora( $bu['abertura'] ), 'encerrada ' . self::hora( $bu['encerramento'] ) ),
		);
		foreach ( $itens as $rot => $v ) {
			echo '<div><dt>' . esc_html( $rot ) . '</dt><dd><strong>' . esc_html( $v[0] ) . '</strong><span>' . esc_html( $v[1] ) . '</span></dd></div>';
		}
		echo '</dl>';

		// Votos por cargo, na ordem da cédula.
		$cargos = array();
		foreach ( $bu['eleicoes'] as $e ) {
			foreach ( $e['cargos'] as $cd => $c ) {
				$cargos[ $cd ] = $c;
			}
		}
		$resumo = array();
		foreach ( self::ORDEM as $cd => $slug ) {
			if ( isset( $cargos[ $cd ] ) ) {
				$top = self::cargo( $slug, $cargos[ $cd ], $loc, $el['turno'] );
				if ( $top && ! NFE_TSE::CARGOS[ $slug ]['prop'] ) {
					$resumo[] = NFE_TSE::CARGOS[ $slug ]['nome'] . ': ' . $top;
				}
			}
		}

		echo '<p class="nfe-nota">Votos apurados nesta urna. ▲▼ indicam a diferença, em pontos percentuais, entre o % na seção e o % do candidato em ' . esc_html( $loc['nome'] ) . '. Situação dos candidatos (eleito, 2º turno…) conforme a totalização do TSE. Boletim emitido em ' . esc_html( $bu['emissao'] ) . ( $bu['recebido'] ? ', recebido pelo TSE em ' . esc_html( $bu['recebido'] ) : '' ) . '.</p>';
		echo '<footer class="nfe-foot"><span>Fonte: TSE – <a href="' . esc_url( $bu['arquivo'] ) . '" rel="noopener nofollow">boletim de urna oficial (.dat)</a></span><span class="nfe-foot__dir"><span class="nfe-countdown" aria-hidden="true"></span>' . NFE_Render::botao_compartilhar() . '</span></footer>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		$txt  = 'Seção ' . $secao . ' (zona ' . $zona . ') · ' . $loc['nome'] . ' — ' . implode( ' | ', $resumo ) . ' — ' . get_bloginfo( 'name' );
		$html = str_replace( '%%NFE_COMPARTILHAR%%', esc_attr( $txt ), ob_get_clean() );
		return array( 'html' => $html, 'intervalo' => HOUR_IN_SECONDS );
	}

	private static function cargo( $slug, $c, $loc, $turno ) {
		$cfg = NFE_TSE::CARGOS[ $slug ];

		// Nomes vêm do resultado do município (mesmo turno); o BU só traz números.
		$nomes   = array();
		$siglas  = array();
		$res     = NFE_TSE::resultado( $slug, $loc['chave'], $turno );
		if ( ! is_wp_error( $res ) ) {
			foreach ( $res['candidatos'] as $cand ) {
				$nomes[ $cand['numero'] ] = $cand;
				$siglas[ substr( $cand['numero'], 0, 2 ) ] = $cand['partido'];
			}
		}

		$linhas = array();
		$tot    = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0 );
		foreach ( $c['votos'] as $v ) {
			$t         = isset( $tot[ $v['tipo'] ] ) ? $v['tipo'] : 3;
			$tot[ $t ] += $v['qtd'];
			if ( 1 === $v['tipo'] || 4 === $v['tipo'] ) {
				$num = (string) $v['numero'];
				$cad = 1 === $v['tipo'] && isset( $nomes[ $num ] ) ? $nomes[ $num ] : null;
				$sg  = isset( $siglas[ (string) $v['partido'] ] ) ? $siglas[ (string) $v['partido'] ] : (string) $v['partido'];
				$linhas[] = array(
					'nome'    => 4 === $v['tipo'] ? 'Voto de legenda' : ( $cad ? $cad['nome'] : 'Candidato ' . $num ),
					'sub'     => 4 === $v['tipo'] ? $num . ' · ' . $sg : $num . ' · ' . ( $cad ? $cad['partido'] : $sg ),
					'qtd'     => $v['qtd'],
					'cand'    => $cad,
					'legenda' => 4 === $v['tipo'],
				);
			}
		}
		usort(
			$linhas,
			function ( $a, $b ) {
				return $b['qtd'] <=> $a['qtd'];
			}
		);
		$validos = $tot[1] + $tot[4];

		$comparar = ! is_wp_error( $res ) && $res['secoes']['totalizadas'] > 0;
		echo '<section class="nfe-bu-cargo">';
		echo '<h4 class="nfe-bu-cargo__t">' . esc_html( $cfg['nome'] ) . '<small>' . esc_html( NFE_Render::n( $validos ) ) . ' votos válidos' . ( $comparar ? ' · comparado a ' . esc_html( $loc['nome'] ) : '' ) . '</small></h4>';
		$limite = $cfg['prop'] ? 10 : 0;
		$abriu  = false;
		echo '<ol class="nfe-bu-rows">';
		foreach ( $linhas as $i => $r ) {
			if ( $limite && $i === $limite ) {
				echo '</ol><details class="nfe-bu-mais"><summary>Ver todos os ' . count( $linhas ) . ' votados</summary><ol class="nfe-bu-rows" start="' . ( $limite + 1 ) . '">';
				$abriu = true;
			}
			$pct = $validos ? 100 * $r['qtd'] / $validos : 0;
			$cls = 'nfe-bu-row' . ( 0 === $i ? ' is-lider' : '' ) . ( $r['legenda'] ? ' is-legenda' : '' );
			echo '<li class="' . esc_attr( $cls ) . '"><span class="nfe-bu-row__main"><span class="nfe-cand__nome">' . esc_html( $r['nome'] ) . '</span>';
			if ( $r['cand'] ) {
				echo self::tag( $r['cand'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '<span class="nfe-row__sub">' . esc_html( $r['sub'] ) . '</span></span>';
			echo '<span class="nfe-bu-row__num">';
			// Seção × cidade: pontos percentuais acima/abaixo do resultado no município.
			if ( $comparar && $r['cand'] ) {
				$d = $pct - $r['cand']['pct'];
				if ( abs( $d ) >= 0.05 ) {
					echo '<span class="nfe-delta ' . ( $d > 0 ? 'is-up' : 'is-down' ) . '" title="' . esc_attr( NFE_Render::pct( $r['cand']['pct'] ) . ' em ' . $loc['nome'] ) . '">' . ( $d > 0 ? '▲ +' : '▼ ' ) . esc_html( number_format( $d, 1, ',', '.' ) ) . '</span>';
				}
			}
			echo '<strong>' . esc_html( NFE_Render::n( $r['qtd'] ) ) . '</strong><small>' . esc_html( NFE_Render::pct( $pct ) ) . '</small></span>';
			echo '<span class="nfe-bar"><span style="width:' . esc_attr( min( 100, $pct ) ) . '%"></span></span></li>';
		}
		echo '</ol>' . ( $abriu ? '</details>' : '' );
		echo '<p class="nfe-bu-tot">Brancos <strong>' . esc_html( NFE_Render::n( $tot[2] ) ) . '</strong> <span class="nfe-sep">·</span> Nulos <strong>' . esc_html( NFE_Render::n( $tot[3] ) ) . '</strong>';
		if ( $cfg['prop'] ) {
			echo ' <span class="nfe-sep">·</span> Legenda <strong>' . esc_html( NFE_Render::n( $tot[4] ) ) . '</strong>';
		}
		echo ' <span class="nfe-sep">·</span> Total <strong>' . esc_html( NFE_Render::n( array_sum( $tot ) ) ) . '</strong></p>';
		echo '</section>';

		$top = array();
		foreach ( array_slice( $linhas, 0, 2 ) as $r ) {
			$top[] = $r['nome'] . ' ' . NFE_Render::pct( $validos ? 100 * $r['qtd'] / $validos : 0, 1 );
		}
		return implode( ', ', $top );
	}

	private static function tag( $c ) {
		$st = trim( $c['situacao'] );
		if ( '' === $st || 'não eleito' === mb_strtolower( $st ) ) {
			return '';
		}
		$cls = $c['eleito'] ? 'nfe-tag--eleito' : ( false !== stripos( $st, 'turno' ) ? 'nfe-tag--turno' : 'nfe-tag--info' );
		return ' <span class="nfe-tag ' . $cls . '">' . esc_html( $st ) . '</span>';
	}
}
