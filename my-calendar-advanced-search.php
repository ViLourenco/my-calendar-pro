<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'mcs_custom_settings_update', 'mcs_advanced_search_update', 10, 2 );
function mcs_advanced_search_update( $value, $post ) {
	// save settings
	$options = get_option( 'mcs_advanced_search' );
	if ( isset( $_POST['advanced_search_settings'] ) ) {
		$options['home'] = isset( $_POST['mcs_home'] ) ? $_POST['mcs_home'] : false;
		if ( !isset( $_POST['mcs_home'] ) || empty( $_POST['mcs_home'] ) ) {
			$page = mcs_generate_search_page( 'advanced-event-search' );
			$options['home'] = $page;
		}
		$options['date'] =  isset( $_POST['mcs_date'] ) ? true : false;
		$options['author'] =  isset( $_POST['mcs_author'] ) ? true : false;
		$options['host'] =  isset( $_POST['mcs_host'] ) ? true : false;
		$options['category'] =  isset( $_POST['mcs_category'] ) ? true : false;
		$options['location'] =  isset( $_POST['mcs_location'] ) ? true : false;
		$options['template'] = isset( $_POST['mcs_template'] ) ? $_POST['mcs_template'] : '';
		update_option( 'mcs_advanced_search', $options );
		
		$value .= "<div class='notice updated'><p>" . __( 'Advanced Search Settings Updated', 'my-calendar-submissions' ) . "</p></div>";
	}
	
	return $value;
}

add_filter( 'mcs_settings_tabs', 'mcs_advanced_search_tabs' );
function mcs_advanced_search_tabs( $tabs ) {
	$tabs['advanced_search'] = __( 'Advanced Search', 'my-calendar-submissions' );
	
	return $tabs;
}

function mcs_generate_search_page( $slug ) {
	global $current_user;
	$current_user = wp_get_current_user();
	if ( ! is_page( $slug ) ) {
		$page = array(
			'post_title'  => __( 'Advanced Event Search', 'my-calendar' ),
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_author' => $current_user->ID,
			'ping_status' => 'closed',
			'post_content' => '[advanced_search]'
		);
		$post_ID   = wp_insert_post( $page );
		$post_slug = wp_unique_post_slug( $slug, $post_ID, 'publish', 'page', 0 );
		wp_update_post( array( 'ID' => $post_ID, 'post_name' => $post_slug ) );
	} else {
		$post    = get_page_by_path( $slug );
		$post_ID = $post->ID;
	}
	$options = get_option( 'mcs_advanced_search' );
	$options['home'] = $post_ID;
	update_option( 'mcs_advanced_search', $options );
	
	return $post_ID;	
}

add_filter( 'mcs_settings_panels', 'mcs_advanced_search_settings' );
function mcs_advanced_search_settings( $panels ) {
	$options = ( is_array( get_option( 'mcs_advanced_search' ) ) ) ? get_option( 'mcs_advanced_search' ) : array();
	$defaults = array( 'home'=>'', 'date'=>true, 'author'=>'', 'host'=>'', 'category'=>'', 'location'=>'', 'template'=>'<strong>{date}</strong> {title} {details}' );
	$options = array_merge( $defaults, $options );
	$url = ( $options['home'] != '' && is_numeric( $options['home'] ) ) ? "<a href='" . get_edit_post_link( $options['home'] ) . "'>" . get_the_title( $options['home'] ) . "</a>" : __( 'Page will be generated on save', 'my-calendar-submissions' );
	
	$controls = "
	<p>
		<label for='mcs_home'>" . __( 'Advanced Search Page', 'my-calendar-submissions' ) . "</label>
		<input size='4' type='number' name='mcs_home' readonly id='mcs_home' aria-describedby='mcs_home_url' value='" . esc_attr( $options['home'] ) . "' /> <span id='mcs_home_url'>" . sprintf( __( 'Edit: %s', 'my-calendar-submissions' ), $url ) . "</span>
	</p>
	<fieldset>		
		<legend>" . __( 'Enabled Search Fields', 'my-calendar-submissions' ) . "</legend>		
		<ul>
			<li>
				<input type='checkbox' value='true' name='mcs_date' id='mcs_date' " . checked( $options['date'], true, false ) . " /> <label for='mcs_date'>" . __( 'Dates', 'my-calendar-submissions' ) . "</label>
			</li>
			<li>
				<input type='checkbox' value='true' name='mcs_author' id='mcs_author' " . checked( $options['author'], true, false ) . " /> <label for='mcs_author'>" . __( 'Author', 'my-calendar-submissions' ) . "</label>
			</li>
			<li>
				<input type='checkbox' value='true' name='mcs_host' id='mcs_host' " . checked( $options['host'], true, false ) . " /> <label for='mcs_host'>" . __( 'Host', 'my-calendar-submissions' ) . "</label>
			</li>
			<li>
				<input type='checkbox' value='true' name='mcs_category' id='mcs_category' " . checked( $options['category'], true, false ) . " /> <label for='mcs_category'>" . __( 'Category', 'my-calendar-submissions' ) . "</label>
			</li>
			<li>
				<input type='checkbox' value='true' name='mcs_location' id='mcs_location' " . checked( $options['location'], true, false ) . " /> <label for='mcs_location'>" . __( 'Location', 'my-calendar-submissions' ) . "</label>
			</li>			
		</ul>
	</fieldset>
	<p>
		<label for='mcs_template'>" . __( 'Search Results Template', 'my-calendar-submissions' ) . "</label>
		<textarea aria-describedby='mcs_template_help' class='widefat' cols='60' rows='12' name='mcs_template' id='mcs_template'>" . stripslashes( esc_attr( $options['template'] ) ) ."</textarea>
		<span id='mcs_template_help'>" . sprintf( __( 'See <a href="%s">templating help</a> for template assistance.', 'my-calendar-submissions' ), admin_url( 'admin.php?page=my-calendar-help#templates' ) ) . "</span>
	</p>";
	
	$panels['advanced_search'] = '
		<h3>' . __( 'Advanced Search', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">'.
			$controls . '
			{submit}
		</div>';
	
	return $panels;
}

