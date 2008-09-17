<?php /*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Tools for adding maps to your blog, and plotting posts on a master map. Configure in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 1.1.1
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

load_plugin_textdomain('GeoMashup', 'wp-content/plugins/geo-mashup/languages');
$geoMashupOpts = get_settings('geo_mashup_options');

/**
 * [geo_mashup_map ...]
 */
function geo_mashup_map($atts)
{
	global $geoMashupOpts,$wp_query;

	$options = array('post_ids' => '');
	if ($wp_query->current_post == -1)
	{
		// We're outside of The Loop, go contextual
		$comma = '';
		while ($wp_query->have_posts()) {
			$wp_query->the_post();
			$options['post_ids'] .= $comma.$wp_query->post->ID;
			$comma = ',';
		}
	} else {
		$coords = GeoMashup::post_coordinates();
		if (empty($coords)) {
			// The post or page has no location - show an overview map
			$options['width'] = $geoMashupOpts['map_width'];
			$options['height'] = $geoMashupOpts['map_height'];
			$options['show_future'] = $geoMashupOpts['show_future'];
			$options['post_ids'] = $wp_query->post->ID;
			if (isset($_SERVER['QUERY_STRING'])) {
				$options = wp_parse_args($_SERVER['QUERY_STRING'],$options);
			} 
		} else {
			// The post or page has a location - show a local map
			$options['width'] = $geoMashupOpts['in_post_map_width']; 
			$options['height'] = $geoMashupOpts['in_post_map_height'];
			$options['post_ids'] = $wp_query->post->ID;
		}
	}
	if (is_array($atts)) {
		$options = array_merge($options, $atts);
	}

	$iframe_src = get_bloginfo('wpurl').'/wp-content/plugins/geo-mashup/render-map.php?'.GeoMashup::implode_assoc('=','&',$options,false,true);
	return "<div class=\"geo_mashup_map\"><iframe src=\"{$iframe_src}\" height=\"{$options['height']}\" ".
		"width=\"{$options['width']}\" marginheight=\"0\" marginwidth=\"0\" ".
		"scrolling=\"no\" frameborder=\"0\"></iframe></div>";
}

/**
* [geo_mashup_show_on_map_link ...]
*/
function geo_mashup_show_on_map_link($atts) {
	return GeoMashup::post_link($atts);
}

/**
* [geo_mashup_full_post]
*/
function geo_mashup_full_post($atts) {
	return GeoMashup::full_post();
}

/**
* [geo_mashup_category_name]
*/
function geo_mashup_category_name($atts) {
	return GeoMashup::category_name($atts);
}

/**
* [geo_mashup_category_legend]
*/
function geo_mashup_category_legend($atts) {
	return GeoMashup::category_legend($atts);
}

/**
* [geo_mashup_list_located_posts]
*/
function geo_mashup_list_located_posts($atts) {
	return GeoMashup::list_located_posts($atts);
}

/**
 * The Geo Mashup class/namespace.
 */
class GeoMashup {
	var $kml_load_script = null;

