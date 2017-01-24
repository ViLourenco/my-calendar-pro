<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mcs_importer_update() {
	// save settings
	if ( isset( $_FILES['mcs_importer'] ) || isset( $_POST['mcs_remote_import'] ) ) {
		$nonce = $_POST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce, 'importer' ) ) return;
		
		$constructed = false;
		$delimiter    = ',';
		
		if ( isset( $_FILES['mcs_importer'] ) && !empty( $_FILES['mcs_importer']['name'] ) ) {
			// Read the contents of the file
			$path        = $_FILES['mcs_importer']['name'];
			$ext         = pathinfo( $path, PATHINFO_EXTENSION );
			$rows        = '';
			$notice      = '';
			
			if ( $ext == 'ics' ) {
				$constructed = true;
				$rows        = mcs_convert_ics( $_FILES['mcs_importer']['tmp_name'] );
			}
			if ( $rows == '' ) {
				$file = fopen( $_FILES['mcs_importer']['tmp_name'], 'r' );
				while ( ( $row = fgetcsv( $file, 0, $delimiter ) ) !== false ) {
					$csv[]  = $row;
				}
			} else {
				$csv = $rows;
			}
		} else {
			// remote file
			$type     = ( $_POST['mcs_remote_type'] == 'csv' ) ? 'csv' : 'ics';
			$url      = esc_url( $_POST['mcs_remote_import'] );
			$response = wp_remote_get( $url, array( 'user-agent' => 'WordPress/My Calendar Importer; ' . home_url() ) );
			$body     = wp_remote_retrieve_body( $response );	
			
			if ( $type == 'csv' ) {
				$csv = $body;
			} else {
				// converting ics requires file input.
				$file        = fopen( dirname( __FILE__ ). '/mcs_csv_source.ics', 'w' );			 
				fwrite( $file, $body );
				fclose( $file );				
				$constructed = true;
				$csv         = mcs_convert_ics( dirname( __FILE__ ). '/mcs_csv_source.ics' );
				
				unlink( dirname( __FILE__ ). '/mcs_csv_source.ics' );
			}
		}
		
		/* 
		 * Separate each file into an index of an array and store the total
		 * number of rows that exist in the array. This assumes that
		 * each row of the CSV file is on a new line.
		 */
		$csv_rows       = ( !is_array( $csv ) ) ? explode( PHP_EOL, $csv ) : $csv;
		$total_rows     = count( $csv_rows );
		// number of rows per file
		$number_per_row = 30;
		
		// Store the title row. This will be written at the top of every file.
		$title_row  = $csv_rows[ 0 ];
		
		if ( is_array( $title_row ) ) {
			$title_row = implode( $delimiter, $title_row );
		}
		/* Calculate the number of rows that will consist of $number_per_row, and then calculate
		 * the number of rows for the final file.
		 *
		 * We use floor() so we don't round up by one.
		 */
		$rows          = ceil( $total_rows / $number_per_row );
		// have to have at least one file.
		$rows          = ( $rows == 0 ) ? 1 : $rows;
		$remaining_row = ( $total_rows % $number_per_row );
		// Prepare to write out the files. This could just as easily be a for loop.
		$file_counter = 0;
		
		while( 0 < $rows ) {
			$csv_data = '';
			/* Create the output file that will contain a title row and a set of ten rows.
			 * The filename is generated based on which file we're importing.
			 */
			$loops = ( $rows == 1 && $remaining_row != 0 ) ? $remaining_row : $number_per_row;
			// set a batch of rows into a string.
			for( $i = 1; $i < $loops; $i++ ) {
				/* Read the value from the array and then remove from
				 * the array so it's not read again.
				 */
				if ( isset( $csv_rows[ $i ] ) && is_array( $csv_rows[ $i ] ) ) {
					$data = implode( $delimiter, array_map( 'mcs_wrap_field', $csv_rows[ $i ] ) );
				} else if ( isset( $csv_rows[ $i ] ) && ! is_array( $csv_rows[ $i ] ) ) {
					$data = $csv_rows[ $i ];
				} else {
					$data = false;
				}
				
				// extra PHP EOL will break data
				$csv_data .= ( $data && trim( $data ) != '' ) ? str_replace( PHP_EOL, '', $data ) . PHP_EOL : PHP_EOL;
				unset( $csv_rows[ $i ] );
			}
			/* Write out the file and then close the handle since we'll be opening
			 * a new one on the next iteration.
			 */
			if ( trim( $csv_data ) != '' ) {
				$csv_data    = $title_row . PHP_EOL . $csv_data;
				$output_file = fopen( dirname( __FILE__ ). '/mcs_csv_' . $file_counter . '.csv', 'w' );
				fwrite( $output_file, $csv_data );
				fclose( $output_file );
				/* Increase the file counter by one and decrement the rows,
				 * reset the keys for the rows of the array, and then reset the
				 * string of data to the title row
				 */
				$file_counter++;
			}
			$rows--;
			$csv_rows = array_values( $csv_rows );
		}
		
		set_transient( 'mcs-number-of-files', $file_counter, 60 );	
		set_transient( 'mcs-parsed-files', 'true', 60 );

		if ( $constructed ) {
			// highly improbable pattern to help cope with unknown data in content.
			$delimiter = '|||';
		} else {
			$delimiter = ',';
		}
		set_transient( 'mcs-delimiter', $delimiter, 60 );
		
		$titles = explode( $delimiter, $title_row );
		$imports = $total_rows - 1;
		$return = "
			<h3>" . sprintf( __( 'Importing %s events.', 'my-calendar-submissions' ), "<strong>$imports</strong>" ) . "</h3>
				<p><strong>" . __( 'Mapping your CSV columns to My Calendar fields:', 'my-calendar-submissions' ) . "</strong></p>
				<ul class='mcs-import-data'>";
		$odd = 0;
		foreach( $titles as $title ) {
			$title = ( $title == '' ) ? __( '(No field name)', 'my-calendar-submissions' ) : trim( $title );
			$fields = mcs_option_fields();
			//$target = ( in_array( $title, array_keys( $fields ) ) ) ? $fields[$title] : mcs_option_list( $title );
			$class = ( $odd == 1 ) ? 'odd' : 'even';
			if ( in_array( $title, array_keys( $fields ) ) ) {
				$target = $fields[$title];
				$class .= '';
			} else {
				$target =  __( 'Unrecognized field - will be ignored', 'my-calendar-submissions' );
				$class .= ' unknown';
			}
			$return .= "<li class='$class'>" . sprintf( __( '&ldquo;<strong>%s</strong>&rdquo; to %s', 'my-calendar-submissions' ), $title, $target ) . "</li>";
			$odd = ( $odd == 1 ) ? 0 : 1;
		}
		unset( $title );		
		$return .= "</ul>";
		$return .= "<button type='button' name='mcs_import_events' class='button-primary' type='button'>" . __( 'Import Events', 'my-calendar-submissions' ) . "</button>
		<div class='mcs-importer-progress' aria-live='assertive' aria-atomic='false'><span>Importing...<strong class='percent'></strong></span></div>";
		
		return $return;
		
	}
}

