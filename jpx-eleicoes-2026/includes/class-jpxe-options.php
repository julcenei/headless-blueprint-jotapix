<?php
/**
 * Configurações do plugin (Configurações → Eleições 2026).
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Options {

	const KEY = 'jpxe_opcoes';

	public static function defaults() {
		return array(
			'ciclo'     => 'ele2026',
			'uf'        => 'sc',
			'destaques' => '',
			'intervalo' => 60,
			'cor'       => '#ff6600',
			'fotos'     => 1,
			'link'      => '',
			'largura'   => 1130,
			'aparencia' => 'claro',
		);
	}

	public static function all() {
		$saved = get_option( self::KEY, null );
		// Sem configurações próprias ainda: usa as do plugin anterior (novafm-eleicoes-tse), se houver.
		// Não grava aqui: no painel, gravar dispara a validação do WordPress, que chama all() de novo.
		if ( null === $saved ) {
			$antigo = get_option( 'nfe_tse_opcoes', null );
			$saved  = is_array( $antigo ) ? $antigo : array();
		}
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Copia uma vez as configurações do plugin anterior. Chamado no admin_init antes do
	 * register_setting, quando ainda não há validação registrada para a opção.
	 */
	public static function migrar() {
		if ( null !== get_option( self::KEY, null ) ) {
			return;
		}
		$antigo = get_option( 'nfe_tse_opcoes', null );
		if ( is_array( $antigo ) ) {
			add_option( self::KEY, $antigo );
		}
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$in  = is_array( $input ) ? $input : array();
		$out = array();

		$ciclo        = isset( $in['ciclo'] ) ? strtolower( trim( $in['ciclo'] ) ) : $d['ciclo'];
		$out['ciclo'] = preg_match( '/^ele\d{4}$/', $ciclo ) ? $ciclo : $d['ciclo'];

		$uf        = isset( $in['uf'] ) ? strtolower( trim( $in['uf'] ) ) : $d['uf'];
		$out['uf'] = isset( JPXE_TSE::UFS[ $uf ] ) ? $uf : $d['uf'];

		$out['destaques'] = isset( $in['destaques'] ) ? sanitize_text_field( $in['destaques'] ) : '';
		$out['intervalo'] = isset( $in['intervalo'] ) ? max( 30, min( 600, (int) $in['intervalo'] ) ) : $d['intervalo'];

		$cor        = isset( $in['cor'] ) ? sanitize_hex_color( $in['cor'] ) : '';
		$out['cor'] = $cor ? $cor : $d['cor'];

		$out['fotos'] = empty( $in['fotos'] ) ? 0 : 1;
		$out['link']  = isset( $in['link'] ) ? esc_url_raw( trim( $in['link'] ) ) : '';
		// 0 = respeitar a largura do tema; senão, entre 600 e 1600 px.
		$larg           = isset( $in['largura'] ) ? (int) $in['largura'] : $d['largura'];
		$out['largura'] = $larg <= 0 ? 0 : max( 600, min( 1600, $larg ) );

		$out['aparencia'] = isset( $in['aparencia'] ) && in_array( $in['aparencia'], array( 'claro', 'auto', 'escuro' ), true ) ? $in['aparencia'] : 'claro';

		// Ciclo ou UF diferentes mudam todas as chaves de dados: invalida o cache.
		$old = self::all();
		if ( $old['ciclo'] !== $out['ciclo'] || $old['uf'] !== $out['uf'] ) {
			JPXE_TSE::flush_cache();
		}

		return $out;
	}
}