	function explode_assoc($glue1, $glue2, $array) {
		$array2=explode($glue2, $array);
		foreach($array2 as  $val)
		{
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
		global $geoMashupOpts, $wp_query;

		if (is_single())
		{
			list($lat, $lon) = split(',', get_post_meta($wp_query->post->ID, '_geo_location', true));
			if (($lat != '') && ($lon != '')) {
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$lat};{$lon}\" />\n";
			}
		}
		else
		{
			$geo_locations = get_settings('geo_locations');
			if (is_array($geo_locations) && $geo_locations['default'])
			{
				list($lat, $lon) = split(',', $geo_locations['default']);
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))));
				echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$lat};{$lon}\" />\n";
			}
		}
	}

	function admin_head($not_used)
	{
		global $geoMashupOpts;
		if ($geoMashupOpts['google_key'] && preg_match('/(post|page)(-new|).php/',$_SERVER['REQUEST_URI'])) {
			$link_url = get_bloginfo('wpurl').'/wp-content/plugins/geo-mashup';
			/* Packed code is more trouble than it's worth
			if ($geoMashupOpts['use_packed'] == 'true') {
				$link_url .= '/packed';
			}
			*/
			echo '
				<style type="text/css"> #geo_mashup_map div { margin:0; } </style>
				<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$geoMashupOpts['google_key'].'" type="text/javascript"></script>
				<script src="'.$link_url.'/geo-mashup-admin.js" type="text/javascript"></script>
				<script src="'.$link_url.'/JSONscriptRequest.js" type="text/javascript"></script>';
		}
	}

	function admin_print_scripts($not_used)
	{
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
		global $post_ID;

		list($post_lat,$post_lng) = split(',',get_post_meta($post_ID,'_geo_location',true));
		$post_location_name = '';
		$link_url = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
		$kml_url = '';
		$kml_urls = GeoMashup::get_kml_attachment_urls($post_ID);
		if (count($kml_urls)>0) {
			$kml_url = array_pop($kml_urls);
		}
		$geo_locations = get_settings('geo_locations');
		$locations_json = '{';
		if (is_array($geo_locations)) {
			$comma = '';
			foreach ($geo_locations as $name => $latlng) {
				list($lat,$lng) = split(',',$latlng);
				$escaped_name = addslashes(str_replace(array("\r\n","\r","\n"),'',$name));
				if ($lat==$post_lat && $lng==$post_lng) {
					$post_location_name = $escaped_name;
				}
				$locations_json .= $comma.'"'.addslashes($name).'":{"name":"'.$escaped_name.'","lat":"'.$lat.'","lng":"'.$lng.'"}';
				$comma = ',';
			}
		}
		$locations_json .= '}';
		$nonce = wp_create_nonce(plugin_basename(__FILE__));
		$edit_html = '
			<input id="geo_mashup_edit_nonce" name="geo_mashup_edit_nonce" type="hidden" value="'.$nonce.'" />
			<img id="geo_mashup_status_icon" src="'.$link_url.'/images/idle_icon.gif" style="float:right" />
			<label for="geo_mashup_search">'.__('Find location:', 'GeoMashup').'
			<input	id="geo_mashup_search" 
				name="geo_mashup_search" 
				type="text" 
				size="35" 
				onfocus="this.select(); GeoMashupAdmin.map.checkResize();"
				onkeypress="return GeoMashupAdmin.searchKey(event, this.value)" />
			</label>
			<select id="geo_mashup_select" name="geo_mashup_select" onchange="GeoMashupAdmin.onSelectChange(this);">
				<option>'.__('[Saved Locations]','GeoMashup').'</option>
			</select>
			<a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'block\'; return false;">'.__('help', 'GeoMashup').'</a>
			<div id="geo_mashup_inline_help" style="padding:5px; border:2px solid blue; background-color:#ffc; display:none;">
				<p>'.__('Put a green pin at the location for this post.', 'GeoMashup').' '.__('There are many ways to do it:', 'GeoMashup').'
				<ul>
					<li>'.__('Search for a location name.', 'GeoMashup').'</li>
					<li>'.__('For multiple search results, mouse over pins to see location names, and click a result pin to select that location.', 'GeoMashup').'</li>
					<li>'.__('Search for a decimal latitude and longitude, like <em>40.123,-105.456</em>.', 'GeoMashup').'</li> 
					<li>'.__('Search for a street address, like <em>123 main st, anytown, acity</em>.', 'GeoMashup').'</li>
					<li>'.__('Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.', 'GeoMashup').'</li>
				</ul>
				'.__('To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name so you can find it again with a quick search. Saved names are searched before doing a GeoNames search for location names.', 'GeoMashup').'</p>
				<p>'.__('To remove the location (green pin) for a post, clear the search box and hit the enter key.', 'GeoMashup').'</p>
				<p><a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'none\'; return false;">'.__('close', 'GeoMashup').'</a>
			</div>
			<div id="geo_mashup_map" style="width:400px;height:300px;">
				'.__('Loading Google map. Check Geo Mashup options if the map fails to load.', 'GeoMashup').'
			</div>
			<script type="text/javascript">//<![CDATA[
				GeoMashupAdmin.registerMap(document.getElementById("geo_mashup_map"),
					{"link_url":"'.$link_url.'",
					"post_lat":"'.$post_lat.'",
					"post_lng":"'.$post_lng.'",
					"post_location_name":"'.$post_location_name.'",
					"saved_locations":'.$locations_json.',
					"kml_url":"'.$kml_url.'",
					"status_icon":document.getElementById("geo_mashup_status_icon")});
			// ]]>
			</script>
			<label for="geo_mashup_location_name">'.__('Save As:', 'GeoMashup').'
				<input id="geo_mashup_location_name" name="geo_mashup_location_name" type="text" size="45" />
			</label>
			<input id="geo_mashup_location" name="geo_mashup_location" type="hidden" value="'.$post_lat.','.$post_lng.'" />';
		echo $edit_html;
	}

	function save_post($post_id) {
		if (!wp_verify_nonce($_POST['geo_mashup_edit_nonce'], plugin_basename(__FILE__))) {
			return $post_id;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id )) return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
		}

		delete_post_meta($post_id, '_geo_location');
		update_option('geo_mashup_temp_kml_url','');
		if (isset($_POST['geo_mashup_location'])) {
			add_post_meta($post_id, '_geo_location', $_POST['geo_mashup_location']);

			if (isset($_POST['geo_mashup_location_name']) && $_POST['geo_mashup_location_name'] != '') {
				$geo_locations = get_settings('geo_locations');
				$geo_locations[$_POST['geo_mashup_location_name']] = $_POST['geo_mashup_location'];
				update_option('geo_locations',$geo_locations);
			}
		}
	}

	function getLocationsJson($query_args)
	{
		global $wpdb;
		$query_args = wp_parse_args($query_args);
		
		$json = '{ posts : [';

		// Construct the query 
		$fields = 'ID, post_title, meta_value';
		$tables = "$wpdb->postmeta pm
			INNER JOIN $wpdb->posts p
			ON pm.post_id = p.ID";
		$where = 'meta_key=\'_geo_location\''.
			' AND length(meta_value)>1';

		if ($query_args['show_future'] == 'true') {
			$where .= ' AND post_status in (\'publish\',\'future\')';
		} else if ($query_args['show_future'] == 'only') {
			$where .= ' AND post_status=\'future\'';
		} else {
			$where .= ' AND post_status=\'publish\'';
		}

		// Ignore nonsense bounds
		if ($query_args['minlat'] && $query_args['maxlat'] && $query_args['minlat']>$query_args['maxlat']) {
			$query_args['minlat'] = $query_args['maxlat'] = null;
		}
		if ($query_args['minlon'] && $query_args['maxlon'] && $query_args['minlon']>$query_args['maxlon']) {
			$query_args['minlon'] = $query_args['maxlon'] = null;
		}
		// Build bounding where clause
		if (is_numeric($query_args['minlat'])) $where .= " AND substring_index(meta_value,',',1)>{$query_args['minlat']}";
		if (is_numeric($query_args['minlon'])) $where .= " AND substring_index(meta_value,',',-1)>{$query_args['minlon']}";
		if (is_numeric($query_args['maxlat'])) $where .= " AND substring_index(meta_value,',',1)<{$query_args['maxlat']}";
		if (is_numeric($query_args['maxlon'])) $where .= " AND substring_index(meta_value,',',-1)<{$query_args['maxlon']}";

		$tables .= " JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID 
			JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
				AND tt.taxonomy='category'";
		if (is_numeric($query_args['map_cat'])) {
			$cat = $wpdb->escape($query_args['map_cat']);
			$where .= " AND tt.term_id=$cat";
		} 

		if (isset($query_args['post_ids']))
		{
			$where .= ' AND p.ID in (' . $wpdb->escape($query_args['post_ids']) .')';
		}

		$query_string = "SELECT $fields FROM $tables WHERE $where ORDER BY post_status ASC, post_date DESC";

		if (!($query_args['minlat'] && $query_args['maxlat'] && $query_args['minlon'] && $query_args['maxlon']) && !$query_args['limit']) {
			// result should contain all posts (possibly for a category)
		} else if (is_numeric($query_args['limit']) && $query_args['limit']>0) {
			$query_string .= " LIMIT 0,{$query_args['limit']}";
		}

		$wpdb->query($query_string);

		if ($wpdb->last_result) {
			$comma = '';
			$posts = $wpdb->last_result; 
			foreach ($posts as $post) {
				list($lat,$lng) = split(',',$post->meta_value);
				$json .= $comma.'{"post_id":"'.$post->ID.'","title":"'.addslashes($post->post_title).
					'","lat":"'.$lat.'","lng":"'.$lng.'","categories":[';
				$categories_sql = "SELECT name 
					FROM {$wpdb->term_relationships} tr
					JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
					JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
					WHERE tt.taxonomy='category' 
					AND tr.object_id = {$post->ID}";
				$categories = $wpdb->get_col($categories_sql);
				$categories_comma = '';
				foreach ($categories as $category) {
					$json .= $categories_comma.'"'.addslashes($category).'"';
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
			$option_args = $option_args + GeoMashup::explode_assoc('=','&',$_SERVER['QUERY_STRING']);
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
		global $wpdb, $geoMashupOpts;
		if ($category) {
			$query = "SELECT count(*) FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID 
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID 
				WHERE tt.term_id={$category->cat_ID} 
				AND pm.meta_key='_geo_location'
				AND length(pm.meta_value)>1
				AND p.post_status='publish'";
			$count = $wpdb->get_var($query);
			if ($count) {
				// Add map link only if there are geo-located posts to see
				$link = '';
				$url = get_page_link($geoMashupOpts['mashup_page']);
				if (strstr($url,'?')) {
					$url .= '&';
				} else {
					$url .= '?';
				}
				$link = '<a href="'.$url.'map_cat='.$category->cat_ID.'&zoom='.$geoMashupOpts['category_zoom'].
					'" title="'.$geoMashupOpts['category_link_text'].'">';
				return $content.'</a>'.$geoMashupOpts['category_link_separator'].$link.$geoMashupOpts['category_link_text'];
			}
		}
		return $content;
	}

	function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page('Geo Mashup Options', 'Geo Mashup', 8, __FILE__, array('GeoMashup', 'options_page'));
		}
		if (function_exists('add_meta_box')) {
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'post','advanced');
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'page','advanced');
		}
	}

	function options_page() {
		global $wpdb,$geoMashupOpts;

		if (isset($_POST['submit'])) {
			// Process option updates
			$geoMashupOpts['add_map_type_control'] = 'false';
			$geoMashupOpts['in_post_add_map_type_control'] = 'false';
			$geoMashupOpts['add_overview_control'] = 'false';
			$geoMashupOpts['in_post_add_overview_control'] = 'false';
			$geoMashupOpts['add_category_links'] = 'false';
			$geoMashupOpts['show_post'] = 'false';
			$geoMashupOpts['show_future'] = 'false';
			$geoMashupOpts['auto_info_open'] = 'false';
			foreach($_POST as $name => $value) {
				$geoMashupOpts[$name] = $value;
			}
			update_option('geo_mashup_options', $geoMashupOpts);
			echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
		}

		// Add defaults for missing options
		if (!isset($geoMashupOpts['map_width'])) {
			$geoMashupOpts['map_width'] = '400';
			$geoMashupOpts['map_height'] = '500';
			$geoMashupOpts['in_post_map_width'] = '400';
			$geoMashupOpts['in_post_map_height'] = '500';
			$geoMashupOpts['in_post_map_width'] = '400';
			$geoMashupOpts['in_post_map_height'] = '500';
			$geoMashupOpts['excerpt_format'] = 'text';
			$geoMashupOpts['excerpt_length'] = '250';
			$geoMashupOpts['add_category_links'] = 'false';
			$geoMashupOpts['category_link_separator'] = '::';
			$geoMashupOpts['category_link_text'] = 'map';
			$geoMashupOpts['category_zoom_level'] = '7';
			$geoMashupOpts['marker_min_zoom'] = '7';
			if (!isset($geoMashupOpts['map_control'])) {
				$geoMashupOpts['map_control'] = 'GSmallMapControl';
			}
			if (!isset($geoMashupOpts['in_post_map_control'])) {
				$geoMashupOpts['map_control'] = 'GSmallMapControl';
			}
			if (!isset($geoMashupOpts['add_map_type_control'])) {
				$geoMashupOpts['add_map_type_control'] = 'true';
			}
			if (!isset($geoMashupOpts['in_post_add_map_type_control'])) {
				$geoMashupOpts['add_map_type_control'] = 'true';
			}
			if (!isset($geoMashupOpts['auto_info_open'])) {
				$geoMashupOpts['auto_info_open'] = 'true';
			}
			update_option('geo_mashup_options', $geoMashupOpts);
			echo '<div class="updated"><p>'.__('Defaults set.', 'GeoMashup').'</p></div>';
		}

		// Create form elements
		$pageSlugOptions = "";
		$pageSlugs = $wpdb->get_results("SELECT DISTINCT ID, post_name FROM $wpdb->posts " .
			"WHERE post_status='static' OR post_type='page' ORDER BY post_name");
		if ($pageSlugs) {
			foreach($pageSlugs as $slug) {
				$selected = "";
				if ($slug->ID == $geoMashupOpts['mashup_page']) {
					$selected = ' selected="true"';
				}
				$pageSlugOptions .= '<option value="'.$slug->ID.'"'.$selected.'>'.$slug->post_name."</option>\n";
			}
		} else {
			$pageSlugOptions = '<option value="">No pages found</option>';
		}

		// Create marker and color arrays
		$link_url = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
		$colorNames = Array(
			'aqua' => '#00ffff',
			'black' => '#000000',
			'blue' => '#0000ff',
			'fuchsia' => '#ff00ff',
			'gray' => '#808080',
			'green' => '#008000',
			'lime' => '#00ff00',
			'maroon' => '#800000',
			'navy' => '#000080',
			'olive' => '#808000',
			'orange' => '#ffa500',
			'purple' => '#800080',
			'red' => '#ff0000',
			'silver' => '#c0c0c0',
			'teal' => '#008080',
			'white' => '#ffffff',
			'yellow' => '#ffff00');
		
		// Create category table
		$categoryTable = '
			<table>
				<tr><th>'.__('Category', 'GeoMashup').'</th><th>'.__('Color').'</th>
				<th>'.__('Show Connecting Line Until Zoom Level (0-17 or none)','GeoMashup')."</th></tr>\n";
		$categorySelect = "SELECT * 
			FROM $wpdb->terms t 
			JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id
			WHERE taxonomy='category'";
		$categories = $wpdb->get_results($categorySelect);
		if (is_array($categories))
		{
			foreach($categories as $category) {
				$colorOptions = '';
				foreach($colorNames as $name => $rgb) {
					$colorOptions .= '<option value="'.$name.'"';
					if (is_array($geoMashupOpts['category_color']) && $name == $geoMashupOpts['category_color'][$category->slug]) {
						$colorOptions .= ' selected="true"';
					}
					$colorOptions .= ' style="background-color:'.$rgb.'">'.
						__($name,'GeoMashup').'</option>';
				}
				$categoryTable .= '<tr><td>' . $category->name . '</td><td><select id="category_color_' .
					$category->slug . '" name="category_color[' . $category->slug . ']">'.$colorOptions.
					'</select></td><td><input id="category_line_zoom_' . $category->slug . 
					'" name="category_line_zoom['.$category->slug.']" value="'.
					$geoMashupOpts['category_line_zoom'][$category->slug].
					'" type="text" size="2" maxlength="2" /></td></tr>'."\n";
			}
		}
		$categoryTable .= "</table>\n";

		$mapTypeOptions = "";
		$mapTypes = Array(
			'G_NORMAL_MAP' => __('Roadmap', 'GeoMashup'), 
			'G_SATELLITE_MAP' => __('Satellite', 'GeoMashup'),
			'G_HYBRID_MAP' => __('Hybrid', 'GeoMashup'),
			'G_PHYSICAL_MAP' => __('Terrain', 'GeoMashup'));
		foreach($mapTypes as $type => $label) {
			$selected = "";
			if ($type == $geoMashupOpts['map_type']) {
				$selected = ' selected="true"';
			}
			$mapTypeOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
			$in_post_selected = "";
			if ($type == $geoMashupOpts['in_post_map_type']) {
				$in_post_selected = ' selected="true"';
			}
			$inPostMapTypeOptions .= '<option value="'.$type.'"'.$in_post_selected.'>'.$label."</option>\n";
		}
		$mapControlOptions = "";
		$inPostMapControlOptions = "";
		$mapControls = Array(
			'GSmallZoomControl' => __('Small Zoom', 'GeoMashup'),
			'GSmallMapControl' => __('Small Pan/Zoom', 'GeoMashup'),
			'GLargeMapControl' => __('Large Pan/Zoom', 'GeoMashup'));
		foreach($mapControls as $type => $label) {
			$selected = "";
			if ($type == $geoMashupOpts['map_control']) {
				$selected = ' selected="true"';
			}
			$mapControlOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
			$in_post_selected = "";
			if ($type == $geoMashupOpts['in_post_map_control']) {
				$in_post_select = ' selected="true"';
			}
			$inPostMapControlOptions .= '<option value="'.$type.'"'.$in_post_selected.'>'.$label."</option>\n";
		}

		if ($geoMashupOpts['add_map_type_control'] == 'true') {
			$mapTypeChecked = ' checked="true"';
		} else {
			$mapTypeChecked = '';
		}

		if ($geoMashupOpts['in_post_add_map_type_control'] == 'true') {
			$inPostMapTypeChecked = ' checked="true"';
		} else {
			$inPostMapTypeChecked = '';
		}

		if ($geoMashupOpts['add_overview_control'] == 'true') {
			$overviewChecked = ' checked="true"';
		} else {
			$overviewmapChecked = '';
		}

		if ($geoMashupOpts['in_post_add_overview_control'] == 'true') {
			$inPostOverviewChecked = ' checked="true"';
		} else {
			$inPostOverviewmapChecked = '';
		}

		if ($geoMashupOpts['add_category_links'] == 'true') {
			$categoryLinksChecked = ' checked="true"';
		} else {
			$categoryLinksChecked = '';
		}

		if ($geoMashupOpts['show_post'] == 'true') {
			$showPostChecked = ' checked="true"';
		} else {
			$showPostChecked = '';
		}

		$showFutureOptions = "";
		$futureOptions = Array(
			'true' => __('Yes', 'GeoMashup'),
			'false' => __('No', 'GeoMashup'),
			'only' => __('Only', 'GeoMashup'));
		foreach($futureOptions as $value => $label) {
			$selected = "";
			if ($value == $geoMashupOpts['show_future']) {
				$selected = ' selected="true"';
			}
			$showFutureOptions .= '<option value="'.$value.'"'.$selected.'>'.$label."</option>\n";
		}

		if ($geoMashupOpts['excerpt_format'] == 'text') {
			$textExcerptChecked = ' checked="true"';
			$htmlExcerptChecked = '';
		} else {
			$textExcerptChecked = '';
			$htmlExcerptChecked = ' checked="true"';
		}
		if ($geoMashupOpts['auto_info_open'] == 'true') {
			$autoInfoOpenChecked = ' checked="true"';
		} else {
			$autoInfoOpenChecked = '';
		}
	
		// Write the form
		echo '
		<div class="wrap">
			<form method="post">
				<h2>'.__('Geo Mashup Plugin Options', 'GeoMashup').'</h2>
				<fieldset>
					<legend>'.__('Overall Settings', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Google Maps Key', 'GeoMashup').'</th>
							<td><input id="google_key" name="google_key" type="text" size="40" value="'.$geoMashupOpts['google_key'].'" />
							<a href="http://maps.google.com/apis/maps/signup.html">'.__('Get yours here', 'GeoMashup').'</a></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>'.__('Default Settings For Maps In Located Posts and Pages', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th scope="row">'.__('Map Control', 'GeoMashup').'</th>
							<td>
								<select id="in_post_map_control" name="in_post_map_control">'.$inPostMapControlOptions.'</select>
							</td>
						</tr>
						<tr>
							<th scope="row">'.__('Add Map Type Control', 'GeoMashup').'</th>
							<td><input id="in_post_add_map_type_control" name="in_post_add_map_type_control" type="checkbox" value="true"'.$inPostMapTypeChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Add Overview Control', 'GeoMashup').'</th>
							<td><input id="in_post_add_overview_control" name="in_post_add_overview_control" type="checkbox" value="true"'.$inPostOverviewChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Default Map Type', 'GeoMashup').'</th>
							<td>
								<select id="in_post_map_type" name="in_post_map_type">'.$inPostMapTypeOptions.'</select>
							</td>
						</tr>
						<tr>
							<th scope="row">'.__('Default Zoom Level', 'GeoMashup').'</th>
							<td><input id="in_post_zoom_level" name="in_post_zoom_level" type="text" size="2" value="'.$geoMashupOpts['in_post_zoom_level'].'" />'.
							__('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup').'</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>'.__('Default Settings For Global Maps', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th scope="row" title="'.__('Generated links go here','GeoMashup').'">'.__('Global Mashup Page', 'GeoMashup').'</th>
							<td>
								<select id="mashup_page" name="mashup_page">'.$pageSlugOptions.'</select>
							</td>
						</tr>
						<tr>
							<th scope="row">'.__('Map Control', 'GeoMashup').'</th>
							<td>
								<select id="map_control" name="map_control">'.$mapControlOptions.'</select>
							</td>
						</tr>
						<tr>
							<th scope="row">'.__('Add Map Type Control', 'GeoMashup').'</th>
							<td><input id="add_map_type_control" name="add_map_type_control" type="checkbox" value="true"'.$mapTypeChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Add Overview Control', 'GeoMashup').'</th>
							<td><input id="add_overview_control" name="add_overview_control" type="checkbox" value="true"'.$overviewChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Default Map Type', 'GeoMashup').'</th>
							<td>
								<select id="map_type" name="map_type">'.$mapTypeOptions.'</select>
							</td>
						</tr>
						<tr>
							<th scope="row">'.__('Default Zoom Level', 'GeoMashup').'</th>
							<td><input id="zoom_level" name="zoom_level" type="text" size="2" value="'.$geoMashupOpts['zoom_level'].'" />'.
							__('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Hide Markers Until Zoom Level', 'GeoMashup').'</th>
							<td><input id="marker_min_zoom" name="marker_min_zoom" type="text" size="2" value="'.$geoMashupOpts['marker_min_zoom'].'" />'.
							__('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Show Only Most Recent Posts', 'GeoMashup').'</th>
							<td><input id="max_posts" name="max_posts" type="text" size="4" value="'.$geoMashupOpts['max_posts'].'" />'.
							__('Number of posts to show, leave blank for all', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Show Future Posts', 'GeoMashup').'</th>
							<td><select id="show_future" name="show_future">'.$showFutureOptions.'</select></td>
						</tr>
						<tr>
							<th scope="row">'.__('Automatically Open Linked Post Info Window', 'GeoMashup').'</th>
							<td><input id="auto_info_open" name="auto_info_open" type="checkbox" value="true"'.$autoInfoOpenChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Enable Full Post Display', 'GeoMashup').'</th>
							<td><input id="show_post" name="show_post" type="checkbox" value="true"'.$showPostChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Add Category Links', 'GeoMashup').'</th>
							<td><input id="add_category_links" name="add_category_links" type="checkbox" value="true"'.$categoryLinksChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Category Link Separator', 'GeoMashup').'</th>
							<td><input id="category_link_separator" name="category_link_separator" type="text" size="3" value="'.$geoMashupOpts['category_link_separator'].'" /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Category Link Text', 'GeoMashup').'</th>
							<td><input id="category_link_text" name="category_link_text" type="text" size="5" value="'.$geoMashupOpts['category_link_text'].'" /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Single Category Zoom Level', 'GeoMashup').'</th>
							<td><input id="category_zoom" name="category_zoom" type="text" size="2" value="'.$geoMashupOpts['category_zoom'].'" />'.
							__('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup').'</td>
						</tr>
						<tr><td colspan="2" align="center">'.$categoryTable.'
						</td></tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>'.__('Presentation', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Post Excerpt Format', 'GeoMashup').'</th>
							<td><input name="excerpt_format" type="radio" value="text"'.$textExcerptChecked.' />'.
							__('Text', 'GeoMashup').'<input name="excerpt_format" type="radio" value="html"'.$htmlExcerptChecked.' />'.
							__('HTML', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th width="33%" scopt="row">'.__('Post Excerpt Length', 'GeoMashup').'</th>
							<td><input id="excerpt_length" name="excerpt_length" type="text" size="5" value="'.$geoMashupOpts['excerpt_length'].'" /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Global Map Width', 'GeoMashup').'</th>
							<td><input id="map_width" name="map_width" type="text" size="5" value="'.$geoMashupOpts['map_width'].'" />'.__('px', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Global Map Height', 'GeoMashup').'</th>
							<td><input id="map_height" name="map_height" type="text" size="5" value="'.$geoMashupOpts['map_height'].'" />'.__('px', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('In-Post Map Width', 'GeoMashup').'</th>
							<td><input id="in_post_map_width" name="in_post_map_width" type="text" size="5" value="'.$geoMashupOpts['in_post_map_width'].'" />'.__('px', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('In-Post Map Height', 'GeoMashup').'</th>
							<td><input id="in_post_map_height" name="in_post_map_height" type="text" size="5" value="'.$geoMashupOpts['in_post_map_height'].'" />'.__('px', 'GeoMashup').'</td>
						</tr>
					</table>
				</fieldset>
				<div class="submit"><input type="submit" name="submit" value="'.__('Update Options', 'GeoMashup').' &raquo;" /></div>
			</form>
			<p><a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation">'.__('Geo Mashup Documentation', 'GeoMashup').'</a></p>
		</div>';
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
		global $geoMashupOpts,$post;
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
				$icon = '<img src="'.get_bloginfo('wpurl') .
					'/wp-content/plugins/geo-mashup/images/geotag_16.png" alt="'.__('Geotag Icon','GeoMashup').'"/>';
			}
			$link = '';
			$url = get_page_link($geoMashupOpts['mashup_page']);
			if (strstr($url,'?')) {
				$url .= '&';
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
		global $wpdb;
		$query = "SELECT * 
			FROM {$wpdb->posts} JOIN
				{$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
			WHERE meta_key='_geo_location' AND
				length(meta_value) > 1";
		if ($geoMashupOpts['show_future'] == 'false') {
			$query .= ' AND post_status=\'publish\'';
		} else if ($geoMashupOpts['show_future'] == 'only') {
			$query .= ' AND post_status=\'future\'';
		} else {
			$query .= ' AND post_status IN (\'publish\',\'future\')';
		}

		$query .= ' ORDER BY post_date DESC';
		$list_html = '<ul id="geo_mashup_located_post_list">';
		$posts = $wpdb->get_results($query);
		if ($posts)
		{
			foreach ($posts as $post) {
				$list_html .= '<li><a href="'.get_permalink($post->ID).'">'.
					$post->post_title."</a></li>\n";
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

		$meta = trim(get_post_meta($post->ID, '_geo_location', true));
		$coordinates = array();
		if (strlen($meta)>1) {
			list($lat, $lng) = split(',', $meta);
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
		$coordinates = trim(get_post_meta($wp_query->post->ID, '_geo_location', true));
		if (strlen($coordinates) > 1) {
			$coordinates = str_replace(',',' ',$coordinates);
			echo '<georss:point>' . $coordinates . '</georss:point>';
		}
	}
} // class GeoMashup

// frontend hooks
add_shortcode('geo_mashup_map','geo_mashup_map');
add_shortcode('geo_mashup_show_on_map_link', 'geo_mashup_show_on_map_link');
add_shortcode('geo_mashup_full_post','geo_mashup_full_post');
add_shortcode('geo_mashup_category_name','geo_mashup_category_name');
add_shortcode('geo_mashup_category_legend','geo_mashup_category_legend');
add_shortcode('geo_mashup_list_located_posts','geo_mashup_list_located_posts');

if ($geoMashupOpts['add_category_links'] == 'true') {
	add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
}

add_action('wp_head', array('GeoMashup', 'wp_head'));
add_action('rss_ns', array('GeoMashup', 'rss_ns'));
add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
add_action('atom_ns', array('GeoMashup', 'rss_ns'));
add_action('rss_item', array('GeoMashup', 'rss_item'));
add_action('rss2_item', array('GeoMashup', 'rss_item'));
add_action('atom_entry', array('GeoMashup', 'rss_item'));

// admin hooks
add_filter('upload_mimes', array('GeoMashup', 'upload_mimes'));

add_action('admin_menu', array('GeoMashup', 'admin_menu'));
add_action('admin_head', array('GeoMashup', 'admin_head'));
add_action('save_post', array('GeoMashup', 'save_post'));
add_action('wp_handle_upload', array('GeoMashup', 'wp_handle_upload'));
add_action('admin_print_scripts', array('GeoMashup', 'admin_print_scripts'));
?>