add_filter( 'mc_advanced_search', 'mcs_advanced_search', 10, 2 );
function mcs_advanced_search( $sql, $query ) {
	$nonce = $query['_mcsnonce'];
	if ( !wp_verify_nonce($nonce, 'mcs-advanced-search' ) ) {
		die( 'Security Check Failed' );
	}
	
	$defaults = array( 
		'mc_from' => false,
		'mc_to' => false,	
		'mc_author' => false,
		'mc_host' => false,
		'mc_category' => false, 
		'mc_location' => false,
		'mcs' => false
	);
	$query = array_merge( $defaults, $query );
		
	$category = $query['mc_category'];
	$author   = $query['mc_author'];
	$host     = $query['mc_host'];
	$location = intval( $query['mc_location'] );
	$term     = $query['mcs'];
	$from     = ( $query['mc_from'] ) ? date( 'Y-m-d', strtotime( $query['mc_from'] ) ) : false;
	$to       = ( $query['mc_to'] ) ? date( 'Y-m-d', strtotime( $query['mc_to'] ) ) : false;
	
	if ( $location ) {
		global $wpdb;
		$mcdb    = $wpdb;
		$cur_loc = false;
		$sql     = "SELECT * FROM " . my_calendar_locations_table() . " WHERE location_id=$location";
		$cur_loc = $mcdb->get_row( $sql );
		
		$lvalue = $cur_loc->location_label;
	} else {
		$lvalue = '';
	}
	
	
	if ( !$from && !$to ) {
		$select_category = ( $category ) ? mc_select_category( $category ) : '';
		$limit_string    = ( $location ) ? mc_limit_string( false, 'event_label', $lvalue ) : '';
		$select_author   = ( $author ) ? mc_select_author( $author ) : '';
		$select_host     = ( $host ) ? mc_select_host( $host ) : '';
		$limits = $select_category . $limit_string . $select_author . $select_host;
		$search = mc_prepare_search_query( $term );
	} else {
		$search = array( 'category' => $category, 'author' => $author, 'host' => $host, 'ltype' => 'event_label', 'lvalue' => $lvalue, 'search' => $term, 'from' => $from, 'to' => $to );
	}
		
	return $search;
}

add_shortcode( 'advanced_search', 'mcs_advanced_search_form' );
function mcs_advanced_search_form( $atts, $content ) {
	$args = shortcode_atts( array(
		'date' => true,
		'author' => true,
		'host' => true,		
		'category' => true,
		'location' => true,
		'home' => ''
	), $atts, 'advanced_search' );
	
	$options = get_option( 'mcs_advanced_search' );
	
	if ( is_page( $options['home'] ) ) {
		$args = array_merge( $args, $options );
		$url = get_permalink( $args['home'] );
	} else {
		$url = $args['home'];
	}
	
	return mcs_search_form( $args, $url );
}

