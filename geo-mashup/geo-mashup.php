<?php /*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Tools for adding maps to your blog, and plotting posts on a master map. Configure in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Settings->Geo Mashup</a> after the plugin is activated.
Version: 1.2beta2
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 2.5.1
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
class GeoMashup {

	function load() {
		GeoMashup::load_constants();
		load_plugin_textdomain('GeoMashup', 'wp-content/plugins/'.GEO_MASHUP_DIRECTORY.'/languages');

		GeoMashup::load_dependencies();
		GeoMashup::load_hooks();
		GeoMashup::load_styles();
		GeoMashup::load_scripts();
	}

	function load_dependencies() {
		include_once ( dirname( __FILE__) . '/geo-mashup-options.php' );
		include_once( dirname( __FILE__ ) . '/geo-mashup-db.php' );
		if ( !is_admin() ) {
			include_once(dirname(__FILE__) . '/shortcodes.php');
		}
	}

	function load_hooks() {
		global $geo_mashup_options;
		if (is_admin()) {
			register_activation_hook( __FILE__, array( 'GeoMashupDB', 'install' ) );
			add_filter('upload_mimes', array('GeoMashup', 'upload_mimes'));

			add_action('admin_menu', array('GeoMashup', 'admin_menu'));
			add_action('save_post', array('GeoMashup', 'save_post'));
			add_action('wp_handle_upload', array('GeoMashup', 'wp_handle_upload'));
			add_action('admin_print_scripts', array('GeoMashup', 'admin_print_scripts'));
		} else {
			if ($geo_mashup_options->get('overall','add_category_links') == 'true') {
				add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
			}

			add_action('wp_head', array('GeoMashup', 'wp_head'));
			add_action('rss_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
			add_action('atom_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss_item', array('GeoMashup', 'rss_item'));
			add_action('rss2_item', array('GeoMashup', 'rss_item'));
			add_action('atom_entry', array('GeoMashup', 'rss_item'));
		}
	}

	function load_constants() {
		define('GEO_MASHUP_PLUGIN_NAME', plugin_basename(__FILE__));
		define('GEO_MASHUP_DIRECTORY', substr(GEO_MASHUP_PLUGIN_NAME, 0, strpos(GEO_MASHUP_PLUGIN_NAME, '/')));
		define('GEO_MASHUP_URL_PATH', WP_CONTENT_URL . '/plugins/' . GEO_MASHUP_DIRECTORY);
		define('GEO_MASHUP_MAX_ZOOM', 20);
		define('GEO_MASHUP_DB_VERSION', '1.0');
	}

	function load_scripts() {
		global $geo_mashup_options;
		if (is_admin()) {
			if ($_GET['page'] == GEO_MASHUP_PLUGIN_NAME) {

				wp_enqueue_script('jquery-ui-tabs');

			} else if (preg_match('/(post|page)(-new|).php/',$_SERVER['REQUEST_URI'])) {

				wp_enqueue_script('geo-mashup-google-api', 'http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$geo_mashup_options->get('overall', 'google_key'));
				wp_enqueue_script('geo-mashup-admin', GEO_MASHUP_URL_PATH.'/geo-mashup-admin.js', false, '1.0.0');
				wp_enqueue_script('json-script-request', GEO_MASHUP_URL_PATH.'/JSONscriptRequest.js', false, '1.0.0');

			}
		} else {
			wp_enqueue_script('geo-mashup-loader',GEO_MASHUP_URL_PATH.'/geo-mashup-loader.js',false,'1.0.0');
		}
	}

	function load_styles() {
		if (is_admin()) {
			if ($_GET['page'] == GEO_MASHUP_PLUGIN_NAME) {

				wp_enqueue_style('geo-mashup-tabs', GEO_MASHUP_URL_PATH.'/jquery.tabs.css', false, '2.5.0', 'screen');

			} else if (preg_match('/(post|page)(-new|).php/', $_SERVER['REQUEST_URI'])) {

				wp_enqueue_style('geo-mashup-edit-form', GEO_MASHUP_URL_PATH.'/edit-form.css', false, '1.0.0', 'screen');

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
					$output[] = $key.$inner_glue.urlencode($item);
				else
					$output[] = $key.$inner_glue.$item;
			}
		}
		return implode($outer_glue, $output);
	}

	function get_kml_attachment_urls($post_id)
	{
		if (!isset($post_id) || is_null($post_id))
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

	function upload_mimes($mimes) {
		$mimes['kml'] = 'application/vnd.google-earth.kml+xml';
		return $mimes;
	}

	function wp_head() {
		global $wp_query;

		if (is_single())
		{
			$loc = GeoMashupDB::get_post_location( $wp_query->post->ID );
			if (!empty($loc)) {
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo "<meta name=\"ICBM\" content=\"{$loc->lat}, {$loc->lng}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$lat->lat};{$lon->lng}\" />\n";
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

	function admin_print_scripts($not_used)
	{
		if ($_GET['page'] == GEO_MASHUP_PLUGIN_NAME) {

			echo '
				<script type="text/javascript"> 
					addLoadEvent(function() { jQuery("#geo-mashup-settings-form > ul").tabs(); }); 
				</script>';

		} else if (preg_match('/(post|page)(-new|).php/', $_SERVER['REQUEST_URI'])) {

			$kml_url = get_option('geo_mashup_temp_kml_url');
			if (strlen($kml_url) > 0)
			{
				echo '
					<script type="text/javascript"> 
						if (parent.GeoMashupAdmin) parent.GeoMashupAdmin.loadKml(\''.$kml_url.'\');
					</script>';
				update_option('geo_mashup_temp_kml_url','');
			}
		}
	}

	function wp_handle_upload($args)
	{
		update_option('geo_mashup_temp_kml_url','');
		if (is_array($args) && isset($args->file)) {
			if (stripos($args['file'],'.kml') == strlen($args['file'])-4) {
				update_option('geo_mashup_temp_kml_url',$args['url']);
			}
		}
		return $args;
	}

	function edit_form_advanced()
	{
		include_once(dirname(__FILE__) . '/edit-form.php');
		geo_mashup_edit_form();
	}

	function save_post($post_id) {
		if (!wp_verify_nonce($_POST['geo_mashup_edit_nonce'], 'geo-mashup-edit-post')) {
			return $post_id;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
		}

		update_option('geo_mashup_temp_kml_url','');

		if (isset($_POST['geo_mashup_changed']) && $_POST['geo_mashup_changed'] == 'true') {
			if ( empty( $_POST['geo_mashup_location'] ) ) {
				GeoMashupDB::delete_post_location( $post_id );
			} else if ( !empty( $_POST['geo_mashup_location_id'] ) ) {
				GeoMashupDB::set_post_location( $post_id, $_POST['geo_mashup_location_id'] );
			} else {
				list( $lat, $lng ) = split( ',', $_POST['geo_mashup_location'] );
				$post_location = array( );
				$post_location['lat'] = trim( $lat );
				$post_location['lng'] = trim( $lng );
				$post_location['saved_name'] = $_POST['geo_mashup_location_name'];
				$post_location['geoname'] = $_POST['geo_mashup_geoname'];
				$post_location['address'] = $_POST['geo_mashup_address'];
				$post_location['postal_code'] = $_POST['geo_mashup_postal_code'];
				$post_location['country_code'] = $_POST['geo_mashup_country_code'];
				$post_location['admin_code'] = $_POST['geo_mashup_admin_code'];
				$post_location['admin_name'] = $_POST['geo_mashup_admin_name'];
				$post_location['sub_admin_code'] = $_POST['geo_mashup_sub_admin_code'];
				$post_location['sub_admin_name'] = $_POST['geo_mashup_sub_admin_name'];
				$post_location['locality_name'] = $_POST['geo_mashup_locality_name'];
				GeoMashupDB::set_post_location( $post_id, $post_location );
			}
		}
		
		return $post_id;
	}

	function get_post_locations_json($query_args)
	{
		$json = '{ posts : [';
		$posts = GeoMashupDB::get_post_locations($query_args);
		if ($posts) {
			$comma = '';
			foreach ($posts as $post) {
				$json .= $comma.'{"post_id":"'.$post->post_id.'","title":"'.addslashes($post->post_title).
					'","lat":"'.$post->lat.'","lng":"'.$post->lng.'","categories":[';
				$categories = get_the_category( $post->post_id );
				$categories_comma = '';
				foreach ($categories as $category) {
					$json .= $categories_comma.'"'.addslashes($category->name).'"';
					$categories_comma = ',';
				}
				$json .= ']}';
				$comma = ',';
			}
		}
		$json .= ']}';

		return $json;
	}

	function map($option_args = null)
	{
		return geo_mashup_map(wp_parse_args($option_args));
	}

	function full_post($option_args = null)
	{
		return '<div id="geoPost"></div>';
	}

	function category_name($option_args = null)
	{
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

	function category_legend($option_args = null)
	{
		return '<div id="geoMashupCategoryLegend"></div>';
	}

	function list_cats($content, $category = null) {
		global $geo_mashup_options;

		if ($category) {
			$count = GeoMashupDB::category_located_post_count( $category->cat_ID );
			if ($count) {
				// Add map link only if there are geo-located posts to see
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
		if (function_exists('add_meta_box')) {
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'post','advanced');
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'page','advanced');
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
			$link = '<a class="geo_mashup_link" href="'.$url.htmlentities("lat=$lat&lng=$lng&openPostId={$post->ID}").'">'.$icon.' '.$options['text'].'</a>';
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
	 * List all located posts.
	 */
	function list_located_posts($option_args = null)
	{
		global $geo_mashup_options;

		$list_html = '<ul id="geo_mashup_located_post_list">';
		$locs = GeoMashupDB::get_post_locations( $option_args );
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

	/**
	* Fetch post coordinates.
	*/
	function post_coordinates($places = 10) {
		global $post;

		$location = GeoMashupDB::get_post_location( $post->ID );
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
		$location = GeoMashupDB::get_post_location( $wp_query->post->ID );
		if ( !empty( $location ) ) {
			echo '<georss:point>' . $location->lat . ',' . $location->lng . '</georss:point>';
		}
	}
} // class GeoMashup

GeoMashup::load();

?>
