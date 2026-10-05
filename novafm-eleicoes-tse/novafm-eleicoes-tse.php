<?php
/**
 * Plugin Name:       Eleições TSE – Nova FM
 * Plugin URI:        https://novafmportal.com.br
 * Description:       Apuração e resultados oficiais das eleições direto do TSE (resultados.tse.jus.br): Presidente, Governador, Senador, Deputados, por Brasil, estado e município. Inclui boletim de urna por seção. Shortcodes [eleicoes_tse], [eleicoes_tse_painel] e [eleicoes_tse_secoes].
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Portal Nova FM
 * License:           GPL-2.0-or-later
 * Text Domain:       novafm-eleicoes-tse
 */

defined( 'ABSPATH' ) || exit;

define( 'NFE_VERSION', '1.2.0' );
define( 'NFE_FILE', __FILE__ );
define( 'NFE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NFE_URL', plugin_dir_url( __FILE__ ) );

require_once NFE_DIR . 'includes/class-nfe-options.php';
require_once NFE_DIR . 'includes/class-nfe-tse.php';
require_once NFE_DIR . 'includes/class-nfe-render.php';
require_once NFE_DIR . 'includes/class-nfe-bu.php';
require_once NFE_DIR . 'includes/class-nfe-secoes.php';
require_once NFE_DIR . 'includes/class-nfe-rest.php';
require_once NFE_DIR . 'includes/class-nfe-shortcodes.php';

if ( is_admin() ) {
	require_once NFE_DIR . 'includes/class-nfe-admin.php';
	NFE_Admin::init();
}

NFE_Rest::init();
NFE_Shortcodes::init();

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=nfe-tse' ) ) . '">Configurações</a>' );
		return $links;
	}
);
