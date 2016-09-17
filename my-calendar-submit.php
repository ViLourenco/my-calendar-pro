<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mcs_verify_receipt() {
	global $wpdb; 
	if ( isset( $_GET['mcs_receipt'] ) ) {
		$receipt = ( isset( $_GET['mcs_receipt'] ) ) ? $_GET['mcs_receipt'] : false;
		$email = ( isset( $_POST['mcs_email'] ) ) ? is_email( $_POST['mcs_email'] ) : false;
		if ( $receipt && $email ) {
			$query = "SELECT id FROM ".my_calendar_payments_table().' WHERE txn_id = %s AND payer_email = %s';
			$result = $wpdb->get_var( $wpdb->prepare( $query, $receipt, $email ) );
			if ( $result ) { setcookie( "mcs_receipt", 'true', time()+60*60, SITECOOKIEPATH, COOKIE_DOMAIN, false, true );}
		}
	}
}

function mcs_show_receipt() {
	$url = plugin_dir_url( __FILE__ );
	$header = '<!DOCTYPE html>
<html dir="'. ( is_rtl() ) ? 'rtl' : 'ltr' .'" lang="'.get_bloginfo('language').'">
<head>
	<meta charset="'.get_bloginfo('charset').'" />
	<meta name="viewport" content="width=device-width" />
	<title>'.get_bloginfo('name').' - '.__('My Calendar Submissions: Receipt','my-calendar').'</title>
	<meta name="generator" content="My Calendar for WordPress" />
	<meta name="robots" content="noindex,nofollow" />';
	if ( function_exists( 'mc_file_exists' ) && mc_file_exists( 'mcs-receipt.css' ) ) {
		$stylesheet = mc_get_file( 'mcs-receipt.css', 'url' );
	} else {
		$stylesheet = $url."css/mcs-receipt.css";
	}
	$header .= "
	<!-- Copy mcs-receipt.css to your theme directory if you wish to replace the default print styles -->
	<link rel='stylesheet' href='$stylesheet' type='text/css' media='screen,print' />
</head>
<body>
	<div class='mcs-receipt'>\n";
		$logo = apply_filters( 'mcs_receipt_logo', sprintf( '<h1><a href="%1$s">%2$s</a></h1>', home_url(), get_bloginfo('blogname') ) );
		$footer = "</div>
</body>
</html>";
	// verify validity of viewer
	if ( isset( $_GET['mcs_receipt'] ) && isset( $_COOKIE['mcs_receipt'] ) && $_COOKIE['mcs_receipt'] == 'true' ) {
		global $wpdb;
		$receipt_id = $_GET['mcs_receipt'];
		$query = "SELECT * FROM ".my_calendar_payments_table()." WHERE item_number = 1 AND txn_id = %s";
		$results = $wpdb->get_row( $wpdb->prepare( $query, $receipt_id ), ARRAY_A );

		$template = '
			<h2>Payment Key Purchase Receipt</h2>
			
			<strong>Purchased By</strong><span> {first_name} {last_name}</span>
			<strong>Transaction ID</strong><span> {txn_id}</span>
			<strong>Payment Key</strong><span> {hash}</span>
			<strong>Payer Email</strong><span> {payer_email}</span>
			
			<strong>Amount Paid</strong><span> ${price}</span>
			<strong>Submissions Granted</strong><span> {total}</span>
			<strong>Submissions Remaining</strong><span> {quantity}</span>			
			<strong>Transaction Date</strong><span> {transaction_date}</span>
			<em><a href="javascript:window.print()">Print</a></em>';
		$template = apply_filters( 'mcs_receipt_template', $template, $results );
		$wrapper = apply_filters( 'mcs_receipt_header', $header, $receipt_id );
		$wrapper .= $logo;
		$wrapper .= jd_draw_template( $results, $template );
		$wrapper .= apply_filters( 'mcs_receipt_header', $footer, $receipt_id );
		echo $wrapper;
		exit;
		// require entry of email address to view receipt... > place email into session data to avoid doing this first time?
		// display message noting that viewer will need to enter email to view receipt.
	} else if ( isset( $_GET['mcs_receipt'] ) ) {
		$body = "<form action='' method='POST'>
					<div>
						<label for='mcs-email'>Your purchase email</label>: <input type='email' name='mcs_email' id='mcs-email' /> <input type='submit' name='mcs-verify' value='Verify' />
					</div>
				</form>";
		echo $header . $body . $footer;
		exit;
	}
}
add_filter( 'mcs_receipt_template', 'wpautop' );

function mcs_generate_submit_page( $slug ) {
	$current_user = wp_get_current_user();
	if ( ! is_page( $slug ) ) {
		$page      = array(
			'post_title'  => __( 'Submit Events', 'my-calendar' ),
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_author' => $current_user->ID,
			'ping_status' => 'closed',
			'post_content' => '[submit_event]'
		);
		$post_ID   = wp_insert_post( $page );
		$post_slug = wp_unique_post_slug( $slug, $post_ID, 'publish', 'page', 0 );
		wp_update_post( array( 'ID' => $post_ID, 'post_name' => $post_slug ) );
	} else {
		$post    = get_page_by_path( $slug );
		$post_ID = $post->ID;
	}
	update_option( 'mcs_submit_id', $post_ID );
	
	return $post_ID;	
}


add_shortcode('submit_event','mcs_submit_form');
add_shortcode('submit_payment','mcs_payment_form');

function mcs_submit_form( $atts, $content=null ) {
	extract( shortcode_atts( 
		array( 
			'fields' => 'end_time,description,event_link,event_recurring,event_image',
			'categories' => 1,
			'locations' => 'either',
			'category' => 1, // default category value
			'location' => 0, // default location value
			'location_fields'=>'street,street2,phone,city,state,zip,country,url'
		), $atts, 'submit_event' ) );
	$fields = explode( ',', $fields );
	$fld = array();
	foreach ( $fields as $value ) {
		$set = explode( '=',$value );
		$value = strtolower( trim( $set[0] ) );
		$fld[$value]=( isset($set[1]) )?$set[1]:'true';
	}
	$location_fields = explode(',',$location_fields );
	$loc = array();
	foreach ( $location_fields as $value ) {
		$set = explode( '=',$value );
		$value = strtolower(trim($set[0]));
		$loc[$value]=( isset($set[1]) )?$set[1]:'true';	
	}
	if ( mcs_user_can_submit_events() ) {
		return mc_submit_form( $fld, $categories, $locations, $category, $location, $loc );
	} else {
		return $content;
	}
}

add_action( 'init', 'mcs_set_unique_id' );
function mcs_set_unique_id() {
	$unique_id = ( isset( $_COOKIE['mcs_unique_id'] ) ) ? $_COOKIE['mcs_unique_id'] : false;
	if ( !$unique_id ) {
		$unique_id = mcs_generate_unique_id();
		setcookie( "mcs_unique_id", $unique_id, time() + 60 * 60 * 24 * 7, COOKIEPATH, COOKIE_DOMAIN, false, true );
	}	
}

function mcs_generate_unique_id() {
	$length = 16;
	$characters = "0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-_";
	$string = '';
	for ( $p = 0; $p < $length; $p++ ) {
		$string .= $characters[mt_rand(0, strlen($characters)-1)];
	}
	
	return $string;
}

add_action( 'init', 'mcs_run_processor' );
function mcs_run_processor() {	
	$response  = mcs_processor( $_POST );
	$unique_id = isset( $_COOKIE['mcs_unique_id'] ) ? $_COOKIE['mcs_unique_id'] : false;
	
	if ( $response !== false ) {
		set_transient( 'mcs_'.$unique_id, $response, 10 );
	} else {
		delete_transient( 'mcs_'.$unique_id );
	}
}