function mcs_wrap_field( $content ) {
	return '"' . wptexturize( $content ) . '"';
}

function mcs_convert_ics( $file ) {
	include( dirname( __FILE__ ). '/classes/class.iCalReader.php' );
	$ics    = new ical( $file );
	// is timezone from/to data in $ics->cal['VCALENDAR']?
	$events = $ics->cal['VEVENT'];
	$uids = array();
	// map each element to existing My Calendar fields
	$rows = 'event_begin|||event_time|||event_end|||event_endtime|||content|||event_label|||event_title|||event_group_id'.PHP_EOL;
	foreach ( $events as $event ) {
				
		$event_begin = date( 'Y-m-d', strtotime( $event['DTSTART'] ) );
		$event_time = date( "H:i:00", strtotime( $event['DTSTART'] ) );
		$event_end = date( 'Y-m-d', strtotime( $event['DTEND'] ) );
		$event_endtime = date( 'H:i:00', strtotime( $event['DTEND'] ) );
		
		$date_diff   = strtotime( $event_end ) - strtotime( $event_begin );
		$time_diff   = strtotime( $event_endtime ) - strtotime( $event_time );
		$description = ( isset( $event['DESCRIPTION'] ) ) ? str_replace( PHP_EOL, '', wpautop( $event['DESCRIPTION'] ) ) : '';
		$location    = ( isset( $event['LOCATION'] ) ) ? $event['LOCATION'] : '';
		$summary     = ( isset( $event['SUMMARY'] ) ) ? $event['SUMMARY'] : '';
			
		$uid         = ( isset( $event['UID'] ) ) ? $event['UID'] : '';
		// add UID field to event object; use to track for imports
		if ( in_array( $uid, $uids ) ) {
			$group_id = mc_group_id();
		} else {
			$group_id = 'default';
			$uids[]   = $uid;
		}
		$rows .= "\"$event_begin\"|||\"$event_time\"|||\"$event_end\"|||\"$event_endtime\"|||\"$description\"|||\"$location\"|||\"$summary\"|||\"$group_id\"" . PHP_EOL;		
	}
	unset( $event );
	
	return $rows;
}

