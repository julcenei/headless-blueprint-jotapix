<?php
/**
 * Shortcodes:
 *   [eleicoes_tse cargo="governador" local="pinhalzinho" layout="completo|compacto" limite="" turno="auto" fotos="sim" titulo="" link=""]
 *   [eleicoes_tse_painel cargos="presidente,governador,senador,deputado-federal,deputado-estadual,secoes" locais="br,sc,pinhalzinho" cargo="presidente" local="sc"]
 *   [eleicoes_tse_secoes local="pinhalzinho" turno="auto"]
 */

defined( 'ABSPATH' ) || exit;

class NFE_Shortcodes {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'eleicoes_tse', array( __CLASS__, 'widget' ) );
		add_shortcode( 'eleicoes_tse_painel', array( __CLASS__, 'painel' ) );
		add_shortcode( 'eleicoes_tse_secoes', array( __CLASS__, 'secoes' ) );
	}

	public static function register_assets() {
		wp_register_style( 'nfe-tse', NFE_URL . 'assets/css/eleicoes.css', array(), NFE_VERSION );
		wp_register_script( 'nfe-tse', NFE_URL . 'assets/js/eleicoes.js', array(), NFE_VERSION, true );
		wp_add_inline_script(
			'nfe-tse',
			'window.NFE_TSE=' . wp_json_encode( array( 'rest' => esc_url_raw( rest_url( NFE_Rest::NS . '/resultado' ) ) ) ) . ';',
			'before'
		);

		// Carrega o CSS no <head> quando o conteúdo da página já usa o shortcode (evita "piscar").
		$post = get_post();
		if ( $post && ( has_shortcode( $post->post_content, 'eleicoes_tse' ) || has_shortcode( $post->post_content, 'eleicoes_tse_painel' ) || has_shortcode( $post->post_content, 'eleicoes_tse_secoes' ) ) ) {
			wp_enqueue_style( 'nfe-tse' );
		}
	}

	private static function enqueue() {
		if ( ! wp_style_is( 'nfe-tse', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'nfe-tse' );
		wp_enqueue_script( 'nfe-tse' );
	}

	/** Intervalo de atualização em segundos para um resultado. */
	public static function intervalo( $r ) {
		$base = (int) NFE_Options::get( 'intervalo' );
		if ( is_wp_error( $r ) ) {
			return min( $base, 60 );
		}
		return $r['finalizada'] ? 15 * MINUTE_IN_SECONDS : $base;
	}

	/** Opções de renderização a partir de atributos de shortcode ou parâmetros REST. */
	public static function opcoes_render( $a ) {
		$fotos = isset( $a['fotos'] ) && '' !== $a['fotos']
			? ! in_array( strtolower( (string) $a['fotos'] ), array( 'nao', 'não', '0', 'false', 'off' ), true )
			: (bool) NFE_Options::get( 'fotos' );

		$link = isset( $a['link'] ) ? esc_url_raw( $a['link'] ) : '';
		// Só links para o próprio site (o endpoint REST é público).
		if ( $link && wp_parse_url( $link, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$link = '';
		}
		if ( ! $link && isset( $a['layout'] ) && 'compacto' === $a['layout'] ) {
			$link = (string) NFE_Options::get( 'link' );
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
		$cor = NFE_Options::get( 'cor' );
		if ( $cor ) {
			$css[] = '--nfe-accent:' . $cor;
		}
		if ( $largura ) {
			$css[] = '--nfe-largura:' . (int) $largura . 'px';
		}
		return ( $largura ? ' data-largo="1"' : '' ) . ( $css ? ' style="' . esc_attr( implode( ';', $css ) ) . '"' : '' );
	}

	/** Largura do atributo "largura" (px) ou, sem ele, da configuração. */
	private static function largura( $attr, $padrao ) {
		if ( '' !== (string) $attr ) {
			$v = (int) $attr;
			return $v > 0 ? max( 320, min( 1600, $v ) ) : 0;
		}
		return $padrao ? (int) NFE_Options::get( 'largura' ) : 0;
	}

	private static function cargo_valido( $c, $padrao = 'presidente' ) {
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
		return isset( NFE_TSE::CARGOS[ $c ] ) ? $c : $padrao;
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
		$a['local'] = NFE_Rest::limpa_local( $a['local'] );
		$opts       = self::opcoes_render( $a );
		$r          = NFE_TSE::resultado( $a['cargo'], $a['local'], self::turno_attr( $a['turno'] ) );

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
		return '<div class="nfe nfe-widget"' . self::estilo( $largura ) . ' data-nfe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . time() . '" data-intervalo="' . (int) self::intervalo( $r ) . '">'
			. '<div class="nfe-out">' . NFE_Render::resultado( $r, $opts ) . '</div></div>';
	}

	/** Seção pedida na URL (?nfe_secao=66-50). */
	private static function secao_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s = isset( $_GET['nfe_secao'] ) ? sanitize_text_field( wp_unslash( $_GET['nfe_secao'] ) ) : '';
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

		$local = NFE_Rest::limpa_local( $a['local'] );
		if ( '' === $local ) {
			$local = self::primeiro_mun( NFE_TSE::destaques() );
		}
		$loc    = NFE_TSE::local( $local );
		$chave  = is_wp_error( $loc ) ? $local : $loc['chave'];
		$secao  = self::secao_url();
		$s      = NFE_Secoes::render( $chave, $secao, self::turno_attr( $a['turno'] ) );
		$params = array_filter(
			array(
				'cargo' => 'secoes',
				'local' => $chave,
				'turno' => (string) self::turno_attr( $a['turno'] ),
				'secao' => $secao,
			),
			'strlen'
		);
		return '<div class="nfe nfe-widget"' . self::estilo( self::largura( $a['largura'], true ) ) . ' data-nfe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . time() . '" data-intervalo="' . (int) $s['intervalo'] . '" data-url="1">'
			. '<div class="nfe-out">' . $s['html'] . '</div></div>';
	}

	/** [eleicoes_tse_painel] — abas de cargo + seletor de local (Brasil, UF, municípios). */
	public static function painel( $atts ) {
		$uf = NFE_TSE::uf_padrao();
		$a  = shortcode_atts(
			array(
				'cargos' => implode( ',', array_keys( NFE_TSE::CARGOS ) ) . ',secoes',
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
			$c = self::cargo_valido( trim( $c ), '' );
			if ( $c ) {
				$cargos[ $c ] = NFE_TSE::CARGOS[ $c ]['nome'];
			}
		}
		if ( ! $cargos ) {
			$cargos = wp_list_pluck( NFE_TSE::CARGOS, 'nome' );
		}

		// Locais: Brasil + UF padrão + destaques das configurações (ou os do atributo).
		$locais = array();
		$lista  = '' !== trim( $a['locais'] ) ? explode( ',', $a['locais'] ) : array_merge( array( 'br', $uf ), array_keys( NFE_TSE::destaques() ) );
		foreach ( $lista as $item ) {
			$loc = NFE_TSE::local( NFE_Rest::limpa_local( trim( $item ) ) );
			if ( ! is_wp_error( $loc ) ) {
				$locais[ $loc['chave'] ] = $loc;
			}
		}

		// Estado inicial: ?nfe_cargo=&nfe_local= na URL (links compartilháveis) ou atributos.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$pedido = isset( $_GET['nfe_cargo'] ) ? sanitize_key( wp_unslash( $_GET['nfe_cargo'] ) ) : $a['cargo'];
		$cargo  = 'secoes' === $pedido ? 'secoes' : self::cargo_valido( $pedido, key( $cargos ) );
		$local = NFE_Rest::limpa_local( isset( $_GET['nfe_local'] ) ? wp_unslash( $_GET['nfe_local'] ) : ( $a['local'] ? $a['local'] : $uf ) );
		// phpcs:enable
		if ( ! isset( $cargos[ $cargo ] ) ) {
			$cargo = key( $cargos );
		}
		$escopo = 'secoes' === $cargo ? 'mun' : NFE_TSE::CARGOS[ $cargo ]['escopo'];
		if ( 'br' === $local && 'br' !== $escopo ) {
			$local = $uf;
		}
		$loc_atual = NFE_TSE::local( $local );
		$chave     = is_wp_error( $loc_atual ) ? $uf : $loc_atual['chave'];
		if ( 'mun' === $escopo && ( is_wp_error( $loc_atual ) || 'mun' !== $loc_atual['tipo'] ) ) {
			$chave = self::primeiro_mun( $locais );
		}

		$opts  = self::opcoes_render( $a );
		$secao = 'secoes' === $cargo ? self::secao_url() : '';
		if ( 'secoes' === $cargo ) {
			$sec       = NFE_Secoes::render( $chave, $secao, self::turno_attr( $a['turno'] ) );
			$html      = $sec['html'];
			$intervalo = $sec['intervalo'];
		} else {
			$r         = NFE_TSE::resultado( $cargo, $chave, self::turno_attr( $a['turno'] ) );
			$html      = NFE_Render::resultado( $r, $opts );
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

		$muns = NFE_TSE::municipios( $uf );

		ob_start();
		echo '<div class="nfe nfe-widget nfe-painel"' . self::estilo( self::largura( $a['largura'], true ) ) . ' data-nfe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . time() . '" data-intervalo="' . (int) $intervalo . '" data-uf="' . esc_attr( $uf ) . '" data-url="1">'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<div class="nfe-painel__bar">';
		echo '<div class="nfe-tabs" role="group" aria-label="Cargo">';
		foreach ( $cargos as $slug => $nome ) {
			echo '<button type="button" class="nfe-tab" data-cargo="' . esc_attr( $slug ) . '" data-escopo="' . esc_attr( 'secoes' === $slug ? 'mun' : NFE_TSE::CARGOS[ $slug ]['escopo'] ) . '" aria-pressed="' . ( $slug === $cargo ? 'true' : 'false' ) . '">' . esc_html( $nome ) . '</button>';
		}
		echo '</div>';

		echo '<div class="nfe-locais" role="group" aria-label="Local">';
		foreach ( $locais as $k => $loc ) {
			$oculto = ( 'br' === $k && 'br' !== $escopo ) || ( 'mun' === $escopo && 'mun' !== $loc['tipo'] );
			echo '<button type="button" class="nfe-chip" data-local="' . esc_attr( $k ) . '" data-tipo="' . esc_attr( $loc['tipo'] ) . '" aria-pressed="' . ( $k === $chave ? 'true' : 'false' ) . '"' . ( $oculto ? ' hidden' : '' ) . '>' . esc_html( 'uf' === $loc['tipo'] ? strtoupper( $loc['uf'] ) . ' · ' . $loc['nome'] : $loc['nome'] ) . '</button>';
		}
		if ( ! is_wp_error( $muns ) ) {
			echo '<label class="nfe-select"><span class="screen-reader-text">Outro município</span><select class="nfe-mun">';
			echo '<option value="">Outro município de ' . esc_html( strtoupper( $uf ) ) . '…</option>';
			foreach ( $muns as $m ) {
				$k = $uf . '-' . $m['cd'];
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $k === $chave && ! isset( $locais[ $k ] ), true, false ) . '>' . esc_html( $m['nome'] ) . '</option>';
			}
			echo '</select></label>';
		}
		echo '</div></div>';

		echo '<div class="nfe-out">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		return ob_get_clean();
	}
}
