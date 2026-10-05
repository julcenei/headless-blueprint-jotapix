<?php
/**
 * Configurações do plugin (Configurações → Eleições TSE).
 */

defined( 'ABSPATH' ) || exit;

class NFE_Options {

	const KEY = 'nfe_tse_opcoes';

	public static function defaults() {
		return array(
			'ciclo'     => 'ele2026',
			'uf'        => 'sc',
			'destaques' => 'pinhalzinho, sao-lourenco-do-oeste',
			'intervalo' => 60,
			'cor'       => '#ff6600',
			'fotos'     => 1,
			'link'      => '',
		);
	}

	public static function all() {
		$saved = get_option( self::KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$in  = is_array( $input ) ? $input : array();
		$out = array();

		$ciclo        = isset( $in['ciclo'] ) ? strtolower( trim( $in['ciclo'] ) ) : $d['ciclo'];
		$out['ciclo'] = preg_match( '/^ele\d{4}$/', $ciclo ) ? $ciclo : $d['ciclo'];

		$uf        = isset( $in['uf'] ) ? strtolower( trim( $in['uf'] ) ) : $d['uf'];
		$out['uf'] = isset( NFE_TSE::UFS[ $uf ] ) ? $uf : $d['uf'];

		$out['destaques'] = isset( $in['destaques'] ) ? sanitize_text_field( $in['destaques'] ) : '';
		$out['intervalo'] = isset( $in['intervalo'] ) ? max( 30, min( 600, (int) $in['intervalo'] ) ) : $d['intervalo'];

		$cor        = isset( $in['cor'] ) ? sanitize_hex_color( $in['cor'] ) : '';
		$out['cor'] = $cor ? $cor : $d['cor'];

		$out['fotos'] = empty( $in['fotos'] ) ? 0 : 1;
		$out['link']  = isset( $in['link'] ) ? esc_url_raw( trim( $in['link'] ) ) : '';

		// Ciclo ou UF diferentes mudam todas as chaves de dados: invalida o cache.
		$old = self::all();
		if ( $old['ciclo'] !== $out['ciclo'] || $old['uf'] !== $out['uf'] ) {
			NFE_TSE::flush_cache();
		}

		return $out;
	}
}
