<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core QR codes class (shortcode, REST field, frontend assets).
 */
class WCQRCodes {

	public $admin;
	public $frontend;

	public function __construct() {
		add_action( 'init', array( $this, 'bootstrap_woocommerce_qr_codes' ) );
		add_shortcode( 'wooqr', array( $this, 'wooqr' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script' ), 10 );
		add_action( 'rest_api_init', array( $this, 'wooqr_add_restapi' ) );
	}

	public function wooqr_add_restapi() {
		register_rest_field(
			'product',
			'wooqr_code',
			array(
				'get_callback'    => array( $this, 'get_wooqr_code' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * @param array           $object     Product data.
	 * @param string          $field_name Field name.
	 * @param WP_REST_Request $request    Request.
	 * @return mixed
	 */
	public function get_wooqr_code( $object, $field_name, $request ) {
		return get_post_meta( $object['id'], '_product_qr_code', true );
	}

	public function enqueue_frontend_script() {
		global $WooCommerceQrCodes;

		$options       = function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options() : array();
		$wooqr_options = array(
			'qr_options' => $options,
		);

		$font_url = function_exists( 'wcqrc_google_fonts_url' ) ? wcqrc_google_fonts_url( isset( $options['fontname'] ) ? $options['fontname'] : '' ) : '';
		if ( $font_url ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External Google Fonts URL; null avoids bogus query args.
			wp_enqueue_style( 'wcqrc-googleFonts', $font_url, array(), null );
		}

		wp_enqueue_style( 'wcqrc-product', $WooCommerceQrCodes->plugin_url . 'assets/css/wooqr-code.css', array(), $WooCommerceQrCodes->version );
		wp_enqueue_script( 'qrcode-qrcode', $WooCommerceQrCodes->plugin_url . 'assets/common/js/kjua.js', array( 'jquery' ), $WooCommerceQrCodes->version, true );
		wp_enqueue_style( 'qrcode-style', $WooCommerceQrCodes->plugin_url . 'assets/admin/css/style.css', array(), $WooCommerceQrCodes->version );
		wp_enqueue_script( 'qrcode-createqr', $WooCommerceQrCodes->plugin_url . 'assets/common/js/createqr.js', array( 'jquery', 'qrcode-qrcode' ), $WooCommerceQrCodes->version, true );
		wp_localize_script( 'qrcode-createqr', 'wooqr_options', $wooqr_options );
	}

	public function bootstrap_woocommerce_qr_codes() {
		if ( is_admin() ) {
			require_once 'class-woo-qr-codes-admin.php';
			$this->admin = new WCQRCodesAdmin();
		}
	}

	/**
	 * Shortcode renderer.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function wooqr( $atts ) {
		global $post;

		$default_id = '';
		if ( function_exists( 'is_product' ) && is_product() && $post ) {
			$default_id = (string) $post->ID;
		}

		$atts = shortcode_atts(
			array(
				'id'          => $default_id,
				'title'       => '',
				'price'       => '',
				'description' => '',
				'type'        => 'product',
			),
			$atts,
			'wooqr'
		);

		$id = absint( $atts['id'] );
		if ( ! $id ) {
			return '';
		}

		$permalink    = '';
		$output_price = '';
		$output_title = '';
		$post_type    = get_post_type( $id );

		if ( 'shop_coupon' === $post_type ) {
			$permalink    = add_query_arg(
				'coupon_code',
				rawurlencode( get_the_title( $id ) ),
				function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' )
			);
			$output_price = wc_price( get_post_meta( $id, 'coupon_amount', true ) );
			$output_title = sprintf(
				/* translators: %s: coupon code */
				__( 'Coupon Code: %s', 'woocommerce-qrc' ),
				get_the_title( $id )
			);
		} elseif ( 'product_variation' === $post_type ) {
			$permalink    = get_permalink( $id );
			$output_price = wc_price( get_post_meta( $id, '_price', true ) );
			$output_title = get_the_title( $id );
		} elseif ( 'product' === $post_type ) {
			$_product = wc_get_product( $id );
			if ( ! $_product ) {
				return '';
			}
			$permalink    = get_permalink( $id );
			$output_price = $_product->get_price_html();
			$output_title = get_the_title( $id );
		} else {
			$_product = wc_get_product( $id );
			if ( ! $_product ) {
				return '';
			}
			$permalink    = get_permalink( $id );
			$output_title = get_the_title( $id );
			$output_price = $_product->get_price_html();
		}

		if ( ! $permalink ) {
			return '';
		}

		$output  = '';
		$output .= apply_filters( 'before_wooqrc_box', '', $id );
		$output .= '<div class="wooqr_code">';
		$output .= apply_filters( 'before_wooqrc_content', '', $id );
		$output .= '<div id="product_qrcode_' . esc_attr( (string) $id ) . '" class="product_qrcode" data-wooqr-text="' . esc_url( $permalink ) . '" data-wooqr-id="' . esc_attr( (string) $id ) . '"></div>';
		$output .= '<div class="wooqr_product_details">';

		if ( '1' === (string) $atts['title'] ) {
			$output .= '<h3 class="wooqr_product_title">';
			$output .= esc_html( wp_strip_all_tags( $output_title ) );
			$output .= '</h3>';
		}

		if ( '1' === (string) $atts['price'] && 'shop_coupon' !== $post_type ) {
			$output .= '<span class="wooqr_product_price">';
			$output .= wp_kses_post( $output_price );
			$output .= '</span>';
		}

		if ( '1' === (string) $atts['description'] && 'shop_coupon' === $post_type ) {
			$output .= '<span class="wooqr_product_description">';
			$output .= esc_html( get_the_excerpt( $id ) );
			$output .= '</span>';
		}

		$output .= '</div>';
		$output .= apply_filters( 'after_wooqrc_content', '', $id );
		$output .= '</div>';
		$output .= apply_filters( 'after_wooqrc_box', '', $id );

		return $output;
	}
}
