<?php
/**
 * Visões extras: "mais votados da região" e mapa do Brasil por UF.
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Extras {

	/* ------------------------------------------------------------------ *
	 * Mais votados da região
	 * ------------------------------------------------------------------ */

	/**
	 * @param string[] $cargos Slugs de cargos.
	 * @param string[] $locais Municípios (nomes, códigos ou chaves).
	 * @return array { html, intervalo }
	 */
	public static function regiao( $cargos, $locais, $limite = 10, $titulo = '', $turno = 'auto' ) {
		$base = (int) JPXE_Options::get( 'intervalo' );
		$muns = array();
		foreach ( $locais as $l ) {
			$loc = JPXE_TSE::local( $l );
			if ( ! is_wp_error( $loc ) && 'mun' === $loc['tipo'] ) {
				$muns[ $loc['chave'] ] = $loc;
			}
		}
		if ( ! $muns ) {
			return array( 'html' => JPXE_Render::resultado( new WP_Error( 'jpxe_local', 'Nenhum município válido para a região.' ) ), 'intervalo' => $base );
		}
		$limite  = max( 3, min( 50, (int) $limite ) );
		$nomes   = implode( ' + ', wp_list_pluck( $muns, 'nome' ) );
		$fim     = true;
		$pct_min = 100;
		$share   = array();

		ob_start();
		echo '<div class="jpxe-res jpxe-regiao" data-compartilhar="%%JPXE_COMPARTILHAR%%">';
		echo '<header class="jpxe-head"><div><span class="jpxe-eyebrow">Eleições · Mais votados da região</span>';
		echo '<h3 class="jpxe-title">' . esc_html( $titulo ? $titulo : 'Mais votados em ' . $nomes ) . '</h3></div><div class="jpxe-pills">%%JPXE_PILL%%</div></header>';

		foreach ( $cargos as $slug ) {
			if ( ! isset( JPXE_TSE::CARGOS[ $slug ] ) ) {
				continue;
			}
			$soma    = array();
			$validos = 0;
			foreach ( $muns as $k => $loc ) {
				$r = JPXE_TSE::resultado( $slug, $k, $turno );
				if ( is_wp_error( $r ) && 'jpxe_frio' === $r->get_error_code() ) {
					ob_end_clean();
					return array( 'html' => JPXE_Render::esqueleto(), 'intervalo' => $base );
				}
				if ( is_wp_error( $r ) ) {
					continue;
				}
				$fim     = $fim && $r['finalizada'];
				$pct_min = min( $pct_min, $r['secoes']['pct'] );
				$validos += $r['validos'][0];
				$fotos    = $r['fotos'];
				foreach ( $r['candidatos'] as $c ) {
					if ( ! isset( $soma[ $c['sq'] ] ) ) {
						$soma[ $c['sq'] ] = array( 'c' => $c, 'por' => array(), 'total' => 0, 'fotos' => $fotos );
					}
					$soma[ $c['sq'] ]['por'][ $k ] = $c['votos'];
					$soma[ $c['sq'] ]['total']    += $c['votos'];
				}
			}
			uasort(
				$soma,
				function ( $a, $b ) {
					return $b['total'] <=> $a['total'];
				}
			);
			$top = array_slice( $soma, 0, $limite );
			if ( ! $top ) {
				continue;
			}

			echo '<section class="jpxe-reg" style="--jpxe-cols:' . count( $muns ) . '">';
			echo '<h4 class="jpxe-bu-cargo__t">' . esc_html( JPXE_TSE::CARGOS[ $slug ]['nome'] ) . '<small>' . esc_html( JPXE_Render::n( $validos ) ) . ' votos válidos na região</small></h4>';
			echo '<div class="jpxe-reg__head" aria-hidden="true"><span></span><span>Candidato</span>';
			foreach ( $muns as $loc ) {
				echo '<span class="jpxe-reg__col">' . esc_html( $loc['nome'] ) . '</span>';
			}
			echo '<span class="jpxe-reg__col">Total</span></div>';
			echo '<ol class="jpxe-reg__rows">';
			$i = 0;
			foreach ( $top as $t ) {
				$c = $t['c'];
				$i++;
				if ( $i <= 3 ) {
					$share[ $slug ][] = $c['nome'] . ' ' . JPXE_Render::n( $t['total'] );
				}
				echo '<li class="jpxe-reg__row' . ( $c['eleito'] ? ' is-eleito' : '' ) . '">';
				echo '<span class="jpxe-row__pos">' . (int) $i . 'º</span>';
				echo '<span class="jpxe-reg__cand">' . JPXE_Render::foto_publica( $c, $t['fotos'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<span class="jpxe-row__main"><span class="jpxe-cand__nome">' . esc_html( $c['nome'] ) . '</span><span class="jpxe-row__sub">' . esc_html( $c['numero'] . ' · ' . $c['partido'] ) . '</span>' . JPXE_Render::tag_publica( $c ) . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( $muns as $k => $loc ) {
					echo '<span class="jpxe-reg__col" data-rot="' . esc_attr( $loc['nome'] ) . '">' . esc_html( JPXE_Render::n( isset( $t['por'][ $k ] ) ? $t['por'][ $k ] : 0 ) ) . '</span>';
				}
				echo '<span class="jpxe-reg__col jpxe-reg__tot" data-rot="Total"><strong>' . esc_html( JPXE_Render::n( $t['total'] ) ) . '</strong><small>' . esc_html( JPXE_Render::pct( $validos ? 100 * $t['total'] / $validos : 0, 1 ) ) . '</small></span>';
				echo '</li>';
			}
			echo '</ol></section>';
		}

		echo '<p class="jpxe-nota">Soma dos votos recebidos nos municípios da região. Situação (eleito, suplente…) conforme a totalização no estado.</p>';
		echo '<footer class="jpxe-foot"><span>Fonte: <a href="https://resultados.tse.jus.br" target="_blank" rel="noopener">TSE</a></span><span class="jpxe-foot__dir"><span class="jpxe-countdown" aria-hidden="true"></span>' . JPXE_Render::botao_compartilhar() . '</span></footer></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

		$txt = array();
		foreach ( $share as $slug => $l ) {
			$txt[] = JPXE_TSE::CARGOS[ $slug ]['nome'] . ': ' . implode( ', ', $l );
		}
		$pill = $fim ? '<span class="jpxe-pill jpxe-pill--ok">Totalização encerrada</span>' : ( $pct_min >= 100 ? '<span class="jpxe-pill jpxe-pill--info">Seções 100% apuradas</span>' : ( $pct_min > 0 ? '<span class="jpxe-pill jpxe-pill--live">Ao vivo</span>' : '' ) );
		$html = str_replace(
			array( '%%JPXE_COMPARTILHAR%%', '%%JPXE_PILL%%' ),
			array( esc_attr( 'Mais votados em ' . $nomes . ' — ' . implode( ' | ', $txt ) . ' — ' . get_bloginfo( 'name' ) ), $pill ),
			ob_get_clean()
		);
		return array( 'html' => $html, 'intervalo' => $fim || $pct_min >= 100 ? JPXE_Shortcodes::CONSOLIDADO : $base );
	}

	/* ------------------------------------------------------------------ *
	 * Mapa do Brasil (grade de UFs)
	 * ------------------------------------------------------------------ */

	/** Posição aproximada de cada UF numa grade 7 × 9. */
	const GRADE = array(
		'rr' => array( 1, 0 ), 'ap' => array( 3, 0 ),
		'am' => array( 1, 1 ), 'pa' => array( 2, 1 ), 'ma' => array( 3, 1 ), 'ce' => array( 4, 1 ), 'rn' => array( 5, 1 ),
		'ac' => array( 0, 2 ), 'ro' => array( 1, 2 ), 'to' => array( 2, 2 ), 'pi' => array( 3, 2 ), 'pe' => array( 4, 2 ), 'pb' => array( 5, 2 ),
		'mt' => array( 1, 3 ), 'go' => array( 2, 3 ), 'ba' => array( 3, 3 ), 'se' => array( 4, 3 ), 'al' => array( 5, 3 ),
		'ms' => array( 1, 4 ), 'df' => array( 2, 4 ), 'mg' => array( 3, 4 ), 'es' => array( 4, 4 ),
		'sp' => array( 2, 5 ), 'rj' => array( 3, 5 ),
		'pr' => array( 2, 6 ),
		'sc' => array( 2, 7 ),
		'rs' => array( 2, 8 ),
	);

	private static function rgba( $hex, $a ) {
		$hex = ltrim( $hex, '#' );
		return sprintf( 'rgba(%d,%d,%d,%.2f)', hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), $a );
	}

	/** @return array { html, intervalo } */
	public static function mapa( $cargo = 'presidente', $turno = 'auto', $link = '' ) {
		$base = (int) JPXE_Options::get( 'intervalo' );
		if ( ! in_array( $cargo, array( 'presidente', 'governador', 'senador' ), true ) ) {
			$cargo = 'presidente';
		}
		$ufs = array_keys( self::GRADE );
		JPXE_TSE::precarregar( $cargo, $ufs, $turno );

		$res   = array();
		$fim   = true;
		$todas = true;
		$cores = array();
		$turno_n = 1;
		foreach ( $ufs as $uf ) {
			$r = JPXE_TSE::resultado( $cargo, $uf, $turno );
			if ( is_wp_error( $r ) ) {
				continue;
			}
			$res[ $uf ] = $r;
			$fim        = $fim && $r['finalizada'];
			$todas      = $todas && JPXE_TSE::consolidado( $r );
			$turno_n    = $r['turno'];
		}
		if ( JPXE_TSE::$sem_rede && count( $res ) < count( $ufs ) ) {
			return array( 'html' => JPXE_Render::esqueleto(), 'intervalo' => $base );
		}
		$br = 'presidente' === $cargo ? JPXE_TSE::resultado( 'presidente', 'br', $turno ) : null;

		// Legenda: candidatos que vencem em ao menos uma UF (Presidente) ou partidos vencedores.
		$venc = array();
		foreach ( $res as $uf => $r ) {
			$c = isset( $r['candidatos'][0] ) ? $r['candidatos'][0] : null;
			if ( $c && $c['votos'] > 0 ) {
				$chave = 'presidente' === $cargo ? $c['numero'] : $c['partido'];
				if ( ! isset( $venc[ $chave ] ) ) {
					$venc[ $chave ] = array( 'rot' => 'presidente' === $cargo ? $c['nome'] : $c['partido'], 'partido' => $c['partido'], 'n' => 0 );
				}
				$venc[ $chave ]['n']++;
			}
		}
		uasort(
			$venc,
			function ( $a, $b ) {
				return $b['n'] <=> $a['n'];
			}
		);
		$usadas = array();
		foreach ( $venc as $k => $v ) {
			$cor = JPXE_Render::cor_partido( $v['partido'] );
			if ( isset( $usadas[ $cor ] ) ) {
				$cor = JPXE_Render::RESERVA[ count( $usadas ) % count( JPXE_Render::RESERVA ) ];
			}
			$usadas[ $cor ] = true;
			$cores[ $k ]    = $cor;
		}

		$nome_cargo = JPXE_TSE::CARGOS[ $cargo ]['nome'];
		ob_start();
		echo '<div class="jpxe-res jpxe-mapa" data-compartilhar="' . esc_attr( 'Mapa da apuração: quem venceu para ' . $nome_cargo . ' em cada estado — ' . get_bloginfo( 'name' ) ) . '">';
		echo '<header class="jpxe-head"><div><span class="jpxe-eyebrow">Eleições · ' . (int) $turno_n . 'º turno · Mapa por estado</span>';
		echo '<h3 class="jpxe-title">' . esc_html( $nome_cargo ) . ' · Quem venceu em cada UF</h3></div>';
		echo '<div class="jpxe-pills">' . ( $fim ? '<span class="jpxe-pill jpxe-pill--ok">Totalização encerrada</span>' : ( $todas ? '<span class="jpxe-pill jpxe-pill--info">Seções 100% apuradas</span>' : '<span class="jpxe-pill jpxe-pill--live">Ao vivo</span>' ) ) . '</div></header>';

		if ( $br && ! is_wp_error( $br ) ) {
			echo '<p class="jpxe-meta"><strong>Brasil:</strong> ';
			$p = array();
			foreach ( array_slice( $br['candidatos'], 0, 2 ) as $c ) {
				$p[] = esc_html( $c['nome'] . ' ' . JPXE_Render::pct( $c['pct'] ) );
			}
			echo implode( ' <span class="jpxe-sep">·</span> ', $p ) . ' <span class="jpxe-sep">·</span> ' . esc_html( JPXE_Render::pct( $br['secoes']['pct'] ) ) . ' das seções</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '<div class="jpxe-mapa__wrap">';
		echo '<div class="jpxe-mapa__grade" role="list">';
		foreach ( self::GRADE as $uf => $xy ) {
			$r   = isset( $res[ $uf ] ) ? $res[ $uf ] : null;
			$c   = $r && isset( $r['candidatos'][0] ) && $r['candidatos'][0]['votos'] > 0 ? $r['candidatos'][0] : null;
			$k   = $c ? ( 'presidente' === $cargo ? $c['numero'] : $c['partido'] ) : '';
			$cor = $c && isset( $cores[ $k ] ) ? $cores[ $k ] : '#cbd5e1';
			// Intensidade pela força da vitória: 40% → claro, 70%+ → cheio.
			$a   = $c ? max( 0.38, min( 1, ( $c['pct'] - 30 ) / 40 ) ) : 1;
			$tit = strtoupper( $uf ) . ' · ' . JPXE_TSE::UFS[ $uf ] . ( $c ? ': ' . $c['nome'] . ' ' . JPXE_Render::pct( $c['pct'] ) : '' );
			$st  = 'grid-column:' . ( $xy[0] + 1 ) . ';grid-row:' . ( $xy[1] + 1 ) . ';background:' . ( $c ? self::rgba( $cor, $a ) : $cor ) . ';color:' . ( $a > 0.6 ? '#fff' : 'var(--jpxe-ink)' );
			$href = $link ? add_query_arg( array( 'jpxe_cargo' => $cargo, 'jpxe_local' => $uf ), $link ) : '';
			$tag  = $href ? 'a href="' . esc_url( $href ) . '"' : 'button type="button"';
			echo '<' . $tag . ' class="jpxe-mapa__uf" role="listitem" style="' . esc_attr( $st ) . '" title="' . esc_attr( $tit ) . '" aria-label="' . esc_attr( $tit ) . '" data-ir-cargo="' . esc_attr( $cargo ) . '" data-ir-local="' . esc_attr( $uf ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<b>' . esc_html( strtoupper( $uf ) ) . '</b>';
			if ( $c ) {
				echo '<small>' . esc_html( JPXE_Render::pct( $c['pct'], 0 ) ) . '</small>';
			}
			echo $href ? '</a>' : '</button>';
		}
		echo '</div>';

		echo '<div class="jpxe-mapa__lado"><ul class="jpxe-mapa__leg">';
		foreach ( $venc as $k => $v ) {
			echo '<li style="--jpxe-c:' . esc_attr( $cores[ $k ] ) . '"><span class="jpxe-hemi__sw"></span><strong>' . esc_html( $v['rot'] ) . '</strong><small>' . (int) $v['n'] . ( 1 === $v['n'] ? ' estado' : ' estados' ) . '</small></li>';
		}
		echo '</ul><p class="jpxe-nota">Cor mais forte = vitória mais folgada. Toque num estado para ver o resultado.</p></div>';
		echo '</div>';

		echo '<details class="jpxe-bu-mais jpxe-mapa__tab"><summary>Tabela por estado</summary><table class="jpxe-tabela"><thead><tr><th>UF</th><th>1º colocado</th><th>2º colocado</th><th>Seções</th></tr></thead><tbody>';
		$ord = $res;
		ksort( $ord );
		foreach ( $ord as $uf => $r ) {
			$c1 = isset( $r['candidatos'][0] ) ? $r['candidatos'][0] : null;
			$c2 = isset( $r['candidatos'][1] ) ? $r['candidatos'][1] : null;
			echo '<tr><th scope="row">' . esc_html( strtoupper( $uf ) ) . '</th>';
			echo '<td>' . ( $c1 ? esc_html( $c1['nome'] . ' (' . $c1['partido'] . ') ' . JPXE_Render::pct( $c1['pct'] ) ) : '—' ) . '</td>';
			echo '<td>' . ( $c2 ? esc_html( $c2['nome'] . ' (' . $c2['partido'] . ') ' . JPXE_Render::pct( $c2['pct'] ) ) : '—' ) . '</td>';
			echo '<td>' . esc_html( JPXE_Render::pct( $r['secoes']['pct'], 0 ) ) . '</td></tr>';
		}
		echo '</tbody></table></details>';

		echo '<footer class="jpxe-foot"><span>Fonte: <a href="https://resultados.tse.jus.br" target="_blank" rel="noopener">TSE</a></span><span class="jpxe-foot__dir"><span class="jpxe-countdown" aria-hidden="true"></span>' . JPXE_Render::botao_compartilhar() . '</span></footer></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		return array( 'html' => ob_get_clean(), 'intervalo' => $todas ? JPXE_Shortcodes::CONSOLIDADO : $base );
	}
}
