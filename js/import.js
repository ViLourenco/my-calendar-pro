(function( $ ) {
  'use strict';
  
	$( '.mcs-importer-progress' ).hide();
	/* Use the global WordPress 'ajaxurl' to send the request to WordPress
	 * and tell it to fire the import_files function.
	 *
	 * Use the response returned from the server for how to update the display
	 * when the operation has completely finished.
	 */
	$( 'button[name=mcs_import_events]' ).on( 'click', function(e) {
		
		$( '.mcs-importer-progress' ).show();
		
		$(function() {
		
			var importTimer;
				
			/* Every second, we're going to poll the server to request for the
			 * value of the progress being made. This is using the get_import_status
			 * function on the server-side.
			 *
			 * If the response is -1, then the operation is done and we can stop the
			 * timer; otherwise, we can update the progressbar.
			 */
			importTimer = setInterval(function() {

				// Get the current status of the update
				$.get( ajaxurl, {
					action:    'mcs_get_import_status'
				}, function( response ) {

					console.log( response );
					
					if ( '-1' === response ) {
						
						$( '.mcs-importer-progress span' ).attr( 'data-progress', 100 ).addClass( 'percent100' ).css( 'width', '100%' );
						$( '.mcs-importer-progress strong' ).text( '100%' ).delay( 1000 ).text( 'Import Completed' );
						// Set the progress bar equal to 100 and clear the timer
						window.clearInterval( importTimer );

					} else {
						
						$( '.mcs-importer-progress span' ).attr( 'data-progress', response*100 ).css( 'width', response*100 + '%' );
						$( '.mcs-importer-progress strong' ).text( Math.round( response*100 ) + '%' );

					}

				});

			}, 1000 );

		});

	});
	
})( jQuery );