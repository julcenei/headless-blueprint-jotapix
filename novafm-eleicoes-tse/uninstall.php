<?php
/**
 * Remove opções e cache ao excluir o plugin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'nfe_tse_opcoes' );
delete_option( 'nfe_tse_cache_v' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_nfe%' OR option_name LIKE '\\_transient\\_timeout\\_nfe%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
