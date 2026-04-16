<?php
/**
 * Template: Affiliate Dashboard (My Account tab).
 *
 * Variables available: $user_id, $total_clicks, $total_referrals,
 *                      $pending_earnings, $approved_earnings,
 *                      $url_param, $site_url, $settings
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;

$currency_symbol = get_woocommerce_currency_symbol();
?>

<div class="wabj-dashboard" id="wabj-dashboard">

    <!-- Stats Cards -->
    <div class="wabj-stats-grid">

        <div class="wabj-stat-card wabj-stat-clicks">
            <div class="wabj-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h6v6"/><path d="M10 14L21 3"/>
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                </svg>
            </div>
            <div class="wabj-stat-content">
                <span class="wabj-stat-number"><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></span>
                <span class="wabj-stat-label"><?php esc_html_e( 'Total Clicks', 'woo-affiliate-by-j' ); ?></span>
            </div>
        </div>

        <div class="wabj-stat-card wabj-stat-referrals">
            <div class="wabj-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="wabj-stat-content">
                <span class="wabj-stat-number"><?php echo esc_html( number_format_i18n( $total_referrals ) ); ?></span>
                <span class="wabj-stat-label"><?php esc_html_e( 'Total Referrals', 'woo-affiliate-by-j' ); ?></span>
            </div>
        </div>

        <div class="wabj-stat-card wabj-stat-pending">
            <div class="wabj-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="wabj-stat-content">
                <span class="wabj-stat-number"><?php echo esc_html( $currency_symbol . number_format( $pending_earnings, 2 ) ); ?></span>
                <span class="wabj-stat-label"><?php esc_html_e( 'Pending Earnings', 'woo-affiliate-by-j' ); ?></span>
            </div>
        </div>

        <div class="wabj-stat-card wabj-stat-approved">
            <div class="wabj-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="wabj-stat-content">
                <span class="wabj-stat-number"><?php echo esc_html( $currency_symbol . number_format( $approved_earnings, 2 ) ); ?></span>
                <span class="wabj-stat-label"><?php esc_html_e( 'Approved (Unpaid)', 'woo-affiliate-by-j' ); ?></span>
            </div>
        </div>

        <div class="wabj-stat-card wabj-stat-processed">
            <div class="wabj-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
            <div class="wabj-stat-content">
                <span class="wabj-stat-number"><?php echo esc_html( $currency_symbol . number_format( $processed_earnings, 2 ) ); ?></span>
                <span class="wabj-stat-label"><?php esc_html_e( 'Total Paid', 'woo-affiliate-by-j' ); ?></span>
            </div>
        </div>

    </div>

    <!-- Link Generator -->
    <div class="wabj-link-generator" id="wabj-link-generator">
        <h3 class="wabj-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
            </svg>
            <?php esc_html_e( 'Affiliate Link Generator', 'woo-affiliate-by-j' ); ?>
        </h3>
        <p class="wabj-generator-desc">
            <?php esc_html_e( 'Paste any store URL below to generate your personal affiliate link.', 'woo-affiliate-by-j' ); ?>
        </p>

        <div class="wabj-generator-form">
            <div class="wabj-input-group">
                <label for="wabj-store-url" class="screen-reader-text">
                    <?php esc_html_e( 'Store URL', 'woo-affiliate-by-j' ); ?>
                </label>
                <input
                    type="url"
                    id="wabj-store-url"
                    class="wabj-url-input"
                    placeholder="<?php esc_attr_e( 'Paste any store URL here…', 'woo-affiliate-by-j' ); ?>"
                    data-site-url="<?php echo esc_url( $site_url ); ?>"
                />
                <button type="button" class="wabj-generate-btn" id="wabj-generate-btn">
                    <?php esc_html_e( 'Generate', 'woo-affiliate-by-j' ); ?>
                </button>
            </div>

            <!-- Generated link output (hidden initially) -->
            <div class="wabj-generated-output" id="wabj-generated-output" style="display: none;">
                <div class="wabj-link-row">
                    <input
                        type="text"
                        id="wabj-generated-link"
                        class="wabj-link-input"
                        readonly
                    />
                    <button
                        type="button"
                        class="wabj-copy-btn"
                        data-copy-target="wabj-generated-link"
                        aria-label="<?php esc_attr_e( 'Copy generated affiliate link', 'woo-affiliate-by-j' ); ?>"
                    >
                        <svg class="wabj-copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <span class="wabj-copy-text"><?php esc_html_e( 'Copy Link', 'woo-affiliate-by-j' ); ?></span>
                    </button>
                </div>
            </div>

            <p class="wabj-generator-error" id="wabj-generator-error" style="display: none;"></p>
        </div>
    </div>

    <!-- Affiliate Info -->
    <div class="wabj-info-box">
        <h3 class="wabj-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <?php esc_html_e( 'How It Works', 'woo-affiliate-by-j' ); ?>
        </h3>
        <ol class="wabj-how-it-works">
            <li><?php esc_html_e( 'Generate your unique affiliate link for any product or page.', 'woo-affiliate-by-j' ); ?></li>
            <li><?php esc_html_e( 'Share your link with friends, followers, or your audience.', 'woo-affiliate-by-j' ); ?></li>
            <li><?php esc_html_e( 'When someone clicks your link and makes a purchase, you earn a commission.', 'woo-affiliate-by-j' ); ?></li>
            <li><?php esc_html_e( 'Track your clicks, referrals, and earnings right here in your dashboard.', 'woo-affiliate-by-j' ); ?></li>
        </ol>
        <p class="wabj-commission-info">
            <?php
            $rate = $settings['commission_rate'];
            $type = $settings['commission_type'];
            if ( 'percentage' === $type ) {
                printf(
                    /* translators: %s: commission percentage */
                    esc_html__( 'Current commission rate: %s%% of the order total.', 'woo-affiliate-by-j' ),
                    esc_html( $rate )
                );
            } else {
                printf(
                    /* translators: %s: flat commission amount */
                    esc_html__( 'Current commission rate: %s per referred order.', 'woo-affiliate-by-j' ),
                    esc_html( $currency_symbol . number_format( $rate, 2 ) )
                );
            }
            ?>
        </p>
    </div>

    <!-- Referral History -->
    <div class="wabj-history-box" id="wabj-history-box" style="margin-top: 24px;">
        <h3 class="wabj-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php esc_html_e( 'Referral History', 'woo-affiliate-by-j' ); ?>
        </h3>

        <?php if ( empty( $history ) ) : ?>
            <p class="wabj-empty-history" style="font-size: 14px; color: #64748b; margin-top: 10px;">
                <?php esc_html_e( 'You have no referral history yet.', 'woo-affiliate-by-j' ); ?>
            </p>
        <?php else : ?>
            <div class="wabj-table-wrapper" style="overflow-x: auto; margin-top: 16px;">
                <table class="wabj-history-table" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 12px 8px; font-weight: 600;"><?php esc_html_e( 'Order', 'woo-affiliate-by-j' ); ?></th>
                            <th style="padding: 12px 8px; font-weight: 600;"><?php esc_html_e( 'Amount', 'woo-affiliate-by-j' ); ?></th>
                            <th style="padding: 12px 8px; font-weight: 600;"><?php esc_html_e( 'Status', 'woo-affiliate-by-j' ); ?></th>
                            <th style="padding: 12px 8px; font-weight: 600;"><?php esc_html_e( 'Date', 'woo-affiliate-by-j' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $history as $row ) : ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 8px;">#<?php echo absint( $row->order_id ); ?></td>
                                <td style="padding: 12px 8px;"><?php echo esc_html( $currency_symbol . number_format( $row->commission_amount, 2 ) ); ?></td>
                                <td style="padding: 12px 8px;">
                                    <span class="wabj-status-badge-inline wabj-status-<?php echo esc_attr( $row->status ); ?>">
                                        <?php echo esc_html( ucfirst( $row->status ) ); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 8px;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>