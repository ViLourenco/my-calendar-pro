<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Create event from post
 */

add_action( 'admin_menu', 'mcs_add_outer_box' );

// begin add boxes
function mcs_add_outer_box() {
	$post_events = ( is_array( get_option( 'mcs_post_event_types' ) ) ) ? get_option( 'mcs_post_event_types' ) : array();
	if ( is_array( $post_events ) ) {
		foreach ( $post_events as $post_type ) {
			add_meta_box( 'mcs_add_event', __('My Calendar Event', 'my-calendar-submissions'), 'mcs_add_inner_box', $post_type, 'normal','high' );
		}
	}
}

function mcs_add_inner_box() {
	global $post;
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
		$event_id = get_post_meta( $post->ID, '_mc_generated_event', true );
		if ( $event_id ) {
			$url = admin_url( 'admin.php?page=my-calendar&mode=edit&event_id='.$event_id );
			$event = mc_get_first_event( $event_id );
			$content = "<p><strong>" . $event->event_title . '</strong><br />' . $event->event_begin . ' @ ' . $event->event_time . "</p>";
			if ( $event->event_label != '' ) {
				$content .= "<p>" . sprintf( __( '<strong>Location:</strong> %s', 'my-calendar-submissions' ), $event->event_label ) . "</p>";
			}
			$content .= "<p>" . sprintf( __( '<a href="%s">Edit event</a>.', 'my-calendar-submissions' ), $url ) . "</p>";
		} else {
			$content = mc_meta_box_form();
		}		
	} else {
		$content = mc_meta_box_form();
	}
	
	//wp_register_script( 'mcs-updater', plugins_url( '/js/jquery.mcs-updater.js', __FILE__ ), array( 'jquery' ) );
	//wp_enqueue_script( 'mcs-updater' );

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
		$( 'input.mc-datepicker' ).pickadate({
			monthsFull: mc_months,
			format: '$js_format',
			weekdaysShort: mc_days,
			selectYears: true,
			selectMonths: true,
			editable: true
		});
		$( 'input.mc-timepicker' ).pickatime({
			interval: 15,
			format: '$js_time_format',
			editable: true		
		});
	})
})(jQuery);
</script>";	
	echo '<div class="mcs_add_event">'.$script . $content.'</div>';
}

// Add settings panel.
add_filter( 'mcs_custom_settings_update', 'mcs_post_events_update', 10, 2 );
function mcs_post_events_update( $value, $post ) {
	// save settings
	if ( isset( $_POST['post_events_settings'] ) ) {
		if ( isset( $_POST['mcs_post_events'] ) ) {
			update_option( 'mcs_post_events', 'true' );
		} else {
			delete_option( 'mcs_post_events' );
		}
		if ( isset( $_POST['mcs_approve_from_post'] ) ) {
			update_option( 'mcs_approve_from_post', 'true' );		
		} else {
			delete_option( 'mcs_approve_from_post' );			
		}
		$post_events = isset( $_POST['mcs_post_event_types'] ) ? $_POST['mcs_post_event_types'] : array();
		update_option( 'mcs_post_event_types', $post_events );
		
		return "<div class='notice updated'><p>" . __( 'Post Event Settings Updated', 'my-calendar-submissions' ) . "</p></div>";
	}
	
	return $value;
}

add_filter( 'mcs_settings_tabs', 'mcs_post_events_tabs' );
function mcs_post_events_tabs( $tabs ) {
	$tabs['post_events'] = __( 'Events from Posts', 'my-calendar-submissions' );
	
	return $tabs;
}

