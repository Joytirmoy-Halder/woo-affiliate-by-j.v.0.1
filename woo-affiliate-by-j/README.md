# WooCommerce Affiliate System by J

A production-ready, lightweight, and powerful affiliate system for WooCommerce. This plugin allows every logged-in user on your site to become an affiliate instantly, generate links for any product, and earn commissions on referred sales.

## 🚀 Key Features

### 1. Instant Affiliate Status
- No application process needed. Any logged-in member is automatically an affiliate.
- No bulky third-party dependencies; built specifically for WooCommerce performance.

### 2. Frontend Affiliate Tools
- **Product Page Widget**: A sleek, premium widget appears on every single product page (visible only to logged-in users).
- **One-Click Copy**: Users can instantly copy their unique affiliate link for that specific product.
- **Smart URL Structure**: Uses clean parameters (e.g., `?ref=123`) for easy sharing.

### 3. Integrated Affiliate Dashboard
- **My Account Tab**: A new "Affiliate Dashboard" tab is added to the standard WooCommerce My Account page.
- **Real-Time Stats**:
    - **Total Clicks**: Monitor high-level traffic.
    - **Total Referrals**: Count successful conversions.
    - **Pending Earnings**: View income waiting for approval.
    - **Approved Earnings**: View verified income.
- **Custom Link Generator**: A tool inside the dashboard where users can paste ANY store URL to generate a custom affiliate link.

### 4. Robust Tracking & Attribution
- **Secure Cookies**: Tracks visitors using HttpOnly, SameSite=Lax cookies with configurable expiration.
- **Self-Referral Prevention**: Affiliates cannot earn commissions on their own purchases.
- **Privacy First**: IP addresses are hashed using SHA-256 for GDPR compliance while still preventing click fraud.
- **Duplicate Prevention**: Intelligently ignores duplicate clicks from the same source to keep stats clean.

### 5. Admin Management
- **Commissions List**: A dedicated admin table to view, filter, approve, or reject referrals.
- **AJAX Actions**: Fast, inline approval/rejection without page reloads.
- **Flexible Settings**:
    - Set global Commission Rates (Percentage or Flat amount).
    - Configure Cookie Duration (how long the lead stays attributed).
    - Customize Link Parameters (change `ref` to `aff`, etc.).

## 📦 Installation

1. Upload the `woo-affiliate-by-j` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **Important**: Go to **Settings > Permalinks** and click **Save Changes** once to ensure the new Dashboard URL works correctly.
4. Configure your rates in **WooCommerce > Affiliate Settings**.

## 🛠 Technical Details

- **Database**: Creates two custom tables (`wp_wabj_clicks` and `wp_wabj_referrals`) to maintain performance without bloating `wp_options` or `wp_postmeta`.
- **HPOS Compatible**: Fully supports WooCommerce High-Performance Order Storage.
- **Security**: Nonce verification, capability checks, and input sanitization implemented on every hook.

## 🎨 Professional Design
The plugin features a high-end UI with:
- **Responsive Layout**: Works perfectly on mobile, tablet, and desktop.
- **Modern Aesthetics**: Interactive SVG icons, CSS variables for easy branding, and subtle micro-animations.

---
*Created for a3cricket.eu*
