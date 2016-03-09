<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Create a post when an event is created.
 */

add_action( 'mc_save_event', 'my_event_post', 20, 3 );
function my_event_post( $action, $data, $new_event ) {
	if ( ! get_option( 'mcs_create_post' ) == 'true' ) {
		return;
	}
	// if the event save was successful.
	$options = get_option( 'mcs_event_post' );
	switch( $options['content'] ) {
		case 'event': $content = "[my_calendar_event event='$new_event' template='details' list='']"; break;
		case 'custom': $content = ( isset( $_POST['mcs_custom_content'] ) ) ? $_POST['mcs_custom_content'] : ''; break;
		default: $content = $data['event_desc']; break;
	}
	
	switch( $options['title'] ) {
		case 'custom': $title = sprintf( $options['custom_title'], $data['event_title'] ); break;
		default: $title = $data['event_title'];		
	}
	
	switch( $options['author'] ) {
		case 'host': $auth = $data['event_host']; break;
		default: $auth = $data['event_author'];			
	}
	
	$status = ( isset( $options['status'] ) ) ? $options['status'] : 'publish';
	
	switch( $options['timestamp'] ) {
		case 'event': $date = strtotime( $data['event_begin'] . ' ' . $data['event_time'] ); break;
		case 'custom': 
			if ( $options['custom_time'] < 0 ) {
				$date = strtotime( $data['event_begin'] . ' ' . $data['event_time'] ) - intval( $options['custom_time'] );
			} else {
				$date = current_time( 'timestamp' ) + intval( $options['custom_time'] );				
			}
			break;
		default: $date = current_time( 'timestamp' );		
	}
	
	$type = ( isset( $options['post_type'] ) ) ? $options['post_type'] : 'post';
	
	if ( $action == 'add' && !( isset( $_POST['event_source'] ) && $_POST['event_source'] == 'post' ) ) {
		$post_status = 'publish';
		$type = 'post';
		$my_post = array(
			'post_title' => $title,
			'post_content' => $content,
			'post_status' => $status,
			'post_author' => $auth,
			'post_name' => sanitize_title( $title ),
			'post_date' => date( 'Y-m-d H:i:s', $date ),
			'post_type' => $type
		);
		$post_id = wp_insert_post( $my_post );
		$attachment_id = ( isset( $_POST['event_image_id'] ) && is_numeric( $_POST['event_image_id'] ) ) ? $_POST['event_image_id'] : false;
		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}		
		$category = mc_get_category_detail( $data['event_category'], 'category_name' );
		$taxonomy = 'category';
		wp_set_post_tags( $post_id, $category );
		wp_set_post_terms( $post_id, $category, $taxonomy );
		add_post_meta( $post_id, '_mc_event_id', $new_event );
		$event = mc_get_event_core( $new_event );
		$event_id = $event->event_post;
		add_post_meta( $event_id, '_mc_related_post', $post_id );
		do_action( 'mcp_post_published', $post_id, $event );
		
		wp_publish_post( $post_id );
	}
}


add_filter( 'mcs_custom_settings_update', 'mcs_event_posts_update', 10, 2 );
function mcs_event_posts_update( $value, $post ) {
	// save settings
	if ( isset( $_POST['event_posts_settings'] ) ) {
		$options = get_option( 'mcs_event_post' );
		if ( isset( $_POST['mcs_create_post'] ) ) {
			update_option( 'mcs_create_post', 'true' );
		} else {
			delete_option( 'mcs_create_post' );
		}
		$options['content'] = $_POST['mcs_content'];
		$options['title'] = $_POST['mcs_title'];
		$options['custom_title'] = $_POST['mcs_custom_title'];
		$options['author'] = $_POST['mcs_author'];
		$options['status'] = $_POST['mcs_status'];
		$options['timestamp'] = $_POST['mcs_timestamp'];
		$options['custom_time'] = $_POST['mcs_custom_time'];
		$options['type'] = $_POST['mcs_type'];
		update_option( 'mcs_event_post', $options );
		
		return "<div class='notice updated'><p>" . __( 'Event Post Settings Updated', 'my-calendar-submissions' ) . "</p></div>";
	}
	
	return $value;
}

