/*global jQuery, geo_mashup_search_find_me_env */
/*jslint browser: true */

jQuery( function( $ ) {
	$( '.geo-mashup-search-find-me' ).show().click( function() {
		var $button = $( this ),
			$form = $button.parent( 'form' ),
			$search_input = $form.find( 'input.geo-mashup-search-input' ),
			$geolocation_input = $form.find( 'input[name=geolocation]' ),
			$submit_button = $form.find( 'input[name=geo_mashup_search_submit]' ),
			ignore_timeout,

			succeed = function( lat, lng ) {
				var geoplugin_url = 'http://www.geoplugin.net/extras/location.gp?jsoncallback=?&format=json&lat=' + 
					encodeURIComponent( lat ) + '&long=' + encodeURIComponent( lng );
				clearTimeout( ignore_timeout );
				$button.hide();
				$geolocation_input.val( lat + ',' + lng );
				$.getJSON( geoplugin_url, function( data ) {
					if ( data.geoplugin_place && data.geoplugin_region ) {
						$search_input.val( data.geoplugin_place + ', ' + data.geoplugin_region );
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
			},
			
			alternateGeolocation = function() {
				var geoplugin_url;
					
				clearTimeout( ignore_timeout );

				if ( !geo_mashup_search_find_me_env.client_ip ) {
					fail();
				}
				geoplugin_url = 'http://www.geoplugin.net/json.gp?jsoncallback=?&ip=' + 
					geo_mashup_search_find_me_env.client_ip;
				$.getJSON( geoplugin_url, function( data ) {
					if ( data.geoplugin_latitude && data.geoplugin_longitude ) {
						succeed( data.geoplugin_latitude, data.geoplugin_longitude );
					} else {
						fail();
					}
				} ).error( fail );
			};

		$button.prop( 'disabled', true );

		$search_input.change( function() {
			$geolocation_input.val( '' );
		} );

		if ( navigator.geolocation ) {
			navigator.geolocation.getCurrentPosition( function( position ) {
				succeed( position.coords.latitude, position.coords.longitude );
			}, function( error ) {
				alternateGeolocation();
			}, {timeout: 4000} );

			// Firefox needs another timer, because nothing happens if the user chooses "not now"
			ignore_timeout = setTimeout( alternateGeolocation, 10000 );

		}

		return false; // Don't submit
	});
});
