<?php /*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Tools for adding maps to your blog, and plotting posts on a master map. Configure in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Settings->Geo Mashup</a> after the plugin is activated. Documentation is <a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation">on the project site</a>.
Version: 1.3alpha
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 2.6
*/

/*
Copyright (c) 2005-2007 Dylan Kuhn

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

/**
 * The Geo Mashup static class.
 */
if ( !class_exists( 'GeoMashup' ) ) {
class GeoMashup {

	function load() {
		GeoMashup::load_constants();
		load_plugin_textdomain('GeoMashup', 'wp-content/plugins/'.GEO_MASHUP_DIRECTORY, GEO_MASHUP_DIRECTORY);

		GeoMashup::load_dependencies();
		GeoMashup::load_hooks();
	}

	function init() {
		GeoMashup::load_styles();
		GeoMashup::load_scripts();
	}

	function load_dependencies() {
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-db.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-options.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-ui-managers.php' );
		if ( !is_admin() ) {
			include_once( GEO_MASHUP_DIR_PATH . '/shortcodes.php');
		}
	}

	function load_hooks() {
		global $geo_mashup_options;
		if (is_admin()) {

			// To upgrade tables
			register_activation_hook( __FILE__, array( 'GeoMashupDB', 'install' ) );

			// To add Geo Mashup settings page
			add_action('admin_menu', array('GeoMashup', 'admin_menu'));

			// To activate jQuery tabs on the settings page
			add_action('admin_print_scripts', array('GeoMashup', 'admin_print_scripts'));

		} else {

			if ($geo_mashup_options->get('overall','add_category_links') == 'true') {
				// To add map links to a category list - flaky
				add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
			}

			// To output location meta tags in the page head
			add_action('wp_head', array('GeoMashup', 'wp_head'));

			// To add the GeoRSS namespace to RSS feeds
			add_action('rss_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
			add_action('atom_ns', array('GeoMashup', 'rss_ns'));

			// To add GeoRSS location to RSS feeds
			add_action('rss_item', array('GeoMashup', 'rss_item'));
			add_action('rss2_item', array('GeoMashup', 'rss_item'));
			add_action('atom_entry', array('GeoMashup', 'rss_item'));
		}
	}

	function load_constants() {
		define('GEO_MASHUP_PLUGIN_NAME', plugin_basename(__FILE__));
		define('GEO_MASHUP_DIR_PATH', dirname( __FILE__ ));
		define('GEO_MASHUP_DIRECTORY', substr(GEO_MASHUP_PLUGIN_NAME, 0, strpos(GEO_MASHUP_PLUGIN_NAME, '/')));
		if ( defined( 'WPMU_PLUGIN_URL' ) &&  strpos( __FILE__, substr( MUPLUGINDIR, strpos( MUPLUGINDIR, '/' ) ) ) !== false ) {
			// We are in an mu-plugins directory
			define('GEO_MASHUP_URL_PATH', WPMU_PLUGIN_URL . '/' . GEO_MASHUP_DIRECTORY);
		} else {
			// We're in the usual plugin directory
			define('GEO_MASHUP_URL_PATH', WP_PLUGIN_URL . '/' . GEO_MASHUP_DIRECTORY);
		}
		define('GEO_MASHUP_MAX_ZOOM', 20);
		define('GEO_MASHUP_VERSION', '1.2.4');
		define('GEO_MASHUP_DB_VERSION', '1.2');
	}

	function load_scripts() {
		global $geo_mashup_options;

		// Other plugins could want the google-jsapi script
		wp_register_script( 'google-jsapi', 'http://www.google.com/jsapi?key='.$geo_mashup_options->get('overall', 'google_key') );
		if (is_admin()) {
			if ( isset($_GET['page']) &&  GEO_MASHUP_PLUGIN_NAME === $_GET['page'] ) {
				wp_enqueue_script( 'jquery-ui-tabs' );
			}
		} else {
			wp_enqueue_script( 'geo-mashup-loader', GEO_MASHUP_URL_PATH.'/geo-mashup-loader.js', array( 'google-jsapi' ), GEO_MASHUP_VERSION);
		}
	}

	function load_styles() {
		if (is_admin()) {
			if ( isset($_GET['page']) && GEO_MASHUP_PLUGIN_NAME === $_GET['page'] ) {
				wp_enqueue_style( 'geo-mashup-tabs', GEO_MASHUP_URL_PATH . '/jquery.tabs.css', false, '2.5.0', 'screen' );
			}
		}
	}

	function explode_assoc($glue1, $glue2, $array) {
		$array2=explode($glue2, $array);
		foreach($array2 as  $val) {
			$pos=strpos($val,$glue1);
			$key=substr($val,0,$pos);
			$array3[$key] =substr($val,$pos+1,strlen($val));
		}
		return $array3;
	}

	function implode_assoc($inner_glue, $outer_glue, $array, $skip_empty=false, $urlencoded=false) {
	$output = array();
		foreach($array as $key=>$item) {
			if (!$skip_empty || isset($item)) {
				if ($urlencoded)
					$output[] = preg_replace('/\s/', ' ', $key.$inner_glue.urlencode($item));
				else
					$output[] = preg_replace('/\s/', ' ', $key.$inner_glue.$item);
			}
		}
		return implode($outer_glue, $output);
	}

	function get_kml_attachment_urls($post_id)
	{
		if ( empty( $post_id ) )
		{
			return array();
		}
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => null,
			'post_status' => null,
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$urls = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$attachment_url = $attachment->guid;
				if (stripos($attachment_url,'.kml') == strlen($attachment_url)-4)
				{
					array_push($urls,$attachment_url);
				}
			}
		}
		return $urls;
	}

	function wp_head() {
		global $wp_query;

		if (is_single())
		{
			$loc = GeoMashupDB::get_object_location( 'post', $wp_query->post->ID );
			if (!empty($loc)) {
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo "<meta name=\"ICBM\" content=\"{$loc->lat}, {$loc->lng}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$loc->lat};{$loc->lng}\" />\n";
			}
		}
		else
		{
			$saved_locations = GeoMashupDB::get_saved_locations( );
			if ( !empty( $saved_locations ) )
			{
				foreach ( $saved_locations as $saved_location ) {
					if ( $saved_location->saved_name == 'default' ) {
						$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))));
						echo "<meta name=\"ICBM\" content=\"{$saved_location->lat}, {$saved_location->lon}\" />\n";
						echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
						echo "<meta name=\"geo.position\" content=\"{$saved_location->lat};{$saved_location->lon}\" />\n";
					}
				}
			}
		}
	}

	function admin_print_scripts($not_used) {
		// Use jQuery to tabify the Geo Mashup options
		if ( isset($_GET['page']) && GEO_MASHUP_PLUGIN_NAME === $_GET['page'] ) {

			echo '
				<script type="text/javascript"> 
					addLoadEvent(function() { jQuery("#geo-mashup-settings-form > ul").tabs(); }); 
				</script>';

		}
	}

	function get_post_locations_json($query_args) {
		$json = '{ posts : [';
		$posts = GeoMashupDB::get_object_locations($query_args);
		if ($posts) {
			$comma = '';
			foreach ($posts as $post) {
				$json .= $comma.'{"post_id":"'.$post->post_id.'","title":"'.addslashes($post->post_title).
					'","lat":"'.$post->lat.'","lng":"'.$post->lng.'","categories":[';
				$categories = get_the_category( $post->post_id );
				$categories_comma = '';
				foreach ($categories as $category) {
					$json .= $categories_comma.'"'.$category->cat_ID.'"';
					$categories_comma = ',';
				}
				$json .= ']}';
				$comma = ',';
			}
		}
		$json .= ']}';

		return $json;
	}

	function map($option_args = null) {
		return geo_mashup_map(wp_parse_args($option_args));
	}

	function full_post($args = null) {
		$args = wp_parse_args($args);
		$for_map = 'gm';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	

		return '<div id="' . $for_map . '-post"></div>';
	}

	function category_name($option_args = null) {
		$category_name = '';
		if (is_string($option_args)) {
			$option_args = wp_parse_args($option_args);
		}
		if (is_page() && isset($_SERVER['QUERY_STRING'])) {
			$option_args = $option_args + GeoMashup::explode_assoc('=','&amp;',$_SERVER['QUERY_STRING']);
		}
		if (isset($option_args['map_cat'])) {
			$category_name = get_cat_name($option_args['map_cat']);
		}
		return $category_name;
	}

	function category_legend($args = null) {
		$args = wp_parse_args($args);
		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		return '<div id="' . $for_map . '-legend"></div>';
	}

	function list_cats($content, $category = null) {
		global $geo_mashup_options;

		if ($category) {
			$count = GeoMashupDB::category_located_post_count( $category->cat_ID );
			// Add map link only if there are geo-located posts to see
			if ($count) {
				// This feature doesn't work unless there is a category description
				if ( empty( $category->description ) ) {
					return $content . $geo_mashup_options->get('overall', 'category_link_separator') . 
						__( 'You must add a description to this category to use this Geo Mashup feature.', 'GeoMashup' );
				}
				$link = '';
				$url = get_page_link($geo_mashup_options->get('overall', 'mashup_page'));
				if (strstr($url,'?')) {
					$url .= '&amp;';
				} else {
					$url .= '?';
				}
				$link = '<a href="'.$url.'map_cat='.$category->cat_ID.'&amp;zoom='.$geo_mashup_options->get('overall', 'category_zoom').
					'" title="'.$geo_mashup_options->get('overall', 'category_link_text').'">';
				return $content.'</a>'.$geo_mashup_options->get('overall', 'category_link_separator').$link.
					$geo_mashup_options->get('overall', 'category_link_text');
			}
		}
		return $content;
	}

	function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page(__('Geo Mashup Options','GeoMashup'), __('Geo Mashup','GeoMashup'), 8, __FILE__, array('GeoMashup', 'options_page'));
		}
	}

	function options_page() {
		include_once(dirname(__FILE__) . '/options.php');
		geo_mashup_options_page();
	}

	/**
	 * A tag to insert the the onload call needed by IE 
	 * (and works in Firefox) in the body tag to load the 
	 * Google map. DEPRECATED
	 */
	function body_attribute() {
	}

	/**
	 * A tag to insert a link to a post on the mashup.
	 */
	function post_link($option_args = null) {
		global $post, $geo_mashup_options;
		$options = array('text' => __('Show on map','GeoMashup'),
			 'display' => false,
			 'show_icon' => true);
		if (is_array($option_args)) {
			$options = $option_args + $options;
		} if (is_string($option_args)) {
			$options = wp_parse_args($option_args, $options);
		}
		$coords = GeoMashup::post_coordinates();
		$lat = $coords['lat'];
		$lng = $coords['lng'];
		if ($lat && $lng) {
			$icon = '';
			if ($options['show_icon'] && strcmp($options['show_icon'],'false') != 0) {
				$icon = '<img src="'.GEO_MASHUP_URL_PATH.
					'/images/geotag_16.png" alt="'.__('Geotag Icon','GeoMashup').'"/>';
			}
			$link = '';
			$url = get_page_link($geo_mashup_options->get('overall', 'mashup_page'));
			if (strstr($url,'?')) {
				$url .= '&amp;';
			} else {
				$url .= '?';
			}
			$open = '';
			if ($geo_mashup_options->get('global_map', 'auto_info_open') == 'true') {
				$open = '&open_post_id=' . $post->ID;
			}
			$zoom = '';
			if ( !empty( $options['zoom'] ) ) {
				$zoom = '&zoom=' . $options['zoom'];
			}
			$link = '<a class="gm-link" href="'.$url.htmlentities("center_lat=$lat&center_lng=$lng$open$zoom").'">'.
				$icon.' '.$options['text'].'</a>';
			if ($options['display']) {
				echo $link;
				return true;
			} else {
				return $link;
			}
		}
	}

	/**
	* A better name for the post_link tag.
	*/
	function show_on_map_link($option_args = null) {
		return GeoMashup::post_link($option_args);
	}

	/** 
	 * Place a list of visible posts.
	 */
	function visible_posts_list($args = null) {
		$args = wp_parse_args($args);

		$list_html = '';

		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		if ( !empty( $args['heading_text'] ) ) {
			$heading_div = '<div id="' . $for_map . '-visible-list-header" style="display:none;">';
			$heading_tags = '<h2>';
			if ( !empty( $args['heading_tags'] ) ) {
				$heading_tags = $args['heading_tags'];
			}
			$list_html .= balanceTags( $heading_div . $heading_tags . $args['heading_text'], true );
		}
		$list_html .= '<div id="' . $for_map . '-visible-list"></div>';
		return $list_html;
	}

	/**
	 * List all located posts.
	 */
	function list_located_posts( $option_args = null ) {
		$option_args = wp_parse_args( $option_args );
		$option_args['object_name'] = 'post';
		$list_html = '<ul class="gm-index-posts">';
		$locs = GeoMashupDB::get_object_locations( $option_args );
		if ($locs)
		{
			foreach ($locs as $loc) {
				$list_html .= '<li><a href="'.get_permalink($loc->post_id).'">'.
					$loc->post_title."</a></li>\n";
			}
		}
		$list_html .= '</ul>';
		return $list_html;
	}

	function list_located_posts_by_area( $args ) {
		$args = wp_parse_args( $args );
		$list_html = '<div class="gm-area-list">';
		$countries = GeoMashupDB::get_distinct_located_values( 'country_code' );
		$country_count = count( $countries );
		foreach ( $countries as $country ) {
			if ( $country_count > 1 ) {
				$list_html .= '<h3>' . GeoMashupDB::get_administrative_name( $country->country_code ) . '</h3>';
			}
			$states = GeoMashupDB::get_distinct_located_values( 'admin_code', $country->country_code );
			foreach ($states  as $state ) { 
				$list_html .= '<h4>' . 
					GeoMashupDB::get_administrative_name( $country->country_code, $state->admin_code ) . 
					'</h4><ul class="gm-index-posts">';
				$location_query = array( 
					'country_code' => $country->country_code,
					'admin_code' => $state->admin_code 
				);
				$post_locations = GeoMashupDB::get_object_locations( 'post', $location_query );
				foreach ( $post_locations as $post_location ) { 
					$list_html .= '<li><a href="' . 
						get_permalink( $post_location->post_id ) .
						'">' .
						$post_location->post_title .
						'</a>';
					if ( isset( $args['include_address'] ) && $args['include_address'] == 'true' ) {
						$list_html .= '<p>' . $post_location->address . '</p>';
					}
					$list_html .= '</li>';
				}
				$list_html .= '</ul>';
			}
		}
		$list_html .= '</div>';
		return $list_html;
	}

	/**
	* Fetch post coordinates.
	*/
	function post_coordinates($places = 10) {
		global $post;

		$location = GeoMashupDB::get_object_location( 'post', $post->ID );
		$coordinates = array();
		if ( !empty( $location ) ) {
			$lat = $location->lat;
			$lng = $location->lng;
			$lat_dec_pos = strpos($lat,'.');
			if ($lat_dec_pos !== false) {
				$lat = substr($lat, 0, $lat_dec_pos+$places+1);
			}
			$lng_dec_pos = strpos($lng,'.');
			if ($lng_dec_pos !== false) {
				$lng = substr($lng, 0, $lng_dec_pos+$places+1);
			}
			$coordinates['lat'] = $lat;
			$coordinates['lng'] = $lng;
		}
		return $coordinates;
	}

	/**
	 * Emit GeoRSS namespace
	 */
	function rss_ns() {
		echo 'xmlns:georss="http://www.georss.org/georss" ';
	}

	/**
	* Emit GeoRSS tags.
	*/
	function rss_item() {
		global $wp_query;

		// Using Simple GeoRSS for now
		$location = GeoMashupDB::get_object_location( 'post', $wp_query->post->ID );
		if ( !empty( $location ) ) {
			echo '<georss:point>' . $location->lat . ' ' . $location->lng . '</georss:point>';
		}
	}

	/**
	 * Return the container div for a tabbed category index for a map.
	 */
	function tabbed_category_index( $args ) {
		$args = wp_parse_args($args);

		wp_enqueue_script('jquery-ui-tabs');
		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		
		return '<div id="' . $for_map . '-tabbed-index"></div>';
	}
} // class GeoMashup
GeoMashup::load();
} // class exists

add_action( 'init', array('GeoMashup', 'init') );

?>
