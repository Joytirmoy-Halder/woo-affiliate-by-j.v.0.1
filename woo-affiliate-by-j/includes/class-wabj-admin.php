<?php
/**
 * Admin settings page and commissions management.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

class WABJ_Admin {

    /**
     * Initialize admin hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // Handle settings save.
        add_action( 'admin_init', array( __CLASS__, 'handle_save_settings' ) );

        // AJAX handlers for commission status.
        add_action( 'wp_ajax_wabj_update_referral_status', array( __CLASS__, 'ajax_update_status' ) );
    }

    /* ----------------------------------------------------------
     * Menu Registration
     * --------------------------------------------------------*/

    /**
     * Register admin menus under WooCommerce.
     */
    public static function register_menus() {
        // Parent menu: Settings.
        add_submenu_page(
            'woocommerce',
            __( 'Affiliate Settings', 'woo-affiliate-by-j' ),
            __( 'Affiliate Settings', 'woo-affiliate-by-j' ),
            'manage_woocommerce',
            'wabj-settings',
            array( __CLASS__, 'render_settings_page' )
        );

        // Sub page: Commissions.
        add_submenu_page(
            'woocommerce',
            __( 'Affiliate Commissions', 'woo-affiliate-by-j' ),
            __( 'Affiliate Commissions', 'woo-affiliate-by-j' ),
            'manage_woocommerce',
            'wabj-commissions',
            array( __CLASS__, 'render_commissions_page' )
        );
    }

    /* ----------------------------------------------------------
     * Assets
     * --------------------------------------------------------*/

    /**
     * Enqueue admin CSS and JS on our pages only.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( $hook ) {
        $our_pages = array(
            'woocommerce_page_wabj-settings',
            'woocommerce_page_wabj-commissions',
        );

        if ( ! in_array( $hook, $our_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'wabj-admin',
            WABJ_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WABJ_VERSION
        );

        wp_enqueue_script(
            'wabj-admin',
            WABJ_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            WABJ_VERSION,
            true
        );

        wp_localize_script( 'wabj-admin', 'wabjAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wabj_admin_nonce' ),
        ) );
    }

    /* ----------------------------------------------------------
     * Manual Settings Handler
     * --------------------------------------------------------*/

    /**
     * Handle manual POST save for settings.
     */
    public static function handle_save_settings() {
        if ( ! isset( $_POST['wabj_save_settings'] ) || ! isset( $_POST['wabj_settings_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['wabj_settings_nonce'], 'wabj_save_settings_action' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'woo-affiliate-by-j' ) );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'woo-affiliate-by-j' ) );
        }

        $input = isset( $_POST['wabj_settings'] ) ? (array) $_POST['wabj_settings'] : array();
        $sanitized = self::sanitize_settings( $input );

        update_option( 'wabj_settings', $sanitized );

        // Redirect back with success message.
        wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
        exit;
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $input Raw input values.
     * @return array Sanitized values.
     */
    public static function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        $sanitized['commission_rate'] = isset( $input['commission_rate'] )
            ? floatval( $input['commission_rate'] )
            : 10.0;

        $sanitized['commission_type'] = isset( $input['commission_type'] ) && in_array( $input['commission_type'], array( 'percentage', 'flat' ), true )
            ? $input['commission_type']
            : 'percentage';

        $sanitized['cookie_duration'] = isset( $input['cookie_duration'] )
            ? absint( $input['cookie_duration'] )
            : 30;

        $sanitized['cookie_name'] = isset( $input['cookie_name'] )
            ? sanitize_key( $input['cookie_name'] )
            : 'wabj_ref';

        $sanitized['url_param'] = isset( $input['url_param'] )
            ? sanitize_key( $input['url_param'] )
            : 'ref';

        return $sanitized;
    }

    /* ----------------------------------------------------------
     * Field Renderers
     * --------------------------------------------------------*/

    public static function field_commission_rate() {
        $settings = wabj_get_settings();
        printf(
            '<input type="number" name="wabj_settings[commission_rate]" value="%s" step="0.01" min="0" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr( $settings['commission_rate'] ),
            esc_html__( 'Commission rate value. Interpreted based on the Commission Type setting.', 'woo-affiliate-by-j' )
        );
    }

    public static function field_commission_type() {
        $settings = wabj_get_settings();
        $options  = array(
            'percentage' => __( 'Percentage of order total (%)', 'woo-affiliate-by-j' ),
            'flat'       => __( 'Flat amount per order', 'woo-affiliate-by-j' ),
        );

        echo '<select name="wabj_settings[commission_type]">';
        foreach ( $options as $value => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $value ),
                selected( $settings['commission_type'], $value, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
    }

    public static function field_cookie_duration() {
        $settings = wabj_get_settings();
        printf(
            '<input type="number" name="wabj_settings[cookie_duration]" value="%s" min="1" max="365" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr( $settings['cookie_duration'] ),
            esc_html__( 'Number of days the affiliate tracking cookie stays active.', 'woo-affiliate-by-j' )
        );
    }

    public static function field_cookie_name() {
        $settings = wabj_get_settings();
        printf(
            '<input type="text" name="wabj_settings[cookie_name]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr( $settings['cookie_name'] ),
            esc_html__( 'Name of the cookie used for affiliate tracking.', 'woo-affiliate-by-j' )
        );
    }

    public static function field_url_param() {
        $settings = wabj_get_settings();
        printf(
            '<input type="text" name="wabj_settings[url_param]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr( $settings['url_param'] ),
            esc_html__( 'The URL query parameter used for affiliate links (e.g. "ref" produces ?ref=123).', 'woo-affiliate-by-j' )
        );
    }

    /* ----------------------------------------------------------
     * Settings Page Renderer
     * --------------------------------------------------------*/

    /**
     * Render the settings page.
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'woo-affiliate-by-j' ) );
        }

        $updated = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];
        ?>
        <div class="wrap wabj-admin-wrap">
            <h1 class="wabj-admin-title">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e( 'Affiliate System Settings', 'woo-affiliate-by-j' ); ?>
            </h1>

            <?php if ( $updated ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings saved successfully.', 'woo-affiliate-by-j' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="wabj-admin-card">
                <form method="post" action="">
                    <?php wp_nonce_field( 'wabj_save_settings_action', 'wabj_settings_nonce' ); ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Commission Rate', 'woo-affiliate-by-j' ); ?></th>
                                <td><?php self::field_commission_rate(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Commission Type', 'woo-affiliate-by-j' ); ?></th>
                                <td><?php self::field_commission_type(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Cookie Duration (days)', 'woo-affiliate-by-j' ); ?></th>
                                <td><?php self::field_cookie_duration(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Cookie Name', 'woo-affiliate-by-j' ); ?></th>
                                <td><?php self::field_cookie_name(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'URL Parameter', 'woo-affiliate-by-j' ); ?></th>
                                <td><?php self::field_url_param(); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <input type="submit" name="wabj_save_settings" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'woo-affiliate-by-j' ); ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /* ----------------------------------------------------------
     * Commissions Page Renderer
     * --------------------------------------------------------*/

    /**
     * Render the commissions management page.
     */
    public static function render_commissions_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'woo-affiliate-by-j' ) );
        }

        $list_table = new WABJ_Admin_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap wabj-admin-wrap">
            <h1 class="wabj-admin-title">
                <span class="dashicons dashicons-money-alt"></span>
                <?php esc_html_e( 'Affiliate Commissions', 'woo-affiliate-by-j' ); ?>
            </h1>

            <div class="wabj-admin-card">
                <form method="get">
                    <input type="hidden" name="page" value="wabj-commissions" />
                    <?php
                    $list_table->search_box( __( 'Search', 'woo-affiliate-by-j' ), 'wabj-search' );
                    $list_table->views();
                    $list_table->display();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /* ----------------------------------------------------------
     * AJAX: Update Referral Status
     * --------------------------------------------------------*/

    /**
     * Handle AJAX status update for referrals.
     */
    public static function ajax_update_status() {
        check_ajax_referer( 'wabj_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-affiliate-by-j' ) ) );
        }

        $referral_id = isset( $_POST['referral_id'] ) ? absint( $_POST['referral_id'] ) : 0;
        $new_status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $referral_id || ! $new_status ) {
            wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'woo-affiliate-by-j' ) ) );
        }

        $result = WABJ_DB::update_referral_status( $referral_id, $new_status );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Status updated.', 'woo-affiliate-by-j' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update status.', 'woo-affiliate-by-j' ) ) );
        }
    }
}