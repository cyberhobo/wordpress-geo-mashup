/**
 * GeoMashup customization example
 * 
 * The filename must be custom.js for customizations to take effect.
 * You can edit the example and delete customizations you don't want.
 * If you know javascript, you can add your own customizations.
 *
 * The old custom-marker.js from pre 1.0 versions of Geo Mashup is no longer used.
 */
function customizeGeoMashup(mashup) {
	// Use a different marker icon
	mashup.marker_icon = new GIcon();
	mashup.marker_icon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
	mashup.marker_icon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
	mashup.marker_icon.iconSize = new GSize(12, 20);
	mashup.marker_icon.shadowSize = new GSize(22, 20);
	mashup.marker_icon.iconAnchor = new GPoint(6, 20);
	mashup.marker_icon.infoWindowAnchor = new GPoint(5, 1);

	// Recenter the map
	mashup.map.setCenter( new GLatLng(18.5,15.3) );
}