add_action( 'admin_enqueue_scripts', 'mcs_importer_enqueue_scripts' );
function mcs_importer_enqueue_scripts() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'my-calendar-submissions' ) {
		wp_enqueue_script( 'mcs.import', plugins_url( 'js/import.js', __FILE__ ), array( 'jquery' ), get_option( 'mcs_version' ), true );
	}
}

add_filter( 'mcs_settings_tabs', 'mcs_importer_tabs' );
function mcs_importer_tabs( $tabs ) {
	$tabs['importer'] = __( 'Importer', 'my-calendar-submissions' );
	
	return $tabs;
}

add_filter( 'mcs_settings_panels', 'mcs_importer_settings' );
function mcs_importer_settings( $panels ) {
	
	// delete any old data from incomplete imports	
	$update = mcs_importer_update();
	
	$importer = '
		<h3>' . __( 'Import Events', 'my-calendar-submissions' ) . '</h3>
		<div class="inside">'.
		$update;
		if ( $update == false ) {
			if ( isset( $_GET['test_import'] ) ) {
				mcs_import_files();
			}
			$importer .= '
				<p>
					<label for="mcs_importer">' . __( 'Upload File (.csv or .ics)', 'my-calendar-submissions' ) . '</label>			
					<input type="file" name="mcs_importer" id="mcs_importer_mode" /> 
				</p>
				<fieldset class="mcs-importer">
				<legend>' . __( 'Remote import', 'my-calendar-submissions' ) . '</legend>
				<p>
					<label for="mcs_remote_import">' . __( 'Import from URL', 'my-calendar-submissions' ) . '</label><br />			
					<input type="url" name="mcs_remote_import" id="mcs_remote_import" class="widefat" /> 
				</p>
				<p>			
					<input type="radio" name="mcs_remote_type" id="mcs_remote_type_ics" value="ics" /> <label for="mcs_remote_type_ics">' . __( 'iCal format (.ics)', 'my-calendar-submissions' ) . '</label> 
					<input type="radio" name="mcs_remote_type" id="mcs_remote_type_csv" value="csv" checked="checked" /> <label for="mcs_remote_type_csv">' . __( 'Character separated values (.csv)', 'my-calendar-submissions' ) . '</label>
				</p>
				</fieldset>
				{submit}';
		}
		$importer .= '
		</div>';
	
	$panels['importer']['content'] = $importer;
	$panels['importer']['label'] = __( 'Import Events', 'my-calendar-submissions' );
	
	return $panels;
}

