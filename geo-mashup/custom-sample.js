/**
 * GeoMashup customization examples 
 * 
 * * The filename must be changed to custom.js for customizations to take effect.
 * * You can edit the examples and enable customizations you want.
 * * If you know javascript, you can add your own customizations using the Google Maps API
 *   documented at http://code.google.com/apis/maps/documentation/reference.html#GMap2
 *
 * Properties
 * A properties object is available in all custom functions, and has these useful
 * variables:
 * * properties.url_path - the URL of the geo-mashup plugin directory
 * * properties.template_url_path - the URL of the active theme directory
 * * properties.map_content - 'global', 'single', or 'contextual'
 * * properties.map_cat     - the category ID of a cateogory map
 *
 * The old custom-marker.js from pre-1.0 versions of Geo Mashup is no longer used.
 */

/**
 * Customize a Geo Mashup map after the relevant content has been loaded.
 *
 * @param properties the properties of the GeoMashup being customized
 * @param map        the Google Map object documented at http://code.google.com/apis/maps/documentation/reference.html#GMap2
 */
function customizeGeoMashupMap ( properties, map ) {

	// Load some KML only into global maps - for instance pictures of squirrels
	
	/* DELETE this line to enable this example
	if (properties.map_content == 'global') {
		var kml = new GGeoXml("http://api.flickr.com/services/feeds/geo/?g=52241987644@N01&lang=en-us&format=rss_200");
		map.addOverlay(kml);
	}
	DELETE this line to enable this example */

	// Recenter the map when displaying category ID 7
	
	/* DELETE this line to enable this example
	if (properties.map_cat == 7) {
		map.setCenter( new GLatLng(18.5,15.3), 3 );
	}
	DELETE this line to enable this example */
}

/**
 * Provide a custom marker icon by color name. Since multiple categories can
 * be assigned to the same color in the Geo Mashup Options, this can be more
 * efficient than providing individual category icons.
 *
 * @param properties the properties of the GeoMashup object being customized
 * @param color_name the color_name assigned in the Geo Mashup Options
 * @return the custom GIcon
 */
function customGeoMashupColorIcon ( properties, color_name ) {
  var icon = null;
	
	// Make an icon for the color 'lime' from images in the geo-mashup/images directory
	
	/* DELETE this line to enable this example 
  if (color_name == 'fuchsia') {
    icon = new GIcon();
    icon.image = properties.url_path + '/images/my_lime_icon.png';
    icon.shadow = properties.url_path + '/images/my_shadow.png';
    icon.iconSize = new GSize(21, 31);
    icon.shadowSize = new GSize(51, 29);
    icon.iconAnchor = new GPoint(8, 31);
    icon.infoWindowAnchor = new GPoint(8, 1);
  }
	DELETE this line to enable this example */

  return icon;
}

/**
 * Provide a custom marker icon by category names.
 *
 * @param properties the properties of the GeoMashup object being customized
 * @param categories the array of category names assigned to post being marked
 * @return the custom GIcon
 */
function customGeoMashupCategoryIcon ( properties, categories ) {
  var icon = null;

	// Make an icon for posts whose first category is 7
	// using images from the current template directory
	
	/* DELETE this line to enable this example 
  if (categories[0] == 7) {
    icon = new GIcon();
    icon.image = properties.template_url_path + '/images/my_elephant_icon.png';
    icon.shadow = properties.template_url_path + '/images/my_shadow.png';
    icon.iconSize = new GSize(21, 31);
    icon.shadowSize = new GSize(51, 29);
    icon.iconAnchor = new GPoint(8, 31);
    icon.infoWindowAnchor = new GPoint(8, 1);
  }
	DELETE this line to enable this example */

	// Make an icon for posts assigned to multiple categories
	// using images from the current template directory
	
	/* DELETE this line to enable this example 
  if (categories.length > 1) {
    icon = new GIcon();
    icon.image = properties.template_url_path + '/images/my_mixed_icon.png';
    icon.shadow = properties.template_url_path + '/images/my_shadow.png';
    icon.iconSize = new GSize(21, 31);
    icon.shadowSize = new GSize(51, 29);
    icon.iconAnchor = new GPoint(8, 31);
    icon.infoWindowAnchor = new GPoint(8, 1);
  }
	DELETE this line to enable this example */

  return icon;
}

/**
 * Provide a custom marker for single post maps.
 *
 * @param properties the properties of the GeoMashup object being customized
 * @return the custom GIcon
 */
function customGeoMashupSinglePostIcon ( properties ) {
	var icon = null;
  
	// Use the Google 'A' icon instead of the default

	/* DELETE this line to enable this example 
	icon = new GIcon(G_DEFAULT_ICON);
	icon.image = 'http://www.google.com/mapfiles/markerA.png';
	DELETE this line to enable this example */

	return icon;
}
	
/**
 * Provide a custom marker for locations with multiple posts.  The marker icon 
 * already exists when we discover more posts there, so we just change the image. 
 *
 * @param properties the properties of the GeoMashup object being customized
 * @return the custom GIcon
 */
function customGeoMashupMultiplePostImage ( properties, current_image ) {
	var image = null;

	// Replace multiple post icons only when the current image is in the format 
	// my_*.png, where the asterisk can be any letters.

	/* DELETE this line to enable this example 
	if (current_image.search(/my_\w*.png/) >= 0) {
		image = properties.template_url_path + '/images/my_plus.png';
	}
	DELETE this line to enable this example */

  return image;
}


