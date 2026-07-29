=== QR Code Woocommerce ===
Contributors: gangesh
Tags: qr-code, qrcode, woocommerce, barcode, print
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate and print QR codes for WooCommerce simple/variable products and coupons.

== Description ==

<a href="https://wooqr.com">Demo</a>

Simple yet powerful plugin that helps WooCommerce shops reach customers via mobile devices. Generate printable QR codes for simple and variable products, plus coupon QR codes. PDF print support helps with offline promotions, physical stores, and marketing banners.

Useful for:

1. Easy access over mobile devices
2. Physical store owners
3. Affiliates
4. Marketing on multiple domains
5. Offline promotions

Shortcode usage:

For product:
`[wooqr id="product_id" title="1" price="1"]`

For coupon:
`[wooqr id="coupon_id" title="1" description="1"]`

* `product_id` / `coupon_id` — post ID
* `title` — show title when set to `1`
* `price` — show product price when set to `1`
* `description` — show coupon description when set to `1`

From v1.0, `id` is optional on single product pages.

Features:

1. Generate QR codes for simple products
2. Generate QR codes for variable products
3. Download product PDF
4. Coupon QR / coupon PDF
5. Bulk QR generator
6. Design options (color, label, image overlay)

== Screenshots ==

1. Simple Product
2. Variable Product
3. Coupons
4. Shortcode Output
5. Bulk QR Code
6. Design QR code

== Installation ==

1. Upload the plugin to the `wp-content/plugins` directory
2. Activate the plugin through the Plugins screen in WordPress
3. Ensure WooCommerce is installed and active
4. Open **Woo QR** in wp-admin to design codes and use Bulk Generator

== Changelog ==

= 2.1.2 =
* Design: light accent admin top bar; framed live preview; Size/POS field placement
* Fix: variation PDF print no longer includes HTML from attribute markup
* Hardening: replace inline genqrcode script tags with data-wooqr attributes
* Tooling: PHPCS WordPress ruleset (composer) + smoke/SVN release docs

= 2.1.1 =
* Update bundled kjua QR library from 0.9.0 to 0.10.0

= 2.1.0 =
* Security: remove hardcoded Google Fonts API key; use curated local font list
* Security: escape shortcode/admin output; harden bulk QR admin JS
* Security: sanitize and validate coupon URL handling
* Security: tighten settings sanitization allowlists
* Compatibility: WordPress 7.0 tested header, Requires PHP 7.4
* Compatibility: declare WooCommerce HPOS and Cart/Checkout Blocks support
* Compatibility: PHP 8.2-safe Settings API registration and option access
* Add activation defaults and uninstall cleanup for design options
* Unify text domain to `woocommerce-qrc`

= 2.0.5 =
* Feature: Added Google font integration for QR code label design

= 2.0.4 =
* Bug fix: Added default value for QR design

= 2.0.3 =
* Feature: QR design section added
* Modify: Add separate menu option for QR design

= 2.0.2 =
* Fix: Added fix for Variable price range display

= 2.0.1 =
* Fix: Added check for ID in shortcode parameter

= 2.0 =
* Complete overhaul of code. Using WooCommerce REST API and JS based QR code library

= 1.1 =
* Separated Bulk QR code file for better management
* Updated variation query for Bulk QR code generation
* Fixed product_type WooCommerce notice

= 1.0 =
* Shortcode can work without product ID if used on Single Product page

= 0.9 =
* Generate Bulk QR code from single screen

= 0.8 =
* Added fix to wrap long title text below QR code

= 0.7 =
* Added fix to add Variable product attribute with product title
* Minor UI fixes

= 0.6 =
* Minor UI fixes

= 0.5 =
* Product title added on QR Code on print option
* QR image size increased for Print option
* Minor UI fixes

= 0.4 =
* Shortcode fix for Gutenberg editor
* Added frontend style for shortcode

= 0.3 =
* Banner updated

= 0.2 =
* Readme Update with User case and Shortcode instructions

= 0.1 =
* Initial Release

== Upgrade Notice ==

= 2.1.2 =
Admin Design UI polish, variation print fix, and safer QR script loading. Recommended update.

= 2.1.1 =
Updates the bundled kjua QR library to 0.10.0.

= 2.1.0 =
Security and compatibility release for WordPress 7.0 / PHP 7.4–8.x. Recommended update for all sites.
