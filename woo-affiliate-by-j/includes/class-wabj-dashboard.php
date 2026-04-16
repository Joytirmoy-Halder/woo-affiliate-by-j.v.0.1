<?php
/**
 * My Account: Affiliate Dashboard tab.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

class WABJ_Dashboard {

    /**
     * Initialize dashboard hooks.
     */
    public static function init() {
        // Register the custom endpoint.
        add_action( 'init', array( __CLASS__, 'register_endpoint' ) );

        // Add menu item to My Account.
        add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ) );

        // Render the endpoint content.
        add_action( 'woocommerce_account_affiliate-dashboard_endpoint', array( __CLASS__, 'render_dashboard' ) );

        // Set the endpoint title.
        add_filter( 'the_title', array( __CLASS__, 'set_endpoint_title' ), 10, 2 );
    }

    /**
     * Register the affiliate-dashboard endpoint.
     */
    public static function register_endpoint() {
        add_rewrite_endpoint( 'affiliate-dashboard', EP_ROOT | EP_PAGES );
    }

    /**
     * Add "Affiliate Dashboard" to the My Account menu.
     *
     * @param array $items Menu items.
     * @return array Modified menu items.
     */
    public static function add_menu_item( $items ) {
        // Insert before "Logout".
        $new_items = array();

        foreach ( $items as $key => $label ) {
            if ( 'customer-logout' === $key ) {
                $new_items['affiliate-dashboard'] = __( 'Affiliate Dashboard', 'woo-affiliate-by-j' );
            }
            $new_items[ $key ] = $label;
        }

        // Fallback if "customer-logout" key doesn't exist.
        if ( ! isset( $new_items['affiliate-dashboard'] ) ) {
            $new_items['affiliate-dashboard'] = __( 'Affiliate Dashboard', 'woo-affiliate-by-j' );
        }

        return $new_items;
    }

    /**
     * Set the page title for the endpoint.
     *
     * @param string $title The page title.
     * @param int    $id    The post ID.
     * @return string Modified title.
     */
    public static function set_endpoint_title( $title, $id = 0 ) {
        if ( is_account_page() && in_the_loop() && is_main_query() ) {
            global $wp_query;
            if ( isset( $wp_query->query_vars['affiliate-dashboard'] ) ) {
                return __( 'Affiliate Dashboard', 'woo-affiliate-by-j' );
            }
        }
        return $title;
    }

    /**
     * Render the Affiliate Dashboard content.
     */
    public static function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();

        // Gather stats.
        $total_clicks      = WABJ_DB::get_total_clicks( $user_id );
        $total_referrals   = WABJ_DB::get_total_referrals( $user_id );
        $pending_earnings   = WABJ_DB::get_earnings_by_status( $user_id, 'pending' );
        $approved_earnings  = WABJ_DB::get_earnings_by_status( $user_id, 'approved' );
        $processed_earnings = WABJ_DB::get_earnings_by_status( $user_id, 'processed' );
        $history            = WABJ_DB::get_user_referral_history( $user_id );

        $settings  = wabj_get_settings();
        $url_param = $settings['url_param'];
        $site_url  = home_url( '/' );

        // Load template.
        $template = WABJ_PLUGIN_DIR . 'templates/dashboard.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
    }
}