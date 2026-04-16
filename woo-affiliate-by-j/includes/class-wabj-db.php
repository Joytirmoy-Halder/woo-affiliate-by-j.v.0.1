<?php
/**
 * Database table creation and query helpers.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

class WABJ_DB {

    /* ----------------------------------------------------------
     * Table names (with WP prefix)
     * --------------------------------------------------------*/

    /**
     * Get the clicks table name.
     */
    public static function clicks_table() {
        global $wpdb;
        return $wpdb->prefix . 'wabj_clicks';
    }

    /**
     * Get the referrals table name.
     */
    public static function referrals_table() {
        global $wpdb;
        return $wpdb->prefix . 'wabj_referrals';
    }

    /* ----------------------------------------------------------
     * Table Creation
     * --------------------------------------------------------*/

    /**
     * Create custom tables on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $clicks_table    = self::clicks_table();
        $referrals_table = self::referrals_table();

        $sql_clicks = "CREATE TABLE {$clicks_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_user_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED DEFAULT 0,
            ip_hash VARCHAR(64) NOT NULL DEFAULT '',
            referrer_url VARCHAR(2083) DEFAULT '',
            landing_url VARCHAR(2083) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_affiliate_user (affiliate_user_id),
            KEY idx_created_at (created_at),
            KEY idx_dedup (affiliate_user_id, product_id, ip_hash)
        ) {$charset_collate};";

        $sql_referrals = "CREATE TABLE {$referrals_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_user_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            order_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            commission_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_affiliate_user (affiliate_user_id),
            KEY idx_order_id (order_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_clicks );
        dbDelta( $sql_referrals );

        update_option( 'wabj_db_version', WABJ_VERSION );
    }

    /**
     * Set default plugin options.
     */
    public static function set_default_options() {
        if ( false === get_option( 'wabj_settings' ) ) {
            add_option( 'wabj_settings', array(
                'commission_rate'  => 10,
                'commission_type'  => 'percentage',
                'cookie_duration'  => 30,
                'cookie_name'      => 'wabj_ref',
                'url_param'        => 'ref',
            ) );
        }
    }

    /**
     * Drop tables (used on uninstall).
     */
    public static function drop_tables() {
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wabj_clicks" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wabj_referrals" );
        // phpcs:enable
    }

    /* ----------------------------------------------------------
     * Click Queries
     * --------------------------------------------------------*/

    /**
     * Record a click.
     *
     * @param array $data Click data.
     * @return int|false Insert ID or false on failure.
     */
    public static function record_click( $data ) {
        global $wpdb;

        $defaults = array(
            'affiliate_user_id' => 0,
            'product_id'        => 0,
            'ip_hash'           => '',
            'referrer_url'      => '',
            'landing_url'       => '',
            'created_at'        => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            self::clicks_table(),
            array(
                'affiliate_user_id' => absint( $data['affiliate_user_id'] ),
                'product_id'        => absint( $data['product_id'] ),
                'ip_hash'           => sanitize_text_field( $data['ip_hash'] ),
                'referrer_url'      => esc_url_raw( $data['referrer_url'] ),
                'landing_url'       => esc_url_raw( $data['landing_url'] ),
                'created_at'        => $data['created_at'],
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Check if a duplicate click exists within the last hour.
     *
     * @param int    $affiliate_user_id Affiliate user ID.
     * @param int    $product_id        Product ID.
     * @param string $ip_hash           Hashed IP address.
     * @return bool True if duplicate exists.
     */
    public static function is_duplicate_click( $affiliate_user_id, $product_id, $ip_hash ) {
        global $wpdb;

        $table = self::clicks_table();
        $one_hour_ago = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE affiliate_user_id = %d
               AND product_id = %d
               AND ip_hash = %s
               AND created_at > %s",
            absint( $affiliate_user_id ),
            absint( $product_id ),
            sanitize_text_field( $ip_hash ),
            $one_hour_ago
        ) );

        return (int) $count > 0;
    }

    /**
     * Get total clicks for an affiliate.
     *
     * @param int $user_id Affiliate user ID.
     * @return int Total click count.
     */
    public static function get_total_clicks( $user_id ) {
        global $wpdb;
        $table = self::clicks_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE affiliate_user_id = %d",
            absint( $user_id )
        ) );
    }

    /* ----------------------------------------------------------
     * Referral Queries
     * --------------------------------------------------------*/

    /**
     * Record a referral.
     *
     * @param array $data Referral data.
     * @return int|false Insert ID or false.
     */
    public static function record_referral( $data ) {
        global $wpdb;

        $defaults = array(
            'affiliate_user_id' => 0,
            'order_id'          => 0,
            'order_total'       => 0.00,
            'commission_amount' => 0.00,
            'commission_type'   => 'percentage',
            'commission_rate'   => 0.00,
            'status'            => 'pending',
            'created_at'        => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            self::referrals_table(),
            array(
                'affiliate_user_id' => absint( $data['affiliate_user_id'] ),
                'order_id'          => absint( $data['order_id'] ),
                'order_total'       => floatval( $data['order_total'] ),
                'commission_amount' => floatval( $data['commission_amount'] ),
                'commission_type'   => sanitize_text_field( $data['commission_type'] ),
                'commission_rate'   => floatval( $data['commission_rate'] ),
                'status'            => sanitize_text_field( $data['status'] ),
                'created_at'        => $data['created_at'],
            ),
            array( '%d', '%d', '%f', '%f', '%s', '%f', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Check if referral already recorded for this order.
     *
     * @param int $order_id WooCommerce order ID.
     * @return bool True if referral exists.
     */
    public static function referral_exists_for_order( $order_id ) {
        global $wpdb;
        $table = self::referrals_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d",
            absint( $order_id )
        ) );

        return (int) $count > 0;
    }

    /**
     * Get total referrals for an affiliate.
     *
     * @param int $user_id Affiliate user ID.
     * @return int Total referral count.
     */
    public static function get_total_referrals( $user_id ) {
        global $wpdb;
        $table = self::referrals_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE affiliate_user_id = %d",
            absint( $user_id )
        ) );
    }

    /**
     * Get earnings by status.
     *
     * @param int    $user_id Affiliate user ID.
     * @param string $status  Status (pending, approved, rejected).
     * @return float Total earnings.
     */
    public static function get_earnings_by_status( $user_id, $status = 'pending' ) {
        global $wpdb;
        $table = self::referrals_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(commission_amount), 0) FROM {$table}
             WHERE affiliate_user_id = %d AND status = %s",
            absint( $user_id ),
            sanitize_text_field( $status )
        ) );

        return floatval( $total );
    }

    /**
     * Update referral status.
     *
     * @param int    $referral_id Referral ID.
     * @param string $new_status  New status (approved/rejected).
     * @return bool Success.
     */
    public static function update_referral_status( $referral_id, $new_status ) {
        global $wpdb;
        $table = self::referrals_table();

        $allowed = array( 'pending', 'approved', 'rejected', 'processed' );
        if ( ! in_array( $new_status, $allowed, true ) ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->update(
            $table,
            array(
                'status'     => $new_status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $referral_id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Get referrals for admin list table.
     *
     * @param array $args Query arguments.
     * @return array Array of referral objects.
     */
    public static function get_referrals( $args = array() ) {
        global $wpdb;
        $table = self::referrals_table();

        $defaults = array(
            'status'   => '',
            'per_page' => 20,
            'offset'   => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'search'   => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $where = '1=1';
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $search_val = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where .= ' AND (order_id LIKE %s OR affiliate_user_id IN (SELECT ID FROM ' . $wpdb->users . ' WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s))';
            $params[] = $search_val;
            $params[] = $search_val;
            $params[] = $search_val;
            $params[] = $search_val;
        }

        // Whitelist orderby.
        $allowed_orderby = array( 'id', 'affiliate_user_id', 'order_id', 'order_total', 'commission_amount', 'status', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $params[] = absint( $args['per_page'] );
        $params[] = absint( $args['offset'] );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    /**
     * Count referrals.
     *
     * @param string $status Optional status filter.
     * @return int Count.
     */
    public static function count_referrals( $status = '', $search = '' ) {
        global $wpdb;
        $table = self::referrals_table();

        $where = '1=1';
        $params = array();

        if ( ! empty( $status ) ) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }

        if ( ! empty( $search ) ) {
            $search_val = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
            $where .= ' AND (order_id LIKE %s OR affiliate_user_id IN (SELECT ID FROM ' . $wpdb->users . ' WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s))';
            $params[] = $search_val;
            $params[] = $search_val;
            $params[] = $search_val;
            $params[] = $search_val;
        }

        if ( empty( $params ) ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        }

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE {$where}",
            $params
        ) );
    }

    /**
     * Get referral history for a specific user.
     *
     * @param int $user_id Affiliate user ID.
     * @return array History items.
     */
    public static function get_user_referral_history( $user_id ) {
        global $wpdb;
        $table = self::referrals_table();

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE affiliate_user_id = %d ORDER BY created_at DESC",
            absint( $user_id )
        ) );
    }
}