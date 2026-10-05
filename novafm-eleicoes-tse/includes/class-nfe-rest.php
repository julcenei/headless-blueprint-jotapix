<?php
/**
 * Endpoint público usado pela atualização automática:
 *   GET /wp-json/nfe-tse/v1/resultado?cargo=governador&local=sc
 * Devolve o HTML já renderizado, servido do cache do servidor.
 */

defined( 'ABSPATH' ) || exit;

class NFE_Rest {

	const NS = 'nfe-tse/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/resultado',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'resultado' ),
				'args'                => array(
					'cargo'  => array( 'type' => 'string', 'required' => true, 'enum' => array_merge( array_keys( NFE_TSE::CARGOS ), array( 'secoes', 'mapa', 'regiao' ) ) ),
					'mapa'   => array( 'type' => 'string', 'default' => 'presidente', 'enum' => array( 'presidente', 'governador', 'senador' ) ),
					'cargos' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => array( __CLASS__, 'limpa_lista' ) ),
					'locais' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => array( __CLASS__, 'limpa_lista' ) ),
					'secao'  => array( 'type' => 'string', 'default' => '', 'pattern' => '^(\\d{1,4}-\\d{1,4})?$' ),
					'local'  => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => array( __CLASS__, 'limpa_local' ) ),
					'turno'  => array( 'type' => 'string', 'default' => 'auto', 'enum' => array( 'auto', '1', '2' ) ),
					'layout' => array( 'type' => 'string', 'default' => 'completo', 'enum' => array( 'completo', 'compacto' ) ),
					'limite' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0, 'maximum' => 2000 ),
					'fotos'  => array( 'type' => 'string', 'default' => '' ),
					'titulo' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
					'link'   => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'esc_url_raw' ),
				),
			)
		);
	}

	public static function limpa_local( $v ) {
		return substr( preg_replace( '/[^a-z0-9:-]/', '', strtolower( remove_accents( (string) $v ) ) ), 0, 60 );
	}

	public static function limpa_lista( $v ) {
		return substr( preg_replace( '/[^a-z0-9:,-]/', '', strtolower( remove_accents( (string) $v ) ) ), 0, 300 );
	}

	public static function resultado( WP_REST_Request $req ) {
		$turno = $req['turno'];
		$turno = 'auto' === $turno ? 'auto' : (int) $turno;

		if ( 'mapa' === $req['cargo'] ) {
			$s = NFE_Extras::mapa( $req['mapa'], $turno, NFE_Shortcodes::link_painel() );
			return self::resposta( $s['html'], $s['intervalo'], false );
		}
		if ( 'regiao' === $req['cargo'] ) {
			$s = NFE_Shortcodes::regiao_render( $req['cargos'], $req['locais'], $req['limite'], $req['titulo'], $turno );
			return self::resposta( $s['html'], $s['intervalo'], false );
		}
		if ( 'secoes' === $req['cargo'] ) {
			$s = NFE_Secoes::render( $req['local'], $req['secao'], $turno );
			return self::resposta( $s['html'], $s['intervalo'], false );
		}

		$r     = NFE_TSE::resultado( $req['cargo'], $req['local'], $turno );
		$opts  = NFE_Shortcodes::opcoes_render( $req->get_params() );

		return self::resposta( NFE_Render::resultado( $r, $opts ), NFE_Shortcodes::intervalo( $r ), ! is_wp_error( $r ) && $r['finalizada'] );
	}

	private static function resposta( $html, $intervalo, $finalizada ) {
		$resp = new WP_REST_Response(
			array(
				'html'       => $html,
				'gerado'     => time(),
				'intervalo'  => (int) $intervalo,
				'finalizada' => (bool) $finalizada,
			)
		);
		// Deixa CDN/proxy segurar a resposta por alguns segundos em noite de apuração.
		$resp->header( 'Cache-Control', 'public, max-age=20, stale-while-revalidate=40' );
		return $resp;
	}
}
