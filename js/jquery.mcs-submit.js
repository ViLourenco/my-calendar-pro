(function( $ ) { 'use strict';
	$( '.mcs_location_fields' ).hide();
	$( '.toggle_location_fields' ).on( 'click', function(e) {
		var change = ( $(this).attr( 'aria-expanded' ) == 'false' ) ? 'true' : 'false';
		var dashicon_old = ( $(this).attr( 'aria-expanded' ) == 'true' ) ? 'dashicons-minus' : 'dashicons-plus';
		var dashicon_new = ( $(this).attr( 'aria-expanded' ) == 'false' ) ? 'dashicons-minus' : 'dashicons-plus';
		$(this).attr( 'aria-expanded', change );
		$(this).find( 'span' ).removeClass( dashicon_old ).addClass( dashicon_new );
		$( '.mcs_location_fields' ).toggle();
		if ( $( '.mcs_location_fields' ).is( ':visible' ) ) {
			$( '.mcs_location_fields #event_label' ).focus();
		}
	});
}(jQuery));