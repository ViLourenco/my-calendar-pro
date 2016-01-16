<?php
/*
Plugin Name: My Calendar Pro
Plugin URI: http://www.joedolson.com/my-calendar/pro/
Description: Expands the capabilities of My Calendar to add premium features.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Version: 1.4.0
*/
/*  Copyright 2012-2015  Joe Dolson (email : joe@joedolson.com)

    This program is open source software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $mcs_version, $mc_package, $wpdb;
$mc_package = 'submissions';
$mcs_version = '1.4.0';

// Define the tables used in My Calendar
define('MY_CALENDAR_PAYMENTS_TABLE', $wpdb->prefix . 'my_calendar_payments');

if ( function_exists('is_multisite') && is_multisite() ) {
	// Define the tables used in My Calendar
	define('MY_CALENDAR_GLOBAL_PAYMENTS_TABLE', $wpdb->base_prefix . 'my_calendar_payments');
}

// these are existence checkers. Exist if licensed.
if ( !function_exists( 'mcs_submit_exists' ) ) {
	function mcs_submit_exists() {
		return true;
	}
}

include(dirname(__FILE__).'/updates/my-calendar-common.php' );
include(dirname(__FILE__).'/gateways/my-calendar-ipn.php' );
include(dirname(__FILE__).'/my-calendar-submit.php' );
include(dirname(__FILE__).'/my-calendar-submit-payments.php' );
include(dirname(__FILE__).'/my-calendar-submit-widgets.php' );
include(dirname(__FILE__).'/my-calendar-submit-settings.php' );
include(dirname(__FILE__).'/my-calendar-event-posts.php' );
include(dirname(__FILE__).'/my-calendar-post-events.php' );
include(dirname(__FILE__).'/my-calendar-advanced-search.php' );
include(dirname(__FILE__).'/my-calendar-responsive-mode.php' );
include(dirname(__FILE__).'/my-calendar-import.php' );


if ( get_option( 'mcs_license_key_valid' ) != 'true' ) {
	$message = sprintf(__("You must <a href='%s'>enter your license key</a> to get support and updates for My Calendar Pro.", 'my-calendar-submissions'), admin_url('admin.php?page=my-calendar-submissions'));
	add_action('admin_notices', create_function( '', "if ( ! current_user_can( 'manage_options' ) ) { return; } else { echo \"<div class='error'><p>$message</p></div>\";}" ) );
}

add_action( 'widgets_init', create_function('', 'return register_widget("mc_submissions_widget");') );
add_action( 'init', 'mcs_register_actions' );
function mcs_register_actions() {
	add_action( 'wp_loaded', 'mcs_verify_receipt' );
	add_action( 'parse_request', 'mcs_receive_ipn' );
	add_action( 'parse_request', 'mcs_show_receipt' );
	add_action( 'mcs_complete_submission', 'mcs_notify_admin', 10, 4 );
	add_action( 'mcs_complete_submission', 'mcs_notify_submitter', 10, 4 );
	add_filter( 'mc_generator_tabs', 'mcs_generator_tab', 10, 1 );			
	add_filter( 'mc_generator_tab_content', 'mcs_generator_tab_content', 10, 1 );
	add_filter( 'mc_shortcode_generator', 'mcs_shortcode_generator', 10, 2 );	
}

load_plugin_textdomain( 'my-calendar-submissions',false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

// installation
function mcs_check() {
	global $wpdb, $mcs_version;
	$mcdb = $wpdb;
	$current_version = get_option('mcs_version');
	// If current version matches, don't bother running this.
	if ($current_version == $mcs_version) {	return true; }
	// Lets see if this is first run and create a table if it is!
	// Assume this is not a new install until we prove otherwise
	$new_install = false;
	$my_calendar_exists = false;
	$upgrade_path = array();

	$tables = $mcdb->get_results("show tables;");
	foreach ( $tables as $table ) {
		foreach ( $table as $value )  {
			if ( $value == MY_CALENDAR_PAYMENTS_TABLE ) {
				$my_calendar_exists = true;
				// check whether installed version matches most recent version, establish upgrade process.
			} 
		}
	}
	if ( $my_calendar_exists == false ) {
      $new_install = true;	  
	} else {	
		// for each release requiring an upgrade path, add a version compare. 
		// at this time, there are no upgrades available.
	}
	// having determined upgrade path, assign new version number
	update_option( 'mcs_version' , $mcs_version );
	// Now we've determined what the current install is or isn't 
	if ( $new_install == true ) {
		mcs_default_settings();
    } else {
		if ( version_compare( $current_version, "1.1.1", "<" ) ) {	$upgrade_path[] = "1.1.1"; } 
	}
	// switch for different upgrade paths
	foreach ($upgrade_path as $upgrade) {
		switch ($upgrade) {
			case '1.1.1':
				mcs_update_database();
			break;
		}
	}
}

function mcs_default_settings() {
	global $mcs_version;
	$options = array(
		'fields' => array(
			'end_date'=>__('End date','my-calendar-submissions'),
			'end_time'=>__('End time','my-calendar-submissions'),
			'description'=>__('Event Description','my-calendar-submissions'),
			'short_description'=>__('Short Description','my-calendar-submissions'),
			'event_link'=>__('Event Link','my-calendar-submissions'),
			'event_recurring'=>__('Event Recurrences','my-calendar-submissions'),
			'event_image'=>__('Event image','my-calendar-submissions')
			),
		'location_fields' => array(
			'street'=>__('Street Address','my-calendar-submissions'),
			'street2'=>__('Street Address (2)','my-calendar-submissions'),
			'phone'=>__('Phone','my-calendar-submissions'),
			'city'=>__('City','my-calendar-submissions'),
			'state'=>__('State','my-calendar-submissions'),
			'zip'=>__('Zip','my-calendar-submissions'),
			'region'=>__('Region','my-calendar-submissions'),
			'country'=>__('Country','my-calendar-submissions'),
			'url'=>__('Location URL','my-calendar-submissions'),
			'gps'=>__('GPS Coordinates','my-calendar-submissions'),
			),
		'defaults' => array(
			'mcs_response'=>'
A new event has been submitted by {first_name} {last_name}. 

{title}
{date}, {time}

Approve or reject this event: {edit_link}',
			'mcs_confirmation'=>'
Thanks for proposing a new event, {first_name} {last_name}!  

{title}
{date}, {time}

This event must be approved by a site administrator.',
			'mcs_subject'=>'New event on {blogname}',
			'mcs_confirmation_subject'=>'Your event submission has been received.',
			'mcs_payment_response'=>'
A payment to create events has been submitted by {first_name} {last_name}. 

New payment key: {key}
Paid: ${price} for {quantity} event/s',
			'mcs_payment_confirmation'=>'
Thanks for purchasing an event submission key for {blogname} a new event, {first_name}!  

Your payment key: {key}. You paid ${price} for {quantity} event/s.
Your Receipt: {receipt}

You may use this key to submit your events.',
			'mcs_payment_subject'=>'New event submission payment on {blogname}',
			'mcs_payment_confirmation_subject'=>'Your event submission payment has been received.',	
			'mcs_payment_message'=>'Payment is required to submit an event! Submission costs {price} {currency}.',
			'mcs_criteria'=>1,
			'mcs_payments'=>'false',
			//'mcs_payments_approved'=>'false',
			'mcs_use_sandbox'=>'false',
			'mcs_submission_fee'=>'5.00',
			'mcs_currency'=>'USD',
			'mcs_check_conflicts'=>'false',
			'mcs_upload_images'=>'false'
		),
		'widget_defaults'=> array(
			'title'=>'Submit an Event',
			'categories'=>'true',
			'category'=>1,
			'locations'=>'choose',
			'location'=>''
		)
	);
	update_option( 'mcs_criteria',1 );
	update_option( 'mc_event_approve','true' ); // must have approvals enabled if using public submissions
	update_option( 'mcs_options',$options );
	mcs_generate_submit_page( 'submit-events' );
	mcs_update_database();
	add_option( "mcs_db_version", $mcs_version );	// sync db version to version. 
}

if ( !function_exists('mc_is_url') ) {
	function mc_is_url($url) {
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
	}
}

function mcs_submissions() { // must exist for settings page to appear
	return true;
}

function my_calendar_sub_js() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'my-calendar-submissions' ) {
		wp_enqueue_script( 'mc.tabs' );
	}
}

function my_calendar_sub_styles() {
	if ( !empty($_GET['page']) ) {
		if (  isset( $_GET['page'] ) && ( $_GET['page'] == 'my-calendar-submissions' || $_GET['page'] == 'my-calendar-payments' ) ) {
			wp_enqueue_style( 'mc-styles', plugins_url( 'my-calendar/css/mc-styles.css' ) );
			wp_enqueue_style( 'mcs-admin', plugins_url( 'css/mcs-admin.css', __FILE__ ) );
		}
	}
	$post_events = ( is_array( get_option( 'mcs_post_event_types' ) ) ) ? get_option( 'mcs_post_event_types' ) : array();
	foreach ( $post_events as $post_type ) {
		global $current_screen;
		if ( $current_screen->base == 'post' && $current_screen->post_type == $post_type ) { 
			wp_enqueue_style( 'my-calendar-submissions-style' );
		}
	}
}

add_action( 'init', 'mcs_register_styles' );
function mcs_register_styles() {
	wp_register_style( 'mc-datepicker', plugins_url( 'my-calendar/js/pickadate/themes/default.css' ) );
	wp_register_style( 'mc-datepicker-date', plugins_url( 'my-calendar/js/pickadate/themes/default.date.css' ) );
	wp_register_style( 'mc-datepicker-time', plugins_url( 'my-calendar/js/pickadate/themes/default.time.css' ) );
	wp_register_style( 'my-calendar-submissions-style', plugins_url( 'css/mcs-styles.css', __FILE__ ), array( 'mc-datepicker', 'mc-datepicker-date', 'mc-datepicker-time' ) );
}

add_action( 'wp_enqueue_scripts', 'mcs_styles' );
function mcs_styles() {
	if ( version_compare( get_option( 'mc_version' ), '2.4.0', '>=' ) ) {
		wp_enqueue_style( 'my-calendar-submissions-style' );
	}
}

add_action('init', 'mcs_check_for_upgrades');
function mcs_check_for_upgrades() {
	if ( is_admin() ) {
		global $mcs_version;
		$hash = get_option( 'mcs_license_key' );
		if ( !$hash ) { return; }
		if ( !class_exists( 'jd_auto_update' ) ) {
			require_once ('updates/plugin-update-checker.php');
		}
		$mcs_plugin_current_version = $mcs_version;
		$mcs_plugin_remote_path = "http://www.joedolson.com/wp-content/plugins/files/updates-v2.php?key=$hash";
		$mcs_plugin_slug = plugin_basename( __FILE__ );
		new jd_auto_update( $mcs_plugin_current_version, $mcs_plugin_remote_path, $mcs_plugin_slug );
	}
}

function mcs_update_database() {
	$payments = "CREATE TABLE " . my_calendar_payments_table() . "  (
			`id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`item_number` BIGINT( 20 ) NOT NULL ,
			`event_id` BIGINT( 20 ) NOT NULL ,			
			`quantity` BIGINT( 20 ) NOT NULL ,			
			`total` BIGINT( 20 ) NOT NULL ,			
			`hash` VARCHAR( 255 ) NOT NULL ,
			`txn_id` VARCHAR( 255 ) NOT NULL ,
			`price` FLOAT( 10, 2 ) NOT NULL ,
			`fee` FLOAT( 10, 2 ) NOT NULL ,
			`status` VARCHAR( 255 ) NOT NULL ,
			`transaction_date` DATETIME NOT NULL ,
			`first_name` VARCHAR( 255 ) NOT NULL ,
			`last_name` VARCHAR( 255 ) NOT NULL ,
			`payer_email` VARCHAR( 255 ) NOT NULL
			) CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($payments);
}