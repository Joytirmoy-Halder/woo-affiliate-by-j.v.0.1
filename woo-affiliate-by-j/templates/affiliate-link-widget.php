<?php
/**
 * Template: Affiliate link widget on single product page.
 *
 * Variables available: $aff_link, $user_id, $product
 *
 * @package WooAffiliateByJ
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wabj-affiliate-widget" id="wabj-affiliate-widget">
    <div class="wabj-widget-header">
        <svg class="wabj-widget-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
        </svg>
        <span class="wabj-widget-title"><?php esc_html_e( 'Your Affiliate Link', 'woo-affiliate-by-j' ); ?></span>
    </div>

    <div class="wabj-widget-body">
        <div class="wabj-link-row">
            <input
                type="text"
                id="wabj-aff-link"
                class="wabj-link-input"
                value="<?php echo esc_url( $aff_link ); ?>"
                readonly
            />
            <button
                type="button"
                class="wabj-copy-btn"
                id="wabj-copy-btn"
                data-copy-target="wabj-aff-link"
                aria-label="<?php esc_attr_e( 'Copy affiliate link to clipboard', 'woo-affiliate-by-j' ); ?>"
            >
                <svg class="wabj-copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span class="wabj-copy-text"><?php esc_html_e( 'Copy Link', 'woo-affiliate-by-j' ); ?></span>
            </button>
        </div>
        <p class="wabj-widget-note">
            <?php esc_html_e( 'Share this link to earn commissions on referred sales.', 'woo-affiliate-by-j' ); ?>
        </p>
    </div>
</div>