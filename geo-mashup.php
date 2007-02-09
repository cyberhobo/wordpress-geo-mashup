<?php
/*
Plugin Name: Geo Mashup
Plugin URI: http://www.cyberhobo.net/downloads/geo-mashup-plugin/
Description: Adds a Google Maps mashup of geocoded blog posts. Configure in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 1.0 
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 1.5.1
*/

/*
Geo Mashup - Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. 
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

load_plugin_textdomain('GeoMashup');
$geoMashupOpts = get_settings('geo_mashup_options');

/**
 * The Geo Mashup class/namespace.
 */
class GeoMashup {

	function wp_head() {
		global $geoMashupOpts;
		if (!is_page($geoMashupOpts['mashup_page'])) {
			return;
		}

		if ($geoMashupOpts['google_key']) {
			// Generate the mashup javascript
			if ($geoMashupOpts['include_style'] == 'true') {
				// Generate map style
				echo '
				<style type="text/css">
				v\:* {
					behavior:url(#default#VML);
				}
				#geoMashup {';
				if ($geoMashupOpts['map_width']) {
					echo '
					width:'.$geoMashupOpts['map_width'].'px;';
				}
				if ($geoMashupOpts['map_height']) {
					echo '
					height:'.$geoMashupOpts['map_height'].'px;';
				}
				echo '
				}
				.locationinfo {
					overflow:auto;';
				if ($geoMashupOpts['info_window_height']) {
					echo '
					height:'.$geoMashupOpts['info_window_height'].'px;';
				}
				if ($geoMashupOpts['info_window_width']) {
					echo '
					width:'.$geoMashupOpts['info_window_width'].'px;';
				}
				if ($geoMashupOpts['font_size']) {
					echo '
					font-size:'.$geoMashupOpts['font_size'].'%;';
				}
				echo '
				}
				</style>';
			}
			$linkDir = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
			$custom_marker_file = dirname(__FILE__).'/custom.js';
			if (is_readable($custom_marker_file)) {
				echo '
					<script src="'.$linkDir.'/custom.js" type="text/javascript"></script>';
			}
			// Other javascript may be packed
			if ($geoMashupOpts['use_packed'] == 'true') {
				$linkDir .= '/packed';
			}
			echo '
				<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$geoMashupOpts['google_key'].'" type="text/javascript"></script>
				<script src="'.$linkDir.'/geo-mashup.js" type="text/javascript"></script>';
		}
	}

	function admin_head($not_used)
	{
		global $geoMashupOpts;
		if ($geoMashupOpts['google_key'] && preg_match('/post(-new|).php/',$_SERVER['REQUEST_URI'])) {
			$link_url = get_bloginfo('wpurl').'/wp-content/plugins/geo-mashup';
			if ($geoMashupOpts['use_packed'] == 'true') {
				$link_url .= '/packed';
			}
			echo '
				<style type="text/css"> #geo_mashup_map div { margin:0; } </style>
				<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$geoMashupOpts['google_key'].'" type="text/javascript"></script>
				<script src="'.$link_url.'/geo-mashup-admin.js" type="text/javascript"></script>
				<script src="'.$link_url.'/JSONscriptRequest.js" type="text/javascript"></script>';
		}
	}

	function edit_form_advanced()
	{
		ob_start(array('GeoMashup','advanced_buffer'));
	}

