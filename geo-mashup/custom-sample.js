/**
 * GeoMashup customization example
 * 
 * The filename must be custom.js for customizations to take effect.
 * You can edit the example and delete customizations you don't want.
 * If you know javascript, you can add your own customizations.
 *
 * The old custom-marker.js from pre 1.0 versions of Geo Mashup is no longer used.
 * Look for full category marker customization in future releases.
 */
function customizeGeoMashup(mashup) {
	// Add my geotagged Flickr photos
	var geoXmlFlickr = new GGeoXml('http://api.flickr.com/services/feeds/geo/?id=82809242@N00&lang=en-us&format=rss_200');
	mashup.map.addOverlay( geoXmlFlickr );

	// Recenter the map
	// Replace 18.5, 15.3 with your latitude, longitude
	// www.theoutdoormap.com is a good place to find coordinates if you need them
	mashup.map.setCenter( new GLatLng(18.5,15.3) );
}
