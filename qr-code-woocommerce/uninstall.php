<?php
/**
 * Uninstall QR Code WooCommerce.
 *
 * @package WooCommerceQrCodes
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wooqr_option_name' );