	function advanced_buffer($content)
	{
		global $post_ID;

		list($post_lat,$post_lng) = split(',',get_post_meta($post_ID,'_geo_location',true));
		$post_location_name = '';
		$link_url = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
		$geo_locations = get_settings('geo_locations');
		$locations_json = '{';
		$comma = '';
		foreach ($geo_locations as $name => $latlng) {
			list($lat,$lng) = split(',',$latlng);
			if ($lat==$post_lat && $lng==$post_lng) $post_location_name = addslashes($name);
			$locations_json .= $comma.'"'.addslashes($name).'":{"name":"'.addslashes($name).'","lat":"'.$lat.'","lng":"'.$lng.'"}';
			$comma = ',';
		}
		$locations_json .= '}';
		$edit_html = '
			<div class="dbx-box-wrapper">
				<fieldset class="dbx-box">
					<div class="dbx-handle-wrapper"><h3 class="dbx-handle">Location</h3></div>
					<div class="dbx-content-wrapper"><div class="dbx-content">
						<img id="geo_mashup_status_icon" src="'.$link_url.'/images/idle_icon.gif" style="float:right" />
						<label for="geo_mashup_search">Find location:
							<input	id="geo_mashup_search" 
											name="geo_mashup_search" 
											type="text" 
											size="35" 
											onfocus="this.select(); GeoMashupAdmin.map.checkResize();"
											onkeypress="return GeoMashupAdmin.searchKey(event, this.value)" />
						</label>
						<a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'block\'; return false;">help</a>
						<div id="geo_mashup_inline_help" style="position:absolute; z-index:1; left:0; top:0; padding:5px; border:2px solid blue; background-color:#ffc; display:none;">
							<p>Put a green pin at the location for this post. There are many ways to do it:
							<ul>
								<li>Search for a location name.</li>
								<li>For multiple search results, mouse over pins to see location names, and click a result pin to select that location.</li>
								<li>Search for a decimal latitude and longitude, like <em>40.123,-105.456</em>.</li> 
								<li>Search for a street address, like <em>123 main st, anytown, acity</em>.</li>
								<li>Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.</li>
							</ul>
							To execute a search, type search text into the Find Location box and hit the enter key. 
							If you type a name next to "Save As", the location will be saved under that name so you can find it again with a quick
							search. Saved names are searched before doing a GeoNames search for location names.</p>
							<p>To remove the location (green pin) for a post, clear the search box and hit the enter key.</p>
							<p><a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'none\'; return false;">close</a>
						</div>
						<div id="geo_mashup_map" style="width:400px;height:300px;">
							Loading Google map. Check Geo Mashup options if the map fails to load.
						</div>
						<script type="text/javascript">//<![CDATA[
							GeoMashupAdmin.registerMap(document.getElementById("geo_mashup_map"),
																				{"link_url":"'.$link_url.'",
																				"post_lat":"'.$post_lat.'",
																				"post_lng":"'.$post_lng.'",
																				"post_location_name":"'.$post_location_name.'",
																				"saved_locations":'.$locations_json.',
																				"status_icon":document.getElementById("geo_mashup_status_icon")});
							// ]]>
						</script>
						<label for="geo_mashup_location_name">Save As: 
							<input id="geo_mashup_location_name" name="geo_mashup_location_name" type="text" size="45" />
						</label>
						<input id="geo_mashup_location" name="geo_mashup_location" type="hidden" value="'.$post_lat.','.$post_lng.'" />
					</div></div>
				</fieldset>
			</div>';
		return preg_replace('#(<div.*?id="advancedstuff".*?'.'>)#ims', '\\1' . $edit_html, $content, 1);
	}

	function save_post($post_id) {
		delete_post_meta($post_id, '_geo_location');
		if (isset($_POST['geo_mashup_location'])) {
			add_post_meta($post_id, '_geo_location', $_POST['geo_mashup_location']);

			if (isset($_POST['geo_mashup_location_name']) && $_POST['geo_mashup_location_name'] != '') {
				$geo_locations = get_settings('geo_locations');
				$geo_locations[$_POST['geo_mashup_location_name']] = $_POST['geo_mashup_location'];
				update_option('geo_locations',$geo_locations);
			}
		}
	}

	function the_content($content = '') {
		global $geoMashupOpts;
		if (is_page($geoMashupOpts['mashup_page'])) {
			$mapdiv = '<div id="geoMashup">';
			if (!$geoMashupOpts['google_key']) {
				$mapdiv .= '<p>The Google Mashup plugin needs a 
					<a href="http://maps.google.com/apis/maps/signup.html">Google API Key</a> set
					in the <a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">
					plugin options</a> before it will work.</p>';
			}
			$mapdiv .= '</div>';
			$linkDir = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
			$script = '<script type="text/javascript">
				GeoMashup.registerMap(document.getElementById("geoMashup"), {
					addMapTypeControl:'.($geoMashupOpts['add_map_type_control']?$geoMashupOpts['add_map_type_control']:'false').',
					linkDir:"'.$linkDir.'",
					mapControl:"'.$geoMashupOpts['map_control'].'",
					addOverviewControl:'.($geoMashupOpts['add_overview_control']?$geoMashupOpts['add_overview_control']:'false').',
					defaultMapType:'.$geoMashupOpts['map_type'].',
					defaultZoom:'.($geoMashupOpts['zoom_level']?$geoMashupOpts['zoom_level']:'null').',
					showPostHere:'.($geoMashupOpts['show_post']?$geoMashupOpts['show_post']:'false').',
					cat:"'.$_GET['cat'].'",
					loadLat:'.($_GET['lat']?$_GET['lat']:'null').',
					loadLon:'.($_GET['lon']?$_GET['lon']:'null').',
					loadZoom:'.($_GET['zoom']?$_GET['zoom']:'null').',
					autoOpenInfoWindow:'.($geoMashupOpts['auto_info_open']?$geoMashupOpts['auto_info_open']:'false').'});</script>';

			$postdiv = '';
			if ($geoMashupOpts['show_post'] == 'true') {
				$postdiv = '<div id="geoPost"></div>';
			}

			if ($content) {
				$content = preg_replace('/<\!--\s*Geo.?Mashup\s*-->/i',$mapdiv.$script,$content);
				$content = preg_replace('/<\!--\s*Geo.?Post\s*-->/i',$postdiv,$content);
				$content = preg_replace('/<\!--\s*Geo.?Category\s*-->/i',single_cat_title('',false),$content);
			} else {
				$content = $mapdiv.$script;
			}
		}
		return $content;
	}

