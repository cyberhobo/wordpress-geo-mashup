<?php
/** 
 * Geo Mashup shortcodes
 *
 * @package GeoMashup
 */

add_shortcode('geo_mashup_map', array( 'GeoMashup', 'map' ) );
add_shortcode('geo_mashup_show_on_map_link', array( 'GeoMashup', 'show_on_map_link' ) );
add_shortcode('geo_mashup_show_on_map_link_url', array( 'GeoMashup', 'show_on_map_link_url' ) );
add_shortcode('geo_mashup_full_post', array( 'GeoMashup', 'full_post' ) );
add_shortcode('geo_mashup_category_name', array( 'GeoMashup', 'category_name' ) );
add_shortcode('geo_mashup_category_legend', array( 'GeoMashup', 'category_legend') );
add_shortcode('geo_mashup_term_legend', array( 'GeoMashup', 'term_legend') );
add_shortcode('geo_mashup_list_located_posts', array( 'GeoMashup', 'list_located_posts' ) );
add_shortcode('geo_mashup_list_located_posts_by_area', array( 'GeoMashup', 'list_located_posts_by_area' ) );
add_shortcode('geo_mashup_tabbed_category_index', array( 'GeoMashup', 'tabbed_category_index' ) );
add_shortcode('geo_mashup_tabbed_term_index', array( 'GeoMashup', 'tabbed_term_index' ) );
add_shortcode('geo_mashup_visible_posts_list', array( 'GeoMashup', 'visible_posts_list' ) );
add_shortcode('geo_mashup_location_info', array( 'GeoMashup', 'location_info' ) );
add_shortcode('geo_mashup_nearby_list', array( 'GeoMashup', 'nearby_list' ) );

/**
 * Map template tag alias.
 *
 * Leave one old shortcode function in the global namespace, since Bruce used
 * it with function_exists() in his implementation guide.
 *
 * @since 1.0
 * @access public
 * @deprecated 1.2 Use GeoMashup::map()
 *
 * @param array $atts Shortcode arguments.
 */
function geo_mashup_map( $atts ) {
	return GeoMashup::map( $atts );
}

?>