/* 
 * Setup the hook for the Ajax request.
 */
add_action( 'wp_ajax_mcs_import_files', 'mcs_import_files' );
function mcs_import_files( $i = 0 ) {
   set_transient( "mcs-parsing-$i", 'true', 10 );
   $content         = array();
   $defaults        = mcs_default_event_values();
   $number_of_files = get_transient( 'mcs-number-of-files' );
   $delimiter       = get_transient( 'mcs-delimiter' );	
   
   if ( $i <= $number_of_files ) {
		$filename = dirname( __FILE__ ). '/mcs_csv_' . $i . '.csv';

		if ( file_exists( $filename ) ) {
			$file = fopen( $filename, 'r' );	
			//$content    = fread( $input_file, filesize( $filename ) );
			//$f    = file( $filename );
			while(( $row = fgetcsv( $file, 0, $delimiter ) ) !== false ) {
				$content[]  = $row;
			}
			$array      = mcs_translate_csv( $content, $delimiter );			
			unset( $content );
			fclose( $file );
			
			foreach( $array as $event ) {
				if ( !is_array( $event ) || $event['event_title'] == 'event_title'  ) {
					continue;
				}
				if ( isset( $event['event_group_id'] ) && $event['event_group_id'] == 'default' ) {
					unset( $event['event_group_id'] );
				}
				$event = array_merge( $defaults, $event );
				if ( isset( $event['event_category'] ) && !is_numeric( $event['event_category'] ) ) {
					// event category is not numeric, so check database to see if exists. 
					$cat_id = mcs_category_by_name( $event['event_category'] );
					if ( ! $cat_id ) {
						$cat_id = ( $event['event_category'] == '' ) ? 1 : mcs_insert_category( $event['event_category'] );
					}
					$event['event_category'] = $cat_id;
				}
				mcs_import_event( $event );
			}
			unset( $event );
			// update count of parsed files; close & delete input file
			unlink( $filename );
			//echo 'Parsed: ' . get_transient( 'mcs-parsed-files' );
			//usleep( 200000 );
		}
	} else {
		// delete data about imports
		delete_transient( 'mcs-parsed-files' );
		delete_transient( 'mcs-number-of-files' );
		delete_transient( 'mcs-delimiter' );
	}
	delete_transient( "mcs-parsing-$i" );
}

// Define the hook for getting the status somewhere in your plugin
add_action( 'wp_ajax_mcs_get_import_status', 'mcs_get_import_status' );
function mcs_get_import_status() {
	/* Notice that in the code we use die() with
	* a value - this is how the value is returned to
	* the client requesting a value from this function.
	*
	* $progress indicates how far we are during the import,
	* -1 indicates that we're done
	*/

	if ( FALSE !== get_transient( 'mcs-parsed-files' ) ) {
		$parsed_files = floatval( get_transient( 'mcs-parsed-files' ) );
		$total_files =  floatval( get_transient( 'mcs-number-of-files' ) );
		if ( $total_files != 0 ) {
			// prevent duplicate imports
			if ( $parsed_files == 'true' ) {
				$parsed_files = 0;
			}
			if ( get_transient( "mcs-parsing-$parsed_files" ) != 'true' ) {
				mcs_import_files( $parsed_files );
				/**
				 * Update data about imports 
				 */
				set_transient( 'mcs-parsed-files', $parsed_files + 1, 60 );
			}
			$progress =  $parsed_files / $total_files;
			/* Return progress values */
			if ( $progress == 1 ) {
				die( '-1' );
			}
			die( $progress );
		} else {
			die ( '0' );
		}
		
	} else {		
		die( '-1' );
	}
}