function mcs_processor_response() {
	$unique_id = isset( $_COOKIE['mcs_unique_id'] ) ? $_COOKIE['mcs_unique_id'] : false;

	$response = get_transient( 'mcs_'.$unique_id );
		
	if ( $response != '' ) {
		return $response;
	}
}

function mc_submit_form( $fields,$categories,$locations,$category,$location,$location_fields ) {
	
	$fields = apply_filters( 'mcs_submit_fields', $fields );
	$location_fields = apply_filters( 'mcs_submit_location_fields', $location_fields );
	// the big function. This creates the form.
	wp_enqueue_script( 'pickadate', plugins_url( 'my-calendar/js/pickadate/picker.js' ) );
	wp_enqueue_script( 'pickadate.date', plugins_url( 'my-calendar/js/pickadate/picker.date.js' ) );
	wp_enqueue_script( 'pickadate.time', plugins_url( 'my-calendar/js/pickadate/picker.time.js' ) );
	wp_localize_script( 'pickadate.date', 'mc_months', array(
		date_i18n( 'F', strtotime( 'January 1' ) ),
		date_i18n( 'F', strtotime( 'February 1' ) ),
		date_i18n( 'F', strtotime( 'March 1' ) ),
		date_i18n( 'F', strtotime( 'April 1' ) ),
		date_i18n( 'F', strtotime( 'May 1' ) ),
		date_i18n( 'F', strtotime( 'June 1' ) ),
		date_i18n( 'F', strtotime( 'July 1' ) ),
		date_i18n( 'F', strtotime( 'August 1' ) ),
		date_i18n( 'F', strtotime( 'September 1' ) ),
		date_i18n( 'F', strtotime( 'October 1' ) ),
		date_i18n( 'F', strtotime( 'November 1' ) ),
		date_i18n( 'F', strtotime( 'December 1' ) )
	) );
	wp_localize_script( 'pickadate.date', 'mc_days', array(
		date_i18n( 'D', strtotime( 'Sunday' ) ),
		date_i18n( 'D', strtotime( 'Monday' ) ),
		date_i18n( 'D', strtotime( 'Tuesday' ) ),
		date_i18n( 'D', strtotime( 'Wednesday' ) ),
		date_i18n( 'D', strtotime( 'Thursday' ) ),
		date_i18n( 'D', strtotime( 'Friday' ) ),
		date_i18n( 'D', strtotime( 'Saturday' ) )
	) );
	
	wp_register_script( 'mcs-submit-form', plugins_url( '/js/jquery.mcs-submit.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'mcs-submit-form' );

	$format = get_option('mcs_date_format');
	switch ($format) {
		case "m/d/Y": $js_format = 'mm/dd/yyyy'; break;
		case "d-m-Y": $js_format = 'dd-mm-yyyy'; break;
		case "Y-m-d": $js_format = 'yy-mm-dd'; break;
		case "j F Y": $js_format = 'd mmmm yyyy'; break;
		case "M j, Y": $js_format = 'mmm d, yyyy'; break;
		default: $js_format = 'yy-mm-dd';
	}
	$time = get_option('mcs_time_format');
	switch ($time) {
		case "H:i": $js_time_format = 'HH:i'; break;
		default: $js_time_format = 'h:i a';
	}	
	$script = "
<script>
(function ($) {
	$(function() {
		$( 'input.mc-date' ).pickadate({
			monthsFull: mc_months,
			format: '$js_format',
			weekdaysShort: mc_days,
			selectYears: true,
			selectMonths: true,
			editable: true
		});
		$( 'input.mc-time' ).pickatime({
			interval: 15,
			format: '$js_time_format',
			editable: true		
		});
	})
})(jQuery);
</script>";
	global $user_ID;
	if ( is_user_logged_in() ) { $auth = $user_ID; } else { $auth = 0; }
	$nonce = "<input type='hidden' name='event_nonce_name' value='".wp_create_nonce('event_nonce')."' />";
	$response = mcs_processor_response();
	$event = false;
	if ( empty( $response[1] ) && isset( $_GET['mcs_id'] ) && is_user_logged_in() ) {
		$mc_id = intval( $_GET['mcs_id'] );
		$event = mc_form_data( $mc_id );
		if ( !mc_can_edit_event( $event->event_id ) ) {
			$event = false;
		}
	}
	
	if ( isset( $_GET['mcs_id'] ) && !is_user_logged_in() ) {
		$message = "<div class='updated'><p>" . __( "You'll need to log-in to edit this event.", 'my-calendar-submissions' ) . "</p></div>";
	} else {
		$message = '';
	}
	
	$data = ( !empty( $response[1] ) ) ? $response[1] : $event;
	$has_data = ( empty($data) ) ? false : true; 
	$title = ( !empty($data) ) ? esc_attr( $data->event_title ) : '';
	$desc = ( !empty($data) )?esc_attr( $data->event_desc ): '';
	$tickets = ( !empty( $data ) )?esc_attr( $data->event_tickets ): '';
	$registration = ( !empty( $data ) ) ? esc_attr( $data->event_registration ) : '';	
	$begin = ( !empty($data) ) ? esc_attr( $data->event_begin ) : '';
	$format = date( get_option('mcs_date_format'), current_time( 'timestamp' ) );
	$format2 = date( get_option('mcs_time_format'), current_time( 'timestamp' ) );
	$endformat2 = date( get_option('mcs_time_format'), current_time( 'timestamp' ) + 3600 );
	$end = ( !empty($data) ) ? esc_attr( $data->event_end ) : '';
	$time = ( !empty($data) ) ? esc_attr( $data->event_time ) : $format2;
	$endtime = ( !empty($data) ) ? esc_attr( $data->event_endtime ) : $endformat2;
	$recur = ( !empty($data) ) ? esc_attr( $data->event_recur ) : 'S';
		$recurs = str_split( $recur, 1 );
		$recur = $recurs[0];
		$every = ( isset( $recurs[1] ) )?$recurs[1]:1;
		if ( $every == 1 && $recur == 'B' ) { $every = 2; }
	$repeats = ( !empty($data) ) ? esc_attr( $data->event_repeats ) : 0;
	$selected_category = ( !empty($data) )?esc_attr( $data->event_category ):$category;
	$event_host = ( !empty($data) ) ? esc_attr( $data->event_host ) : $user_ID;
	$link = ( !empty($data) )?esc_attr( $data->event_link ):'';
	$label = ( !empty($data) )?esc_attr( $data->event_label ):'';
	$street = ( !empty($data) )?esc_attr( $data->event_street ):'';	
	$street2 = ( !empty($data) )?esc_attr( $data->event_street2 ):'';
	$city = ( !empty($data) )?esc_attr( $data->event_city ):'';
	$state = ( !empty($data) )?esc_attr( $data->event_state ):'';
	$postcode = ( !empty($data) )?esc_attr( $data->event_postcode ):'';
	$country = ( !empty($data) )?esc_attr( $data->event_country ):'';
	$region = ( !empty($data) )?esc_attr( $data->event_region ):'';	
	$url = ( !empty($data) )?esc_attr( $data->event_url ):'';
	$longitude = ( !empty($data) )?esc_attr( $data->event_longitude ):'';
	$latitude = ( !empty($data) )?esc_attr( $data->event_latitude ):'';
	$phone = ( !empty($data) )?esc_attr( $data->event_phone ):'';
	$short = ( !empty($data) )?esc_attr( $data->event_short ):'';
	$image = ( !empty($data) )?esc_attr( $data->event_image ):'';
	$name = ( isset( $_POST['event_name'] ) )?esc_attr( $_POST['event_name'] ):'';
	$email = ( isset( $_POST['event_email'] ) )?esc_attr( $_POST['event_email'] ):'';
	$key = ( isset( $_POST['event_key'] ) )?esc_attr( $_POST['event_key'] ):'';
	$key = ( isset( $_GET['event_key'] ) )?esc_attr( $_GET['event_key'] ):'';

	if ( $event ) {
		$link_expires = $event->event_link_expires;
		$event_holiday = $event->event_holiday;
		$event_fifth_week = $event->event_fifth_week;
		$edit = "<input type='hidden' name='event_edit' value='$mc_id' />";
		$edit .= "
			<input type='hidden' name='prev_event_begin' value='$begin' />
			<input type='hidden' name='prev_event_repeats' value='$repeats' />
			<input type='hidden' name='prev_event_recur' value='$recur' />
			<input type='hidden' name='prev_event_status' value='$event->event_approved' />
			<input type='hidden' name='event_post' value='$event->event_post' />";
	} else {
		$link_expires = ( get_option('mc_event_link_expires') == 'false' ) ? 1 : 0;
		$event_holiday = ( get_option( 'mc_skip_holidays' ) == 'true' )?'on':'false';
		$event_fifth_week = ( get_option( 'mc_no_fifth_week' ) == 'true' )?1:'';
		$edit = '';
	}	
	
	$selected_location = array( 'label'=>$label, 'street'=>$street, 'street2'=>$street2, 'city'=>$city, 'state'=>$state, 
	'postcode'=>$postcode, 'country'=>$country, 'region'=>$region, 'url'=>$url, 'longitude'=>$longitude, 'latitude'=>$latitude, 
	'phone'=>$phone );
	$payment_form = ( mcs_payment_required() ) ? mcs_payment_form() : '';
	$check_conflicts = ( get_option('mcs_check_conflicts')=='true' ) ? '<input type="hidden" name="mcs_check_conflicts" value="true" />':'';
	$approved = ( get_option('mcs_automatic_approval') == 'true' || current_user_can( 'mc_manage_events' ) ) ? 1 : 0; 
	// need to set hidden inputs to default values.
	$enctype = ( get_option('mcs_upload_images') == 'true' )?' enctype="multipart/form-data"':'';
	$return = "$script
	<div class='mc-submissions'>
		$message
		$response[0]
		$payment_form
		<form action='' method='post' class='mcs-submission'$enctype>
		<div>
		$nonce
		<input type='hidden' name='mcs_submission' value='on' />
		<input type='hidden' name='event_approved' value='$approved' /> 
		<input type='hidden' name='event_author' value='$auth' />
		<input type='hidden' name='event_link_expires' value='".$link_expires."' />
		<input type='hidden' name='event_holiday' value='".$event_holiday."' />
		<input type='hidden' name='event_fifth_week' value='".$event_fifth_week."' />
		<input type='hidden' name='event_group_id' value='".mc_group_id()."' />
		<div style='display: none;'>
			<label for='your_name'>" . __( 'Do not complete this field.', 'my-calendar-submissions' ) . "</label>
			<input type='text' name='your_name' id='your_name' value='' />
		</div>
		$edit";
		if ( apply_filters( 'mcs_event_allday', 0 ) == true ) {
			$return .= "<input type='hidden' name='event_allday' value='1' />";
		}
		if ( apply_filters( 'mcs_event_hide_end', 0 ) == true ) {
			$return .= "<input type='hidden' name='event_hide_end' value='1' />";
		}
		$return .= "$check_conflicts
		</div>";
		if ( mcs_payment_required() ) {
			$return .= "<p><label for='mcs_key'>".__('Payment Key','my-calendar-submissions').' <span>'.__('(required)','my-calendar-submissions')."</span></label> <input type='text' name='mcs_key' id='mcs_key' value='$key' required='required' aria-required='true' /></p>";
		} 	
		$flabel = ( isset( $fields['event_title'] ) ) ? $fields['event_title'] : __('Event Title','my-calendar-submissions');
		$dlabel = ( isset( $fields['event_date'] ) ) ? $fields['event_date'] : __('Date','my-calendar-submissions');		
		$tlabel = ( isset( $fields['event_time'] ) ) ? $fields['event_time'] : __('Time','my-calendar-submissions');		
		$return .="
		<p>
		<label for='mc_event_title'>$flabel <span>".__('(required)','my-calendar-submissions')."</span></label> <input type='text' name='event_title' id='mc_event_title' value='$title' required='required' aria-required='true' />
		</p>
		<div class='mc_begin_container'>
		<p>
		<label for='mc_event_date'>$dlabel <span>".__('(required)','my-calendar-submissions')."</span></label> <input type='text' class='mc-date' name='event_begin[]' id='mc_event_date' value='$begin' required='required' aria-required='true' />
		</p>
		<p>
		<label for='mc_event_time'>$tlabel</label> <input type='time' name='event_time[]' id='mc_event_time' class='mc-time' value='$time' />
		</p>
		</div>";
		if ( isset( $fields['end_date']) || isset($fields['end_time'] ) ) {
			$return .= "<div class='mc_end_container'>";
		}
		if ( isset( $fields['end_date'] ) ) {
			$flabel = ( $fields['end_date'] != 'true' && $fields['end_date'] != 'End date' ) ? $fields['end_date'] : __( 'End date','my-calendar-submissions' );
			$return .=	"<p>
			<label for='mc_event_enddate'>$flabel</label> <input type='text' class='mc-date' name='event_end[]' id='mc_event_enddate' value='$end' />
			</p>";
		} else {
			$return .= "<input type='hidden' name='event_end[]' value='' />";
		}
		if ( isset($fields['end_time']) ) {
			$flabel = ( $fields['end_time'] != 'true' && $fields['end_time'] != 'End time' )?$fields['end_time']:__('End time','my-calendar-submissions');
			$return .=	"<p>
			<label for='mc_event_endtime'>$flabel</label> <input type='time' name='event_endtime[]' id='mc_event_endtime' class='mc-time' value='$endtime'/>
			</p>";
		}
		if ( isset($fields['end_date']) || isset($fields['end_time']) ) {
			$return .= "</div>";
		}

		if ( is_user_logged_in() ) { $current_user = wp_get_current_user(); $name = $current_user->display_name; $email = $current_user->user_email; }
		$disallow_user_changes = apply_filters( 'mcs_disallow_user_changes', false );
		$required = apply_filters( 'mcs_require_name_and_email', 'required="required"' );
		if ( $disallow_user_changes && is_user_logged_in() ) {
			$return .= "<input name='mcs_name' value='$name' type='hidden' /><input name='mcs_email' value='$email' type='hidden' />";
		} else {
			$flabel = ( isset( $fields['mcs_name'] ) && $fields['mcs_name'] != 'true' && $fields['mcs_name'] != 'Your Name'  )?$fields['mcs_name']:__('Your Name','my-calendar-submissions');
			$return .=	"<p>
				<label for='mcs_name'>$flabel</label> <input type='text' name='mcs_name' id='mcs_name' value='$name' $required />
				</p>";
			$flabel = ( isset( $fields['mcs_email'] ) && $fields['mcs_email'] != 'true' && $fields['mcs_email'] != 'Your Email'  )?$fields['mcs_email']:__('Your Email','my-calendar-submissions');
			$return .=	"<p>
				<label for='mcs_email'>$flabel</label> <input type='email' name='mcs_email' id='mcs_email' value='$email' $required />
				</p>";
		}
		if ( isset( $fields['event_host'] ) ) {
				$host_select = '<select id="e_host" name="event_host">';
						// Grab all the users and list them
						$users = my_calendar_getUsers();
						$num = 0;
						foreach ( $users as $u ) {
							$display_name = ( $u->display_name == '' ) ? $u->user_nicename : $u->display_name;
							if ( $event_host == $u->ID ) {
								$selected = ' selected="selected"';
							} else {
								$selected = '';
							}
							if ( user_can( $u->ID, apply_filters( 'mcs_eligible_hosts', 'mc_add_events' ) ) ) { 
								$host_select .= "<option value='$u->ID'$selected>$display_name</option>\n";
								$num++;
								$single_host = $u->ID;
							}
						}
				$host_select .= '</select>';
			$flabel = ( isset( $fields['event_host'] ) && $fields['event_host'] != 'true' && $fields['event_host'] != 'Event Host' )?$fields['event_host']:__('Event Host','my-calendar-submissions');
			if ( $num <= 1 ) {
				$return .= "<input type='hidden' name='event_host' value='$single_host' />";					
			} else {
				$return .= "
				<p class='event_host'>
					<label for='e_host'>". $flabel . "</label>
					$host_select
				</p>";	
			}
		}
		if ( isset( $fields['event_recurring'] ) ) {
		$return .=	"
			<p class='recurring'>
			<label for='event_repeats'>".__('Repeats','my-calendar-submissions')."</label> <input type='number' name='event_repeats' id='event_repeats' class='input' size='1' min='0' max='999' value='$repeats' /> 
			<label for='event_every'>".__('every','my-calendar')."</label> <input type='number' name='event_every' id='event_every' class='input' size='1' min='1' max='9' maxlength='1' value='$every' /> 
			<label for='event_recur' class='screen-reader-text'>". __('Units','my-calendar-submissions')."</label> <select name='event_recur' class='input' id='event_recur'>"
				.mc_recur_options($recur)."
			</select> 
			</p>"; // event_repeats, event_recur
		} else {
			$return .= "<div>
					<input type='hidden' name='event_repeats' value='0' />
					<input type='hidden' name='event_recur' value='S' />
					<input type='hidden' name='event_every' value='1' />
					</div>";
		}
		// event_open, event_group
		if ( isset( $fields['description'] ) ) {
			$flabel = ( $fields['description'] != 'true' && !( $fields['description'] == 'Description' || $fields['description'] == 'Event Description' )  )?$fields['description']:__('Description','my-calendar-submissions');
			$return .=	"<p><label for='mc_event_description'>$flabel</label> <textarea name='content' id='mc_event_description' class='full_description'>$desc</textarea></p>";
		}
		
		if ( isset($fields['short_description']) ) {
			$flabel = ( $fields['short_description'] != 'true' && !( $fields['short_description'] == 'Summary' || $fields['short_description'] == 'Short Description' ) )?$fields['short_description'] : __('Summary','my-calendar-submissions');
			$return .=	"<p><label for='mc_event_short_description'>$flabel</label> <textarea name='event_short' id='mc_event_short_description' class='short_description'>$short</textarea></p>";
		}
		
		$return .= apply_filters( 'mc_event_details', '', $has_data, $data, 'public' );

		if ( isset( $fields['access'] ) && function_exists( 'mc_event_accessibility' ) ) {
			$flabel = ( $fields['access'] != 'true' && $fields['access'] != 'Event Access'  )?$fields['access']:__('Event Access','my-calendar-submissions');
			$return .= mc_event_accessibility( '', $data, $flabel );
		}

		if ( isset($fields['event_link']) ) {
			$flabel = ( $fields['event_link'] != 'true' && !( $fields['event_link'] == 'Link' || $fields['event_link'] == 'Event Link' )  ) ? $fields['event_link'] : __('Link','my-calendar-submissions');
			$return .=	"<p>
				<label for='mc_event_link'>$flabel</label> <input type='url' name='event_link' id='mc_event_link' value='$link' placeholder='http://' />
				</p>";
		}
		if ( isset($fields['event_image']) ) {
			$flabel = ( $fields['event_image'] != 'true' && !( $fields['event_image'] == 'Image (URL)' || $fields['event_image'] == 'Event image' ) )?$fields['event_image']:__('Image (URL)','my-calendar-submissions');
			if ( get_option('mcs_upload_images') == 'true' && $image == '' ) { 
				$input_type = 'file'; 
			} else { 
				$input_type = 'url'; 
			}
			$return .=	"<p>
				<label for='mc_event_image'>$flabel</label> <input type='$input_type' name='event_image' id='mc_event_image' value='$image' />
				</p>";
		}	
		$return .= mcs_submit_category( $selected_category, $categories );
		if ( isset( $fields['registration'] ) ) {
			$flabel = ( $fields['registration'] != 'true' && $fields['registration'] != 'Ticketing Information'  )?$fields['registration']:__('Ticketing Information','my-calendar-submissions');	
			$return .= "<fieldset>
			<legend>$flabel</legend>";
			$return .= apply_filters( 'mc_event_registration', '', $has_data, $data, 'public' );
			$return .= "</fieldset>";
		}		
		$return .= mcs_submit_location( $location, $locations, $location_fields, $selected_location );	
		$return .= "<p><input type='submit' name='save' value='".__('Submit your event','my-calendar-submissions')."' /></p>";
		
	$return .="
		</form>	
	</div>";
	$return = apply_filters( 'mcs_after_submissions', $return, $response );
	
	return $return;
}

/*
// Auto refresh after submission (if you want it.)
add_filter( 'mcs_after_submissions', 'after_successful_submission', 10, 2 );
function after_successful_submission( $return, $response ) {
	if ( $response[2] == true ) {
		$_POST = array();
		$reload = "<script>var current = window.location.href; setTimeout('window.location = current',2000);</script>";
	} else {
		$reload = '';
	}
	return $return . $reload;
}
*/

function mcs_submit_category( $category, $categories ) {
	if ( !$categories ) { 
		if ( !$category ) {
			return "<div><input type='hidden' name='event_category' value='1' /></div>";
		} else {
			return "<div><input type='hidden' name='event_category' value='$category' /></div>";
		}
	}
	return "
	<p>
		<label for='mcs_event_category'>".__('Category','my-calendar-submissions')."</label> 
		<select name='event_category' id='mcs_event_category'>".
			mc_category_select( $category )."
		</select>
	</p>";
}

function mcs_submit_location( $location, $locations, $location_fields, $selected_location ) {
	if ( $locations == 'none' ) { return '<div><input type="hidden" name="location_preset" value="none" /></div>'; }
	$return = '';
	switch ( $locations ) {
		case 'choose':
			$return .= "<p><label for='mcs_event_location'>".__('Location','my-calendar-submissions')."</label> <select name='location_preset' id='mcs_event_location'><option value='none'> -- </option>".mc_location_select( $location )."</select></p>";
		break;		
		case 'either':
			$return .= "<p><label for='mcs_event_location'>".__('Location','my-calendar-submissions')."</label> <select name='location_preset' id='mcs_event_location'><option value='none'> -- </option>".mc_location_select( $location )."</select></p>";
			$return .= "<button type='button' class='toggle_location_fields' aria-expanded='false'>" . __( 'Add New Location', 'my-calendar-submissions' ) . "<span class='dashicons dashicons-plus' aria-hidden='true'></span></button>
			<div class='mcs_location_fields'>";
			$return .= mcs_location_form($location_fields, $selected_location);
			$return .= "</div>";
		break;
		case 'enter':
			$return .= mcs_location_form($location_fields, $selected_location);
			$return .= '<div><input type="hidden" name="location_preset" value="none" /></div>'; 
		break;
		default:
			if ( $location ) {
				$return = '<div><input type="hidden" name="location_preset" value="'.$location.'" /></div>'; 
			} else {
				$return = '<div><input type="hidden" name="location_preset" value="none" /></div>'; 
			} // if 'neither', but a preset is set, this value should be set.
		break;
	}
	return $return;
}

function mcs_location_form( $fields, $loc ) {
	$flabel = ( isset( $fields['event_label'] ) && $fields['event_label'] != 'true' ) ? $fields['event_label'] : __('Name of Location','my-calendar-submissions');
	$return = '<p>
		<label for="event_label">'.$flabel.'</label> <input type="text" id="event_label" name="event_label" class="input" value="'.$loc['label'].'" />
		</p>';
	if ( isset( $fields['street'] ) ) {
		$flabel = ( $fields['street'] != 'true' && $fields['street'] != 'Street Address'  )?$fields['street']:__('Street Address','my-calendar-submissions');
		$return .= '		
		<p>
		<label for="event_street">'.$flabel.'</label> <input type="text" id="event_street" name="event_street" class="input" value="'.$loc['street'].'" />
		</p>';
	}
	if ( isset($fields['street2']) ) {
		$flabel = ( $fields['street2'] != 'true' && $fields['street2'] != 'Street Address (2)'  )?$fields['street2']:__('Street Address (2)','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_street2">'.$flabel.'</label> <input type="text" id="event_street2" name="event_street2" class="input" value="'.$loc['street2'].'" />
		</p>';
	}
	if ( isset($fields['phone']) ) {
		$flabel = ( $fields['phone'] != 'true' && $fields['phone'] != 'Phone'  )?$fields['phone']:__('Phone','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_phone">'.$flabel.'</label> <input type="text" id="event_phone" name="event_phone" class="input" value="'.$loc['phone'].'" />
		</p>';
	}
	if ( isset($fields['city']) ) {
		$flabel = ( $fields['city'] != 'true' && $fields['city'] != 'City' )?$fields['city']:__('City','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_city">'.$flabel.'</label> <input type="text" id="event_city" name="event_city" class="input" value="'.$loc['city'].'" /> 
		</p>';
	}
	if ( isset($fields['state']) ) {
		$flabel = ( $fields['state'] != 'true' && !( $fields['state'] == 'State/Province' || $fields['state'] == 'State' ) )?$fields['state']:__('State/Province','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_state">'.$flabel.'</label> <input type="text" id="event_state" name="event_state" class="input" value="'.$loc['state'].'" /> 
		</p>';
	}
	if ( isset($fields['zip']) ) {
		$flabel = ( $fields['zip'] != 'true' && !( $fields['zip'] == 'Zip' || $fields['zip'] == 'Postal Code' ) )?$fields['zip']:__('Postal Code','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_postcode">'.$flabel.'</label> <input type="text" id="event_postcode" name="event_postcode" class="input" size="10" value="'.$loc['postcode'].'" />
		</p>';
	}
	if ( isset($fields['region']) ) {
		$flabel = ( $fields['region'] != 'true' && $fields['region'] != 'Region' )?$fields['region']:__('Region','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_region">'.$flabel.'</label> <input type="text" id="event_region" name="event_region" class="input" value="'.$loc['region'].'" />
		</p>';
	}
	if ( isset($fields['country']) ) {
		$flabel = ( $fields['country'] != 'true' && $fields['country'] != 'Country' )?$fields['country']:__('Country','my-calendar-submissions');
		$return .= '
		<p>	
		<label for="event_country">'.$flabel.'</label> <input type="text" id="event_country" name="event_country" class="input" value="'.$loc['country'].'" />
		</p>';
	}
	if ( isset($fields['url']) ) {
		$flabel = ( $fields['url'] != 'true' && $fields['url'] != 'Location URL' )?$fields['url']:__('Location URL','my-calendar-submissions');
		$return .= '
		<p>
		<label for="event_url">'.$flabel.'</label> <input type="text" id="event_url" name="event_url" class="input" value="'.$loc['url'].'" />
		</p>';
	}
	if ( isset($fields['gps']) ) {
		$return .= '
		<fieldset>
		<legend>'.__('GPS Coordinates','my-calendar-submissions').'</legend>
		<p>
		<label for="event_latitude">'.__('Latitude','my-calendar-submissions').'</label> <input type="text" id="event_latitude" name="event_latitude" class="input" size="10" value="'.$loc['latitude'].'" />
		</p>
		<p>
		<label for="event_longitude">'.__('Longitude','my-calendar-submissions').'</label> <input type="text" id="event_longitude" name="event_longitude" class="input" size="10" value="'.$loc['longitude'].'" />
		</p>
		<input type="hidden" name="event_zoom" value="16" />
		</fieldset>';
	}
	return $return;
}

function mcs_payment_required() {
	if ( ( get_option('mcs_payments') == 'true' && !is_user_logged_in() ) || ( is_user_logged_in() == true && get_option( 'mcs_members_discount' ) < 100 && get_option('mcs_payments') == 'true' ) ) {
		$return = true;
	} else {
		$return = false;
	}
	return apply_filters( 'mcs_payment_required', $return );
}

// returns array 
// @uses mcs_on_sale (boolean)
function mcs_check_discount( $role = 'auto' ) {
	$discounts = get_option( 'mcs_discount' );
	$discounts = apply_filters( 'mcs_alter_discount', $discounts, $role );
	if ( $discounts ) {
		$on_sale = mcs_on_sale( $discounts );
		if ( $on_sale ) {
			return $discounts;
		} else {
			return false;
		}
	}
	return false;
}

function mcs_calculate_price( $quantity, $price, $discount, $discount_rate ) {
	$total = $price * $quantity;
	if ( $discount ) {
		$total = $price * $discount_rate;
	}
	return $total;
}

function mcs_set_quantity_form( $price ) {
	$form = "<form action='' method='POST'>
				<div>
				<label for='mcs_quantity'>".sprintf( __('Number of events?','my-calendar-submissions' ), $price )."</label> <input type='number' name='mcs_quantity' value='1' id='mcs_quantity' /> <input type='submit' name='mcs_set_quantity' value='".__('Go to Payment','my-calendar-submissions')."' />
				</div>
			</form>";
	return $form;
}

if ( apply_filters( 'mcs_force_ssl', true ) ) { // disable with a simple filter.
	add_filter( 'template_redirect', 'mcs_force_ssl' );
	function mcs_force_ssl() {
		$ssl = get_option( 'mcs_ssl' );
		$purchase_page = get_option( 'mcs_purchase_page' );
		if ( $ssl == 'true' && ( is_single( $purchase_page ) || is_page( $purchase_page ) ) && !is_ssl() ) {
			wp_redirect( preg_replace('|^http://|', 'https://', get_permalink( $purchase_page ) ) );
		}
	}
}

function mcs_replace_http( $url ) {
	if ( get_option( 'mcs_ssl' ) == 'true' ) {
		$url = preg_replace('|^http://|', 'https://', $url ); 
	}
	return $url;
}

function mcs_on_sale( $discounts ) {
	if ( !isset( $discounts['begins'] ) || !isset( $discounts['ends'] ) || !isset( $discounts['rate'] ) ) { $return = false; }
	if ( $discounts['begins'] == '' ) { $return = false; }
	$end = ( $discounts['ends'] == '' ) ? date( 'Y-m-d',time()+60*60*24*7 ) : $discounts['ends'];
	if ( function_exists( 'my_calendar_date_xcomp' ) ) {
		if ( my_calendar_date_xcomp( $discounts['begins'], date('Y-m-d',time() ) ) && my_calendar_date_xcomp( date( 'Y-m-d', time() ),$end ) && (int) $discounts['rate'] > 0 ) {
			$return = true;
		}
	} else {
		$return = false;
	}
	return $return;
}

function mcs_get_price( $logged_in ) {
	$price = apply_filters( 'mcs_get_price', get_option( 'mcs_submission_fee' ) );
	if ( $logged_in ) {
		$discounts = mcs_check_discount();
		$discount = $discounts['rate'];
		$discounted = ( $discount != 0) ? $price - ( $price * ( $discount/100 ) ) : $price;
		$discounted = sprintf("%01.2f", $discounted );
		$price = $discounted;
	}
	return apply_filters( 'mcs_get_price', $price );
}

function mcs_processor( $post ) {
	if ( isset( $post['mcs_submission'] ) ) {
		$attach_id = false;
		$nonce = $post['event_nonce_name'];
		if ( !wp_verify_nonce( $nonce,'event_nonce' ) ) return;
		// honeypot - only bots should complete this field;
		$honeypot = ( isset( $_POST['your_name'] ) && $_POST['your_name'] != '' ) ? true : false;
		if ( $honeypot ) {
			return;
		}
		// if files being uploaded, upload file and convert to a string for $post
		if( !empty( $_FILES['event_image'] ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			require_once( ABSPATH . '/wp-admin/includes/image.php' );
			$file   = $_FILES['event_image'];
			$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
			if( !isset( $upload['error'] ) && isset( $upload['file'] ) ) {
				$filetype   = wp_check_filetype( basename( $upload['file'] ), null );
				$title      = $file['name'];
				$ext        = strrchr( $title, '.' );
				$title      = ($ext !== false) ? substr( $title, 0, -strlen( $ext ) ) : $title;
				$attachment = array(
					'post_mime_type'    => $filetype['type'],
					'post_title'        => addslashes( $title ),
					'post_content'      => '',
					'post_status'       => 'inherit'
				);
				$alt = ( isset( $_POST['event_image_alt'] ) ) ? sanitize_text_field( $_POST['event_image_alt'] ) : '';
				$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
				update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );				
				$post['event_image'] = $upload['url'];
			}
		}
		// end file upload
		$check = mc_check_data( 'add', $post, 0 );
		$message = '';
		if ( mcs_payment_required() ) {
			$key = ( isset( $post['mcs_key'] ) ) ? $post['mcs_key'] : false;
			$quantity = mcs_check_key( $key );
			if ( !$quantity ) {
				$reason = mcs_key_status( $key );
				return array( "<div class='notice error'><p>".sprintf(__('That was not a valid payment key: %s','my-calendar-submissions'),$reason)."</p></div>", $check[1], false );
			} else {
				$message = sprintf( "<div class='notice error'><p>".__('%d submissions remaining with this payment key.','my-calendar-submissions')."</p></div>",( $quantity-1 ) );
			}
		}
		if ( $check[0] ) {
			if ( !isset( $_POST['event_edit'] ) ) {
				$response = my_calendar_save( 'add', $check );
				$action = 'add';
			} else {
				$response = my_calendar_save( 'edit', $check, (int) $_POST['event_edit'] );	
				$action = 'edit';
			}
			$event_id = $response['event_id'];
			$response = $response['message'];
			$event = mc_get_event_core( $event_id );
			$post_id = $event->event_post;
			set_post_thumbnail( $post_id, $attach_id ); 
			if ( $message != '' ) { 
				$response .= " $message";
			}
			
			$return = array( $response, array(), true );
		} else {
			$return = array( $check[3], $check[1], false );
			
			return $return;
		}
		if ( $event_id ) {
			$name = $post['mcs_name'];
			$email = $post['mcs_email'];
			if ( mcs_payment_required() ) {
				// Note: payments will be processed on both submissions & on edits.
				mcs_update_key_quantity( $key, $quantity );
			}
			// if no errors and NOT SPAM send notifications.
			if ( mc_event_is_spam( $event_id ) ) {
				do_action( 'mcs_spam_submission', $name, $email, $event_id );
			} else {
				do_action( 'mcs_complete_submission', $name, $email, $event_id, $action );
			}
		}
		return $return;
	} else {
		return false;
	}
}

function mc_event_is_spam( $event_id ) {
	$event = mc_get_event_core( $event_id );
	$flag = $event->event_flagged;
	if ( $flag == 1 ) {
		return true;
	}
	return false;
}

function mcs_update_key_quantity( $key, $quantity ) {
	global $wpdb;
	$data = array( 'quantity'=>$quantity-1 );
	$formats = array( '%d' );
	$result = $wpdb->update(
		my_calendar_payments_table(),
		$data,
		array( 'hash'=>$key ),
		$formats,
		'%s' );	
	return;
}

function mcs_check_key( $key ) {
	if ( !$key ) { return false; }
	global $wpdb;
	$sql = "SELECT quantity FROM ".my_calendar_payments_table()." WHERE hash = '$key' AND status = 'Completed'";
	$quantity = $wpdb->get_var($sql);
	if ( !$quantity || $quantity === 0 ) { 
		return false; 
	} else {
		return $quantity;
	}
}

function mcs_key_status( $key ) {
	if ( !$key ) { return false; }
	global $wpdb;
	$sql = "SELECT quantity,status FROM ".my_calendar_payments_table()." WHERE hash = '$key'";
	$key = $wpdb->get_row($sql);
	if ( !$key ) { 
		$return = __('That payment key does not exist','my-calendar-submissions'); 
	} else if ( $key->quantity == 0 ) {
		$return = __('Your payment key has been used up!','my-calendar-submissions');
	} else if ( $key->status != 'Completed' ) {
		$return = sprintf( __('Your payment key status is "%s".','my-calendar-submissions'),$key->status );
	}
	return $return;
}

function mcs_notify_admin( $name, $email, $event_id, $action ) {
	if ( preg_match( '/\s/', $name ) ) {
		@list($fname,$lname) = preg_split('/\s+(?=[^\s]+$)/', $name, 2); 
	} else {
		$fname = $name; $lname = '';
	}
	$event = mc_get_event_core( $event_id );
	$array = array ( 
		'first_name'  => $fname,
		'last_name'   => $lname,
		'email'       => $email, 
		'title'       => $event->event_title, 
		'date'        => $event->event_begin, 
		'time'        => $event->event_time,
		'description' => $event->event_desc,
		'short'       => $event->event_short,
		'image'       => $event->event_image,
		'url'         => $event->event_link,
		'location'    => $event->event_label,
		'street'      => $event->event_street,
		'city'        => $event->event_city,
		'phone'       => $event->event_phone,
		'blogname'    => get_option( 'blogname' ),
		'edit_link'   => admin_url( "admin.php?page=my-calendar&mode=edit&event_id=$event_id" )
	);
	
	// if event is flagged as spam, don't send email notification.
	// filter allows you to disable email notifications for various custom reasons.
	$dont_send_email = apply_filters( 'mcs_dont_send_admin_email', false, $event_id, $array );
	if ( $event->event_approved == 1 && get_option( 'mcs_dont_send_admin_email' ) == 'true' ) {
		$dont_send_email = true;
	}
	if ( $event->event_flagged == 1 || $dont_send_email == true ) {
		return;
	} else {
		mcs_save_author( $fname, $lname, $email, $event );
		
		$subject = ( get_option( 'mcs_subject' ) == '' ) ? 'New event on {blogname}' : get_option( 'mcs_subject' );
		$edit_subject = ( get_option( 'mcs_edit_subject' ) == '' ) ? 'Edited event on {blogname}' : get_option( 'mcs_edit_subject' );
		$message = ( get_option( 'mcs_response' ) == '' ) ? 'New event from {first_name} {last_name}: {title}, {date}, {time}. Approve or reject this event: {edit_link}' : get_option( 'mcs_response' );
		if ( $action = 'edit' ) {
			$subject = jd_draw_template( $array, $edit_subject );			
		} else {
			$subject = jd_draw_template( $array, $subject );
		}
		$message = jd_draw_template( $array, $message );
		if ( get_option( 'mcs_html_email' ) == 'true' ) {
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
		}
		$mcs_to = ( get_option( 'mcs_to' ) == '' ) ? get_bloginfo( 'admin_email' ) : get_option( 'mcs_to' );
		if ( strpos( $mcs_to, ',' ) !== false ) {
			// remove any extra spaces
			$mcs_to = array_map( 'trim', explode( ',', $mcs_to ) );
			$mcs_to = implode( ',', $mcs_to );
		}

		$headers = array( "From: \"$name\" <$email>" );
		$headers = apply_filters( 'mcs_notify_admin_headers', $headers );
		$mail = wp_mail( $mcs_to, $subject, $message, $headers );
		if ( get_option( 'mcs_html_email' ) == 'true' ) {
			remove_filter( 'wp_mail_content_type',create_function( '', 'return "text/html";' ) );
		}
	}
}

function mcs_submit_url( $event_id = false ) {
	$submit_id = get_option( 'mcs_submit_id' );
	$url = false;	
	if ( $submit_id ) {
		$url = get_permalink( $submit_id );
		if ( $url && $event_id ) {
			$url = add_query_arg( "mcs_id", $event_id, $url );
		} 
	}
	
	return apply_filters( 'mcs_submit_url', $url, $event_id );
}

function mcs_notify_submitter( $name, $email, $event_id, $action ) {
	if ( preg_match( '/\s/', $name ) ) {
		@list($fname,$lname) = preg_split('/\s+(?=[^\s]+$)/', $name, 2); 
	} else {
		$fname = $name; $lname = '';
	}
	$event = mc_get_event_core( $event_id );
	$array = array ( 
		'first_name'  => $fname,
		'last_name'   => $lname,
		'email'       => $email, 
		'title'       => $event->event_title, 
		'date'        => $event->event_begin, 
		'time'        => $event->event_time,
		'description' => $event->event_desc,
		'short'       => $event->event_short,
		'image'       => $event->event_image,
		'url'         => $event->event_link,
		'location'    => $event->event_label,
		'street'      => $event->event_street,
		'city'        => $event->event_city,
		'phone'       => $event->event_phone,
		'blogname'    => get_option('blogname'),
		'edit_link'   => mcs_submit_url( $event_id )		
	);
	// if event is flagged as spam, don't send email notification.
	// filter allows you to disable email notifications for various custom reasons.
	$dont_send_email = apply_filters( 'mcs_dont_send_submitter_email', false, $event_id, $array );
	if ( $event->event_approved == 1 && get_option( 'mcs_dont_send_submitter_email' ) == 'true' ) {
		$dont_send_email = true;
	}
	if ( $event->event_flagged == 1 || $dont_send_email == true ) {
		return;
	} else {
		$subject = ( get_option( 'mcs_confirmation_subject' ) == '' ) ? 'New event on {blogname}' : get_option( 'mcs_confirmation_subject' );
		$edit_subject = ( get_option( 'mcs_edit_confirmation_subject' ) == '' ) ? 'Edited event on {blogname}' : get_option( 'mcs_edit_confirmation_subject' );
		$message = ( get_option( 'mcs_confirmation' ) == '' ) ? 'Thanks for proposing a new event, {first_name} {last_name}! {title}, {date}, {time}' : get_option( 'mcs_confirmation' );
		
		if ( $action = 'edit' ) {
			$subject = $edit_subject;			
		}	
		$subject = jd_draw_template( $array, $subject );
		$message = jd_draw_template( $array, $message );
		$blogname = get_option('blogname');
		$from = ( get_option( 'mcs_from' ) == '' ) ? get_bloginfo( 'admin_email' ) : get_option( 'mcs_from' );	
		if ( get_option( 'mcs_html_email' ) == 'true' ) {
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
		}
		
		$headers = array( "From: $from" );
		$headers = apply_filters( 'mcs_notify_admin_headers', $headers );		
		$mail = wp_mail( $email, $subject, $message, $headers );
		if ( get_option( 'mcs_html_email' ) == 'true' ) {
			remove_filter( 'wp_mail_content_type',create_function( '', 'return "text/html";' ) );
		}
	}	
}

add_filter( 'mc_before_event_form', 'mcs_show_author', 10, 2 );
function mcs_show_author( $content, $event_id ) {
	if ( $event_id ) {
		$event   = mc_get_event_core( $event_id );
		$post_id = $event->event_post;
		$author  = get_post_meta( $post_id, '_submitter_details', true );
		if ( is_array( $author ) ) {
			$fname = ( isset( $author['first_name'] ) ) ? $author['first_name'] : '';
			$lname = ( isset( $author['last_name'] ) ) ? $author['last_name'] : '';
			$email = ( isset( $author['email'] ) && is_email( $author['email'] ) ) ? $author['email'] : '';
			
			$return = ( $email != '' ) ? "<a href='mailto:$email'>$fname $lname</a>" : "$fname $lname";
			$return = sprintf ( __( 'Event submitted by %s', 'my-calendar-submissions' ), $return );
			$content .= "<p class='submitter'>$return</p>";
		}
		
		
	}
	
	return $content;
}

/**
 * Save public submitter data in event post
 */
function mcs_save_author( $fname, $lname, $email, $event ) {
	$post_ID = $event->event_post;
	if ( ! $post_ID ) {
		$post_ID = mc_event_post( 'add', $_POST, $event->event_id );		
	}
	$add = array( 'first_name'=>$fname, 'last_name'=>$lname, 'email'=>$email );
	
	add_post_meta( $post_ID, '_submitter_details', $add );
}

function my_calendar_payments_table() {
	$option = (int) get_site_option('mc_multisite');
	$choice = (int) get_option('mc_current_table');	
	switch ($option) {
		case 0:return MY_CALENDAR_PAYMENTS_TABLE;break;
		case 1:return MY_CALENDAR_GLOBAL_PAYMENTS_TABLE;break;
		case 2:return ($choice==1)?MY_CALENDAR_GLOBAL_PAYMENTS_TABLE:MY_CALENDAR_PAYMENTS_TABLE;break;
		default:return MY_CALENDAR_PAYMENTS_TABLE;
	}
}

function mcs_user_can_submit_events( ) {
	switch( get_option('mcs_criteria') ) {
		case 1: return true;
		break;
		case 2: if ( is_user_logged_in() ) { return true; }
		break;
		case 3: if ( current_user_can( 'mc_add_events' ) ) { return true; }
		break;
		default: return false;
	}
}

function mcs_generator_tab() {
	echo "<li><a href='#mc_submit'>".__( 'Submissions','my-calendar' )."</a></li>";
}

function mcs_generator_tab_content() {
	return "
	<div class='wptab mc_submit' id='mc_submit' aria-live='assertive'>".
		mcs_generator()
	."</div>";
}

function mcs_shortcode_generator( $output, $post ) {
	if ( $post['shortcode'] == 'mcs' ) {
		$fields = $string = '';
		$locations = 'none';
		foreach ( $post as $key => $value ) {
			switch ( $key ) {
				case 'generator' :
				case 'shortcode' :
				break;
				case 'fields' :
					foreach ( $value as $k => $v ) {
						if ( $k == 'event_title' && $v['label'] == '' || $k == 'event_date' && $v['label'] == '' || $k == 'event_time' && $v['label'] == '' ) {
						} else if ( $v['label'] == '' && ( isset( $v['active'] ) && $v['active'] == 'on' ) ) {
							$fields .= "$k,";
						} else if ( isset( $v['active'] ) && $v['active'] == 'on' ) {
							$fields .= "$k=$v[label],";
						}
					}
					$fields = substr( $fields, 0, -1 );
					if ( $fields != '' ) {
						$string .= ' fields="'.$fields.'"';
					}
				break;
				case 'categories' :
					if ( isset( $post['categories'] ) ) {
						$string .= " categories='1'";
					}
				break;
				case 'locations' :
					if ( isset( $post['locations'] ) && in_array( $post['locations'], array( 'none','either','choose','enter' ) ) ) {
						$string .= " locations='".$post['locations']."'";
						$locations = $post['locations'];
					}
				break; 
				case 'category' :
					if ( isset( $post['category'] ) ) {
						$cat = (int) $post['category'];
						$string .= " category='$cat'";
					}
				break;
				case 'location' :
					if ( isset( $post['location'] ) ) {
						$cat = (int) $post['location'];
						$string .= " location='$cat'";
					}				
				break; 
				case 'location_fields' :
					if ( $locations == 'enter' || $locations == 'either' ) {
						foreach ( $value as $k => $v ) {
							if ( $v['label'] == '' && $v['active'] == 'on' ) {
								$locs .= "$k,";
							} else if ( $v['active'] == 'on' ) {
								$locs .= "$k=$v[label],";
							}
						}
						$locs = substr( $locs, 0, -1 );
						if ( $locs != '' ) {
							$string .= ' location_fields="'.$locs.'"';
						}
					}
				break;
				default:break;
			}
		}
		$output = "submit_event$string";
	}
	
	return $output;
}

function mcs_generate_fields() {
	$standard_fields = array( 'event_title','event_date','event_time','end_date','end_time','mcs_name','mcs_email','event_host','event_recurring','description','short_description','access', 'event_link','event_image', 'registration' );
	$output = "<table class='widefat wp-list-table'><caption>".__('Event Field Settings','my-calendar-submissions')."</caption><thead><tr><th>".__('Enable Field','my-calendar-submissions')."</th><th>".__('Label','my-calendar-submissions' )."</th></tr></thead><tbody>";	
	foreach( $standard_fields as $field ) {
		$field_title = ucfirst( str_replace( '_', ' ', str_replace( array( 'event_','mcs_' ), '', $field ) ) );
		if ( $field == 'event_recurring' || $field == 'registration' ) { $disabled = 'disabled="disabled" placeholder="Custom Label Not available"'; } else { $disabled = ''; }
		if ( $field == 'event_title' || $field == 'event_date' || $field == 'event_time' || $field == 'mcs_name' || $field == 'mcs_email' ) { 
			$required = "<input type='hidden' value='on' name='fields[$field][active]' id='$field' />(".__('Required','my-calendar-submissions').")"; 
		} else { 
			$required = "<input type='checkbox' value='on' name='fields[$field][active]' id='$field' />"; 
		}
		$output .= "<tr><td>$required <label for='$field'>$field_title</label></td><td><label class='screen-reader-text' for='label_$field'>".sprintf( __('%s label','my-calendar-submissions'), $field_title )."</label> <input $disabled type='text' name='fields[$field][label]' id='label_$field' /></td></tr>";
	}
	$output .= "</tbody></table>";
	$output .= "<p><input type='checkbox' name='categories' id='categories' value='on' /> <label for='categories'>".__('Enable Category Dropdown','my-calendar-submissions' )."</label></p>";
	$output .= "<p><label for='category'>".__('Select default category','my-calendar-submissions')."</label> <select name='category' id='category'>".mc_category_select()."</select></p>";
	$output .= "<p><label for='locations'>".__('Enable Location Options','my-calendar-submissions' )."</label> 
		<select name='locations' id='locations'>
			<option value='none'>".__('User cannot enter locations','my-calendar-submissions')."</option>
			<option value='choose'>".__('User can choose locations from a dropdown','my-calendar-submissions')."</option>
			<option value='enter'>".__('User can enter new locations','my-calendar-submissions')."</option>
			<option value='either'>".__('User can either enter new locations or select an existing location','my-calendar-submissions')."</option>
		</select>
		</p>";
	$output .= "<p><label for='location'>".__('Select default location','my-calendar-submissions')."</label> <select name='location' id='location'>".mc_location_select()."</select></p>";
	$location_fields = array( 'event_label','street','street2','phone','city','state','zip','region','country','url','gps' );
	
	$output .= "<table class='widefat wp-list-table'><caption>".__('Location Field Settings','my-calendar-submissions')."</caption><thead><tr><th>".__('Enable Location Field','my-calendar-submissions')."</th><th>".__('Label','my-calendar-submissions' )."</th></tr></thead><tbody>";	
	foreach( $location_fields as $field ) {
		$field_title = ucfirst( str_replace( '_', ' ', str_replace( array( 'event_','mcs_' ), '', $field ) ) );
		if ( $field == 'event_gps' ) { $disabled = 'disabled="disabled" placeholder="Custom Label Not available"'; } else { $disabled = ''; }
		$output .= "<tr><td><input type='checkbox' value='on' name='location_fields[$field][active]' id='$field' /> <label for='$field'>$field_title</label></td><td><label class='screen-reader-text' for='label_$field'>".sprintf( __('%s label','my-calendar-submissions'), $field_title )."</label> <input $disabled type='text' name='location_fields[$field][label]' id='label_$field' /></td></tr>";
	}
	$output .= "</tbody></table>";
	
	return $output;
}

function mcs_generator() { 
/* 
'fields' => 'event_title,event_date, event_time, end_date,end_time, mcs_name, mcs_email, event_recurring, description,short_description,access, event_link,event_image, categories, registration, locations',
'categories' => 1, (see above)
'locations' => 'either|none|choose|enter', (see above)
'category' => 1, // select a category
'location' => 1, // select a location
'location_fields'=>'event_label=,street,street2,phone,city,state,zip,region,country,url,gps'
*/
	$include_fields = mcs_generate_fields();
	$output = '<form action="'.admin_url('admin.php?page=my-calendar-help').'" method="POST" id="my-calendar-generate">
			<div><input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-generator' ) . '"/></div>		
		<fieldset> 
		<legend><strong>'.__( 'My Calendar: Submissions','my-calendar-submissions' ).'</strong></legend>
			<div id="mc-generator" class="generator">
				<input type="hidden" name="shortcode" value="mcs" />'.
				$include_fields
			.'</div>
		</fieldset>
		<p>
		<input type="submit" class="button-primary" name="generator" value="'.__('Generate Shortcode', 'my-calendar').'" />
		</p>
	</form>';
	
	return $output;
}
