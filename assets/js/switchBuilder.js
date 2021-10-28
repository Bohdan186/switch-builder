jQuery(document).ready( function ($) {
	let $switchBuilderButton = $('.switch-builder-button');

	let data = {
		action: 'switch-builder'
	};

	$switchBuilderButton.on( 'click', function( event ){
		event.preventDefault();

		$.post( ajaxurl, data, function( response ) {
			console.log( response );
		});
	});
});