	function list_cats($content, $category = null) {
		global $wpdb, $geoMashupOpts;
		if ($category) {
			$query = "SELECT count(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->post2cat} pc 
				ON pc.post_id=p.ID INNER JOIN {$wpdb->postmeta} pm
				ON pm.post_id=p.ID 
				WHERE pc.category_id={$category->cat_ID} 
				AND pm.meta_key='_geo_location'
				AND length(pm.meta_value)>1
				AND p.post_status='publish'";
			$count = $wpdb->get_var($query);
			if ($count) {
				// Add map link only if there are geo-located posts to see
				$using_pretty_links = get_settings('permalink_structure');
				if ($using_pretty_links) {
					return $content.'</a>'.$geoMashupOpts['category_link_separator'].'<a href="'.get_page_link($geoMashupOpts['mashup_page']).
						'?cat='.$category->cat_ID.'&zoom='.$geoMashupOpts['category_zoom'].'" title="'.$geoMashupOpts['category_link_text'].
						'">'.$geoMashupOpts['category_link_text'];
				} else {
					return $content.'</a>'.$geoMashupOpts['category_link_separator'].'<a href="'.get_page_link($geoMashupOpts['mashup_page']).
						'&cat='.$category->cat_ID.'&zoom='.$geoMashupOpts['category_zoom'].'" title="'.$geoMashupOpts['category_link_text'].
						'">'.$geoMashupOpts['category_link_text'];
				}
			}
		}
		return $content;
	}

