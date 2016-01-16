<?php
if ( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
exit();
} else {
delete_option( 'mcs_options' );
delete_option( 'mcs_version' );
delete_option( 'mcs_to' );
delete_option( 'mcs_from' );
delete_option( 'mcs_license_key' );
delete_option( 'mcs_response' );
delete_option( 'mcs_confirmation' );
delete_option( 'mcs_confirmation_subject' );
delete_option( 'mcs_subject' );
delete_option( 'mcs_payment_response' );
delete_option( 'mcs_payment_confirmation' );
delete_option( 'mcs_payment_confirmation_subject' );
delete_option( 'mcs_payment_subject' );
delete_option( 'mcs_criteria' );
delete_option( 'mcs_payments' );
delete_option( 'mcs_submission_fee' );
delete_option( 'mcs_currency' );
delete_option( 'mcs_members_discount' );
delete_option( 'mcs_paypal_email' );
delete_option( 'mcs_paypal_merchant_id' );
delete_option( 'mcs_button' );
delete_option( 'mcs_use_sandbox' );
delete_option( 'mcs_discount' );
delete_option( 'mcs_quantity' );
delete_option( 'mcs_payment_message' );
delete_option( 'mcs_date_format' );
delete_option( 'mcs_license_key_valid' );
delete_option( 'mcs_gateway' );
delete_option( 'mcs_authnet_api' );
delete_option( 'mcs_authnet_key' );
delete_option( 'mcs_authnet_hash' );
}