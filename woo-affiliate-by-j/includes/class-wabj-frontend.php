<?php
/**
 * Frontend: Affiliate link widget on single product pages.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

class WABJ_Frontend {

    /**
     * Initialize frontend hooks.
     */
    public static function init() {
        // Add affiliate link widget to single product pages.
        add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_affiliate_widget' ), 45 );

        // Enqueue frontend assets.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Enqueue frontend CSS and JS on product pages only.
     */
    public static function enqueue_assets() {
        if ( ! is_product() && ! is_account_page() ) {
            return;
        }

        wp_enqueue_style(
            'wabj-frontend',
            WABJ_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WABJ_VERSION
        );

        wp_enqueue_script(
            'wabj-frontend',
            WABJ_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            WABJ_VERSION,
            true
        );

        wp_localize_script( 'wabj-frontend', 'wabjFront', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wabj_frontend_nonce' ),
            'siteUrl'  => home_url( '/' ),
            'urlParam' => wabj_get_settings()['url_param'],
            'userId'   => get_current_user_id(),
            'i18n'     => array(
                'copied'  => __( 'Copied!', 'woo-affiliate-by-j' ),
                'copy'    => __( 'Copy Link', 'woo-affiliate-by-j' ),
                'error'   => __( 'Failed to copy', 'woo-affiliate-by-j' ),
            ),
        ) );
    }

    /**
     * Render the affiliate link widget on the single product page.
     */
    public static function render_affiliate_widget() {
        // Only show to logged-in users.
        if ( ! is_user_logged_in() ) {
            return;
        }

        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $settings   = wabj_get_settings();
        $user_id    = get_current_user_id();
        $product_url = get_permalink( $product->get_id() );
        $aff_link   = add_query_arg( $settings['url_param'], $user_id, $product_url );

        // Load template.
        $template = WABJ_PLUGIN_DIR . 'templates/affiliate-link-widget.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
    }
}