function mcs_search_form( $args = array(), $url = '' ) {
	$defaults = array( 
		'date' => false,
		'author' => false, 
		'host' => false,		
		'category' => false, 
		'location' => false
	);
	$args = array_merge( $defaults, $args );
	$mcnonce = wp_create_nonce( 'mcs-advanced-search' );
	if ( !$url || $url == '' ) {
		$url = ( get_option( 'mc_uri' ) != '' ) ? get_option( 'mc_uri' ) : home_url();
	}
	$mcs = ( isset( $_POST['mcs'] ) ) ? esc_attr( $_POST['mcs'] ) : '';
	
	
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
	
	$fields = '
		<div class="mc-advanced-search">'.
			$script
			.'<form action="' . apply_filters( 'mc_search_page', esc_url( $url ) ) . '" method="POST">
				<div>
					<input type="hidden" name="_mcsnonce" value="' . $mcnonce . '" />
				</div>
				<p>
					<label for="mcs">' . __( 'Search Events', 'my-calendar-submissions' ) . '</label>
					<input type="text" class="widefat" value="' . $mcs . '" name="mcs" id="mcs" />
				</p>';
	foreach( $args as $field => $active ) {
		if ( $active ) {
			$fields .= mcs_generate_search_field( $field );
		}
	}
	$fields .= "
				<p>
					<input type='submit' class='mc-button' value='" . __( 'Search Events', 'my-calendar-submissions' ) . "' />
				</p>
			</form>
		</div>";
			
	return $fields;
}

function my_calendar_getUsed( $type) {
	global $wpdb;
	$sql = "SELECT DISTINCT $type FROM  " . my_calendar_table();
	
	return $wpdb->get_results( $sql );
}

function mcs_select_user( $type = 'authors', $current = false ) {
	$field = ( $type == 'authors' ) ? 'event_author' : 'event_host';	
	$users = my_calendar_getUsed( $field );
	$select = '';
	foreach ( $users as $u ) {
		if ( $field == 'event_author' ) {
			$id = $u->event_author;
		} else {
			$id = $u->event_host;
		}
		$user = get_user_by( 'id', $id );
		$display_name = ( $user->display_name == '' ) ? $user->user_nicename : $user->display_name;
		$current_user = ( $current ) ? $current : get_current_user_id();
		if ( $id == $current ) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}
		$select .= "<option value='$id'$selected>$display_name</option>\n";
	}
	
	return $select;
}

function mcs_generate_search_field( $field ) {
	switch( $field ) {
		case 'author':
			$selected = ( isset( $_POST['mc_author'] ) ) ? intval( $_POST['mc_author'] ) : false;
			$select = mcs_select_user( 'authors', $selected );
			$return = '
				<p class="mc_author">
					<label for="mc_author">' . __( 'Author', 'my-calendar-submissions' ) . '</label>
					<select class="widefat" name="mc_author" id="mc_author">
						<option value="">' . __( 'All authors', 'my-calendar-submissions' ) . '</option>'.
						$select
					.'</select>
				</p>';
			break;
		case 'host':
			$selected = ( isset( $_POST['mc_host'] ) ) ? intval( $_POST['mc_host'] ) : false;		
			$select = mcs_select_user( 'hosts', $selected );		
			$return = '
				<p class="mc_host">
					<label for="mc_host">' . __( 'Host', 'my-calendar-submissions' ) . '</label>
					<select class="widefat" name="mc_host" id="mc_host">
						<option value="">' . __( 'All hosts', 'my-calendar-submissions' ) . '</option>'.
						$select
					.'</select>
				</p>';
			break;
		case 'category':
			$selected = ( isset( $_POST['mc_category'] ) ) ? intval( $_POST['mc_category'] ) : false;
			$select = mc_category_select( $selected );		
			$return = '
				<p class="mc_category">
					<label for="mc_category">' . __( 'Category', 'my-calendar-submissions' ) . '</label>
					<select class="widefat" name="mc_category" id="mc_category">
						<option value="">' . __( 'All categories', 'my-calendar-submissions' ) . '</option>'.
						$select
					.'</select>
				</p>';
			break;
		case 'location': 
			$selected = ( isset( $_POST['mc_location'] ) ) ? intval( $_POST['mc_location'] ) : false;		
			$select = mc_location_select( $selected );
			$return = '
				<p class="mc_location">
					<label for="mc_location">' . __( 'Location', 'my-calendar-submissions' ) . '</label>
					<select class="widefat" name="mc_location" id="mc_location">
						<option value="">' . __( 'All locations', 'my-calendar-submissions' ) . '</option>'.
						$select
					.'</select>
				</p>';
			break;
		case 'date':
			$selected = ( isset( $_POST['mc_from'] ) ) ? $_POST['mc_from'] : date( 'Y-m-d', current_time( 'timestamp' ) );					
			$return = '
				<p class="mc_from">
					<label for="mc_from">' . __( 'From', 'my-calendar-submissions' ) . '</label>
					<input type="date" class="widefat mc-date" value="' . esc_attr( $selected ) . '" name="mc_from" id="mc_from" />
				</p>';
			$selected = ( isset( $_POST['mc_to'] ) ) ? $_POST['mc_to'] : date( 'Y-m-d', strtotime( '+ 1 month' ) );
			$return .= '
				<p class="mc_to">
					<label for="mc_to">' . __( 'To', 'my-calendar-submissions' ) . '</label>
					<input type="date" class="widefat mc-date" value="' . esc_attr( $selected ) . '" name="mc_to" id="mc_to" />
				</p>';
			break;
		default: $return = '';
	}
	
	return $return;	
}