function mcs_import_event( $event ) {
	$check = mc_check_data( 'add', $event, 0 );
	if ( $check[0] ) {
		$response = my_calendar_save( 'add', $check );				
		$event_id = $response['event_id'];
		$response = $response['message'];
		if ( isset( $event['event_image'] ) && $event['event_image'] != '' ) {
			$e = mc_get_event_core( $event_id );
			$post_id = $e->event_post;
			$image = media_sideload_image( $event['event_image'], $post_id );
			$media = get_attached_media( 'image', $post_id );
			$attach = array_shift( $media );
			$attach_id = $attach->ID;
			set_post_thumbnail( $post_id, $attach_id );
		}
	}	
}

function mcs_check_csv_delimiter( $import, $checkLines = 1 ){
	$file = new SplFileObject( $import, 'r', true );
	$delimiters = array(
	  ',',
	  '\t',
	  ';',
	  '|',
	  ':'
	);
	$results = array();
	$i = 0;
	while( $file->valid() && $i <= $checkLines ) {
		$line = $file->fgets();
		foreach ( $delimiters as $delimiter ) {
			$regExp = '/['.$delimiter.']/';
			$fields = preg_split($regExp, $line);
			if(count($fields) > 1){
				if(!empty($results[$delimiter])){
					$results[$delimiter]++;
				} else {
					$results[$delimiter] = 1;
				}   
			}
		}
		unset( $delimiter );
	   $i++;
	}
	
	$results = array_keys( $results, max($results) );
	
	return $results[0];
}

function mcs_option_list( $value ) {
	$fields = mcs_option_fields();
	// eventually, use value to guess target
	$return = "<select name='$value' id='$value'>
				<option value='asis'>" . __( 'Take it as it is...', 'my-calendar-submissions' ) . "</option>";
				foreach( $fields as $key => $value ) {
					$return .= "<option value='$key'>$value</option>";
				}
				unset( $value );
			$return .= "</select>";
	
	return $return;
}

