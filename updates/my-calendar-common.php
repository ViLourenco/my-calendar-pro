<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// PHP functions common to all My Calendar pro packages
function mcs_check_license( $key=false ) {
	global $mcs_version;
	if ( !$key ) {
		return false;
	} else {
		//
		define('MC_SUBMIT_PLUGIN_LICENSE_URL', "https://www.joedolson.com/wp-content/plugins/files/license.php" );
		$response = wp_remote_post( MC_SUBMIT_PLUGIN_LICENSE_URL, 
			array (
				'user-agent' => 'WordPress/My Calendar Submit' . $mcs_version . '; ' . get_bloginfo( 'url' ), 
				'body'=>array( 'key'=>$key, 'site'=>urlencode( home_url() ) ),
				'timeout' => 30				
			) );
		if ( ! is_wp_error( $response ) || is_array( $response ) ) {
			$data = $response['body'];
			if ( !in_array( $data, array( 'false', 'inuse', 'true', 'unconfirmed' ) ) ) {
				$data = @gzinflate(substr($response['body'],2));
			}
			return $data;
		}
		print_r( $response );
	}
	return false;
}

function mcs_verify_key( $option='mcs_license_key', $lang='my-calendar-submissions', $name='My Calendar Pro') {
	$key = $_POST[$option];
	update_option( $option, $key );
	if ( $key != '' ) {
		$confirmation = mcs_check_license( $key );
	} else {
		$confirmation = 'deleted';
	}
	$previously = get_option( $option.'_valid' );
	update_option( $option.'_valid', $confirmation );
	if ( $confirmation == 'false' ) {
		$message = __("$name key not valid.", 'my-calendar-submissions' );
	} else if ( $confirmation == 'inuse' ) {
		$message = __("$name license key already registered.", 'my-calendar-submissions' );				
	} else if ( $confirmation == 'unconfirmed' ) {
		$message = __("Your payment for $name has not been confirmed.", 'my-calendar-submissions' );			
	} else if ( $confirmation == 'true' ) {
		if ( $previously == 'true' ) { 
		} else {
			$message = __("$name key validated. Enjoy!", 'my-calendar-submissions' );
		}
	} else if ( $confirmation == 'deleted' ) {
		$message = __("You have deleted your $name license key.", 'my-calendar-submissions' );
	} else {
		$message = __("$name received an unexpected message from the license server. Try again in a bit.", 'my-calendar-submissions' );
		delete_option( $option );
	}
	$message = ( $message != '' )?" $message ":$message; // just add a space
	return $message;
}