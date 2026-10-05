<?php
/**
 * Plugin Name:       JPX Eleições 2026
 * Description:       Apuração e resultados oficiais das eleições direto do TSE (resultados.tse.jus.br): Presidente, Governador, Senador e Deputados por Brasil, estado, município e seção eleitoral, com mapa, hemiciclo e boletins de urna. Shortcodes [eleicoes_tse_painel], [eleicoes_tse], [eleicoes_tse_secoes], [eleicoes_tse_mapa] e [eleicoes_tse_regiao].
 * Version:           1.4.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            JPX
 * License:           GPL-2.0-or-later
 * Text Domain:       jpx-eleicoes-2026
 */

defined( 'ABSPATH' ) || exit;

define( 'JPXE_VERSION', '1.4.1' );
define( 'JPXE_FILE', __FILE__ );
define( 'JPXE_DIR', plugin_dir_path( __FILE__ ) );
define( 'JPXE_URL', plugin_dir_url( __FILE__ ) );

require_once JPXE_DIR . 'includes/class-jpxe-options.php';
require_once JPXE_DIR . 'includes/class-jpxe-tse.php';
require_once JPXE_DIR . 'includes/class-jpxe-estatico.php';
require_once JPXE_DIR . 'includes/class-jpxe-render.php';
require_once JPXE_DIR . 'includes/class-jpxe-bu.php';
require_once JPXE_DIR . 'includes/class-jpxe-secoes.php';
require_once JPXE_DIR . 'includes/class-jpxe-extras.php';
require_once JPXE_DIR . 'includes/class-jpxe-rest.php';
require_once JPXE_DIR . 'includes/class-jpxe-shortcodes.php';

if ( is_admin() ) {
	require_once JPXE_DIR . 'includes/class-jpxe-admin.php';
	JPXE_Admin::init();
}

JPXE_Rest::init();

// Limpeza diária dos arquivos estáticos antigos (wp-content/uploads/jpx-eleicoes).
add_action(
	'jpxe_limpeza',
	function () {
		JPXE_Estatico::limpar( 2 * DAY_IN_SECONDS );
	}
);
register_activation_hook(
	__FILE__,
	function () {
		if ( ! wp_next_scheduled( 'jpxe_limpeza' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'jpxe_limpeza' );
		}
	}
);
register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'jpxe_limpeza' );
	}
);
JPXE_Shortcodes::init();

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=jpx-eleicoes' ) ) . '">Configurações</a>' );
		return $links;
	}
);
