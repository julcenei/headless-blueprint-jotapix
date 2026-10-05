<?php
/**
 * Shortcodes:
 *   [eleicoes_tse cargo="governador" local="pinhalzinho" layout="completo|compacto" limite="" turno="auto" fotos="sim" titulo="" link=""]
 *   [eleicoes_tse_painel cargos="presidente,governador,senador,deputado-federal,deputado-estadual" locais="br,sc,pinhalzinho" cargo="presidente" local="sc"]
 */

defined( 'ABSPATH' ) || exit;

class NFE_Shortcodes {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'eleicoes_tse', array( __CLASS__, 'widget' ) );
		add_shortcode( 'eleicoes_tse_painel', array( __CLASS__, 'painel' ) );
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
		if ( $post && ( has_shortcode( $post->post_content, 'eleicoes_tse' ) || has_shortcode( $post->post_content, 'eleicoes_tse_painel' ) ) ) {
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

	private static function estilo() {
		$cor = NFE_Options::get( 'cor' );
		return $cor ? ' style="--nfe-accent:' . esc_attr( $cor ) . '"' : '';
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
				'titulo' => '',
				'link'   => '',
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

		return '<div class="nfe nfe-widget"' . self::estilo() . ' data-nfe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . time() . '" data-intervalo="' . (int) self::intervalo( $r ) . '">'
			. '<div class="nfe-out">' . NFE_Render::resultado( $r, $opts ) . '</div></div>';
	}

	/** [eleicoes_tse_painel] — abas de cargo + seletor de local (Brasil, UF, municípios). */
	public static function painel( $atts ) {
		$uf = NFE_TSE::uf_padrao();
		$a  = shortcode_atts(
			array(
				'cargos' => implode( ',', array_keys( NFE_TSE::CARGOS ) ),
				'locais' => '',
				'cargo'  => '',
				'local'  => '',
				'fotos'  => '',
				'limite' => '',
				'turno'  => 'auto',
			),
			$atts,
			'eleicoes_tse_painel'
		);
		self::enqueue();

		$cargos = array();
		foreach ( explode( ',', $a['cargos'] ) as $c ) {
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
		$cargo = self::cargo_valido( isset( $_GET['nfe_cargo'] ) ? wp_unslash( $_GET['nfe_cargo'] ) : $a['cargo'], key( $cargos ) );
		$local = NFE_Rest::limpa_local( isset( $_GET['nfe_local'] ) ? wp_unslash( $_GET['nfe_local'] ) : ( $a['local'] ? $a['local'] : $uf ) );
		// phpcs:enable
		if ( ! isset( $cargos[ $cargo ] ) ) {
			$cargo = key( $cargos );
		}
		if ( 'br' === $local && 'br' !== NFE_TSE::CARGOS[ $cargo ]['escopo'] ) {
			$local = $uf;
		}
		$loc_atual = NFE_TSE::local( $local );
		$chave     = is_wp_error( $loc_atual ) ? $uf : $loc_atual['chave'];

		$opts = self::opcoes_render( $a );
		$r    = NFE_TSE::resultado( $cargo, $chave, self::turno_attr( $a['turno'] ) );

		$params = array_filter(
			array(
				'cargo'  => $cargo,
				'local'  => $chave,
				'turno'  => (string) self::turno_attr( $a['turno'] ),
				'limite' => $opts['limite'] ? (string) $opts['limite'] : '',
				'fotos'  => '' !== $a['fotos'] ? ( $opts['fotos'] ? '1' : '0' ) : '',
			),
			'strlen'
		);

		$muns = NFE_TSE::municipios( $uf );

		ob_start();
		echo '<div class="nfe nfe-widget nfe-painel"' . self::estilo() . ' data-nfe="' . esc_attr( wp_json_encode( $params ) ) . '" data-gerado="' . time() . '" data-intervalo="' . (int) self::intervalo( $r ) . '" data-uf="' . esc_attr( $uf ) . '" data-url="1">'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<div class="nfe-painel__bar">';
		echo '<div class="nfe-tabs" role="group" aria-label="Cargo">';
		foreach ( $cargos as $slug => $nome ) {
			echo '<button type="button" class="nfe-tab" data-cargo="' . esc_attr( $slug ) . '" data-escopo="' . esc_attr( NFE_TSE::CARGOS[ $slug ]['escopo'] ) . '" aria-pressed="' . ( $slug === $cargo ? 'true' : 'false' ) . '">' . esc_html( $nome ) . '</button>';
		}
		echo '</div>';

		echo '<div class="nfe-locais" role="group" aria-label="Local">';
		foreach ( $locais as $k => $loc ) {
			$oculto = 'br' === $k && 'br' !== NFE_TSE::CARGOS[ $cargo ]['escopo'];
			echo '<button type="button" class="nfe-chip" data-local="' . esc_attr( $k ) . '" aria-pressed="' . ( $k === $chave ? 'true' : 'false' ) . '"' . ( $oculto ? ' hidden' : '' ) . '>' . esc_html( 'uf' === $loc['tipo'] ? strtoupper( $loc['uf'] ) . ' · ' . $loc['nome'] : $loc['nome'] ) . '</button>';
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

		echo '<div class="nfe-out">' . NFE_Render::resultado( $r, $opts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		return ob_get_clean();
	}
}