function mcs_option_fields() {
	return array(
			// Event data
			'event_title'        => __( 'Title', 'my-calendar-submissions' ),			
			'event_begin'        => __( 'Starting Date', 'my-calendar-submissions' ),
			'occur_begin'        => __( 'Starting Date', 'my-calendar-submissions' ),
			'event_end'          => __( 'Ending Date', 'my-calendar-submissions' ),
			'occur_end'          => __( 'Ending Date', 'my-calendar-submissions' ),
			'event_time'         => __( 'Starting Time', 'my-calendar-submissions' ),
			'event_endtime'      => __( 'Ending Time', 'my-calendar-submissions' ),
			'content'            => __( 'Description', 'my-calendar-submissions' ),
			'event_desc'         => __( 'Description', 'my-calendar-submissions' ),
			'event_short'        => __( 'Short Description', 'my-calendar-submissions' ),
			'event_link'         => __( 'External event URL', 'my-calendar-submissions' ),
			'event_link_expires' => __( 'Link expiration', 'my-calendar-submissions' ),
			'event_recur'        => __( 'Recurring frequency period', 'my-calendar-submissions' ), 
			// above codes: S - single,D - day,E - weekdays,W - weekly,M - month/date,U - month/day,Y - year
			'event_repeats'      => __( 'Number of repetitions', 'my-calendar-submissions' ),
			// above means: 4 = event repeats 4 times, for a total of 5 occurrences.
			'event_every'		 => __( 'Recurrence frequency multiplier', 'my-calendar-submissions' ),
			// above represents: D + 3 == every 3 days, 2 + W == every two weeks
			'event_image'        => __( 'Event Image URL', 'my-calendar-submissions' ),
			'event_all_day'		 => __( 'Event is all-day', 'my-calendar-submissions' ),
			'event_author'       => __( 'Author ID', 'my-calendar-submissions' ),
			'event_category'     => __( 'Category Name or ID', 'my-calendar-submissions' ),	
			'event_fifth_week'   => __( 'Omit week 5 recurrences', 'my-calendar-submissions' ),
			'event_holiday'      => __( 'Cancel on Holidays', 'my-calendar-submissions' ),
			'event_group'        => __( 'Event is grouped',	'my-calendar-submissions' ),
			'event_group_id'     => __( 'Event Group ID', 'my-calendar-submissions' ),
			'event_span'         => __( 'Event spans multiple days', 'my-calendar-submissions' ),
			'event_hide_end'     => __( 'Hide end date', 'my-calendar-submissions' ),
			// Ticketing/Registration data
			'event_status'       => __( 'Event Status', 'my-calendar-submissions' ),
			'event_approved'     => __( 'Event Approved', 'my-calendar-submissions' ),
			'event_flagged'      => __( 'Event Flagged as Spam', 'my-calendar-submissions' ),
			'event_tickets'      => __( 'Event Tickets Link', 'my-calendar-submissions' ),
			'event_registration' => __( 'Event Registration Info', 'my-calendar-submissions' ),
			'event_open'         => __( 'Open for registrations', 'my-calendar-submissions' ),
			'event_host'         => __( 'Event Host ID', 'my-calendar-submissions' ),
			'event_access'       => __( 'Event Accessibility Data', 'my-calendar-submissions' ), // this is event, right?
			// location data
			'location_preset'	 => __( 'Location ID', 'my-calendar-submissions' ),			
			'event_label'        => __( 'Location Label', 'my-calendar-submissions' ),
			'event_street'       => __( 'Location Street', 'my-calendar-submissions' ),
			'event_street2'      => __( 'Location Street (2)', 'my-calendar-submissions' ),
			'event_city'         => __( 'Location City', 'my-calendar-submissions' ),
			'event_state'        => __( 'Location State', 'my-calendar-submissions' ),
			'event_postcode'     => __( 'Location Postcode', 'my-calendar-submissions' ),
			'event_region'       => __( 'Location Region', 'my-calendar-submissions' ),
			'event_country'      => __( 'Location Country', 'my-calendar-submissions' ),
			'event_url'          => __( 'Location URL', 'my-calendar-submissions' ),
			'event_phone'        => __( 'Event Phone Number', 'my-calendar-submissions' ), // event or location?
			'event_phone2'       => __( 'Alternate Event Phone', 'my-calendar-submissions' ), // event or location?
			'event_longitude'    => __( 'Location longitude', 'my-calendar-submissions' ),
			'event_latitude'     => __( 'Location latitude', 'my-calendar-submissions' ),
			'event_zoom'         => __( 'Map zoom level', 'my-calendar-submissions' ),			
			// meta data
			'mc_copy_location'   => __( 'Copy location into DB', 'my-calendar-submissions' )
		);
}

function mcs_translate_csv( $content, $delimiter = ';', $enclosure = '"', $escape = '\\', $terminator = PHP_EOL  ) {
	$r      = array();
	$output = array();
	//$titles = explode( $delimiter, trim( $content[0] ) );
	$titles = $content[0];
	unset( $content[0] );
		
	foreach ( $content as $key => $row ) {
		if ( $row ) {
			// convert back into string
			/*$row = implode( $delimiter, array_map( 'mcs_wrap_field', $csv_rows[ $i ] ) );
			if ( function_exists( 'str_getcsv' ) ) {
				$values = str_getcsv( $row, $delimiter, $enclosure, $escape ); // --> requires 5.3.0	
			} else {
				$values = explode( $delimiter, $row ); // won't accept cases where the delimiter is in values
			}*/
			$values = $row;
			$i = 0;
			$event_begin = '';
			$event_end   = '';
			foreach ( $values as $value ) {
				$value = str_replace( array( $enclosure, $escape ), '', $value );
				if ( in_array( $titles[$i], array( 'event_begin', 'event_end', 'event_time', 'event_endtime', 'occur_begin', 'occur_end' ) ) ) {
					if ( in_array( $titles[$i], array( 'event_begin', 'event_end', 'occur_begin', 'occur_end' ) ) ) {
						$value = date( 'Y-m-d', strtotime( $value ) );
					} else {
						$value = date( 'H:i:s', strtotime( $value ) );
					}					
					// endtime must be listed after start time.
					if ( $titles[$i] == 'event_endtime' && $value == '00:00:00' ) {
						$value = date( 'H:i:s', strtotime( $r['event_time'][0] . ' + 1 hour' ) );
					}
					$r[ $titles[$i] ][0] = ( isset( $value ) ) ? trim( $value ) : '' ;					
				} else {
					$r[ $titles[$i] ] = ( isset( $value ) ) ? trim( $value ) : '' ;
				}
				$i++;
			}
			
			unset( $value );
		}
				
		$event_begin = ( isset( $r['occur_begin'][0] ) ) ? $r['occur_begin'][0] : $r['event_begin'][0]; // todo
		$event_end   = ( isset( $r['occur_end'][0] ) ) ? $r['occur_end'][0] : $r['event_end'][0];
		
		$r['event_begin'] = array( $event_begin );
		$r['event_end']   = array( $event_end );
		
		if ( strtotime( $event_end ) < strtotime( $event_begin ) ) {
			$r['event_end'] = array( $event_begin );
		}
		
		$output[] = $r;
	}
	unset( $row );
	
	return $output;	
}


