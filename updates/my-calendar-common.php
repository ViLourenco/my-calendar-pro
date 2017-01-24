<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mcs_recheck_license( $key ) {
	$transient = get_transient( 'mcs_last_check' );
	if ( $transient ) {
		return;
	} else {
		$api_params = array(
			'edd_action'=> 'check_license', 
			'license' 	=> $key, 
			'item_name' => urlencode( EDD_MCP_ITEM_NAME ), // the name of our product in EDD,
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( EDD_MCP_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}
		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		update_option( 'mcs_license_status', array( current_time( 'timestamp' ) => $license_data ) );
		set_transient( 'mcs_last_check', true, DAY_IN_SECONDS );
	}
}
// PHP functions common to all My Calendar pro packages
function mcs_check_license( $output = 'status' ) {
	// listen for our activate button to be clicked
	if( isset( $_POST['mcs_license_key'] ) ) {
		// run a quick security check 
	 	if( ! check_admin_referer( 'license', '_wpnonce' ) ) 	
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
		$license = trim( $_POST[ 'mcs_license_key'] );
		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( EDD_MCP_ITEM_NAME ), // the name of our product in EDD,
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( EDD_MCP_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;
		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		update_option( 'mcs_license_status', array( current_time( 'timestamp' ) => $license_data ) );
		// $license_data->license will be either "valid" or "?"
		
		return $license_data->license;
	}	
}

function mcs_verify_key() {
	$key = isset( $_POST['mcs_license_key'] ) ? $_POST['mcs_license_key'] : false;
	if ( !$key ) {
		return;
	}
	if ( $key !== '' ) {
		$confirmation = mcs_check_license();
	} else {
		$confirmation = 'deleted';
	}
	update_option( 'mcs_license_key', $key );
	
	$previously = get_option( 'mcs_license_key_valid' );
	update_option( 'mcs_license_key_valid', $confirmation );
	if ( $confirmation == 'inactive' ) {
		$message = __('Your My Calendar Pro license key was not activated.','my-calendar-submissions');
	} else if ( $confirmation == 'active' || $confirmation == 'valid' ) {
		$message = __( 'Your My Calendar Pro license key has been activated! Enjoy!', 'my-calendar-submissions' );
	} else if ( $confirmation == 'deleted' ) {
		$message = __('You have deleted your My Calendar Pro license key.','my-calendar-submissions');
	} else if ( $confirmation == 'invalid' ) {
		if ( $mcs_license_key != '' ) {
			$message = sprintf( __( 'Your My Calendar Pro license key is either expired or invalid. If expired, you can <a href="%s">renew now</a>.', 'my-calendar-submissions' ), "https://www.joedolson.com/checkout/?edd_license_key=$mcs_license_key&download_id=5734" ); 
		} else {
			$message = sprintf( __( 'Your My Calendar Pro license key is either expired or invalid. <a href="%s">Log in to your account to check</a>.', 'my-calendar-submissions' ), "https://www.joedolson.com/account/" ); 						
		}
	} else {
		$message = __( 'My Calendar Pro received an unexpected message from the license server. Please try again!','my-calendar-submissions');
	}
	$message = ( $message != '' )?" $message ":$message; // just add a space
	
	return $message;
}