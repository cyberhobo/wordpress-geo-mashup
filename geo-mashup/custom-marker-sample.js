// Use a different marker icon
GeoMashup.marker_icon = new GIcon();
GeoMashup.marker_icon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
GeoMashup.marker_icon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
GeoMashup.marker_icon.iconSize = new GSize(12, 20);
GeoMashup.marker_icon.shadowSize = new GSize(22, 20);
GeoMashup.marker_icon.iconAnchor = new GPoint(6, 20);
GeoMashup.marker_icon.infoWindowAnchor = new GPoint(5, 1);

// Provide a default center location
GeoMashup.loadLat = 18.5;
GeoMashup.loadLon = 15.3;

// Customize the map
GeoMashup.customizeMap = function(map) {
	// Manipulate the map
}
