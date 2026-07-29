<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'woo_bulk_qr_codes', 9999 );

/**
 * Register Bulk QR Codes submenu.
 */
function woo_bulk_qr_codes() {
	add_submenu_page(
		'wooqr',
		'Bulk QR Codes',
		'Bulk QR Codes',
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a WooCommerce capability.
		'manage_woocommerce',
		'woo_bulk_qr_codes',
		'woo_bulk_qr_codes_callback'
	);
}

/**
 * Bulk QR Codes admin page markup.
 */
function woo_bulk_qr_codes_callback() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a WooCommerce capability.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	?>
	<div class="wrap wooqr-admin-wrap">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Woo QR Code Bulk Generator', 'woocommerce-qrc' ); ?></h1>
		<header class="wooqr-topbar">
			<div class="wooqr-topbar__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="24" height="24" focusable="false">
					<path fill="currentColor" d="M2 2h7v7H2V2zm1.5 1.5v4h4v-4h-4zM11 2h7v7h-7V2zm1.5 1.5v4h4v-4h-4zM2 11h7v7H2v-7zm1.5 1.5v4h4v-4h-4zM11 11h2v2h-2v-2zm3 0h4v1.5h-2.5V14H17v1.5h-1.5V17H14v-2.5h-1.5V13H14v-2zm0 4.5H14V17h-1.5v-1.5zM16.5 14H18v3h-2.5v-1.5H17V14h-.5z"/>
				</svg>
			</div>
			<div class="wooqr-topbar__text">
				<p class="wooqr-topbar__eyebrow"><?php esc_html_e( 'WooCommerce QR', 'woocommerce-qrc' ); ?></p>
				<p class="wooqr-topbar__title"><?php esc_html_e( 'Bulk Generator', 'woocommerce-qrc' ); ?></p>
			</div>
			<p class="wooqr-topbar__aside"><?php esc_html_e( 'Generate and print QR codes for products in bulk.', 'woocommerce-qrc' ); ?></p>
		</header>
		<div id="wooqr-api">
			<div id="wpr-action-bar">
				<div id="wooqr_loader"><?php esc_html_e( 'Fetching Products...', 'woocommerce-qrc' ); ?></div>
				<div class="wooqr-bulk-generate"></div>
				<div class="wooqr-search-wrap">
					<input type="text" onkeyup="search_wooqr_list()" name="find-wooqr-pro" id="search-wooqr-pro" placeholder="<?php esc_attr_e( 'Search Product..', 'woocommerce-qrc' ); ?>">
				</div>
			</div>
			<div class="product-grid-container" id="wooqr_pro_grid">
				<div id="wooqr-status"></div>
				<ul id="wooqr-data"></ul>
			</div>
		</div>
	</div>
	<?php
}
