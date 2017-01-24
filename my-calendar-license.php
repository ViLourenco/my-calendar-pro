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
	mcs_recheck_license( $mcs_license_key );
	$mcs_license_key_valid = ( get_option( 'mcs_license_key_valid' ) != '' ) ? " (" . get_option( 'mcs_license_key_valid' ) . ")" : '';
	$mcs_license           = get_option( 'mcs_license_status' );
	$output                = $expired = '';
	
	if ( !empty( $mcs_license ) ) {
		foreach( $mcs_license as $test => $status ) {
			$date = date_i18n( 'Y-m-d', $test );
			$expires = ( $status->expires == 'lifetime' ) ? 'Lifetime' : date_i18n( 'Y-m-d', strtotime( $status->expires ) );
			if ( $status->expires != 'lifetime' && strtotime( $status->expires ) < $test ) {
				$expired = '<p>' . sprintf( 
										__( 'Your license has expired. <a href="%s">Renew your license now!</a>', 'my-calendar-submissions' ), 
										"https://www.joedolson.com/checkout/?edd_license_key=$mcs_license_key&download_id=5734" 
									) . 
							'</p>';
			}
			$license = $status->license;
			$email   = $status->customer_email;
			$output .= "<div class='mcs-license-status'>
							<p>" . sprintf( __( 'Last Checked: %s', 'my-calendar-submissions' ), "<strong>$date</strong>" ) . "</p>
							<p class='mcs-check-license'>" . sprintf( __( 'License expires on %s. License is %s. Customer email %s.', 'my-calendar-submissions' ), "<strong>$expires</strong>", "<strong>$license</strong>", "<strong>$email</strong>" ) . "</p>
							$expired
						</div>";
		}
	}
	
	$panels['license']['content'] = '
		<h3>' . __( 'Activate Your License Key', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">
			' . $output . '
			<p class="license">
			<label for="mcs_license_key">' . __('License key:','my-calendar-submissions') . $mcs_license_key_valid . '</label><br /><input type="text" name="mcs_license_key" id="mcs_license_key" class="widefat" size="60" value="' . esc_attr( trim( $mcs_license_key ) ) . '" />
			</p>
			{submit}
		</div>';
	
	$label = ( $mcs_license_key_valid == ' (valid)' ) ? __( 'Update License Key', 'my-calendar-submissions' ) : __( 'Activate License', 'my-calendar-submissions' );
	
	$panels['license']['label'] = $label;

	
	return $panels;
}