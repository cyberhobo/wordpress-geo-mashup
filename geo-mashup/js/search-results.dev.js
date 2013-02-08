/*
 * Geo Mashup customization for search results map
 */
/*global GeoMashup, google, mxn */
GeoMashup.addAction( 'loadedMap', function( properties, map ) {
	var search_marker, search_latlng, icon; 
	// Double-check that we're customizing the right map
	if ( 'search-results-map' === properties.name && properties.search_text ) {

		// Put the blue dot at the search location
		if ( 'google' === properties.map_api ) {

			icon = new google.maps.Icon();
			icon.image = properties.url_path + '/images/bluedot16.png';
			icon.shadow = properties.url_path + '/images/dotshadow.png';
			icon.iconSize = new google.maps.Size( 16, 16 );
			icon.shadowSize = new google.maps.Size( 25, 16 );
			icon.iconAnchor = new google.maps.Point( 8, 8 );
			search_latlng = new google.maps.LatLng( parseFloat( properties.search_lat), parseFloat( properties.search_lng ) );
			search_marker = new google.maps.Marker( search_latlng, {
				icon: icon,
				title: properties.search_text
			} );
			map.addOverlay( search_marker );

		} else {

			// mxn
			search_latlng = new mxn.LatLonPoint( parseFloat( properties.search_lat), parseFloat( properties.search_lng ) );
			search_marker = new mxn.Marker( search_latlng );
			search_marker.addData( {
				icon: properties.url_path + '/images/bluedot16.png',
				iconShadow: properties.url_path + '/images/dotshadow.png',
				iconSize: [ 16, 16 ],
				iconShadowSize: [ 25, 16 ],
				iconAnchor: [ 8, 8 ],
				label: properties.search_text
			} );
			map.addMarker( search_marker );
		}
	}
} );