add_filter( 'mcs_settings_tabs', 'mcs_event_posts_tabs' );
function mcs_event_posts_tabs( $tabs ) {
	$tabs['event_posts'] = __( 'Blog New Events', 'my-calendar-submissions' );
	
	return $tabs;
}

add_filter( 'mcs_settings_panels', 'mcs_event_posts_settings' );
function mcs_event_posts_settings( $panels ) {
	$mcs_create_post = get_option( 'mcs_create_post' );
	$options = get_option( 'mcs_event_post' );
	$mcs_custom_title = ( isset( $options['custom_title'] ) ) ? $options['custom_title'] : 'New Event: %s';
	$mcs_custom_time = ( isset( $options['custom_time'] ) ) ? $options['custom_time'] : 3600;
	$diff = human_time_diff( current_time( 'timestamp' ), current_time( 'timestamp' ) + $mcs_custom_time );
	if ( $mcs_custom_time < 0 ) {
		$diff = sprintf( __( '<strong>%s</strong> before event happens', 'my-calendar-submissions' ), $diff );
	} else {
		$diff = sprintf( __( '<strong>%s</strong> after event is published', 'my-calendar-submissions' ), $diff );
	}
	
	if ( $mcs_create_post == 'true' ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$types = '';
		foreach ( $post_types as $type ) {
			if ( $type->name == 'mc-events' ) {
				continue;
			}
			$types .= "<option value='$type->name'" . selected( $options['type'], $type->name, false ) . ">" . $type->labels->name . "</option>";
		}
		$controls = "
		<fieldset>
			<legend>" . __( 'Events as Posts Settings', 'my-calendar-submissions' ) . "</legend>
			<p>
				<label for='mcs_content'>" . __( 'Blog Post Content', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_content' id='mcs_content'>
					<option value='default'>" . __( 'Event Description', 'my-calendar-submissions' ) . "</option>
					<option value='event'" . selected( $options['content'], 'event', false ) . ">" . __( 'Full Event Template', 'my-calendar-submissions' ) . "</option>
					<option value='custom'" . selected( $options['content'], 'custom', false ) . ">" . __( 'Custom Content added at event creation', 'my-calendar-submissions' ) . "</option>
				</select>
			</p>
			<p>
				<label for='mcs_title'>" . __( 'Blog Post Title', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_title' id='mcs_title'>
					<option value='default'>" . __( 'Event Title', 'my-calendar-submissions' ) . "</option>
					<option value='custom'" . selected( $options['title'], 'custom', false ) . ">" . __( 'Custom Format', 'my-calendar-submissions' ) . "</option>
				</select>
			</p>
			<p>
				<label for='mcs_custom_title'>" . __( 'Custom Title Format', 'my-calendar-submissions' ) . "</label>
				<input type='text' name='mcs_custom_title' id='mcs_custom_title' value='" . esc_attr( $mcs_custom_title ) . "' />
			</p>
			<p>
				<label for='mcs_author'>" . __( 'Blog Post Author', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_author' id='mcs_author'>
					<option value='default'>" . __( 'Event Author', 'my-calendar-submissions' ) . "</option>
					<option value='host'" . selected( $options['author'], 'host', false ) . ">" . __( 'Event Host', 'my-calendar-submissions' ) . "</option>
				</select>
			</p>
			<p>
				<label for='mcs_status'>" . __( 'Blog Post Status', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_status' id='mcs_status'>
					<option value='publish'>" . __( 'Published', 'my-calendar-submissions' ) . "</option>
					<option value='pending'" . selected( $options['status'], 'pending', false ) . ">" . __( 'Pending', 'my-calendar-submissions' ) . "</option>
					<option value='draft'" . selected( $options['status'], 'draft', false ) . ">" . __( 'Draft', 'my-calendar-submissions' ) . "</option>
					<option value='private'" . selected( $options['status'], 'private', false ) . ">" . __( 'Private', 'my-calendar-submissions' ) . "</option>
					<option value='future'" . selected( $options['status'], 'future', false ) . ">" . __( 'Future', 'my-calendar-submissions' ) . "</option>
				</select>
			</p>
			<p>
				<label for='mcs_type'>" . __( 'Post Type', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_type' id='mcs_type'>
					<option value='post'>" . __( 'Blog Post', 'my-calendar-submissions' ) . "</option>
					$types
				</select>
			</p>			
			<p>
				<label for='mcs_timestamp'>" . __( 'Post Publish Date', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_timestamp' id='mcs_timestamp'>
					<option value='default'>" . __( 'Publication Date', 'my-calendar-submissions' ) . "</option>
					<option value='event'" . selected( $options['timestamp'], 'event', false ) . ">" . __( 'Event Date', 'my-calendar-submissions' ) . "</option>
					<option value='custom'" . selected( $options['timestamp'], 'custom', false ) . ">" . __( 'Custom Date', 'my-calendar-submissions' ) . "</option>
				</select>
			</p>
			<p>
				<label for='mcs_custom_time'>" . __( 'Custom Post Time (in seconds before event date)', 'my-calendar-submissions' ) . "</label>
				<input type='text' name='mcs_custom_time' id='mcs_custom_time' value='" . esc_attr( $mcs_custom_time ) . "' aria-describedby='mcs_custom_time_diff' /> <span id='mcs_custom_time_diff'>$diff</span>
			</p>			
		</fieldset>
		";
	} else {
		$controls = "
		<div>
			<input type='hidden' name='mcs_content' value='" . esc_attr( $options['content'] ) . "' />
			<input type='hidden' name='mcs_title' value='" . esc_attr( $options['title'] ) . "' />
			<input type='hidden' name='mcs_custom_title' value='" . esc_attr( $mcs_custom_title ) . "' />
			<input type='hidden' name='mcs_author' value='" . esc_attr( $options['author'] ) . "' />
			<input type='hidden' name='mcs_status' value='" . esc_attr( $options['status'] ) . "' />
			<input type='hidden' name='mcs_timestamp' value='" . esc_attr( $options['timestamp'] ) . "' />
			<input type='hidden' name='mcs_custom_time' value='" . esc_attr( $mcs_custom_time ) . "' />
			<input type='hidden' name='mcs_type' value='" . esc_attr( $options['type'] ) . "' />
		</div>";
	}
	$panels['event_posts'] = '
		<h3>' . __( 'Post New Events as Posts', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">
			<p>
				<input type="checkbox" name="mcs_create_post" id="mcs_create_post" value="true" ' . checked( $mcs_create_post, 'true', false ) . '/> <label for="mcs_create_post">' . __( 'Copy new events as posts', 'my-calendar-submissions' ) . '</label>
			</p>'.
			$controls . '
			{submit}
		</div>';
	
	return $panels;
}

