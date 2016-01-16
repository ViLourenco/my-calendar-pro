(function( $ ) { 'use strict';
	var quantity = $('input[name="x_quantity"]');
	var price = $('input[name="x_amount_base"]');
	quantity.on('change',function(e) {
		$('.x_amount').html( ( quantity.val() * price.val() ) );
		$('input[name="x_amount"]').val( ( quantity.val() * price.val() ) );
	});
}(jQuery));