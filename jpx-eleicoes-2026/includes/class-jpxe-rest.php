<?php
/**
 * Endpoint público usado pela atualização automática:
 *   GET /wp-json/jpx-eleicoes/v1/resultado?cargo=governador&local=sc
 * Devolve o HTML já renderizado, servido do cache do servidor.
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Rest {

	const NS = 'jpx-eleicoes/v1';

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
					'cargo'  => array( 'type' => 'string', 'required' => true, 'enum' => array_merge( array_keys( JPXE_TSE::CARGOS ), array( 'secoes', 'mapa', 'regiao' ) ) ),
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
			$s = JPXE_Extras::mapa( $req['mapa'], $turno, JPXE_Shortcodes::link_painel() );
		} elseif ( 'regiao' === $req['cargo'] ) {
			$s = JPXE_Shortcodes::regiao_render( $req['cargos'], $req['locais'], $req['limite'], $req['titulo'], $turno );
		} elseif ( 'secoes' === $req['cargo'] ) {
			$s = JPXE_Secoes::render( $req['local'], $req['secao'], $turno );
		} else {
			$r = JPXE_TSE::resultado( $req['cargo'], $req['local'], $turno );
			$s = array(
				'html'      => JPXE_Render::resultado( $r, JPXE_Shortcodes::opcoes_render( $req->get_params() ) ),
				'intervalo' => JPXE_Shortcodes::intervalo( $r ),
			);
		}
		return self::resposta( $req, $s['html'], $s['intervalo'] );
	}

	private static function resposta( WP_REST_Request $req, $html, $intervalo ) {
		$intervalo   = (int) $intervalo;
		$consolidado = $intervalo >= JPXE_Shortcodes::CONSOLIDADO;
		$dados       = array(
			'html'        => $html,
			'gerado'      => time(),
			'intervalo'   => $intervalo,
			'consolidado' => $consolidado,
			// Até quando o arquivo estático vale: depois disso o navegador pede ao REST de novo.
			'expira'      => time() + ( $consolidado ? $intervalo : max( 20, (int) ( $intervalo / 2 ) ) ),
		);
		JPXE_Estatico::gravar( $req->get_query_params(), $dados );

		$resp = new WP_REST_Response( $dados );
		// Deixa CDN/proxy segurar a resposta por alguns segundos em noite de apuração.
		$resp->header( 'Cache-Control', 'public, max-age=' . ( $consolidado ? 600 : 20 ) . ', stale-while-revalidate=60' );
		return $resp;
	}
}
