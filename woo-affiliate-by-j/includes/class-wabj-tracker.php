<?php
/**
 * Cookie tracking and checkout attribution.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

class WABJ_Tracker {

    /**
     * Initialize tracker hooks.
     */
    public static function init() {
        // Detect affiliate parameter and set cookie.
        add_action( 'template_redirect', array( __CLASS__, 'track_visit' ) );

        // Attribute referral at checkout.
        add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'attribute_referral' ), 10, 3 );
    }

    /* ----------------------------------------------------------
     * Click Tracking
     * --------------------------------------------------------*/

    /**
     * Detect affiliate URL parameter, set cookie, record click.
     */
    public static function track_visit() {
        $settings  = wabj_get_settings();
        $url_param = $settings['url_param'];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset( $_GET[ $url_param ] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $affiliate_id = absint( $_GET[ $url_param ] );

        if ( $affiliate_id < 1 ) {
            return;
        }

        // Validate affiliate user exists.
        $affiliate_user = get_user_by( 'ID', $affiliate_id );
        if ( ! $affiliate_user ) {
            return;
        }

        // Don't track if the visitor IS the affiliate (logged-in self-click).
        if ( is_user_logged_in() && get_current_user_id() === $affiliate_id ) {
            return;
        }

        // Set the tracking cookie.
        self::set_cookie( $affiliate_id );

        // Record the click.
        self::record_click( $affiliate_id );
    }

    /**
     * Set the affiliate tracking cookie.
     *
     * @param int $affiliate_id Affiliate user ID.
     */
    private static function set_cookie( $affiliate_id ) {
        $settings = wabj_get_settings();
        $name     = $settings['cookie_name'];
        $days     = absint( $settings['cookie_duration'] );
        $expire   = time() + ( $days * DAY_IN_SECONDS );

        // Set cookie with security flags.
        setcookie(
            $name,
            (string) $affiliate_id,
            array(
                'expires'  => $expire,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly'  => true,
                'samesite'  => 'Lax',
            )
        );

        // Also set in $_COOKIE for immediate access in same request.
        $_COOKIE[ $name ] = (string) $affiliate_id;
    }

    /**
     * Record a click in the database.
     *
     * @param int $affiliate_id Affiliate user ID.
     */
    private static function record_click( $affiliate_id ) {
        // Hash the IP for privacy (GDPR-friendly).
        $ip_raw  = self::get_client_ip();
        $ip_hash = hash( 'sha256', $ip_raw . wp_salt( 'auth' ) );

        // Determine product ID if on a product page.
        $product_id = 0;
        if ( is_singular( 'product' ) ) {
            $product_id = get_the_ID();
        }

        // Duplicate check (same affiliate + product + IP within 1 hour).
        if ( WABJ_DB::is_duplicate_click( $affiliate_id, $product_id, $ip_hash ) ) {
            return;
        }

        // Record.
        WABJ_DB::record_click( array(
            'affiliate_user_id' => $affiliate_id,
            'product_id'        => $product_id,
            'ip_hash'           => $ip_hash,
            'referrer_url'      => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
            'landing_url'       => esc_url_raw( home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ) ),
        ) );
    }

    /* ----------------------------------------------------------
     * Cookie Reading
     * --------------------------------------------------------*/

    /**
     * Get the affiliate user ID from the tracking cookie.
     *
     * @return int Affiliate user ID, or 0 if not set.
     */
    public static function get_tracking_affiliate_id() {
        $settings = wabj_get_settings();
        $name     = $settings['cookie_name'];

        if ( isset( $_COOKIE[ $name ] ) ) {
            return absint( $_COOKIE[ $name ] );
        }

        return 0;
    }

    /**
     * Clear the tracking cookie.
     */
    public static function clear_cookie() {
        $settings = wabj_get_settings();
        $name     = $settings['cookie_name'];

        setcookie(
            $name,
            '',
            array(
                'expires'  => time() - HOUR_IN_SECONDS,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly'  => true,
                'samesite' => 'Lax',
            )
        );

        unset( $_COOKIE[ $name ] );
    }

    /* ----------------------------------------------------------
     * Checkout Attribution
     * --------------------------------------------------------*/

    /**
     * Attribute a referral when an order is placed.
     *
     * @param int      $order_id    The order ID.
     * @param array    $posted_data The posted checkout data.
     * @param WC_Order $order       The order object.
     */
    public static function attribute_referral( $order_id, $posted_data = array(), $order = null ) {
        $affiliate_id = self::get_tracking_affiliate_id();

        // No affiliate cookie — nothing to do.
        if ( $affiliate_id < 1 ) {
            return;
        }

        // Validate the affiliate user still exists.
        $affiliate_user = get_user_by( 'ID', $affiliate_id );
        if ( ! $affiliate_user ) {
            self::clear_cookie();
            return;
        }

        // Get order object if not passed.
        if ( ! $order instanceof WC_Order ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order ) {
            return;
        }

        // Self-referral block: affiliate cannot earn on their own orders.
        $customer_id = $order->get_customer_id();
        if ( $customer_id && (int) $customer_id === (int) $affiliate_id ) {
            return;
        }

        // Prevent duplicate referrals for the same order.
        if ( WABJ_DB::referral_exists_for_order( $order_id ) ) {
            return;
        }

        // Calculate commission.
        $settings        = wabj_get_settings();
        $order_total     = floatval( $order->get_total() );
        $commission_rate = floatval( $settings['commission_rate'] );
        $commission_type = $settings['commission_type'];

        if ( 'percentage' === $commission_type ) {
            $commission_amount = round( ( $order_total * $commission_rate ) / 100, 2 );
        } else {
            $commission_amount = round( $commission_rate, 2 );
        }

        // Record the referral.
        WABJ_DB::record_referral( array(
            'affiliate_user_id' => $affiliate_id,
            'order_id'          => $order_id,
            'order_total'       => $order_total,
            'commission_amount' => $commission_amount,
            'commission_type'   => $commission_type,
            'commission_rate'   => $commission_rate,
            'status'            => 'pending',
        ) );

        // Store affiliate ID in order meta (HPOS-compatible).
        $order->update_meta_data( '_wabj_affiliate_id', $affiliate_id );
        $order->save();

        // Clear the tracking cookie.
        self::clear_cookie();
    }

    /* ----------------------------------------------------------
     * Utility
     * --------------------------------------------------------*/

    /**
     * Get the client IP address.
     *
     * @return string IP address.
     */
    private static function get_client_ip() {
        $ip = '0.0.0.0';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // May contain multiple IPs — take the first.
            $forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $parts     = explode( ',', $forwarded );
            $ip        = trim( $parts[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}