<?php
/**
 * Plugin Name:       QR Code Woocommerce
 * Plugin URI:        https://wooqr.com/
 * Description:       Generate and print QR codes for WooCommerce products and coupons.
 * Author:            Gangesh Matta
 * Author URI:        https://profiles.wordpress.org/gangesh/
 * Version:           2.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       woocommerce-qrc
 * Domain Path:       /languages/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WooCommerceQrCodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare WooCommerce feature compatibility early.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/**
 * Activation: seed default design options.
 */
function wcqrc_activate() {
	if ( ! function_exists( 'wcqrc_default_options' ) ) {
		require_once __DIR__ . '/includes/helpers.php';
	}
	if ( false === get_option( 'wooqr_option_name', false ) ) {
		add_option( 'wooqr_option_name', wcqrc_default_options() );
	}
}
register_activation_hook( __FILE__, 'wcqrc_activate' );

if ( ! class_exists( 'WooCommerceQrCodes' ) ) :

	/**
	 * Main plugin bootstrap.
	 */
	final class WooCommerceQrCodes {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '2.1.2';

		/**
		 * Singleton instance.
		 *
		 * @var WooCommerceQrCodes|null
		 */
		protected static $_instance = null;

		/**
		 * Core QR codes handler.
		 *
		 * @var WCQRCodes|null
		 */
		public $WCQRCodes = null;

		/**
		 * Reserved for legacy QR library.
		 *
		 * @var object|null
		 */
		public $QRcode = null;

		/**
		 * Text domain.
		 *
		 * @var string
		 */
		public $text_domain = 'woocommerce-qrc';

		/**
		 * Plugin URL with trailing slash.
		 *
		 * @var string
		 */
		public $plugin_url = '';

		/**
		 * @return string
		 */
		public static function qr_item_param() {
			return 'is-from-qr';
		}

		/**
		 * @return WooCommerceQrCodes
		 */
		public static function instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_vars();
			$this->text_domain = WCQRC_TEXT_DOMAIN;
			$this->plugin_url  = trailingslashit( plugins_url( '', __FILE__ ) );
			$this->includes();
			$this->init();
		}

		/**
		 * Define plugin constants.
		 */
		private function define_vars() {
			$upload_dir = wp_upload_dir();
			$this->define( 'WCQRC_PLUGIN_FILE', __FILE__ );
			$this->define( 'WCQRC_TEXT_DOMAIN', 'woocommerce-qrc' );
			$this->define( 'WCQRC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'WCQRC_VERSION', $this->version );
			$this->define( 'WCQRC_QR_IMAGE_DIR', $upload_dir['basedir'] . '/wcqrc-images/' );
			$this->define( 'WCQRC_QR_IMAGE_URL', $upload_dir['baseurl'] . '/wcqrc-images/' );
		}

		/**
		 * @param string      $name  Constant name.
		 * @param string|bool $value Value.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Load includes.
		 */
		public function includes() {
			include_once __DIR__ . '/includes/helpers.php';
			include_once __DIR__ . '/includes/class-woo-qr-codes.php';
			include_once __DIR__ . '/includes/class-woo-coupon-public-url.php';
			include_once __DIR__ . '/includes/class-woo-bulk-qr-codes.php';
			include_once __DIR__ . '/includes/class-woo-admin-panel.php';
		}

		/**
		 * Boot runtime objects.
		 */
		private function init() {
			$this->load_text_domain();
			$this->WCQRCodes = new WCQRCodes();
		}

		/**
		 * Load translations (WordPress.org language packs + bundled /languages).
		 */
		public function load_text_domain() {
			load_plugin_textdomain(
				'woocommerce-qrc',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);
		}
	}

endif;

if ( ! class_exists( 'WooCommerceQrCodesDependencies' ) ) :

	/**
	 * Dependency checks.
	 */
	final class WooCommerceQrCodesDependencies {

		/**
		 * Active plugins list.
		 *
		 * @var array|null
		 */
		private static $active_plugins = null;

		/**
		 * Load active plugin lists.
		 */
		public static function init() {
			self::$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				self::$active_plugins = array_merge( self::$active_plugins, (array) get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}

		/**
		 * @return bool
		 */
		public static function is_woocommerce_active() {
			if ( null === self::$active_plugins ) {
				self::init();
			}
			return in_array( 'woocommerce/woocommerce.php', self::$active_plugins, true )
				|| array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );
		}

		/**
		 * Admin notice when WooCommerce is missing.
		 */
		public static function woocommerce_not_install_notice() {
			echo '<div class="error"><p>';
			echo wp_kses(
				sprintf(
					/* translators: %s: WooCommerce plugin URL */
					__( 'WooCommerce QR Codes requires <a href="%s">WooCommerce</a> to be active!', 'woocommerce-qrc' ),
					esc_url( 'https://wordpress.org/plugins/woocommerce/' )
				),
				array(
					'a' => array(
						'href' => true,
					),
				)
			);
			echo '</p></div>';
		}
	}

endif;

/**
 * @return WooCommerceQrCodes
 */
function WCQRC() {
	return WooCommerceQrCodes::instance();
}

if ( ! WooCommerceQrCodesDependencies::is_woocommerce_active() ) {
	add_action( 'admin_notices', array( 'WooCommerceQrCodesDependencies', 'woocommerce_not_install_notice' ) );
} else {
	$GLOBALS['WooCommerceQrCodes'] = WCQRC();
}
