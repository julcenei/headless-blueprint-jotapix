<?php
/**
 * Remove opções e cache ao excluir o plugin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'jpxe_opcoes' );
delete_option( 'jpxe_cache_v' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_jpxe%' OR option_name LIKE '\\_transient\\_timeout\\_jpxe%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Arquivos estáticos das atualizações.
$jpxe_up  = wp_upload_dir( null, false );
$jpxe_dir = trailingslashit( $jpxe_up['basedir'] ) . 'jpx-eleicoes';
foreach ( (array) glob( $jpxe_dir . '/*' ) as $jpxe_f ) {
	if ( $jpxe_f && is_file( $jpxe_f ) ) {
		@unlink( $jpxe_f ); // phpcs:ignore
	}
}
@rmdir( $jpxe_dir ); // phpcs:ignore
wp_clear_scheduled_hook( 'jpxe_limpeza' );