add_filter( 'mc_event_details', 'mcs_custom_content', 10, 4 );
add_action( 'mc_update_event_post', 'mcs_custom_content_save', 10, 4 );

function mcs_custom_content( $form, $has_data, $event, $context ) {
	if ( get_option( 'mcs_create_post' ) == 'true' ) {
		$options = get_option( 'mcs_event_post' );
		$custom_content = $options['content'];
		if ( $custom_content == 'custom' ) {
			if ( !( is_object( $event ) && $has_data ) ) {
				$form .= "<p><label for='mcs_custom_content'>" . __( 'Custom Content for Post', 'my-calendar-submissions' ) . "</label><br /><textarea name='mcs_custom_content' id='mcs_custom_content' rows='8' cols='60' class='widefat' /></textarea></p>";
			} else {
				$related = get_post_meta( $event->event_post, '_mc_related_post', true );
				$url = ( $related ) ? get_edit_post_link( $related ) : false;
				if ( $url ) {
					$form .= "<p><a href='$url'>" . __( 'Edit blog post associated with this event', 'my-calendar-submissions' ) . "</a></p>";
				}
			}
		}
	}
	
	return $form;
}

function mcs_custom_content_save( $post_id, $post, $data, $event_id ) {
	if ( get_option( 'mcs_create_post' ) == 'true' ) {
		$options = get_option( 'mcs_event_post' );
		$custom_content = $options['content'];
		if ( $custom_content == 'custom' ) {	
			$content = isset( $_POST['mcs_custom_content'] ) ? $_POST['mcs_custom_content'] : '';	
			update_post_meta( $post_id, '_mc_custom_content', esc_sql( $content ) );
		}
	}
}