add_filter( 'mc_search_template', 'mcs_advanced_search_template', 10, 1 );
function mcs_advanced_search_template( $template ) {
	$options = get_option( 'mcs_advanced_search' );
	$temp = $options['template'];
	if ( $temp ) {
		$template = $temp;
	}
	
	return $template;
}

add_filter( 'mc_search_before', 'mcs_advanced_search_before', 10, 2 );
function mcs_advanced_search_before( $content, $search ) {
	$options = get_option( 'mcs_advanced_search' );

	if ( is_page( $options['home'] ) ) {
		$args = $options;
		$url = get_permalink( $options['home'] );	
		return "<div class='mc-advanced-search'><p><a href='#mcs'>" . __( 'Perform another search', 'my-calendar-submissions' ) . "</a><span class='dashicons 	dashicons-arrow-down-alt' aria-hidden='true'></span></p>";
	}
	return "<div class='md-advanced-search'>";
}

add_filter( 'mc_search_after', 'mcs_advanced_search_after', 10, 2 );
function mcs_advanced_search_after( $content, $search ) {
	$options = get_option( 'mcs_advanced_search' );
	
	if ( is_page( $options['home'] ) ) {
		$args = $options;
		$url = get_permalink( $options['home'] );
		return mcs_search_form( $args, $url ) . "</div>";		
	}
		
	return "</div>";
}

add_action( 'widgets_init', create_function( '', 'return register_widget("my_calendar_advanced_search");' ) );
class my_calendar_advanced_search extends WP_Widget {
	function __construct() {
		parent::__construct( false, $name = __( 'My Calendar: Advanced Event Search', 'my-calendar' ), array( 'customize_selective_refresh' => true ) );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $args );
		$widget_title = ( $widget_title != '' ) ? $before_title . $widget_title . $after_title : '';
		$options = get_option( 'mcs_advanced_search' );
		$widget_url = ( isset( $instance['url'] ) ) ? $instance['url'] : get_permalink( $options['home'] );;
		$widget_args = ( isset( $instance['args'] ) ) ? $instance['args'] : array();
		$defaults = array( 
			'date' => false,
			'author' => false, 
			'host' => false,		
			'category' => false, 
			'location' => false
		);
		$atts = array_merge( $defaults, $widget_args );
		echo $before_widget;
		echo ( $instance['title'] != '' ) ? $widget_title : '';
		echo mcs_search_form( $atts, $widget_url );
		echo $after_widget;
	}

	function form( $instance ) {
		$options = get_option( 'mcs_advanced_search' );		
		$widget_title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_url = ( isset( $instance['url'] ) ) ? $instance['url'] : get_permalink( $options['home'] );
		$widget_args = ( isset( $instance['args'] ) ) ? $instance['args'] : array();
		$defaults = array( 
			'date' => true,
			'author' => true, 
			'host' => false,		
			'category' => true, 
			'location' => false
		);
		$widget_args = array_merge( $defaults, $widget_args );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>
				:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php esc_attr_e( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Search Results Page', 'my-calendar' ); ?>
				:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'url' ); ?>"
			       name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_url( $widget_url ); ?>"/>
		</p>
		<ul>
	<?php
		foreach ( $widget_args as $key => $arg ) {
				$checked = ( $arg == 'true' ) ? ' checked="checked"' : '';
			?>	
				<li><input type='checkbox' name='<?php echo $this->get_field_name( 'args' ) . "[$key]" ?>' id='<?php echo $this->get_field_id( 'args' ) . "_$key" ?>' value='true' <?php echo $checked; ?> /><label for='<?php echo $this->get_field_id( 'args' ) . "_$key" ?>'><?php echo mcs_map_label( $key ); ?></label></li>
			<?php
		}
	?>	
		</ul>
	<?php
	}

	function update( $new, $old ) {
		$instance          = $old;
		$instance['title'] = wp_kses_post( $new['title'] );
		$instance['url']   = esc_url_raw( $new['url'] );
		$instance['args']  = $new['args'];

		return $instance;
	}
}

function mcs_map_label( $key ) {
	switch( $key ) {
		case 'date' : $label = __( 'Dates', 'my-calendar-submissions' ); break;
		case 'author' : $label = __( 'Authors', 'my-calendar-submissions' ); break;
		case 'host' : $label = __( 'Hosts', 'my-calendar-submissions' ); break;
		case 'category' : $label = __( 'Categories', 'my-calendar-submissions' ); break;
		case 'location' : $label = __( 'Locations', 'my-calendar-submissions' ); break;
		default: $label = __( 'Undefined key', 'my-calendar-submissions' );
	}
	return $label;
}