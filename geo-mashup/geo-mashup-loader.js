/*global google, GeoMashupLoader */
var GeoMashupLoader;

/**
 * Set client location in comment form when appropriate.
 */
google.setOnLoadCallback( function() {
	var lat_element;
	if ( google.loader.ClientLocation ) {
		lat_element = document.getElementById( 'geo_mashup_lat_input' );
		if (lat_element) {
			lat_element.value = google.loader.ClientLocation.latitude;
			document.getElementById( 'geo_mashup_lng_input' ).value = google.loader.ClientLocation.longitude;
			document.getElementById( 'geo_mashup_address_input' ).value = google.loader.ClientLocation.address.city +
				', ' + google.loader.ClientLocation.address.region + ', ' + google.loader.ClientLocation.address.country_code;
			document.getElementById( 'geo_mashup_country_code_input' ).value = google.loader.ClientLocation.address.country_code;
			document.getElementById( 'geo_mashup_locality_input' ).value = google.loader.ClientLocation.address.city;
		}
	}
} );

/**
 * Geo Mashup Loader object.
 *
 * Currently implements click to load feature.
 */
GeoMashupLoader = {
	addMapFrame : function (element, frame_url, height, width, name) {
		var html = ['<iframe name="'];
		element.style.backgroundImage = 'none';
		html.push(name);
		html.push('" src="');
		html.push(frame_url);
		html.push('" height="');
		html.push(height);
		html.push('" width="');
		html.push(width);
		html.push('" marginheight="0" marginwidth="0" frameborder="0" scrolling="no"></iframe>');
		element.innerHTML = html.join('');
	}
};