add_filter( 'mcs_settings_panels', 'mcs_post_events_settings' );
function mcs_post_events_settings( $panels ) {
	$mcs_post_events = get_option( 'mcs_post_events' );
	$post_events = ( is_array( get_option( 'mcs_post_event_types' ) ) ) ? get_option( 'mcs_post_event_types' ) : array();
	if ( $mcs_post_events == 'true' ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$types = '';
		foreach ( $post_types as $type ) {
			if ( $type->name == 'mc-events' ) {
				continue;
			}
			$selected = ( in_array( $type->name, $post_events ) ) ? ' selected="selected"' : '';
			$types .= "<option value='$type->name'" . $selected . ">" . $type->labels->name . "</option>";
		}
		$controls = "
		<fieldset>
			<legend>" . __( 'Events as Posts Settings', 'my-calendar-submissions' ) . "</legend>
			<p>
				<label for='mcs_post_event_types'>" . __( 'Post from Post Types', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_post_event_types[]' id='mcs_post_event_types' multiple='multiple'>
					$types
				</select>
			</p>
			<p>
				<input type='checkbox' name='mcs_approve_from_post' id='mcs_approve_from_post' " . checked( get_option( 'mcs_approve_from_post' ), 'true', false ). " > <label for='mcs_approve_from_post'>" . __( 'Events from Posts are always unapproved.', 'my-calendar-submissions' ) . "</label> 
			</p>			
		</fieldset>
		";
	} else {
		$controls = "
		<div>";
			if ( is_array( $post_events ) ) {
				foreach ( $post_events as $post_type ) {
					$controls .= "<input type='hidden' name='mcs_post_event_types[]' value='" . esc_attr( $post_type ) . "' />";
				}
			}
		$controls .= "
		</div>";
	}
	$panels['post_events'] = '
		<h3>' . __( 'Post New Events from Posts', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">
			<p>
				<input type="checkbox" name="mcs_post_events" id="mcs_post_events" value="true" ' . checked( $mcs_post_events, 'true', false ) . '/> <label for="mcs_post_events">' . __( 'Post events from posts', 'my-calendar-submissions' ) . '</label>
			</p>'.
			$controls . '
			{submit}
		</div>';
	
	return $panels;
}


function mc_meta_box_form() {
	global $wpdb, $post;
	$has_data = false;
	$data = false;
	if ( get_option( 'mc_event_approve' ) != 'true' ) {
		$dvalue = 1;
	} else if ( current_user_can( 'mc_approve_events' ) ) {
		$dvalue = 1;
	} else {
		$dvalue = 0;
	}
	if ( get_option( 'mcs_approve_from_post' ) == 'true' ) {
		$dvalue = 0;
	}
	$event_desc = mc_show_block( 'event_desc', $has_data, $data, false );
	if ( $event_desc ) {
		$description = "<div class='event_description'>
							<label for='event_desc'>" . __( 'Event Description', 'my-calendar-submissions' ) . "</label><br />
							<textarea id='event_desc' class='event_desc' cols='80' rows='8' name='event_desc'></textarea>
						</div>";
	} else {
		$description = '';
	}
	$event_host = mc_show_block( 'event_host', $has_data, $data, false );
	$event_category = mc_show_block( 'event_category', $has_data, $data, false );
	$event_link = mc_show_block( 'event_link', $has_data, $data, false );
	$event_recurs = mc_show_block( 'event_recurs', $has_data, $data, false );
	$mc_datetime = apply_filters( 'mc_datetime_inputs', '', $has_data, $data, 'admin' );
	if ( mc_show_edit_block( 'event_location_dropdown' ) ) {
		$locs = $wpdb->get_results( "SELECT location_id,location_label FROM " . my_calendar_locations_table() . " ORDER BY location_label ASC" );
		if ( ! empty( $locs ) ) {
			$location = '<p>
			<label for="l_preset">' . __( 'Choose a preset location:', 'my-calendar' ). '</label> <select
				name="location_preset" id="l_preset">
				<option value="none"> --</option>';
				foreach ( $locs as $loc ) {
					if ( is_object( $loc ) ) {
						$location .= "<option value=\"" . $loc->location_id . "\">" . stripslashes( $loc->location_label ) . "</option>";
					}
				}
			$location .= '</select></p>';
		} else {
			$location = '<input type="hidden" name="location_preset" value="none" />';
		}
	} else {
		$location = '<input type="hidden" name="location_preset" value="none" />';
	}	
	$return = '
	<div>
		<input type="hidden" name="event_group_id" value="' . mc_group_id() . '" />
		<input type="hidden" name="event_action" value="add" />
		<input type="hidden" name="event_source" value="post" />
		<input type="hidden" name="event_nonce_name" value="' . wp_create_nonce( 'event_nonce' ) . '" />
	</div>
	<fieldset>
		<legend class="screen-reader-text">' . __( 'Event Details', 'my-calendar' ) . '</legend>
		<p>
			<label for="e_title">' . __( 'Event Title', 'my-calendar' ) . ' <span class="required">(required)</span></label><br/><input type="text" id="e_title" name="event_title" size="50" maxlength="255" value="" />
			<input type="hidden" value="' . $dvalue . '" name="event_approved" />
		</p>'
		. $description
		. $event_host
		. $event_category
		. $event_link
		.'
	</fieldset>
	<fieldset>
		<legend class="screen-reader-text">' . __( 'Event Date and Time', 'my-calendar' ). '</legend>
		<div id="e_schedule">'.
			$mc_datetime
		.'</div>
	</fieldset>'
	    . $event_recurs . 
	'<fieldset>
	<legend class="screen-reader-text">' . __( 'Event Location', 'my-calendar' ) . '</legend>'
		. $location
	.'</fieldset>';
	
	return $return;
}

