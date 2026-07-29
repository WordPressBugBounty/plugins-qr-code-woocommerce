<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default QR design options.
 *
 * @return array
 */
function wcqrc_default_options() {
	return array(
		'render'     => 'image',
		'size'       => '700',
		'crisp'      => 'true',
		'fill'       => '#333333',
		'back'       => '#ffffff',
		'minVersion' => '1',
		'ecLevel'    => 'H',
		'quiet'      => '1',
		'rounded'    => '100',
		'mode'       => 'plain',
		'mSize'      => '20',
		'mPosX'      => '50',
		'mPosY'      => '50',
		'label'      => 'QR Code',
		'fontname'   => 'Lato',
		'fontcolor'  => '#ff9818',
		'image'      => '',
	);
}

/**
 * Curated Google Font families (no remote API / no API keys).
 *
 * @return array<string, string>
 */
function wcqrc_get_font_families() {
	$fonts = array(
		'Lato',
		'Open Sans',
		'Roboto',
		'Montserrat',
		'Oswald',
		'Raleway',
		'Poppins',
		'Ubuntu',
		'Merriweather',
		'Playfair Display',
		'Source Sans Pro',
		'Nunito',
		'PT Sans',
		'Noto Sans',
		'Rubik',
		'Work Sans',
		'Fira Sans',
		'Quicksand',
		'Comfortaa',
		'Ubuntu Mono',
	);

	$families = array();
	foreach ( $fonts as $font ) {
		$families[ $font ] = $font;
	}

	/**
	 * Filter the curated font family list used for QR label design.
	 *
	 * @param array<string, string> $families Font families keyed by family name.
	 */
	return apply_filters( 'wcqrc_font_families', $families );
}

/**
 * Get merged plugin options with defaults.
 *
 * @return array
 */
function wcqrc_get_options() {
	$stored = get_option( 'wooqr_option_name', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_merge( wcqrc_default_options(), $stored );
}

/**
 * Sanitize a hex color; fall back to default when invalid.
 *
 * @param string $color   Raw color.
 * @param string $default Fallback hex color.
 * @return string
 */
function wcqrc_sanitize_hex_color( $color, $default = '#333333' ) {
	$color = sanitize_text_field( $color );
	if ( function_exists( 'sanitize_hex_color' ) ) {
		$sanitized = sanitize_hex_color( $color );
		if ( $sanitized ) {
			return $sanitized;
		}
	}

	if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color ) ) {
		return $color;
	}

	return $default;
}

/**
 * Build a safe Google Fonts stylesheet URL for a family name.
 *
 * @param string $family Font family.
 * @return string
 */
function wcqrc_google_fonts_url( $family ) {
	$families = wcqrc_get_font_families();
	if ( empty( $family ) || '0' === $family || ! isset( $families[ $family ] ) ) {
		return '';
	}

	$query = rawurlencode( $family );
	return 'https://fonts.googleapis.com/css?family=' . $query;
}
