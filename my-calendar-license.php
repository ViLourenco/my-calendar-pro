<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'mcs_custom_settings_update', 'mcs_activate_license', 10, 2 );
function mcs_activate_license( $value, $post ) {
	// save settings
	if ( isset( $_POST['mcs_license_key'] ) ) {
		if ( $post['mcs_license_key'] != get_option( 'mcs_license_key' ) ) {
			$verify = mcs_verify_key();
		} else {
			$verify = '';
		}
		if ( $verify != '' ) {
			return "<div class='notice updated'><p>$verify</p></div>";
		}
	}
	
	return $value;
}

add_filter( 'mcs_settings_tabs', 'mcs_license_tabs' );
function mcs_license_tabs( $tabs ) {
	$tabs['license'] = __( 'License', 'my-calendar-submissions' );
	
	return $tabs;
}

add_filter( 'mcs_settings_panels', 'mcs_license_key' );
function mcs_license_key( $panels ) {

	$mcs_license_key       = get_option( 'mcs_license_key' );
	$mcs_license_key_valid = ( get_option( 'mcs_license_key_valid' ) != '' ) ? " (" . get_option( 'mcs_license_key_valid' ) . ")" : '';
	
	$panels['license'] = '
		<h3>' . __( 'Activate Your License Key', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">
			<p class="license">
			<label for="mcs_license_key">' . __('License key:','my-calendar-submissions') . $mcs_license_key_valid . '</label><br /><input type="text" name="mcs_license_key" id="mcs_license_key" class="widefat" size="60" value="' . esc_attr( trim( $mcs_license_key ) ) . '" />
			</p>
			{submit}
		</div>';
	
	return $panels;
}