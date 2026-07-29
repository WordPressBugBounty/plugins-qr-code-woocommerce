<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_init', 'wcqrc_get_custom_coupon_code_to_session' );
/**
 * Store a coupon code from the URL into the customer session.
 */
function wcqrc_get_custom_coupon_code_to_session() {
	if ( ! isset( $_GET['coupon_code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	if ( ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}

	$existing = WC()->session->get( 'coupon_code' );
	if ( ! empty( $existing ) ) {
		return;
	}

	$coupon_code = wc_format_coupon_code( wp_unslash( $_GET['coupon_code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $coupon_code ) {
		return;
	}

	// Only accept codes that exist as WooCommerce coupons.
	if ( ! function_exists( 'wc_get_coupon_id_by_code' ) || ! wc_get_coupon_id_by_code( $coupon_code ) ) {
		return;
	}

	WC()->session->set( 'coupon_code', $coupon_code );
}

add_action( 'woocommerce_before_cart', 'wcqrc_add_discount_to_cart', 99 );
/**
 * Apply the session coupon on the cart page, then clear it from session.
 */
function wcqrc_add_discount_to_cart() {
	if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart ) {
		return;
	}

	$coupon_code = WC()->session->get( 'coupon_code' );
	if ( empty( $coupon_code ) || WC()->cart->has_discount( $coupon_code ) ) {
		return;
	}

	WC()->cart->add_discount( $coupon_code );
	WC()->session->__unset( 'coupon_code' );
}
