<?php
/**
 * Plugin Name:       WooCommerce Affiliate System by J
 * Plugin URI:        https://a3cricket.eu
 * Description:       A production-ready affiliate system for WooCommerce. Every logged-in user can generate affiliate links, track clicks, and earn commissions on referred sales.
 * Version:           1.0.0
 * Author:            J
 * Author URI:        https://a3cricket.eu
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-affiliate-by-j
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

/*--------------------------------------------------------------
 * Constants
 *------------------------------------------------------------*/
define( 'WABJ_VERSION', '1.0.0' );
define( 'WABJ_PLUGIN_FILE', __FILE__ );
define( 'WABJ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WABJ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WABJ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*--------------------------------------------------------------
 * HPOS Compatibility Declaration
 *------------------------------------------------------------*/
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

/*--------------------------------------------------------------
 * WooCommerce Dependency Check
 *------------------------------------------------------------*/
function wabj_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'WooCommerce Affiliate System requires WooCommerce to be installed and active.', 'woo-affiliate-by-j' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}

/*--------------------------------------------------------------
 * Include Files
 *------------------------------------------------------------*/
function wabj_load_includes() {
    $includes = array(
        'includes/class-wabj-db.php',
        'includes/class-wabj-tracker.php',
        'includes/class-wabj-frontend.php',
        'includes/class-wabj-dashboard.php',
        'includes/class-wabj-admin.php',
        'includes/class-wabj-admin-table.php',
    );

    foreach ( $includes as $file ) {
        $path = WABJ_PLUGIN_DIR . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

/*--------------------------------------------------------------
 * Plugin Initialization
 *------------------------------------------------------------*/
function wabj_init() {
    if ( ! wabj_check_woocommerce() ) {
        return;
    }

    wabj_load_includes();

    // Initialize components.
    WABJ_Tracker::init();
    WABJ_Frontend::init();
    WABJ_Dashboard::init();

    if ( is_admin() ) {
        WABJ_Admin::init();
    }
}
add_action( 'plugins_loaded', 'wabj_init' );

/*--------------------------------------------------------------
 * Activation Hook
 *------------------------------------------------------------*/
function wabj_activate() {
    require_once WABJ_PLUGIN_DIR . 'includes/class-wabj-db.php';
    WABJ_DB::create_tables();
    WABJ_DB::set_default_options();

    // Flush rewrite rules for the My Account endpoint.
    require_once WABJ_PLUGIN_DIR . 'includes/class-wabj-dashboard.php';
    WABJ_Dashboard::register_endpoint();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wabj_activate' );

/*--------------------------------------------------------------
 * Deactivation Hook
 *------------------------------------------------------------*/
function wabj_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wabj_deactivate' );

/*--------------------------------------------------------------
 * Helper: Get plugin settings
 *------------------------------------------------------------*/
function wabj_get_settings() {
    $defaults = array(
        'commission_rate'  => 10,
        'commission_type'  => 'percentage',   // 'percentage' or 'flat'
        'cookie_duration'  => 30,             // days
        'cookie_name'      => 'wabj_ref',
        'url_param'        => 'ref',
    );

    $settings = get_option( 'wabj_settings', array() );

    return wp_parse_args( $settings, $defaults );
}