function mcs_default_event_values() {
	
	if ( get_option( 'mc_event_approve' ) != 'true' ) {
		$dvalue = 1;
	} else if ( current_user_can( 'mc_approve_events' ) ) {
		$dvalue = 1;
	} else {
		$dvalue = 0;
	}	
	$expires = ( get_option('mc_event_link_expires') == 'false' ) ? 1 : 0;
		
	// import values from settings & autogenerate generated values
	$defaults = array(
		'event_fifth_week' => ( get_option( 'event_fifth_week' ) == 'true' ) ? 1 : '',
		'event_holiday' => ( get_option( 'mc_skip_holidays' ) == 'true' ) ? 1 : '',
		'event_group_id' => mc_group_id(),
		'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
		'event_category' => 1,
		'event_recur' => 'S',
		'event_repeats' => 0,
		'event_approved' => $dvalue,
		'event_link_expires' => $expires,
	);
	
	return apply_filters( 'mcs_default_event_values', $defaults );
}

function mcs_category_by_name( $string ) {
	global $wpdb;
	$mcdb    = $wpdb;
	$cat_id  = false;
	$sql     = "SELECT * FROM " . my_calendar_categories_table() . " WHERE category_name = %s";
	$cat     = $mcdb->get_row( $mcdb->prepare( $sql, $string ) );

	if ( is_object( $cat ) ) {
		$cat_id = $cat->category_id;
	}

	return $cat_id;
}

function mcs_insert_category( $string ) {
	global $wpdb;
	$mcdb    = $wpdb;
	$cat_id  = false;
	$formats = array( '%s', '%s', '%s', '%d', '%d' );
	$term    = wp_insert_term( $string, 'mc-event-category' );
	if ( ! is_wp_error( $term ) ) {
		$term = $term['term_id'];
	} else {
		$term = false;
	}
	$add = array(
		'category_name'    => $string,
		'category_color'   => '#ffffcc',
		'category_icon'    => 'event.png',
		'category_private' => 0,
		'category_term'    => $term
	);
	// actions and filters
	$results = $mcdb->insert( my_calendar_categories_table(), $add, $formats );		
	$cat_ID = $mcdb->insert_id;	
	
	return $cat_ID;
}

/*

These are the fields submitted to mc_check_data:

event_title
content
event_short
event_recur
event_every
event_begin
event_end
event_time
event_endtime
event_allday
event_repeats
event_host
event_category
event_link
event_link_expires
event_approved
location_preset
event_author
event_open
event_tickets
event_registration
event_group
event_image
event_fifth_week
event_holiday
event_group_id
event_span
event_hide_end

event_label
event_street
event_street2
event_city
event_state
event_postcode
event_region
event_country
event_url
event_longitude
event_latitude
event_zoom
event_phone
event_phone2
event_access
mc_copy_location
mcs_check_conflicts
*/