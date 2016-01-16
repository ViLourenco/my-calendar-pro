<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'mcs_custom_settings_update', 'mcs_responsive_update', 10, 2 );
function mcs_responsive_update( $value, $post ) {
	// save settings
	if ( isset( $_POST['responsive_settings'] ) ) {
		if ( isset( $_POST['mcs_responsive_mode'] ) ) {
			update_option( 'mcs_responsive_mode', 'true' );
		} else {
			delete_option( 'mcs_responsive_mode' );
		}
		$mcs_stylesheet = in_array( $_POST['mcs_stylesheet'], mcs_responsive_stylesheets() ) ? $_POST['mcs_stylesheet'] : 'twentyfifteen';
		$mcs_responsive_setup = ( $_POST['mcs_responsive_setup'] == '1' ) ? '1' : '2';
		update_option( 'mcs_stylesheet', $mcs_stylesheet );
		update_option( 'mcs_responsive_setup', $mcs_responsive_setup );
		
		return "<div class='notice updated'><p>" . __( 'Responsive Mode Updated', 'my-calendar-submissions' ) . "</p></div>";
	}
	
	return $value;
}

function mcs_responsive_stylesheets() {
	$sheets = array( 
		'TwentyFifteen' => 'twentyfifteen', 
		'Blue' => 'blue', 
		'Green' => 'green', 
		'Light' => 'light',
		'Dark' => 'dark', 
		'Basic' => 'basic' 
	);
	
	return apply_filters( 'mcs_responsive_stylesheets', $sheets );
}

add_filter( 'mcs_settings_tabs', 'mcs_responsive_tabs' );
function mcs_responsive_tabs( $tabs ) {
	$tabs['responsive'] = __( 'Responsive', 'my-calendar-submissions' );
	
	return $tabs;
}

add_filter( 'mcs_settings_panels', 'mcs_responsive_settings' );
function mcs_responsive_settings( $panels ) {
	$mcs_responsive_mode = get_option( 'mcs_responsive_mode' );
	$mcs_stylesheet = get_option( 'mcs_stylesheet' );
	$mcs_breakpoint = get_option( 'mcs_breakpoint' );
	$mcs_responsive_setup = ( get_option( 'mcs_responsive_setup' ) != '' ) ? get_option( 'mcs_responsive_setup' ) : 1;
	
	if ( $mcs_responsive_mode == 'true' ) {
	
		$sheets = '';
		foreach ( mcs_responsive_stylesheets() as $name => $key ) {
			$sheets .= "<option value='$key'" . selected( $mcs_stylesheet, $key, false ) . ">" . $name . "</option>";
		}
	
		$disabled = ( $mcs_responsive_setup == 1 ) ? ' disabled="disabled"' : '';
	
		$controls = "
		<fieldset>
			<legend>" . __( 'Responsive Stylesheet', 'my-calendar-submissions' ) . "</legend>
			<p id='responsive_styles_note'>
				" . __( 'Responsive mode uses different HTML than the default My Calendar stylesheets. Default stylesheets may not render correctly when responsive mode is enabled.', 'my-calendar-submissions' ) . "
			</p>
			<p>
				<input type='radio' name='mcs_responsive_setup' id='mcs_basic_only' value='1'" . checked( $mcs_responsive_setup, '1', false ) . " /> <label for='mcs_basic_only'>" . __( 'Enable base responsive CSS to supplement default styles', 'my-calendar-submissions' ) . "</label><br />
				<input type='radio' name='mcs_responsive_setup' id='mcs_replacement' value='2'" . checked( $mcs_responsive_setup, '2', false ) . " /> <label for='mcs_replacement'>" . __( 'Replace My Calendar default styles', 'my-calendar-submissions' ) . "</label>
			</p>
			<p>
				<label for='mcs_stylesheet'>" . __( 'Select Responsive Theme', 'my-calendar-submissions' ) . "</label> 
				<select name='mcs_stylesheet' id='mcs_stylesheet'$disabled>
					<option value='default'>" . __( 'No styles', 'my-calendar-submissions' ) . "</option>				
					$sheets
				</select>
			</p>
		</fieldset>
		";
	} else {
		$controls = "
		<div>
			<input type='hidden' name='mcs_content' value='" . esc_attr( $mcs_stylesheet ) . "' />
		</div>";
	}
	$panels['responsive'] = '
		<h3>' . __( 'Responsive Mode', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">
			<p>
				<input type="checkbox" name="mcs_responsive_mode" id="mcs_responsive_mode" value="true" ' . checked( $mcs_responsive_mode, 'true', false ) . '/> <label for="mcs_responsive_mode">' . __( 'Enable Responsive Mode', 'my-calendar-submissions' ) . '</label>
			</p>'.
			$controls . '
			{submit}
		</div>';
	
	return $panels;
}

