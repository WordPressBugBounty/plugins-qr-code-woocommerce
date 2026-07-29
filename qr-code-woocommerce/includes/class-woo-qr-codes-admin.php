<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class WCQRCodesAdmin {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ), 30 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'qr_vproduct_metabox_callback' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ), 10 );
	}

	/**
	 * Add QR Code Metabox in product page
	 */
	public function add_product_meta_box() {
		global $WooCommerceQrCodes;
		$post_types = array( 'product', 'shop_coupon' );
		add_meta_box(
			'qrcode_product_metabox',
			__( 'QR Code', 'woocommerce-qrc' ),
			array( $this, 'qrcode_content_meta_box' ),
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Product / coupon metabox content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function qrcode_content_meta_box( $post ) {
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;
		if ( ! $post_id ) {
			return;
		}

		if ( get_post_type( $post_id ) === 'shop_coupon' ) {
			$permalink = add_query_arg(
				'coupon_code',
				rawurlencode( get_the_title( $post_id ) ),
				wc_get_cart_url()
			);
		} else {
			$permalink = get_permalink( $post_id );
		}

		$this->generateqr_common( $permalink, $post_id );
	}

	/**
	 * Variable product QR metabox.
	 *
	 * @param int     $loop           Loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post.
	 */
	public function qr_vproduct_metabox_callback( $loop, $variation_data, $variation ) {
		$permalink = get_permalink( $variation->ID );
		$this->generateqr_common( $permalink, $variation->ID );
	}

	/**
	 * Shared QR markup for admin screens.
	 *
	 * @param string $permalink Target URL.
	 * @param int    $id        Product/coupon/variation ID.
	 */
	public function generateqr_common( $permalink, $id ) {
		$id        = absint( $id );
		$permalink = esc_url( $permalink );
		$shortcode = sprintf( '[wooqr id="%d" title="1" price="1"]', $id );

		echo '<div class="product_qrcode_meta">';
		echo '<div class="product_qrcode_content" id="output_' . esc_attr( (string) $id ) . '">';
		echo '<div id="product_qrcode_' . esc_attr( (string) $id ) . '" class="product_qrcode" data-wooqr-text="' . esc_url( $permalink ) . '" data-wooqr-id="' . esc_attr( (string) $id ) . '"></div>';
		echo '<div class="wooqr_actions"><div data-product_id="' . esc_attr( (string) $id ) . '" class="button-primary print-qr dashicons-before dashicons-print">' . esc_html__( 'Print', 'woocommerce-qrc' ) . '</div></div>';
		echo '<div class="wooqr-shortcode"><input type="text" value="' . esc_attr( $shortcode ) . '" id="qrshortcode_' . esc_attr( (string) $id ) . '" readonly><span class="copyshortcode" data-id="' . esc_attr( (string) $id ) . '">' . esc_html__( 'copy shortcode', 'woocommerce-qrc' ) . '</span></div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_script() {
		global $WooCommerceQrCodes;

		$options       = function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options() : array();
		$wooqr_options = array(
			'qr_options' => $options,
		);
		$screen        = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( 'toplevel_page_wooqr' === $screen->id ) {
			wp_enqueue_media();
		}

		$qr_screens = array( 'product', 'shop_coupon', 'woo-qr_page_woo_bulk_qr_codes', 'toplevel_page_wooqr' );
		if ( in_array( $screen->id, $qr_screens, true ) ) {
			wp_enqueue_style( 'wcqrc-product', $WooCommerceQrCodes->plugin_url . 'assets/admin/css/wooqr-product.css', array(), $WooCommerceQrCodes->version );
			wp_enqueue_script( 'wcqrc-product', $WooCommerceQrCodes->plugin_url . 'assets/admin/js/wooqr-product.js', array( 'jquery' ), $WooCommerceQrCodes->version, true );
			wp_enqueue_script( 'agaf-product', $WooCommerceQrCodes->plugin_url . 'assets/admin/js/jspdf.js', array( 'jquery' ), $WooCommerceQrCodes->version, true );
			wp_enqueue_script( 'qrcode-qrcode', $WooCommerceQrCodes->plugin_url . 'assets/common/js/kjua.js', array( 'jquery' ), $WooCommerceQrCodes->version, true );

			$font_url = function_exists( 'wcqrc_google_fonts_url' ) ? wcqrc_google_fonts_url( isset( $options['fontname'] ) ? $options['fontname'] : '' ) : '';
			if ( $font_url ) {
				// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External Google Fonts URL; null avoids bogus query args.
				wp_enqueue_style( 'wcqrc-googleFonts', $font_url, array(), null );
			}

			// Legacy print stylesheet — keep on product/coupon screens only (Verdana body rules break Design UI).
			if ( in_array( $screen->id, array( 'product', 'shop_coupon' ), true ) ) {
				wp_enqueue_style( 'qrcode-style', $WooCommerceQrCodes->plugin_url . 'assets/admin/css/style.css', array(), $WooCommerceQrCodes->version );
			}
		}

		if ( in_array( $screen->id, array( 'product', 'shop_coupon' ), true ) ) {
			wp_enqueue_script( 'qrcode-createqr', $WooCommerceQrCodes->plugin_url . 'assets/common/js/createqr.js', array( 'jquery', 'qrcode-qrcode' ), $WooCommerceQrCodes->version, true );
			wp_localize_script( 'qrcode-createqr', 'wooqr_options', $wooqr_options );
		}

		if ( 'woo-qr_page_woo_bulk_qr_codes' === $screen->id ) {
			wp_enqueue_script( 'wooqr-bulk', $WooCommerceQrCodes->plugin_url . 'assets/admin/js/wooqr-bulk.js', array( 'jquery', 'qrcode-qrcode' ), $WooCommerceQrCodes->version, true );
			$rest_data = array(
				'wp_rest_url'  => esc_url_raw( get_rest_url() ),
				'wp_rest'      => wp_create_nonce( 'wp_rest' ),
				'wooqr_folder' => esc_url_raw( WCQRC_QR_IMAGE_URL ),
				'wooqr_plugin' => esc_url_raw( $WooCommerceQrCodes->plugin_url ),
				'woo_currency' => html_entity_decode( get_woocommerce_currency_symbol() ),
				'qr_options'   => $options,
				'admin_url'    => esc_url_raw( admin_url( 'post.php' ) ),
			);
			wp_localize_script( 'wooqr-bulk', 'wooqr', $rest_data );
		}
	}
}
