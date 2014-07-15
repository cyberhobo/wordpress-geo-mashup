/*global QUnit, asyncTest, ok, equal, start, jQuery, gm_test_data */
/*jslint browser: true, nomen: true */

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

		asyncTest( api + " global loads", 11, function() {
			loadTestFrame( gm_test_data.global_urls[api], function() {
				var gm = window.frames[gm_test_data.name].GeoMashup;
				ok( gm, 'GeoMashup object is available' );
				ok( gm.map, 'map object is available' );
				ok( gm.map.markers, 'markers are available' );
				equal( gm.map.markers.length, location_count, 'a marker is created for each location' );
				ok( gm.map.polylines, 'polylines are available' );
				equal( gm.map.polylines.length, 1, 'a polyline is created for each term with line zoom set' );
				ok( gm.term_manager.isTermVisible( 2, 'test_tax' ), 'term 2 is visible' );
				ok( gm.term_manager.isTermLineVisible( 2, 'test_tax' ), 'term 2 line is visible' );
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

		asyncTest( api + " line responds to zoom", 2, function() {
			loadTestFrame( gm_test_data.global_urls[api], function() {
				var gm = window.frames[gm_test_data.name].GeoMashup;
				// Just in case a provider doesn't fire an initial change zoom event
				gm.adjustZoom();

				var after_zoom_in_test = function() {
					ok( gm.term_manager.isTermVisible( 2, 'test_tax' ), 'term 2 is visible after zoom in' );
					ok( !gm.term_manager.isTermLineVisible( 2, 'test_tax' ), 'term 2 line is not visible after zoom in' );

					gm.map.changeZoom.removeHandler( after_zoom_in_test, this );
					start();
				};

				gm.map.changeZoom.addHandler( after_zoom_in_test, this );
				gm.map.setZoom( 11 );
			} );
		} );
	} );

	asyncTest( "googlev3 clustering", 10, function() {
		loadTestFrame( gm_test_data.global_urls.googlev3, function() {
			var gm = window.frames[gm_test_data.name].GeoMashup;

			equal( gm.opts.cluster_max_zoom, '9', 'cluster max zoom is 9' );
			ok( gm.map.getZoom() > 9, 'map starts zoomed past max cluster zoom' );
			ok( gm.clusterer, 'clusterer exists' );
			equal( gm.clusterer.getTotalMarkers(), 5, 'clusterer is managing all markers' );
			equal( gm.clusterer.getMaxZoom(), 9, 'clusterer max zoom is 9' );

			var after_zoom_tests = function() {
				var clusters = gm.clusterer.clusters_;
				equal( clusters.length, 1, 'there 1 cluster at this zoom level 9' );
				ok( clusters[0].map_, 'the cluster is on the map' );
				equal( clusters[0].getMarkers().length, 5, 'all five markers are in the cluster' );
				ok( !gm.map.markers[0].proprietary_marker.map, 'first marker is not on the map' );
				ok( !gm.map.markers[4].proprietary_marker.map, 'last marker is not on the map' );
				gm.map.changeZoom.removeHandler( after_zoom_tests, this );
				start();
			};

			gm.map.changeZoom.addHandler( after_zoom_tests, this );

			gm.map.setZoom( 9 );
		});
	});
} );