add_action( 'save_post', 'mc_save_event_post' );
function mc_save_event_post( $id ) {
	$post_types = ( is_array( get_option( 'mcs_post_event_types' ) ) ) ? get_option( 'mcs_post_event_types' ) : array();
	$is_valid_type = ( !empty( $post_types ) && in_array( get_post_type( $id ), $post_types ) ) ? true : false;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $id ) || !$is_valid_type ) {
		return $id;
	}
	
	if ( isset( $_POST['event_nonce_name'] ) && isset( $_POST['event_source'] ) && $_POST['event_source'] == 'post' ) {
		if ( !isset( $_POST['event_title'] ) || empty( $_POST['event_title'] ) ) {
			return $id;
		}
		$post = $_POST;
		if ( isset( $_POST['event_desc'] ) ) {
			$post['content'] = $_POST['event_desc'];
		} else {
			$post['content'] = '';
		}
		$attach_id = get_post_thumbnail_id( $id );
		$featured_image = wp_get_attachment_url( $attach_id );
		if ( isset( $_POST['post_author_override'] ) ) {
			$_POST['event_author'] = intval( $_POST['post_author_override'] );
		} else {
			$_POST['event_author'] = get_current_user_id();
		}
		$check = mc_check_data( 'add', $post, 0 );
		if ( $check[0] ) {
			$response = my_calendar_save( 'add', $check );
			$event_id = $response['event_id'];
			$response = $response['message'];
			$event = mc_get_first_event( $event_id );
			$post_id = $event->event_post;
			set_post_thumbnail( $post_id, $attach_id );
			mc_update_data( $event_id, 'event_image', $featured_image, '%s' );
			update_post_meta( $id, '_mc_new_event', $response );
			update_post_meta( $id, '_mc_generated_event', $event_id );
		}
	}
	
	return $id;
}

add_action( 'admin_enqueue_scripts', 'mcs_metabox_scripts' );
function mcs_metabox_scripts() {
	global $current_screen;
	if ( $current_screen->base == 'post' ) {	
	$post_types = ( is_array( get_option( 'mcs_post_event_types' ) ) ) ? get_option( 'mcs_post_event_types' ) : array();
	$is_valid_type = ( !empty( $post_types ) && in_array( $current_screen->post_type, $post_types ) ) ? true : false;
		if ( $is_valid_type ) {
			wp_enqueue_script( 'pickadate', plugins_url( 'my-calendar/js/pickadate/picker.js' ) );
			wp_enqueue_script( 'mc-ajax', plugins_url( 'my-calendar/js/ajax.js' ) );
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
		}
	}
}