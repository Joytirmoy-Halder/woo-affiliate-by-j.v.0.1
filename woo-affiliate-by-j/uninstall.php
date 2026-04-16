<?php
/**
 * Uninstall handler.
 *
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * @package WooAffiliateByJ
 */

// Abort if not called by WordPress uninstall mechanism.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wabj_clicks" );    // phpcs:ignore
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wabj_referrals" ); // phpcs:ignore

// Delete options.
delete_option( 'wabj_settings' );
delete_option( 'wabj_db_version' );

// Clean up order meta across all orders (optional, can be heavy on large stores).
// Uncomment the following line if you want to remove all affiliate meta from orders:
// $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_wabj_affiliate_id'" );