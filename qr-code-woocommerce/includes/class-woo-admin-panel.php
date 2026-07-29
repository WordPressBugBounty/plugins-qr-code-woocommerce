<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooQR {

	private $wooqr_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wooqr_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'wooqr_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ), 10 );
	}


	public function enqueue_admin_script() {
		global $WooCommerceQrCodes;
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_wooqr' !== $screen->id ) {
			return;
		}

		$options = function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options() : array();
		wp_enqueue_style( 'wcqrc-admin-panel-style', $WooCommerceQrCodes->plugin_url . 'assets/admin/css/wcqrc-admin-panel.css', array(), $WooCommerceQrCodes->version );
		wp_enqueue_script( 'wcqrc-kjua-js', $WooCommerceQrCodes->plugin_url . 'assets/common/js/kjua.js', array( 'jquery' ), $WooCommerceQrCodes->version, true );
		wp_enqueue_script( 'wcqrc-kjua-scripts', $WooCommerceQrCodes->plugin_url . 'assets/admin/js/kjua-scripts.js', array( 'jquery', 'wcqrc-kjua-js' ), $WooCommerceQrCodes->version, true );

		$font_url = function_exists( 'wcqrc_google_fonts_url' ) ? wcqrc_google_fonts_url( isset( $options['fontname'] ) ? $options['fontname'] : '' ) : '';
		if ( $font_url ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External Google Fonts URL; null avoids bogus query args.
			wp_enqueue_style( 'wcqrc-googleFonts', $font_url, array(), null );
		}
	}



	public function wooqr_add_plugin_page() {

		add_menu_page(
			'Woo QR',
			'Woo QR',
			'manage_options',
			'wooqr',
			array( $this, 'wooqr_create_admin_page' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M2 2h7v7H2V2zm1.5 1.5v4h4v-4h-4zM11 2h7v7h-7V2zm1.5 1.5v4h4v-4h-4zM2 11h7v7H2v-7zm1.5 1.5v4h4v-4h-4zM11 11h2v2h-2v-2zm3 0h4v1.5h-2.5V14H17v1.5h-1.5V17H14v-2.5h-1.5V13H14v-2zm0 4.5H14V17h-1.5v-1.5zM16.5 14H18v3h-2.5v-1.5H17V14h-.5z"/></svg>' ),
			56
		);
		add_submenu_page( 'wooqr', 'Design', 'Design', 'manage_options', 'wooqr', array( $this, 'wooqr_create_admin_page' ) );
	}

	public function wooqr_create_admin_page() {
		$this->wooqr_options = function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options() : get_option( 'wooqr_option_name', array() );
		?>

		<div class="wrap wooqr-admin-wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Woo QR Code Design', 'woocommerce-qrc' ); ?></h1>
			<?php settings_errors(); ?>
			<header class="wooqr-topbar">
				<div class="wooqr-topbar__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="24" height="24" focusable="false">
						<path fill="currentColor" d="M2 2h7v7H2V2zm1.5 1.5v4h4v-4h-4zM11 2h7v7h-7V2zm1.5 1.5v4h4v-4h-4zM2 11h7v7H2v-7zm1.5 1.5v4h4v-4h-4zM11 11h2v2h-2v-2zm3 0h4v1.5h-2.5V14H17v1.5h-1.5V17H14v-2.5h-1.5V13H14v-2zm0 4.5H14V17h-1.5v-1.5zM16.5 14H18v3h-2.5v-1.5H17V14h-.5z"/>
					</svg>
				</div>
				<div class="wooqr-topbar__text">
					<p class="wooqr-topbar__eyebrow"><?php esc_html_e( 'WooCommerce QR', 'woocommerce-qrc' ); ?></p>
					<p class="wooqr-topbar__title"><?php esc_html_e( 'Design', 'woocommerce-qrc' ); ?></p>
				</div>
				<p class="wooqr-topbar__aside"><?php esc_html_e( 'Live preview updates as you change fill, corners, and label.', 'woocommerce-qrc' ); ?></p>
			</header>

			<form method="post" action="options.php">
				<div class="wooqr-setting-wrapper">
					<?php
					settings_fields( 'wooqr_option_group' );
					?>
					<div class="left-panel">
						<input type="hidden" name="wooqr_option_name[render]" value="image" id="render">
						<input type="hidden" name="wooqr_option_name[size]" value="700" id="size">
						<input type="hidden" name="wooqr_option_name[text]" value="Woo QR" id="text">
						<?php

						do_settings_sections( 'wooqr-admin' );
						submit_button();
						?>
					</div>
					<div class="wooqr-preview-wrap">
						<div id="qr-container" class="right-panel"></div>
						<p class="wooqr-live-preview-label"><?php esc_html_e( 'Live Preview', 'woocommerce-qrc' ); ?></p>
					</div>

				</div>
			</form>
		</div>
		<?php
	}

	public function wooqr_page_init() {
		register_setting(
			'wooqr_option_group', // option_group
			'wooqr_option_name', // option_name
			array( $this, 'wooqr_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'wooqr_setting_section', // id
			'', // title
			array( $this, 'wooqr_section_info' ), // callback
			'wooqr-admin' // page
		);
		add_settings_field(
			'mode', // id
			'Mode', // title
			array( $this, 'mode_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		// Hidden fields (titles required for PHP 8+ / Settings API signature).
		add_settings_field(
			'render',
			'',
			array( $this, 'render_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			array( 'class' => 'hidden' )
		);

		add_settings_field(
			'size',
			'',
			array( $this, 'size_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			array( 'class' => 'hidden' )
		);

		add_settings_field(
			'crisp', // id
			'Crisp', // title
			array( $this, 'crisp_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'fill', // id
			'Fill', // title
			array( $this, 'fill_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'back', // id
			'Background', // title
			array( $this, 'back_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'minVersion', // id
			'Min Version', // title
			array( $this, 'minVersion_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'ecLevel', // id
			'Error Correction Level', // title
			array( $this, 'ecLevel_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'quiet', // id
			'Quite Zone', // title
			array( $this, 'quiet_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		add_settings_field(
			'rounded', // id
			'Rounded Corners', // title
			array( $this, 'rounded_callback' ), // callback
			'wooqr-admin', // page
			'wooqr_setting_section' // section
		);

		$mode                = function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options()['mode'] : 'plain';
		$hide_label_fields   = in_array( $mode, array( 'plain', 'image' ), true ) ? array( 'class' => 'hidden' ) : array();
		$hide_overlay_fields = ( 'plain' === $mode ) ? array( 'class' => 'hidden' ) : array();
		$hide_image_field    = in_array( $mode, array( 'plain', 'label' ), true ) ? array( 'class' => 'hidden' ) : array();

		add_settings_field(
			'label',
			'Label',
			array( $this, 'label_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_label_fields
		);

		add_settings_field(
			'fontname',
			'Font Name',
			array( $this, 'fontname_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_label_fields
		);

		add_settings_field(
			'fontcolor',
			'Font Color',
			array( $this, 'fontcolor_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_label_fields
		);

		add_settings_field(
			'image',
			'Image',
			array( $this, 'wooqr_upload_image_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_image_field
		);

		add_settings_field(
			'mSize',
			'Size',
			array( $this, 'mSize_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_overlay_fields
		);

		add_settings_field(
			'mPosX',
			'POS X',
			array( $this, 'mPosX_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_overlay_fields
		);

		add_settings_field(
			'mPosY',
			'POS Y',
			array( $this, 'mPosY_callback' ),
			'wooqr-admin',
			'wooqr_setting_section',
			$hide_overlay_fields
		);
	}

	public function wooqr_sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return function_exists( 'wcqrc_default_options' ) ? wcqrc_default_options() : array();
		}

		$defaults        = function_exists( 'wcqrc_default_options' ) ? wcqrc_default_options() : array();
		$sanitary_values = $defaults;

		$sanitary_values['render'] = ( isset( $input['render'] ) && 'image' === $input['render'] ) ? 'image' : 'image';
		$sanitary_values['size']   = isset( $input['size'] ) ? (string) absint( $input['size'] ) : $defaults['size'];

		if ( isset( $input['crisp'] ) && in_array( $input['crisp'], array( 'true', 'false' ), true ) ) {
			$sanitary_values['crisp'] = $input['crisp'];
		}

		$sanitary_values['fill'] = function_exists( 'wcqrc_sanitize_hex_color' )
			? wcqrc_sanitize_hex_color( isset( $input['fill'] ) ? $input['fill'] : $defaults['fill'], $defaults['fill'] )
			: $defaults['fill'];
		$sanitary_values['back'] = function_exists( 'wcqrc_sanitize_hex_color' )
			? wcqrc_sanitize_hex_color( isset( $input['back'] ) ? $input['back'] : $defaults['back'], $defaults['back'] )
			: $defaults['back'];

		if ( isset( $input['minVersion'] ) ) {
			$sanitary_values['minVersion'] = (string) max( 1, min( 10, absint( $input['minVersion'] ) ) );
		}

		if ( isset( $input['ecLevel'] ) && in_array( $input['ecLevel'], array( 'H', 'Q', 'M', 'L' ), true ) ) {
			$sanitary_values['ecLevel'] = $input['ecLevel'];
		}

		if ( isset( $input['quiet'] ) ) {
			$sanitary_values['quiet'] = (string) max( 0, min( 4, absint( $input['quiet'] ) ) );
		}
		if ( isset( $input['rounded'] ) ) {
			$sanitary_values['rounded'] = (string) max( 0, min( 100, absint( $input['rounded'] ) ) );
		}

		if ( isset( $input['mode'] ) && in_array( $input['mode'], array( 'plain', 'label', 'image' ), true ) ) {
			$sanitary_values['mode'] = $input['mode'];
		}

		if ( isset( $input['mSize'] ) ) {
			$sanitary_values['mSize'] = (string) max( 0, min( 40, absint( $input['mSize'] ) ) );
		}
		if ( isset( $input['mPosX'] ) ) {
			$sanitary_values['mPosX'] = (string) max( 0, min( 100, absint( $input['mPosX'] ) ) );
		}
		if ( isset( $input['mPosY'] ) ) {
			$sanitary_values['mPosY'] = (string) max( 0, min( 100, absint( $input['mPosY'] ) ) );
		}

		if ( isset( $input['label'] ) ) {
			$sanitary_values['label'] = sanitize_text_field( $input['label'] );
		}

		$families = function_exists( 'wcqrc_get_font_families' ) ? wcqrc_get_font_families() : array();
		if ( isset( $input['fontname'] ) ) {
			$font                        = sanitize_text_field( $input['fontname'] );
			$sanitary_values['fontname'] = isset( $families[ $font ] ) ? $font : $defaults['fontname'];
		}

		$sanitary_values['fontcolor'] = function_exists( 'wcqrc_sanitize_hex_color' )
			? wcqrc_sanitize_hex_color( isset( $input['fontcolor'] ) ? $input['fontcolor'] : $defaults['fontcolor'], $defaults['fontcolor'] )
			: $defaults['fontcolor'];

		if ( isset( $input['image'] ) ) {
			$sanitary_values['image'] = esc_url_raw( $input['image'] );
		}

		return $sanitary_values;
	}

	public function wooqr_section_info() {
	}

	public function render_callback() {
		printf(
			'<input class="regular-text" type="hidden" name="wooqr_option_name[render]" id="render" value="%s">',
			isset( $this->wooqr_options['render'] ) ? esc_attr( $this->wooqr_options['render'] ) : 'image'
		);
	}

	public function size_callback() {
		printf(
			'<input class="regular-text" type="hidden" name="wooqr_option_name[size]" id="size" value="%s">',
			isset( $this->wooqr_options['size'] ) ? esc_attr( $this->wooqr_options['size'] ) : '700'
		);
	}

	public function crisp_callback() {
		$current = isset( $this->wooqr_options['crisp'] ) ? (string) $this->wooqr_options['crisp'] : 'true';
		?>
		<select name="wooqr_option_name[crisp]" id="crisp">
			<option value="true" <?php selected( $current, 'true' ); ?>><?php esc_html_e( 'True', 'woocommerce-qrc' ); ?></option>
			<option value="false" <?php selected( $current, 'false' ); ?>><?php esc_html_e( 'False', 'woocommerce-qrc' ); ?></option>
		</select>
		<?php
	}
	public function fill_callback() {
		printf(
			'<input class="regular-text" type="color" name="wooqr_option_name[fill]" id="fill" value="%s">',
			isset( $this->wooqr_options['fill'] ) ? esc_attr( $this->wooqr_options['fill'] ) : '#333333'
		);
	}

	public function back_callback() {
		printf(
			'<input class="regular-text" type="color" name="wooqr_option_name[back]" id="back" value="%s">',
			isset( $this->wooqr_options['back'] ) ? esc_attr( $this->wooqr_options['back'] ) : '#ffffff'
		);
	}

	public function minVersion_callback( $args = array() ) {
		$minversion = isset( $this->wooqr_options['minVersion'] ) ? $this->wooqr_options['minVersion'] : '1';
		printf(
			'<input class="regular-text" type="range" min="1" max="10" step="1" name="wooqr_option_name[minVersion]" id="minVersion" value="%1$s" oninput="minversionOutput.value = minVersion.value"> <output id="minversionOutput">%1$s</output>',
			esc_attr( $minversion )
		);
	}

	public function ecLevel_callback() {
		$current = isset( $this->wooqr_options['ecLevel'] ) ? $this->wooqr_options['ecLevel'] : 'H';
		?>
		<select name="wooqr_option_name[ecLevel]" id="ecLevel">
			<option value="H" <?php selected( $current, 'H' ); ?>>H - high (30%)</option>
			<option value="Q" <?php selected( $current, 'Q' ); ?>>Q - quartile (25%)</option>
			<option value="M" <?php selected( $current, 'M' ); ?>>M - medium (15%)</option>
			<option value="L" <?php selected( $current, 'L' ); ?>>L - low (7%)</option>
		</select>
		<?php
	}

	public function quiet_callback() {
		$quitzone = isset( $this->wooqr_options['quiet'] ) ? $this->wooqr_options['quiet'] : '1';
		printf(
			'<input class="regular-text" type="range" min="0" max="4" step="1" name="wooqr_option_name[quiet]" id="quiet" value="%1$s" oninput="quitzoneOutput.value = quiet.value"> <output id="quitzoneOutput">%1$s</output>',
			esc_attr( $quitzone )
		);
	}

	public function rounded_callback() {
		$rc = isset( $this->wooqr_options['rounded'] ) ? $this->wooqr_options['rounded'] : '100';
		printf(
			'<input class="regular-text" type="range" min="0" max="100" step="10" name="wooqr_option_name[rounded]" id="rounded" value="%1$s" oninput="rcOutput.value = rounded.value"> <output id="rcOutput">%1$s</output>',
			esc_attr( $rc )
		);
	}

	public function mode_callback() {
		$current = isset( $this->wooqr_options['mode'] ) ? $this->wooqr_options['mode'] : 'plain';
		?>
		<select name="wooqr_option_name[mode]" id="mode">
			<option value="plain" <?php selected( $current, 'plain' ); ?>><?php esc_html_e( 'Plain', 'woocommerce-qrc' ); ?></option>
			<option value="label" <?php selected( $current, 'label' ); ?>><?php esc_html_e( 'Label', 'woocommerce-qrc' ); ?></option>
			<option value="image" <?php selected( $current, 'image' ); ?>><?php esc_html_e( 'Image', 'woocommerce-qrc' ); ?></option>
		</select>
		<?php
	}

	public function mSize_callback() {
		$msize = isset( $this->wooqr_options['mSize'] ) ? $this->wooqr_options['mSize'] : '20';
		printf(
			'<input class="regular-text" type="range" min="0" max="40" step="1" name="wooqr_option_name[mSize]" id="mSize" value="%1$s" oninput="mSizeOutput.value = mSize.value"> <output id="mSizeOutput">%1$s</output>',
			esc_attr( $msize )
		);
	}

	public function mPosX_callback() {
		$mposx = isset( $this->wooqr_options['mPosX'] ) ? $this->wooqr_options['mPosX'] : '50';
		printf(
			'<input class="regular-text" type="range" min="0" max="100" step="1" name="wooqr_option_name[mPosX]" id="mPosX" value="%1$s" oninput="mposxOutput.value = mPosX.value"> <output id="mposxOutput">%1$s</output>',
			esc_attr( $mposx )
		);
	}

	public function mPosY_callback() {
		$mposy = isset( $this->wooqr_options['mPosY'] ) ? $this->wooqr_options['mPosY'] : '50';
		printf(
			'<input class="regular-text" type="range" min="0" max="100" step="1" name="wooqr_option_name[mPosY]" id="mPosY" value="%1$s" oninput="mposyOutput.value = mPosY.value"> <output id="mposyOutput">%1$s</output>',
			esc_attr( $mposy )
		);
	}

	public function label_callback() {
		printf(
			'<input class="regular-text" type="text" name="wooqr_option_name[label]" id="label" value="%s">',
			isset( $this->wooqr_options['label'] ) ? esc_attr( $this->wooqr_options['label'] ) : 'QR Code'
		);
	}

	public function fontname_callback() {
		$options  = is_array( $this->wooqr_options ) ? $this->wooqr_options : ( function_exists( 'wcqrc_get_options' ) ? wcqrc_get_options() : array() );
		$font     = isset( $options['fontname'] ) ? $options['fontname'] : 'Lato';
		$families = function_exists( 'wcqrc_get_font_families' ) ? wcqrc_get_font_families() : array( 'Lato' => 'Lato' );
		?>
		<select name="wooqr_option_name[fontname]" id="fontname">
			<?php foreach ( $families as $value ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $font, $value ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="hidden" value="<?php echo esc_attr( $font ); ?>" id="wooqr-fontname"/>
		<?php
	}

	public function fontcolor_callback() {
		printf(
			'<input class="regular-text" type="color" name="wooqr_option_name[fontcolor]" id="fontcolor" value="%s">',
			isset( $this->wooqr_options['fontcolor'] ) ? esc_attr( $this->wooqr_options['fontcolor'] ) : '#ff9818'
		);
	}

	public function wooqr_upload_image_callback() {
		global $WooCommerceQrCodes;
		$default_image = $WooCommerceQrCodes->plugin_url . 'assets/admin/images/wooqr-icon.png';
		$image         = ! empty( $this->wooqr_options['image'] ) ? $this->wooqr_options['image'] : $default_image;
		printf(
			'<input id="wooqr_upload_image" type="text" size="36" name="wooqr_option_name[image]" value="%s" />',
			esc_url( $image )
		);
		printf(
			'<input id="wooqr_upload_button" class="button" type="button" value="%s" />',
			esc_attr__( 'Upload image', 'woocommerce-qrc' )
		);
		printf(
			'<img id="wooqrimg-buffer" src="%s" alt="" />',
			esc_url( $image )
		);
	}
}
if ( is_admin() ) {
	$wooqr = new WooQR();
}

/*
	* Retrieve this value with:
	* $wooqr_options = get_option( 'wooqr_option_name' ); // Array of All Options
	* $wooqr_crisp = $wooqr_options['wooqr_crisp']; // Crisp
	* $fill = $wooqr_options['fill']; // Fill
	* $back = $wooqr_options['back']; // Background
	* $minVersion = $wooqr_options['minVersion']; // Min Version
	* $ecLevel = $wooqr_options['ecLevel']; // Error Correction level
	* $quiet = $wooqr_options['quiet']; // Quite Zone
	* $rounded = $wooqr_options['rounded']; // Rounded Corners
	* $mode = $wooqr_options['mode']; // Mode
	* $label = $wooqr_options['label']; // Label
	* $mSize = $wooqr_options['mSize']; // Size
	* $mPosX = $wooqr_options['mPosX']; // POS X
	* $mPosY = $wooqr_options['mPosY']; // POS Y
	* $fontname = $wooqr_options['fontname']; // Font
	* $fontcolor = $wooqr_options['fontcolor']; // Font Color
	* $image = $wooqr_options['wooqr_upload_image']; // Image
*/