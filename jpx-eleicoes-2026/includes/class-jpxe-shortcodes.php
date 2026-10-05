<?php
/**
 * Shortcodes:
 *   [eleicoes_tse cargo="governador" local="pinhalzinho" layout="completo|compacto" limite="" turno="auto" fotos="sim" titulo="" link=""]
 *   [eleicoes_tse_painel cargos="presidente,governador,senador,deputado-federal,deputado-estadual,secoes" locais="br,sc,pinhalzinho" cargo="presidente" local="sc"]
 *   [eleicoes_tse_secoes local="pinhalzinho" turno="auto"]
 *   [eleicoes_tse_mapa cargo="presidente"]
 *   [eleicoes_tse_regiao cargos="deputado-federal,deputado-estadual" locais="pinhalzinho,sao-lourenco-do-oeste" limite="10"]
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Shortcodes {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		foreach ( array( 'eleicoes_tse' => 'widget', 'eleicoes_tse_painel' => 'painel', 'eleicoes_tse_secoes' => 'secoes', 'eleicoes_tse_mapa' => 'mapa', 'eleicoes_tse_regiao' => 'regiao' ) as $tag => $fn ) {
			add_shortcode(
				$tag,
				function ( $atts ) use ( $fn ) {
					// A página nunca espera o TSE: usa só o cache; o navegador completa em seguida.
					JPXE_TSE::$sem_rede     = true;
					JPXE_TSE::$usou_reserva = false;
					$html                  = call_user_func( array( __CLASS__, $fn ), $atts );
					JPXE_TSE::$sem_rede     = false;
					return $html;
				}
			);
		}
	}

	/** Resultado consolidado: atualização a cada 30 min (só para perceber correções do TSE). */
	const CONSOLIDADO = 1800;

	/** data-gerado do contêiner: 0 força o navegador a atualizar logo (cache vazio ou cópia reserva). */
	private static function gerado() {
		return JPXE_TSE::$usou_reserva ? 0 : time();
	}

	public static function register_assets() {
		wp_register_style( 'jpx-eleicoes', JPXE_URL . 'assets/css/eleicoes.css', array(), JPXE_VERSION );
		wp_register_script( 'jpx-eleicoes', JPXE_URL . 'assets/js/eleicoes.js', array(), JPXE_VERSION, true );
		wp_add_inline_script(
			'jpx-eleicoes',
			'window.JPXE_TSE=' . wp_json_encode(
				array(
					'rest'     => esc_url_raw( rest_url( JPXE_Rest::NS . '/resultado' ) ),
					'estatico' => JPXE_Estatico::ativo() ? esc_url_raw( JPXE_Estatico::base()['url'] ) : '',
				)
			) . ';',
			'before'
		);

		// Carrega o CSS no <head> quando o conteúdo da página já usa o shortcode (evita "piscar").
		$post = get_post();
		if ( $post ) {
			foreach ( array( 'eleicoes_tse', 'eleicoes_tse_painel', 'eleicoes_tse_secoes', 'eleicoes_tse_mapa', 'eleicoes_tse_regiao' ) as $sc ) {
				if ( has_shortcode( $post->post_content, $sc ) ) {
					wp_enqueue_style( 'jpx-eleicoes' );
					break;
				}
			}
		}
	}

	private static function enqueue() {
		if ( ! wp_style_is( 'jpx-eleicoes', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'jpx-eleicoes' );
		wp_enqueue_script( 'jpx-eleicoes' );
	}

	/** Intervalo de atualização em segundos para um resultado. */
	public static function intervalo( $r ) {
		$base = (int) JPXE_Options::get( 'intervalo' );
		if ( is_wp_error( $r ) ) {
			return min( $base, 60 );
		}
		return JPXE_TSE::consolidado( $r ) ? self::CONSOLIDADO : $base;
	}

	/** Opções de renderização a partir de atributos de shortcode ou parâmetros REST. */
	public static function opcoes_render( $a ) {
		$fotos = isset( $a['fotos'] ) && '' !== $a['fotos']
			? ! in_array( strtolower( (string) $a['fotos'] ), array( 'nao', 'não', '0', 'false', 'off' ), true )
			: (bool) JPXE_Options::get( 'fotos' );

		$link = isset( $a['link'] ) ? esc_url_raw( $a['link'] ) : '';
		// Só links para o próprio site (o endpoint REST é público).
		if ( $link && wp_parse_url( $link, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$link = '';
		}
		if ( ! $link && isset( $a['layout'] ) && 'compacto' === $a['layout'] ) {
			$link = (string) JPXE_Options::get( 'link' );
		}

		return array(
			'layout' => isset( $a['layout'] ) && 'compacto' === $a['layout'] ? 'compacto' : 'completo',
			'limite' => isset( $a['limite'] ) ? max( 0, (int) $a['limite'] ) : 0,
			'fotos'  => $fotos,
			'titulo' => isset( $a['titulo'] ) ? sanitize_text_field( $a['titulo'] ) : '',
			'link'   => $link,
		);
	}

	/**
	 * Atributos de estilo do contêiner.
	 *
	 * @param int|null $largura Largura máxima em px para sair da coluna do tema (0/null = não sair).
	 */
	private static function estilo( $largura = null ) {
		$css = array();
		$cor = JPXE_Options::get( 'cor' );
		if ( $cor ) {
			$css[] = '--jpxe-accent:' . $cor;
		}
		if ( $largura ) {
			$css[] = '--jpxe-largura:' . (int) $largura . 'px';
		}
		$tema = JPXE_Options::get( 'aparencia' );
		return ( $largura ? ' data-largo="1"' : '' ) . ' data-tema="' . esc_attr( $tema ? $tema : 'claro' ) . '"' . ( $css ? ' style="' . esc_attr( implode( ';', $css ) ) . '"' : '' );
	}

	/** Ícones das abas (traço, 24×24, cor do texto). */
	private static function icone( $slug ) {
		$d = array(
			'presidente'        => '<path d="M3 21h18M5 18v-7M9.5 18v-7M14.5 18v-7M19 18v-7M12 3l9 5H3z"/>',
			'governador'        => '<path d="M5 21V4m0 0h11l-2 4 2 4H5"/>',
			'senador'           => '<path d="M12 4v16M6 7h12M6 7l-3 7a3 3 0 0 0 6 0zm12 0-3 7a3 3 0 0 0 6 0zM8 20h8"/>',
			'deputado-federal'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3a6.5 6.5 0 0 1 3.5 5.7"/>',
			'deputado-estadual' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3a6.5 6.5 0 0 1 3.5 5.7"/>',
			'secoes'            => '<path d="M4 12h16v9H4zM8 12V4h8v8M10.5 8h3"/>',
			'mapa'              => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14m6-12v14"/>',
			'local'             => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		);
		return isset( $d[ $slug ] ) ? '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d[ $slug ] . '</svg>' : '';
	}

	/** Página do painel (Configurações → Página da apuração), para os links do mapa. */
	public static function link_painel() {
		return (string) JPXE_Options::get( 'link' );
	}

	/** Contêiner padrão de um widget com atualização automática. */
	private static function caixa( $params, $html, $intervalo, $largura = 0, $url = false ) {
		return '<div class="jpxe jpxe-widget"' . self::estilo( $largura ) . ' data-jpxe="' . esc_attr( wp_json_encode( array_filter( $params, 'strlen' ) ) ) . '" data-gerado="' . self::gerado() . '" data-intervalo="' . (int) $intervalo . '"' . ( $url ? ' data-url="1"' : '' ) . '>'
			. '<div class="jpxe-out">' . $html . '</div></div>';
	}

	/** Lista "a,b" → array, com os destaques das configurações como padrão. */
	public static function regiao_render( $cargos, $locais, $limite, $titulo, $turno ) {
		$cs = array_filter( array_map( 'trim', explode( ',', (string) $cargos ) ) );
		$cs = $cs ? array_map( array( __CLASS__, 'cargo_valido' ), $cs ) : array( 'deputado-federal', 'deputado-estadual' );
		$ls = array_filter( array_map( 'trim', explode( ',', (string) $locais ) ) );
		$ls = $ls ? $ls : array_keys( JPXE_TSE::destaques() );
		return JPXE_Extras::regiao( array_unique( $cs ), $ls, $limite ? $limite : 10, $titulo, $turno );
	}

	/** [eleicoes_tse_mapa] — Brasil por UF. */
	public static function mapa( $atts ) {
		$a = shortcode_atts( array( 'cargo' => 'presidente', 'turno' => 'auto', 'largura' => '' ), $atts, 'eleicoes_tse_mapa' );
		self::enqueue();
		$cargo = in_array( $a['cargo'], array( 'presidente', 'governador', 'senador' ), true ) ? $a['cargo'] : 'presidente';
		$m     = JPXE_Extras::mapa( $cargo, self::turno_attr( $a['turno'] ), self::link_painel() );
		return self::caixa(
			array( 'cargo' => 'mapa', 'mapa' => $cargo, 'turno' => (string) self::turno_attr( $a['turno'] ) ),
			$m['html'],
			$m['intervalo'],
			self::largura( $a['largura'], false )
		);
	}

	/** [eleicoes_tse_regiao] — mais votados somando os municípios da região. */
	public static function regiao( $atts ) {
		$a = shortcode_atts( array( 'cargos' => '', 'locais' => '', 'limite' => '10', 'titulo' => '', 'turno' => 'auto', 'largura' => '' ), $atts, 'eleicoes_tse_regiao' );
		self::enqueue();
		$cargos = JPXE_Rest::limpa_lista( $a['cargos'] );
		$locais = JPXE_Rest::limpa_lista( $a['locais'] );
		$titulo = sanitize_text_field( $a['titulo'] );
		$r      = self::regiao_render( $cargos, $locais, (int) $a['limite'], $titulo, self::turno_attr( $a['turno'] ) );
		return self::caixa(
			array(
				'cargo'  => 'regiao',
				'cargos' => $cargos,
				'locais' => $locais,
				'limite' => (string) (int) $a['limite'],
				'titulo' => $titulo,
				'turno'  => (string) self::turno_attr( $a['turno'] ),
			),
			$r['html'],
			$r['intervalo'],
			self::largura( $a['largura'], false )
		);
	}

	/** Largura do atributo "largura" (px) ou, sem ele, da configuração. */
	private static function largura( $attr, $padrao ) {
		if ( '' !== (string) $attr ) {
			$v = (int) $attr;
			return $v > 0 ? max( 320, min( 1600, $v ) ) : 0;
		}
		return $padrao ? (int) JPXE_Options::get( 'largura' ) : 0;
	}

	public static function cargo_valido( $c, $padrao = 'presidente' ) {
		$c = sanitize_title( remove_accents( (string) $c ) );
		$alias = array(
			'deputado_federal'  => 'deputado-federal',
			'federal'           => 'deputado-federal',
			'deputado_estadual' => 'deputado-estadual',
			'estadual'          => 'deputado-estadual',
		);
		if ( isset( $alias[ $c ] ) ) {
			$c = $alias[ $c ];
		}
		return isset( JPXE_TSE::CARGOS[ $c ] ) ? $c : $padrao;
	}

	private static function turno_attr( $t ) {
		return in_array( (string) $t, array( '1', '2' ), true ) ? (int) $t : 'auto';
	}

	/** [eleicoes_tse] — um cargo em um local, com atualização automática. */
	public static function widget( $atts ) {
		$a = shortcode_atts(
			array(
				'cargo'  => 'presidente',
				'local'  => '',
				'turno'  => 'auto',
				'layout' => 'completo',
				'limite' => '',
				'fotos'  => '',
				'titulo'  => '',
				'link'    => '',
				'largura' => '',
			),
			$atts,
			'eleicoes_tse'
		);
		self::enqueue();

		$a['cargo'] = self::cargo_valido( $a['cargo'] );
		$a['local'] = JPXE_Rest::limpa_local( $a['local'] );
		$opts       = self::opcoes_render( $a );
		$r          = JPXE_TSE::resultado( $a['cargo'], $a['local'], self::turno_attr( $a['turno'] ) );

		$params = array_filter(
			array(
				'cargo'  => $a['cargo'],
				'local'  => $a['local'],
				'turno'  => (string) self::turno_attr( $a['turno'] ),
				'layout' => $opts['layout'],
				'limite' => $opts['limite'] ? (string) $opts['limite'] : '',
				'fotos'  => '' !== $a['fotos'] ? ( $opts['fotos'] ? '1' : '0' ) : '',
				'titulo' => $opts['titulo'],
				'link'   => $opts['link'],
			),
			'strlen'
		);

		$largura = 'compacto' === $opts['layout'] ? 0 : self::largura( $a['largura'], false );
		return '<div class="jpxe jpxe-widget"' . self::estilo( $largura ) . ' data-jpxe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . self::gerado() . '" data-intervalo="' . (int) self::intervalo( $r ) . '">'
			. '<div class="jpxe-out">' . JPXE_Render::resultado( $r, $opts ) . '</div></div>';
	}

	/** Parâmetro da URL (?jpxe_cargo=…); aceita também ?nfe_cargo=… dos links da versão anterior. */
	private static function get( $nome ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( array( 'jpxe_', 'nfe_' ) as $pref ) {
			if ( isset( $_GET[ $pref . $nome ] ) && is_string( $_GET[ $pref . $nome ] ) ) {
				return wp_unslash( $_GET[ $pref . $nome ] );
			}
		}
		// phpcs:enable
		return null;
	}

	/** Seção pedida na URL (?jpxe_secao=66-50). */
	private static function secao_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s = sanitize_text_field( (string) self::get( 'secao' ) );
		return preg_match( '/^\d{1,4}-\d{1,4}$/', $s ) ? $s : '';
	}

	/** Primeiro município da lista de locais (para a aba "Por seção"). */
	private static function primeiro_mun( $locais ) {
		foreach ( $locais as $k => $loc ) {
			if ( 'mun' === $loc['tipo'] ) {
				return $k;
			}
		}
		return '';
	}

	/** [eleicoes_tse_secoes] — seções de um município e boletim de urna de cada uma. */
	public static function secoes( $atts ) {
		$a = shortcode_atts( array( 'local' => '', 'turno' => 'auto', 'largura' => '' ), $atts, 'eleicoes_tse_secoes' );
		self::enqueue();

		$local = JPXE_Rest::limpa_local( $a['local'] );
		if ( '' === $local ) {
			$local = self::primeiro_mun( JPXE_TSE::destaques() );
		}
		$loc    = JPXE_TSE::local( $local );
		$chave  = is_wp_error( $loc ) ? $local : $loc['chave'];
		$secao  = self::secao_url();
		$s      = JPXE_Secoes::render( $chave, $secao, self::turno_attr( $a['turno'] ) );
		$params = array_filter(
			array(
				'cargo' => 'secoes',
				'local' => $chave,
				'turno' => (string) self::turno_attr( $a['turno'] ),
				'secao' => $secao,
			),
			'strlen'
		);
		return '<div class="jpxe jpxe-widget"' . self::estilo( self::largura( $a['largura'], true ) ) . ' data-jpxe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . self::gerado() . '" data-intervalo="' . (int) $s['intervalo'] . '" data-url="1">'
			. '<div class="jpxe-out">' . $s['html'] . '</div></div>';
	}

	/** [eleicoes_tse_painel] — abas de cargo + seletor de local (Brasil, UF, municípios). */
	public static function painel( $atts ) {
		$uf = JPXE_TSE::uf_padrao();
		$a  = shortcode_atts(
			array(
				'cargos' => implode( ',', array_keys( JPXE_TSE::CARGOS ) ) . ',secoes,mapa',
				'locais' => '',
				'cargo'  => '',
				'local'  => '',
				'fotos'  => '',
				'limite' => '',
				'turno'   => 'auto',
				'largura' => '',
			),
			$atts,
			'eleicoes_tse_painel'
		);
		self::enqueue();

		$cargos = array();
		foreach ( explode( ',', $a['cargos'] ) as $c ) {
			if ( in_array( trim( $c ), array( 'secoes', 'secao', 'por-secao' ), true ) ) {
				$cargos['secoes'] = 'Por seção';
				continue;
			}
			if ( 'mapa' === trim( $c ) ) {
				$cargos['mapa'] = 'Mapa';
				continue;
			}
			$c = self::cargo_valido( trim( $c ), '' );
			if ( $c ) {
				$cargos[ $c ] = JPXE_TSE::CARGOS[ $c ]['nome'];
			}
		}
		if ( ! $cargos ) {
			$cargos = wp_list_pluck( JPXE_TSE::CARGOS, 'nome' );
		}

		// Locais: Brasil + UF padrão + destaques das configurações (ou os do atributo).
		$locais = array();
		$lista  = '' !== trim( $a['locais'] ) ? explode( ',', $a['locais'] ) : array_merge( array( 'br', $uf ), array_keys( JPXE_TSE::destaques() ) );
		foreach ( $lista as $item ) {
			$loc = JPXE_TSE::local( JPXE_Rest::limpa_local( trim( $item ) ) );
			if ( ! is_wp_error( $loc ) ) {
				$locais[ $loc['chave'] ] = $loc;
			}
		}

		// Estado inicial: ?jpxe_cargo=&jpxe_local= na URL (links compartilháveis) ou atributos.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$pedido = null !== self::get( 'cargo' ) ? sanitize_key( self::get( 'cargo' ) ) : $a['cargo'];
		$cargo  = in_array( $pedido, array( 'secoes', 'mapa' ), true ) ? $pedido : self::cargo_valido( $pedido, key( $cargos ) );
		$local = JPXE_Rest::limpa_local( null !== self::get( 'local' ) ? self::get( 'local' ) : ( $a['local'] ? $a['local'] : $uf ) );
		// phpcs:enable
		if ( ! isset( $cargos[ $cargo ] ) ) {
			$cargo = key( $cargos );
		}
		$escopo = 'secoes' === $cargo ? 'mun' : ( 'mapa' === $cargo ? 'mapa' : JPXE_TSE::CARGOS[ $cargo ]['escopo'] );
		if ( 'br' === $local && 'br' !== $escopo && 'mapa' !== $escopo ) {
			$local = $uf;
		}
		$loc_atual = JPXE_TSE::local( $local );
		$chave     = is_wp_error( $loc_atual ) ? $uf : $loc_atual['chave'];
		if ( 'mun' === $escopo && ( is_wp_error( $loc_atual ) || 'mun' !== $loc_atual['tipo'] ) ) {
			$chave = self::primeiro_mun( $locais );
		}

		$opts  = self::opcoes_render( $a );
		$secao = 'secoes' === $cargo ? self::secao_url() : '';
		if ( 'secoes' === $cargo ) {
			$sec       = JPXE_Secoes::render( $chave, $secao, self::turno_attr( $a['turno'] ) );
			$html      = $sec['html'];
			$intervalo = $sec['intervalo'];
		} elseif ( 'mapa' === $cargo ) {
			$m         = JPXE_Extras::mapa( 'presidente', self::turno_attr( $a['turno'] ) );
			$html      = $m['html'];
			$intervalo = $m['intervalo'];
		} else {
			$r         = JPXE_TSE::resultado( $cargo, $chave, self::turno_attr( $a['turno'] ) );
			$html      = JPXE_Render::resultado( $r, $opts );
			$intervalo = self::intervalo( $r );
		}

		$params = array_filter(
			array(
				'cargo'  => $cargo,
				'local'  => $chave,
				'turno'  => (string) self::turno_attr( $a['turno'] ),
				'limite' => $opts['limite'] ? (string) $opts['limite'] : '',
				'fotos'  => '' !== $a['fotos'] ? ( $opts['fotos'] ? '1' : '0' ) : '',
				'secao'  => $secao,
			),
			'strlen'
		);

		$muns = JPXE_TSE::municipios( $uf );

		ob_start();
		echo '<div class="jpxe jpxe-widget jpxe-painel"' . self::estilo( self::largura( $a['largura'], true ) ) . ' data-jpxe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . self::gerado() . '" data-intervalo="' . (int) $intervalo . '" data-uf="' . esc_attr( $uf ) . '" data-url="1">'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<div class="jpxe-painel__bar">';
		echo '<div class="jpxe-tabs-wrap"><div class="jpxe-tabs" role="group" aria-label="Cargo">';
		foreach ( $cargos as $slug => $nome ) {
			echo '<button type="button" class="jpxe-tab" data-cargo="' . esc_attr( $slug ) . '" data-escopo="' . esc_attr( 'secoes' === $slug ? 'mun' : ( 'mapa' === $slug ? 'mapa' : JPXE_TSE::CARGOS[ $slug ]['escopo'] ) ) . '" aria-pressed="' . ( $slug === $cargo ? 'true' : 'false' ) . '">' . self::icone( $slug ) . '<span>' . esc_html( $nome ) . '</span></button>';
		}
		echo '</div></div>';

		echo '<div class="jpxe-locais" role="group" aria-label="Local"><span class="jpxe-locais__rot" aria-hidden="true">' . self::icone( 'local' ) . 'Local</span>';
		foreach ( $locais as $k => $loc ) {
			$oculto = 'mapa' === $escopo || ( 'br' === $k && 'br' !== $escopo ) || ( 'mun' === $escopo && 'mun' !== $loc['tipo'] );
			echo '<button type="button" class="jpxe-chip" data-local="' . esc_attr( $k ) . '" data-tipo="' . esc_attr( $loc['tipo'] ) . '" aria-pressed="' . ( $k === $chave ? 'true' : 'false' ) . '"' . ( $oculto ? ' hidden' : '' ) . '>' . esc_html( 'uf' === $loc['tipo'] ? strtoupper( $loc['uf'] ) . ' · ' . $loc['nome'] : $loc['nome'] ) . '</button>';
		}
		if ( ! is_wp_error( $muns ) ) {
			echo '<label class="jpxe-select"' . ( 'mapa' === $escopo ? ' hidden' : '' ) . '><span class="screen-reader-text">Outro município</span><select class="jpxe-mun">';
			echo '<option value="">Outro município de ' . esc_html( strtoupper( $uf ) ) . '…</option>';
			foreach ( $muns as $m ) {
				$k = $uf . '-' . $m['cd'];
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $k === $chave && ! isset( $locais[ $k ] ), true, false ) . '>' . esc_html( $m['nome'] ) . '</option>';
			}
			echo '</select></label>';
		}
		echo '</div></div>';

		echo '<div class="jpxe-out">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		return ob_get_clean();
	}
}
