<?php
/** 
 * Functions to implement Geo Mashup shortcodes.
 *
 * Originally factored out because shortcodes didn't seem to work
 * with class functions. That may have been my mistake, they definitely
 * do in WP 2.7, so now most of the code is back in the GeoMashup static 
 * class.
 */

add_shortcode('geo_mashup_map', array( 'GeoMashup', 'map' ) );
add_shortcode('geo_mashup_show_on_map_link', array( 'GeoMashup', 'show_on_map_link' ) );
add_shortcode('geo_mashup_show_on_map_link_url', array( 'GeoMashup', 'show_on_map_link_url' ) );
add_shortcode('geo_mashup_full_post', array( 'GeoMashup', 'full_post' ) );
add_shortcode('geo_mashup_category_name', array( 'GeoMashup', 'category_name' ) );
add_shortcode('geo_mashup_category_legend', array( 'GeoMashup', 'category_legend') );
add_shortcode('geo_mashup_list_located_posts', array( 'GeoMashup', 'list_located_posts' ) );
add_shortcode('geo_mashup_list_located_posts_by_area', array( 'GeoMashup', 'list_located_posts_by_area' ) );
add_shortcode('geo_mashup_tabbed_category_index', array( 'GeoMashup', 'tabbed_category_index' ) );
add_shortcode('geo_mashup_visible_posts_list', array( 'GeoMashup', 'visible_posts_list' ) );
add_shortcode('geo_mashup_location_info', array( 'GeoMashup', 'location_info' ) );

/**
 * Leave one old shortcode function in the global namespace, since Bruce used
 * it with function_exists() in the implementation guide.
 */
function geo_mashup_map( $atts ) {
	GeoMashup::map( $atts );
}
