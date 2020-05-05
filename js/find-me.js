/*global jQuery, geo_mashup_search_find_me_env */
/*jslint browser: true */

jQuery( function( $ ) {
	$( '.geo-mashup-search-find-me' ).show().click( function() {
		var $button = $( this ),
			$form = $button.closest('form'),
			$search_input = $form.find( 'input.geo-mashup-search-input' ),
			$geolocation_input = $form.find( 'input[name=geolocation]' ),
			$submit_button = $form.find( 'input[name=geo_mashup_search_submit]' ),
			ignore_timeout,

			succeed = function( lat, lng ) {
				var geonames_url = 'https://secure.geonames.org/findNearbyPlaceNameJSON?lat=' +
					encodeURIComponent( lat ) + '&lng=' + encodeURIComponent( lng ) + '&username=' +
					encodeURIComponent( geo_mashup_search_find_me_env.geonames_username ) + '&callback=?';
				clearTimeout( ignore_timeout );
				$button.hide();
				$geolocation_input.val( lat + ',' + lng );
				$.getJSON( geonames_url, function( data ) {
					if ( data.geonames && data.geonames.length ) {
						$search_input.val( data.geonames[0].toponymName + ', ' + data.geonames[0].adminName1 );
					} else {
						$search_input.val( geo_mashup_search_find_me_env.my_location_message );
					}
				} ).error( function() {
					$search_input.val( geo_mashup_search_find_me_env.my_location_message );
				} );
				$submit_button.focus();
			},

			fail = function() {
				clearTimeout( ignore_timeout );
				$button.hide();
				$search_input.val( geo_mashup_search_find_me_env.fail_message );
			};
			
		$button.prop( 'disabled', true );

		$search_input.change( function() {
			$geolocation_input.val( '' );
		} );

		if ( navigator.geolocation ) {
			navigator.geolocation.getCurrentPosition( function( position ) {
				succeed( position.coords.latitude, position.coords.longitude );
			}, fail, {timeout: 5000} );

			// Firefox needs another timer, because nothing happens if the user chooses "not now"
			ignore_timeout = setTimeout( fail, 10000 );

		}

		return false; // Don't submit
	});
});