/**
 * In responsive mode, no toggle between grid and list mode; they're the same.
 */
add_filter( 'mc_format_toggle_html', 'mcs_disable_toggle', 10, 3 );
function mcs_disable_toggle( $toggle, $format, $time ) {
	$enabled = get_option( 'mcs_responsive_mode' );
	if ( $enabled == 'true' ) {
		$toggle = '';
	}
	
	return $toggle;
}

add_filter( 'mc_registered_stylesheet', 'mcs_responsive_styles' );
function mcs_responsive_styles( $stylesheet ) {
	$enabled = get_option( 'mcs_responsive_mode' );
	$setup = get_option( 'mcs_responsive_setup' );
	if ( $setup == 2 ) {
		$mcs_stylesheet = 'styles/' . get_option( 'mcs_stylesheet' );
		if ( $enabled == 'true' ) {	
			if ( file_exists( get_stylesheet_directory() . '/mc-responsive.css' ) ) {
				$stylesheet = esc_url( get_stylesheet_directory() . '/mc-responsive.css' ); 
			} else {
				if ( $mcs_stylesheet != '' ) {
					$mcs_stylesheet = mcs_get_file( $mcs_stylesheet.'.css', 'url' );
					$stylesheet = esc_url( $mcs_stylesheet );
				}
			}
		}
	}
	
	return $stylesheet;	
}

add_action( 'wp_enqueue_scripts', 'mcs_responsive' );
function mcs_responsive() {
	if ( get_option( 'mcs_responsive_setup' ) == '1' ) {
		wp_enqueue_style( 'mcs.base', mcs_get_file( 'styles/base.css', 'url' ), array( 'my-calendar-style' ) );
	}
}

function mcs_get_file( $file, $type = 'path' ) {
	$dir  = plugin_dir_path( __FILE__ );
	$url  = plugin_dir_url( __FILE__ );
	$base = basename( $dir );
	$path = ( $type == 'path' ) ? $dir . $file : $url . $file;
	if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
		$path = ( $type == 'path' ) ? get_stylesheet_directory() . '/' . $file : get_stylesheet_directory_uri() . '/' . $file;
	}
	if ( file_exists( str_replace( $base, 'my-calendar-custom', $dir ) . $file ) ) {
		$path = ( $type == 'path' ) ? str_replace( $base, 'my-calendar-custom', $dir ) . $file : str_replace( $base, 'my-calendar-custom', $url ) . $file;
	}
	$path = apply_filters( 'mc_get_file', $path, $file );

	return $path;
}

add_action( 'init', 'mcs_activate_responsive_mode' );
function mcs_activate_responsive_mode() {
	$enabled = get_option( 'mcs_responsive_mode' );
	if ( $enabled == 'true' ) {
		add_filter( 'mc_grid_wrapper', 'mcs_grid_wrapper', 10, 2 );
		add_filter( 'mc_grid_week_wrapper', 'mcs_week_wrapper', 10, 2 );
		add_filter( 'mc_grid_header_wrapper', 'mcs_header_wrapper', 10, 2 );
		add_filter( 'mc_grid_day_wrapper', 'mcs_day_wrapper', 10, 2 );
		add_filter( 'mc_grid_caption', 'mcs_grid_caption', 10, 2 );		
	}
}

function mcs_grid_caption( $default, $format ) {
	return ( $format == 'calendar' ) ? 'h2' : $default;
}

function mcs_grid_wrapper( $default, $format ) {
	return ( $format == 'calendar' ) ? 'div' : $default;
}

function mcs_week_wrapper( $default, $format ) {
	return ( $format == 'calendar' ) ? 'ul' : $default;
}

function mcs_header_wrapper( $default, $format ) {
	return ( $format == 'calendar' ) ? 'li' : $default;
}

function mcs_day_wrapper( $default, $format ) {
	return ( $format == 'calendar' ) ? 'li' : $default;
}
