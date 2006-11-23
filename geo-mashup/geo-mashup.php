<?php
/*
Plugin Name: Geo Mashup
Plugin URI: http://www.cyberhobo.net/downloads/geo-mashup-plugin/
Description: Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. For WordPress 1.5.1 or higher. Minimal instructions and configuration will be in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 0.5 
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 1.5.1
*/

/*
Geo Mashup - Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. 
Copyright (c) 2005 Dylan Kuhn

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
			echo '
			<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$geoMashupOpts['google_key'].'" type="text/javascript"></script>';
		}
	}

	function wp_footer() {
		global $wpdb,$geoMashupOpts;
		if (!is_page($geoMashupOpts['mashup_page'])) {
			return;
		}

		if ($geoMashupOpts['google_key']) {
			$linkDir = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
			echo '	
			<script type="text/javascript">
			  //<![CDATA[
				function GeoMashup() {}
				GeoMashup.linkDir = "'.$linkDir.'";
				GeoMashup.mapControl = "'.$geoMashupOpts['map_control'].'";';
			if ($geoMashupOpts['add_map_type_control'] == 'true') {
				echo '
				GeoMashup.addMapTypeControl = true;';
			}
			if ($geoMashupOpts['add_overview_control'] == 'true') {
				echo '
				GeoMashup.addOverviewControl = true;';
			}
			if ($_GET['cat']) {
				echo '
				GeoMashup.cat = "'.$_GET['cat'].'";';
			}
			if ($geoMashupOpts['add_category_control'] == 'true') {
				$query = 'SELECT cat_ID, cat_name FROM '.$wpdb->categories;
				$categories = $wpdb->get_results($query);
				$comma = '';
				echo 'geoMashupCategories = {';
				foreach ($categories as $category) {
					echo $comma.$category->cat_ID.':\''.addslashes($category->cat_name).'\'';
					$comma = ',';
				}
				echo '}; GeoMashup.addCategoryControl = true;';
				readfile('category-control.js',true);
			}
			if ($geoMashupOpts['map_type']) {
				echo '
				GeoMashup.defaultMapType = '.$geoMashupOpts['map_type'].';';
			}
			if (strlen($geoMashupOpts['zoom_level'])>0) {
				echo '
				GeoMashup.defaultZoom = '.$geoMashupOpts['zoom_level'].';';
			}
			if ($geoMashupOpts['show_post'] == 'true') {
				echo '
				GeoMashup.showPostHere = '.$geoMashupOpts['show_post'].';';
			}
			if ($geoMashupOpts['show_log'] == 'true') {
				echo '
				GeoMashup.showLog = '.$geoMashupOpts['show_log'].';';
			}
			if ($_GET['lat'] && $_GET['lon']) {
				echo '
				GeoMashup.loadLat = "'.$_GET['lat'].'";';
				echo '
				GeoMashup.loadLon = "'.$_GET['lon'].'";';
			}
			if (strlen($_GET['zoom'])>0) {
				echo '
				GeoMashup.loadZoom = '.$_GET['zoom'].';';
			}
			if ($geoMashupOpts['auto_info_open'] == 'true') {
				echo '
				GeoMashup.autoOpenInfoWindow = true;';
			}
			$custom_marker_file = dirname(__FILE__).'/custom-marker.js';
			if (is_readable($custom_marker_file)) {
				readfile($custom_marker_file);
			}
			$mashup_js_file = dirname(__FILE__).'/geo-mashup.js';
			readfile($mashup_js_file);
			echo '
				GeoMashup.loadMap();
				//]]>
			</script>';
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
			$postdiv = '';
			if ($geoMashupOpts['show_post'] == 'true') {
				$postdiv = '<div id="geoPost"></div>';
			}

			if ($content) {
				$content = preg_replace('/<\!--\s*Geo.?Mashup\s*-->/i',$mapdiv,$content);
				$content = preg_replace('/<\!--\s*Geo.?Post\s*-->/i',$postdiv,$content);
				$content = preg_replace('/<\!--\s*Geo.?Category\s*-->/i',single_cat_title('',false),$content);
			} else {
				$content = $mapdiv;
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
					return $content.'</a>'.$geoMashupOpts['category_link_separator'].'<a href="'.get_bloginfo('url').'/'.$geoMashupOpts['mashup_page'].
						'?cat='.$category->cat_ID.'&zoom='.$geoMashupOpts['category_zoom'].'" title="'.$geoMashupOpts['category_link_text'].
						'">'.$geoMashupOpts['category_link_text'];
				} else {
					return $content.'</a>'.$geoMashupOpts['category_link_separator'].'<a href="'.get_bloginfo('url').'?pagename='.$geoMashupOpts['mashup_page'].
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

		$activePlugins = get_settings('active_plugins');
		$isGeoActive = false;
		foreach($activePlugins as $pluginFile) {
			if ($pluginFile == 'geo.php') {
				$isGeoActive = true;
			}
		}
		if (!$isGeoActive) {
			echo '
			<div class="updated">
				<p>The <a href="http://dev.wp-plugins.org/wiki/GeoPlugin">Geo Plugin</a> needs to be installed 
				and activated for Geo Mashup to work, but it wasn\'t found. We\'ll go on anyway and hope for the best...</p>
				<p>Here is the array of plugins WordPress says are active:<pre>';
			print_r($activePlugins);
			echo '</pre>
				If Geo is active, this list should contain geo.php. </p>
			</div>';
		}

		if (isset($_POST['submit'])) {
			// Process option updates
			$geoMashupOpts['include_style'] = 'false';
			$geoMashupOpts['add_map_type_control'] = 'false';
			$geoMashupOpts['add_overview_control'] = 'false';
			$geoMashupOpts['add_category_links'] = 'false';
			$geoMashupOpts['show_post'] = 'false';
			$geoMashupOpts['show_log'] = 'false';
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
		$pageSlugs = $wpdb->get_col("SELECT DISTINCT post_name FROM $wpdb->posts " .
			"WHERE post_status='static' ORDER BY post_name");
		if ($pageSlugs) {
			foreach($pageSlugs as $slug) {
				$selected = "";
				if ($slug == $geoMashupOpts['mashup_page']) {
					$selected = ' selected="true"';
				}
				$pageSlugOptions .= '<option value="'.$slug.'"'.$selected.'>'.$slug."</option>\n";
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

		if ($geoMashupOpts['show_log'] == 'true') {
			$showLogChecked = ' checked="true"';
		} else {
			$showLogChecked = '';
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
							<th scope="row">'.__('Mashup Page Slug', 'GeoMashup').'</th>
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
							<th scope="row">'.__('Show Debugging Log', 'GeoMashup').'</th>
							<td><input id="show_post" name="show_log" type="checkbox" value="true"'.$showLogChecked.' /></td>
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
			<p><a href="http://dev.wp-plugins.org/wiki/GeoMashup">Geo Mashup Documentation</a></p>
		</div>';
	}

	/**
	 * A tag to insert the the onload call needed by IE 
	 * (and works in Firefox) in the body tag to load the 
	 * Google map. DEPRECATED
	 */
	function body_attribute() {
		global $geoMashupOpts;
		if (is_page($geoMashupOpts['mashup_page'])) {
			//echo ' onload="GeoMashup.loadMap()"';
		}
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
		$lat = get_Lat();
		$lon = get_Lon();
		if ($lat && $lon) {
			$link = '';
			$url = get_bloginfo('url');
			$using_pretty_links = get_settings('permalink_structure');
			if ($using_pretty_links && !(strstr($url,'index.php'))) {
				$link = '<a href="'.$url.'/'.$geoMashupOpts['mashup_page'].htmlentities("/?lat=$lat&lon=$lon")."\">$text</a>";
			} else {
				$link = '<a href="'.$url.'?pagename='.$geoMashupOpts['mashup_page'].htmlentities("&lat=$lat&lon=$lon")."\">$text</a>";
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

} // class GeoMashup

add_action('wp_head', array('GeoMashup', 'wp_head'));
add_action('wp_footer', array('GeoMashup', 'wp_footer'));
add_action('admin_menu', array('GeoMashup', 'admin_menu'));
add_filter('the_content', array('GeoMashup', 'the_content'));
if ($geoMashupOpts['add_category_links'] == 'true') {
	add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
}

?>
