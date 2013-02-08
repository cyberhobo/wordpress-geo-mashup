/*global QUnit, asyncTest, ok, equal, start, jQuery, gm_test_data */
/*jslint browser: true */

jQuery( function( $ ) {
	var location_count = parseInt( gm_test_data.location_count, 10 );

	function loadTestFrame( url, callback ) {
		var $frame = $( '<iframe/>' )
			.attr( 'name', gm_test_data.name )
			.attr( 'width', gm_test_data.width )
			.attr( 'height', gm_test_data.height )
			.appendTo( '#qunit-fixture' )
			.attr( 'src' , url );

		return $frame.load( callback );
	}

	$.each( gm_test_data.test_apis, function( i, api ) {

		asyncTest( api + " global loads", 9, function() {
			loadTestFrame( gm_test_data.global_urls[api], function() {
				var gm = window.frames[gm_test_data.name].GeoMashup;
				ok( gm, 'GeoMashup object is available' );
				ok( gm.map, 'map object is available' );
				ok( gm.map.markers, 'markers are available' );
				equal( gm.map.markers.length, location_count, 'a marker is created for each location' );
				ok( gm.map.polylines, 'polylines are available' );
				equal( gm.map.polylines.length, 1, 'a polyline is created for each term with line zoom set' );
				equal( gm.map.getZoom(), 10, 'initial zoom is as specified (10)' );
				QUnit.close(
					gm.map.getCenter().lat,
					gm.map.markers[0].location.lat,
					0.005,
					'map center latitude is near the first marker'
				);
				QUnit.close(
					gm.map.getCenter().lon,
					gm.map.markers[0].location.lon,
					0.005,
					'map center longitude is near the first marker'
				);
				start();
			} );
		} );

		asyncTest( api + " markers respond", location_count*3, function() {
			loadTestFrame( gm_test_data.global_urls[api], function() {
				var gm = window.frames[gm_test_data.name].GeoMashup;
				$.each( gm.map.markers, function( i, marker ) {
					// Firefox breaking very strangely here, why?
					marker.click.fire();
					equal( gm.selected_marker, marker, 'a marker is selected when clicked' );
					QUnit.close(
						marker.location.lat,
						gm.map.getCenter().lat,
						0.005,
						'map center latitude is close to selected marker'
					);
					QUnit.close(
						marker.location.lon,
						gm.map.getCenter().lon,
						0.005,
						'map center longitude is close to selected marker'
					);
				} );
				start();
			} );
		} );
	} );
} );
