<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// PHP functions common to all My Calendar pro packages
function mcs_check_license() {
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
		
		// $license_data->license will be either "active" or "inactive"
		return $license_data->license;
	}	
}

function mcs_verify_key( $option='mcs_license_key', $lang='my-calendar-submissions', $name='My Calendar Pro') {
	$key = isset( $_POST[$option] ) ? $_POST[$option] : false;
	if ( !$key ) {
		return;
	}
	if ( $key !== '' ) {
		$confirmation = mcs_check_license();
	} else {
		$confirmation = 'deleted';
	}
	update_option( $option, $key );
	
	$previously = get_option( $option.'_valid' );
	update_option( $option.'_valid', $confirmation );
	if ( $confirmation == 'inactive' ) {
		$message = __("$name key not valid.", 'my-calendar-submissions' );			
	} else if ( $confirmation == 'active' ) {
		if ( $previously == 'true' || $previously == 'active' ) { 
		} else {
			$message = __( "$name key activated. Enjoy!", 'my-calendar-submissions' );
		}
	} else if ( $confirmation == 'deleted' ) {
		$message = __("You have deleted your $name license key.", 'my-calendar-submissions' );
	} else {
		$message = __("$name received an unexpected message from the license server. Try again soon!", 'my-calendar-submissions' );
		delete_option( $option );
	}
	$message = ( $message != '' )?" $message ":$message; // just add a space
	
	return $message;
}