	function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page('Geo Mashup Options', 'Geo Mashup', 8, __FILE__, array('GeoMashup', 'options_page'));
		}
	}

	function options_page() {
		global $wpdb,$geoMashupOpts;

		if (isset($_POST['submit'])) {
			// Process option updates
			$geoMashupOpts['include_style'] = 'false';
			$geoMashupOpts['add_map_type_control'] = 'false';
			$geoMashupOpts['add_overview_control'] = 'false';
			$geoMashupOpts['add_category_links'] = 'false';
			$geoMashupOpts['show_post'] = 'false';
			$geoMashupOpts['use_packed'] = 'false';
			$geoMashupOpts['show_future'] = 'false';
			$geoMashupOpts['auto_info_open'] = 'false';
			foreach($_POST as $name => $value) {
				$geoMashupOpts[$name] = $value;
			}
			update_option('geo_mashup_options', $geoMashupOpts);
			echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
		}

		// Add defaults for missing options
		if (!isset($geoMashupOpts['include_style'])) {
			$geoMashupOpts['include_style'] = 'true';
			$geoMashupOpts['map_width'] = '400';
			$geoMashupOpts['map_height'] = '500';
			$geoMashupOpts['info_window_width'] = '300';
			$geoMashupOpts['info_window_height'] = '175';
			$geoMashupOpts['font_size'] = '75';
			$geoMashupOpts['excerpt_format'] = 'text';
			$geoMashupOpts['excerpt_length'] = '250';
			$geoMashupOpts['add_category_links'] = 'false';
			$geoMashupOpts['category_link_separator'] = '::';
			$geoMashupOpts['category_link_text'] = 'map';
			$geoMashupOpts['category_zoom_level'] = '7';
			if (!isset($geoMashupOpts['map_control'])) {
				$geoMashupOpts['map_control'] = 'GSmallMapControl';
			}
			if (!isset($geoMashupOpts['add_map_type_control'])) {
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

		$mapTypeOptions = "";
		$mapTypes = Array(
			'G_NORMAL_MAP' => 'Roadmap',
			'G_SATELLITE_MAP' => 'Satellite',
			'G_HYBRID_MAP' => 'Hybrid');
		foreach($mapTypes as $type => $label) {
			$selected = "";
			if ($type == $geoMashupOpts['map_type']) {
				$selected = ' selected="true"';
			}
			$mapTypeOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		}
		$mapControlOptions = "";
		$mapControls = Array(
			'GSmallZoomControl' => 'Small Zoom',
			'GSmallMapControl' => 'Small Pan/Zoom',
			'GLargeMapControl' => 'Large Pan/Zoom');
		foreach($mapControls as $type => $label) {
			$selected = "";
			if ($type == $geoMashupOpts['map_control']) {
				$selected = ' selected="true"';
			}
			$mapControlOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		}

		if ($geoMashupOpts['include_style'] == 'true') {
			$styleChecked = ' checked="true"';
		} else {
			$styleChecked = '';
		}

		if ($geoMashupOpts['add_map_type_control'] == 'true') {
			$mapTypeChecked = ' checked="true"';
		} else {
			$mapTypeChecked = '';
		}

		if ($geoMashupOpts['add_overview_control'] == 'true') {
			$overviewChecked = ' checked="true"';
		} else {
			$overviewmapChecked = '';
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

		if ($geoMashupOpts['use_packed'] == 'true') {
			$usePackedChecked = ' checked="true"';
		} else {
			$usePackedChecked = '';
		}
		if ($geoMashupOpts['show_future'] == 'true') {
			$showFutureChecked = ' checked="true"';
		} else {
			$showFutureChecked = '';
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
				<h2>'.__('Geo Mashup Options', 'GeoMashup').'</h2>
				<fieldset>
					<legend>'.__('Behavior', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Google Maps Key', 'GeoMashup').'</th>
							<td><input id="google_key" name="google_key" type="text" size="40" value="'.$geoMashupOpts['google_key'].'" />
							<a href="http://maps.google.com/apis/maps/signup.html">'.__('Get yours here', 'GeoMashup').'</a></td>
						</tr>
						<tr>
							<th scope="row">'.__('Mashup Page', 'GeoMashup').'</th>
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
							__('0-17', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Show Future Posts', 'GeoMashup').'</th>
							<td><input id="show_future" name="show_future" type="checkbox" value="true"'.$showFutureChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Automatically Open the Center Info Window', 'GeoMashup').'</th>
							<td><input id="auto_info_open" name="auto_info_open" type="checkbox" value="true"'.$autoInfoOpenChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Enable Full Post Display', 'GeoMashup').'</th>
							<td><input id="show_post" name="show_post" type="checkbox" value="true"'.$showPostChecked.' /></td>
						</tr>
						<tr>
							<th scope="row">'.__('Use Compressed Javascript', 'GeoMashup').'</th>
							<td><input id="show_post" name="use_packed" type="checkbox" value="true"'.$usePackedChecked.' /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>'.__('Categories', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Add Category Links', 'GeoMashup').'</th>
							<td><input id="add_category_links" name="add_category_links" type="checkbox" value="true"'.$categoryLinksChecked.' /></td>
						</tr>
						<tr>
							<th width="33%" scope="row">'.__('Category Link Separator', 'GeoMashup').'</th>
							<td><input id="category_link_separator" name="category_link_separator" type="text" size="3" value="'.$geoMashupOpts['category_link_separator'].'" /></td>
						</tr>
						<tr>
							<th width="33%" scope="row">'.__('Category Link Text', 'GeoMashup').'</th>
							<td><input id="category_link_text" name="category_link_text" type="text" size="5" value="'.$geoMashupOpts['category_link_text'].'" /></td>
						</tr>
						<tr>
							<th width="33%" scope="row">'.__('Category Zoom Level', 'GeoMashup').'</th>
							<td><input id="category_zoom" name="category_zoom" type="text" size="2" value="'.$geoMashupOpts['category_zoom'].'" />'.
							__('0-17', 'GeoMashup').'</td>
						</tr>
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
							<th width="33%" scope="row">'.__('Include style settings', 'GeoMashup').'</th>
							<td><input id="include_style" name="include_style" type="checkbox" value="true"'.$styleChecked.' />'.
							__(' (Uncheck to use styles from your theme stylesheet instead of these)', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Map Width', 'GeoMashup').'</th>
							<td><input id="map_width" name="map_width" type="text" size="5" value="'.$geoMashupOpts['map_width'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Map Height', 'GeoMashup').'</th>
							<td><input id="map_height" name="map_height" type="text" size="5" value="'.$geoMashupOpts['map_height'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Width', 'GeoMashup').'</th>
							<td><input id="info_window_width" name="info_window_width" type="text" size="5" value="'.$geoMashupOpts['info_window_width'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Height', 'GeoMashup').'</th>
							<td><input id="info_window_height" name="info_window_height" type="text" size="5" value="'.$geoMashupOpts['info_window_height'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Font Size', 'GeoMashup').'</th>
							<td><input id="font_size" name="font_size" type="text" size="5" value="'.$geoMashupOpts['font_size'].'" />%</td>
						</tr>
					</table>
				</fieldset>
				<div class="submit"><input type="submit" name="submit" value="'.__('Update Options', 'GeoMashup').'" /></div>
			</form>
			<p><a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation">Geo Mashup Documentation</a></p>
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
	 * A tag to insert the map itself, as an alternative 
	 * to the_content() filter.
	 */
	function the_map() {
		echo GeoMashup::the_content();
	}

	/**
	 * A tag to insert a link to a post on the mashup.
	 */
	function post_link($text = 'Geo Mashup', $display = true) {
		global $geoMashupOpts;
		$coords = GeoMashup::post_coordinates();
		$lat = $coords['lat'];
		$lng = $coords['lng'];
		if ($lat && $lng) {
			$link = '';
			$using_pretty_links = get_settings('permalink_structure');
			if ($using_pretty_links && !(strstr($url,'index.php'))) {
				$link = '<a href="'.get_page_link($geoMashupOpts['mashup_page']).htmlentities("?lat=$lat&lon=$lng")."\">$text</a>";
			} else {
				$link = '<a href="'.get_page_link($geoMashupOpts['mashup_page']).htmlentities("&lat=$lat&lon=$lng")."\">$text</a>";
			}
			if ($display) {
				echo $link;
			} else {
				return $link;
			}
		}
	}

	/**
	* A better name for the post_link tag.
	*/
	function show_on_map_link($text = 'Show On Map', $display = true) {
		GeoMashup::post_link($text,$display);
	}

	/**
	* Fetch post coordinates.
	*/
	function post_coordinates() {
		global $post;

		$meta = trim(get_post_meta($post->ID, '_geo_location', true));
		if (strlen($meta)>1) {
			list($lat, $lng) = split(',', $meta);
			return array('lat' => $lat, 'lng' => $lng);
		}
		return false;
	}

	/**
	* Fetch RSS geo tags.
	*/
	function geo_rss_tags($display = true) {
		list($lat, $lon) = split(',', get_post_meta($wp_query->post->ID, '_geo_location', true));
		if ($lat == '' || $lon == '') {
			if (get_settings('use_default_geourl')){
				// send the default here
				$lat = get_settings('default_geourl_lat');
				$lon = get_settings('default_geourl_lon');
			}
		}
		if ($lat != '' && $lon != '') {
			$tags = "<geo:lat>$lat</geo:lat><geo:long>$lon</geo:long>\n"
						. "<icbm:latitude>$lat</icbm:latitude><icbm:longitude>$lon</icbm:longitude>\n"
						. "<geourl:longitude>$lon</geourl:longitude><geourl:latitude>$lat</geourl:latitude>\n";
			if ($display) {
				echo $tags;
			}
			return $tags;
		}
		return false;
	}
} // class GeoMashup

// frontend hooks
add_action('wp_head', array('GeoMashup', 'wp_head'));
add_filter('the_content', array('GeoMashup', 'the_content'));
if ($geoMashupOpts['add_category_links'] == 'true') {
	add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
}

// admin hooks
add_action('admin_menu', array('GeoMashup', 'admin_menu'));
add_action('admin_head', array('GeoMashup', 'admin_head'));
add_action('edit_form_advanced', array('GeoMashup', 'edit_form_advanced'));
add_action('save_post', array('GeoMashup', 'save_post'));
?>
