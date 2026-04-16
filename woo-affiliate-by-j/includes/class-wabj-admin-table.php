<?php
/**
 * Admin: WP_List_Table for affiliate commissions.
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WABJ_Admin_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'referral',
            'plural'   => 'referrals',
            'ajax'     => true,
        ) );
    }

    /**
     * Define table columns.
     *
     * @return array Column headers.
     */
    public function get_columns() {
        return array(
            'cb'                => '<input type="checkbox" />',
            'id'                => __( 'ID', 'woo-affiliate-by-j' ),
            'affiliate'         => __( 'Affiliate', 'woo-affiliate-by-j' ),
            'order_id'          => __( 'Order', 'woo-affiliate-by-j' ),
            'order_total'       => __( 'Order Total', 'woo-affiliate-by-j' ),
            'commission_amount' => __( 'Commission', 'woo-affiliate-by-j' ),
            'status'            => __( 'Status', 'woo-affiliate-by-j' ),
            'created_at'        => __( 'Date', 'woo-affiliate-by-j' ),
            'actions'           => __( 'Actions', 'woo-affiliate-by-j' ),
        );
    }

    /**
     * Define sortable columns.
     *
     * @return array Sortable columns.
     */
    public function get_sortable_columns() {
        return array(
            'id'                => array( 'id', false ),
            'order_total'       => array( 'order_total', false ),
            'commission_amount' => array( 'commission_amount', false ),
            'created_at'        => array( 'created_at', true ),
        );
    }

    /**
     * Define bulk actions.
     *
     * @return array Bulk actions.
     */
    public function get_bulk_actions() {
        return array(
            'approve' => __( 'Approve', 'woo-affiliate-by-j' ),
            'reject'  => __( 'Reject', 'woo-affiliate-by-j' ),
            'process' => __( 'Mark as Processed', 'woo-affiliate-by-j' ),
        );
    }

    /**
     * Process bulk actions.
     */
    public function process_bulk_action() {
        $action = $this->current_action();

        if ( ! in_array( $action, array( 'approve', 'reject' ), true ) ) {
            return;
        }

        // Verify nonce.
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'bulk-referrals' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'woo-affiliate-by-j' ) );
        }

        $referral_ids = isset( $_REQUEST['referral'] ) ? array_map( 'absint', (array) $_REQUEST['referral'] ) : array();

        if ( empty( $referral_ids ) ) {
            return;
        }

        $new_status = 'pending';
        if ( 'approve' === $action ) $new_status = 'approved';
        if ( 'reject' === $action ) $new_status = 'rejected';
        if ( 'process' === $action ) $new_status = 'processed';

        foreach ( $referral_ids as $id ) {
            WABJ_DB::update_referral_status( $id, $new_status );
        }

        // Add admin notice.
        add_action( 'admin_notices', function () use ( $referral_ids, $new_status ) {
            $count = count( $referral_ids );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html( sprintf(
                    /* translators: 1: count, 2: status */
                    _n(
                        '%1$d referral marked as %2$s.',
                        '%1$d referrals marked as %2$s.',
                        $count,
                        'woo-affiliate-by-j'
                    ),
                    $count,
                    $new_status
                ) )
            );
        } );
    }

    /**
     * Get status filter views.
     *
     * @return array Views.
     */
    protected function get_views() {
        $current = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $base    = admin_url( 'admin.php?page=wabj-commissions' );

        $total     = WABJ_DB::count_referrals();
        $pending   = WABJ_DB::count_referrals( 'pending' );
        $approved  = WABJ_DB::count_referrals( 'approved' );
        $processed = WABJ_DB::count_referrals( 'processed' );
        $rejected  = WABJ_DB::count_referrals( 'rejected' );

        $views = array();

        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( $base ),
            empty( $current ) ? 'current' : '',
            esc_html__( 'All', 'woo-affiliate-by-j' ),
            $total
        );

        $views['pending'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( add_query_arg( 'status', 'pending', $base ) ),
            'pending' === $current ? 'current' : '',
            esc_html__( 'Pending', 'woo-affiliate-by-j' ),
            $pending
        );

        $views['approved'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( add_query_arg( 'status', 'approved', $base ) ),
            'approved' === $current ? 'current' : '',
            esc_html__( 'Approved', 'woo-affiliate-by-j' ),
            $approved
        );

        $views['processed'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( add_query_arg( 'status', 'processed', $base ) ),
            'processed' === $current ? 'current' : '',
            esc_html__( 'Processed', 'woo-affiliate-by-j' ),
            $processed
        );

        $views['rejected'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( add_query_arg( 'status', 'rejected', $base ) ),
            'rejected' === $current ? 'current' : '',
            esc_html__( 'Rejected', 'woo-affiliate-by-j' ),
            $rejected
        );

        return $views;
    }

    /**
     * Prepare items for display.
     */
    public function prepare_items() {
        $this->process_bulk_action();

        $per_page    = 20;
        $current_page = $this->get_pagenum();

        // phpcs:ignore WordPress.Security.NonceVerification
        $status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
        // phpcs:ignore WordPress.Security.NonceVerification
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';
        // phpcs:ignore WordPress.Security.NonceVerification
        $search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $this->items = WABJ_DB::get_referrals( array(
            'status'   => $status,
            'per_page' => $per_page,
            'offset'   => ( $current_page - 1 ) * $per_page,
            'orderby'  => $orderby,
            'order'    => $order,
            'search'   => $search,
        ) );

        $total_items = WABJ_DB::count_referrals( $status, $search );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    /**
     * Checkbox column.
     *
     * @param object $item Current row item.
     * @return string Checkbox HTML.
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="referral[]" value="%d" />',
            absint( $item->id )
        );
    }

    /**
     * Default column output.
     *
     * @param object $item        Current row item.
     * @param string $column_name Column name.
     * @return string Column value.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                return absint( $item->id );

            case 'affiliate':
                $user = get_user_by( 'ID', $item->affiliate_user_id );
                if ( $user ) {
                    return sprintf(
                        '<strong>%s</strong><br><small>ID: %d</small>',
                        esc_html( $user->display_name ),
                        absint( $item->affiliate_user_id )
                    );
                }
                return sprintf( __( 'User #%d (deleted)', 'woo-affiliate-by-j' ), absint( $item->affiliate_user_id ) );

            case 'order_id':
                $order_url = admin_url( 'post.php?post=' . absint( $item->order_id ) . '&action=edit' );
                // Try HPOS URL.
                if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
                    $order_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $item->order_id ) );
                }
                return sprintf(
                    '<a href="%s">#%d</a>',
                    esc_url( $order_url ),
                    absint( $item->order_id )
                );

            case 'order_total':
                return wc_price( $item->order_total );

            case 'commission_amount':
                $rate_info = '';
                if ( 'percentage' === $item->commission_type ) {
                    $rate_info = sprintf( '(%s%%)', esc_html( $item->commission_rate ) );
                } else {
                    $rate_info = __( '(flat)', 'woo-affiliate-by-j' );
                }
                return wc_price( $item->commission_amount ) . ' <small>' . $rate_info . '</small>';

            case 'status':
                $badge_class = 'wabj-badge-' . esc_attr( $item->status );
                return sprintf(
                    '<span class="wabj-status-badge %s">%s</span>',
                    esc_attr( $badge_class ),
                    esc_html( ucfirst( $item->status ) )
                );

            case 'created_at':
                return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) ) );

            case 'actions':
                return self::render_row_actions( $item );

            default:
                return '';
        }
    }

    /**
     * Render row action buttons.
     *
     * @param object $item Current row item.
     * @return string Action buttons HTML.
     */
    private static function render_row_actions( $item ) {
        $buttons = '';

        if ( 'approved' !== $item->status ) {
            $buttons .= sprintf(
                '<button type="button" class="button button-small wabj-action-btn wabj-approve-btn" data-id="%d" data-status="approved">%s</button> ',
                absint( $item->id ),
                esc_html__( 'Approve', 'woo-affiliate-by-j' )
            );
        }

        if ( 'processed' !== $item->status ) {
            $buttons .= sprintf(
                '<button type="button" class="button button-small wabj-action-btn wabj-process-btn" data-id="%d" data-status="processed">%s</button>',
                absint( $item->id ),
                esc_html__( 'Mark Paid', 'woo-affiliate-by-j' )
            );
        }

        return $buttons;
    }

    /**
     * Message when no items found.
     */
    public function no_items() {
        esc_html_e( 'No referral commissions found.', 'woo-affiliate-by-j' );
    }
}