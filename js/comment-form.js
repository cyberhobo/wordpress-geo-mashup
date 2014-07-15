/* Handle Geo Mashup location collection for comments. */
/*global navigator, google, jQuery, geo_mashup_comment_form_settings */
/*jslint vars: true, white: true, sloppy: true */

/**
 * Add location collection to the comment form.
 */
jQuery( function( $ ) {
	var $comment_input = $( '#comment' ),
		$summary_input = $( '#geo-mashup-summary-input' ),
		$summary_label = $( '#geo-mashup-summary-label' ),
		$busy_icon = $( '#geo-mashup-busy-icon' ),
		$lat_input = $( '#geo-mashup-lat-input'),
		$lng_input = $( '#geo-mashup-lng-input');

	var search_input_changed = false;

	var summarizePostalCodes = function( data ) {
			if ( data.postalCodes.length > 0 ) {
				$summary_input.val( data.postalCodes[0].placeName + ', ' + data.postalCodes[0].adminName1 );
			}
		};

	var summarizeLocation = function( query_data ) {
		var query_url = 'http://api.geonames.org/findNearbyPostalCodesJSON?maxRows=1&callback=?&username=' +
				geo_mashup_comment_form_settings.geonames_username;
		$lat_input.val( query_data.lat );
		$lng_input.val( query_data.lng );
		$busy_icon.hide();
		$summary_input.show().val( '' );
		$.getJSON( query_url, query_data, summarizePostalCodes );
		search_input_changed = false;
	};

	var loadSearchResult = function( data ) {
		$busy_icon.hide();
		if ( data.totalResultsCount === 0 ) {
			$summary_input.val( '' );
		} else {
			$lat_input.val( data.geonames[0].lat );
			$lng_input.val( data.geonames[0].lng );
			$summary_input.val( data.geonames[0].name + ', ' + data.geonames[0].adminName1 );
		}
		search_input_changed = false;
	};

	var search = function() {
		var query_url = 'http://api.geonames.org/searchJSON?maxRows=1&callback=?&username=' +
				geo_mashup_comment_form_settings.geonames_username;
		var query_data = {q: $summary_input.val()};
		if ( search_input_changed ) {
			$lat_input.val('');
			$lng_input.val('');
			$busy_icon.show();
			$.getJSON( query_url, query_data, loadSearchResult );
		}
	};

	$( '#submit' ).before( $summary_label ).before( $busy_icon ).before( $summary_input );

	$comment_input.change( function() {
		if ( $summary_label.is( ':visible' ) ) {
			// Don't ask for user location again
			return;
		}
		$summary_label.show();
		$busy_icon.show();
		if ( navigator.geolocation ) {
			navigator.geolocation.getCurrentPosition( function( position ) {
				summarizeLocation( {
					lat: position.coords.latitude,
					lng: position.coords.longitude
				} );
			}, function() {
				$busy_icon.hide();
				$summary_input.show();
			} );
		} else if ( google && google.gears ) {
			var geo = google.gears.factory.create( 'beta.geolocation' );
			geo.getCurrentPosition( function( position ) {
				summarizeLocation( {
					lat: position.latitude,
					lng: position.longitude
				} );
			}, function() {
				$busy_icon.hide();
				$summary_input.show();
			} );
		}
	} );

	$summary_input.focus( function() {
		$summary_input.select();
	} );
	$summary_input.change( search );
	$summary_input.keypress( function( e ) {
		if ((e.keyCode && e.keyCode === 13) || (e.which && e.which === 13)) {
			// Enter key was hit - new search
			search();
			return false;
		} else {
			search_input_changed = true;
			return true;
		}
	} );
} );