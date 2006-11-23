<?php
/*
Plugin Name: Geo Mashup
Plugin URI: http://www.cyberhobo.net/downloads/geo-mashup-plugin/
Description: Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. For WordPress 1.5.1 or higher. Minimal instructions and configuration will be in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 0.4 
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

/**
 * The Geo Mashup class/namespace.
 */
class GeoMashup {

	function wp_head($not_used) {
		$opts = get_settings('geo_mashup_options');
		if (!is_page($opts['mashup_page'])) {
			return;
		}

		$linkDir = get_bloginfo('url')."/wp-content/plugins/geo-mashup";
		if ($opts['google_key']) {
			// Generate the mashup javascript
			if ($opts['include_style'] == 'true') {
				// Generate map style
				echo '
				<style type="text/css">
				#geoMashup {';
				if ($opts['map_width']) {
					echo '
					width:'.$opts['map_width'].'px;';
				}
				if ($opts['map_height']) {
					echo '
					height:'.$opts['map_height'].'px;';
				}
				echo '
				}
				.locationinfo {
					overflow:auto;';
				if ($opts['info_window_height']) {
					echo '
					height:'.$opts['info_window_height'].'px;';
				}
				if ($opts['info_window_width']) {
					echo '
					width:'.$opts['info_window_width'].'px;';
				}
				if ($opts['font_size']) {
					echo '
					font-size:'.$opts['font_size'].'%;';
				}
				echo '
				}
				</style>';
			}
			echo '
			<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$opts['google_key'].'" type="text/javascript"></script>
			<script type="text/javascript" src="'.$linkDir.'/geo-mashup.js"></script>
			<script type="text/javascript">
			  //<![CDATA[
				GeoMashup.linkDir = "'.$linkDir.'";
				GeoMashup.rssUri = "'.get_bloginfo('url').'/wp-rss2.php";
				GeoMashup.mapControl = "'.$opts['map_control'].'";';
			if ($opts['add_map_type_control'] == 'true') {
				echo '
				GeoMashup.addMapTypeControl = true;';
			}
			if ($opts['add_overview_control'] == 'true') {
				echo '
				GeoMashup.addOverviewControl = true;';
			}
			if ($opts['map_type']) {
				echo '
				GeoMashup.defaultMapType = '.$opts['map_type'].';';
			}
			if ($opts['zoom_level']) {
				echo '
				GeoMashup.defaultZoom = '.$opts['zoom_level'].';';
			}
			if ($opts['show_post'] == 'true') {
				echo '
				GeoMashup.showPostHere = '.$opts['show_post'].';';
			}
			if ($_GET['lat'] && $_GET['lon']) {
				echo '
				GeoMashup.loadLat = "'.$_GET['lat'].'";';
				echo '
				GeoMashup.loadLon = "'.$_GET['lon'].'";';
			}
			if ($_GET['zoom']) {
				echo '
				GeoMashup.loadZoom = '.$_GET['zoom'].';';
			}
			echo '
				//]]>
			</script>';
		}
	}

	function the_content($content = '') {
		$opts = get_settings('geo_mashup_options');
		if (is_page($opts['mashup_page'])) {
			$mapdiv = '<div id="geoMashup">';
			if (!$opts['google_key']) {
				$mapdiv .= '<p>The Google Mashup plugin needs a 
					<a href="http://maps.google.com/apis/maps/signup.html">Google API Key</a> set
					in the <a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">
					plugin options</a> before it will work.</p>';
			}
			$mapdiv .= '</div>';
			$postdiv = '';
			if ($opts['show_post'] == 'true') {
				$postdiv = '<div id="geoPost"></div>';
			}

			if ($content) {
				$content = preg_replace('/<\!--\s*Geo.?Mashup\s*-->/i',$mapdiv,$content);
				$content = preg_replace('/<\!--\s*Geo.?Post\s*-->/i',$postdiv,$content);
			} else {
				$content = $mapdiv;
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
		global $wpdb;

		$activePlugins = get_settings('active_plugins');
		$isGeoActive = false;
		foreach($activePlugins as $pluginFile) {
			if ($pluginFile == 'geo.php') {
				$isGeoActive = true;
			}
		}
		if (!$isGeoActive) {
			echo '
			<div class="wrap">
				<p>The <a href="http://dev.wp-plugins.org/wiki/GeoPlugin">Geo Plugin</a> needs to be installed 
				and activated for Geo Mashup to work.</p>
			</div>';
			return;
		}

		$opts = get_settings('geo_mashup_options');

		if (isset($_POST['submit'])) {
			// Process option updates
			$opts['include_style'] = 'false';
			$opts['add_map_type_control'] = 'false';
			$opts['add_overview_control'] = 'false';
			$opts['show_post'] = 'false';
			foreach($_POST as $name => $value) {
				$opts[$name] = $value;
			}
			update_option('geo_mashup_options', $opts);
			echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
		}

		// Add defaults for missing options
		if (!isset($opts['include_style'])) {
			$opts['include_style'] = 'true';
			$opts['map_width'] = '400';
			$opts['map_height'] = '500';
			$opts['info_window_width'] = '300';
			$opts['info_window_height'] = '175';
			$opts['font_size'] = '75';
			if (!isset($opts['map_control'])) {
				$opts['map_control'] = 'GSmallMapControl';
			}
			if (!isset($opts['add_map_type_control'])) {
				$opts['add_map_type_control'] = 'true';
			}
			update_option('geo_mashup_options', $opts);
			echo '<div class="updated"><p>'.__('Defaults set.', 'GeoMashup').'</p></div>';
		}

		// Create form elements
		$pageSlugOptions = "";
		$pageSlugs = $wpdb->get_col("SELECT DISTINCT post_name FROM $wpdb->posts " .
			"WHERE post_status='static' ORDER BY post_name");
		foreach($pageSlugs as $slug) {
			$selected = "";
			if ($slug == $opts['mashup_page']) {
				$selected = ' selected="true"';
			}
			$pageSlugOptions .= '<option value="'.$slug.'"'.$selected.'>'.$slug."</option>\n";
		}

		$mapTypeOptions = "";
		$mapTypes = Array(
			'G_NORMAL_MAP' => 'Roadmap',
			'G_SATELLITE_MAP' => 'Satellite',
			'G_HYBRID_MAP' => 'Hybrid');
		foreach($mapTypes as $type => $label) {
			$selected = "";
			if ($type == $opts['map_type']) {
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
			if ($type == $opts['map_control']) {
				$selected = ' selected="true"';
			}
			$mapControlOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		}

		if ($opts['include_style'] == 'true') {
			$styleChecked = ' checked="true"';
		} else {
			$styleChecked = '';
		}

		if ($opts['add_map_type_control'] == 'true') {
			$mapTypeChecked = ' checked="true"';
		} else {
			$mapTypeChecked = '';
		}

		if ($opts['add_overview_control'] == 'true') {
			$overviewChecked = ' checked="true"';
		} else {
			$overviewmapChecked = '';
		}

		if ($opts['show_post'] == 'true') {
			$showPostChecked = ' checked="true"';
		} else {
			$showPostChecked = '';
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
							<td><input id="google_key" name="google_key" type="text" size="40" value="'.$opts['google_key'].'" />
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
							<td><input id="zoom_level" name="zoom_level" type="text" size="2" value="'.$opts['zoom_level'].'" />'.
							__('0-17', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Enable Full Post Display', 'GeoMashup').'</th>
							<td><input id="show_post" name="show_post" type="checkbox" value="true"'.$showPostChecked.' /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>'.__('Presentation', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Include style settings', 'GeoMashup').'</th>
							<td><input id="include_style" name="include_style" type="checkbox" value="true"'.$styleChecked.' />'.
							__(' (Uncheck to use styles from your theme stylesheet instead of these)', 'GeoMashup').'</td>
						</tr>
						<tr>
							<th scope="row">'.__('Map Width', 'GeoMashup').'</th>
							<td><input id="map_width" name="map_width" type="text" size="5" value="'.$opts['map_width'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Map Height', 'GeoMashup').'</th>
							<td><input id="map_height" name="map_height" type="text" size="5" value="'.$opts['map_height'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Width', 'GeoMashup').'</th>
							<td><input id="info_window_width" name="info_window_width" type="text" size="5" value="'.$opts['info_window_width'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Height', 'GeoMashup').'</th>
							<td><input id="info_window_height" name="info_window_height" type="text" size="5" value="'.$opts['info_window_height'].'" />px</td>
						</tr>
						<tr>
							<th scope="row">'.__('Info Window Font Size', 'GeoMashup').'</th>
							<td><input id="font_size" name="font_size" type="text" size="5" value="'.$opts['font_size'].'" />%</td>
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
	 * Google map.
	 */
	function body_attribute() {
		$opts = get_settings('geo_mashup_options');
		if (is_page($opts['mashup_page'])) {
			echo ' onload="GeoMashup.loadMap()"';
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
	function post_link($text = 'Geo Mashup') {
		$lat = get_Lat();
		$lon = get_Lon();
		if ($lat && $lon) {
			$opts = get_settings('geo_mashup_options');
			$using_pretty_links = get_settings('permalink_structure');
			if ($using_pretty_links) {
				echo '<a href="'.get_bloginfo('url').'/'.$opts['mashup_page']."?lat=$lat&lon=$lon\">$text</a>";
			} else {
				echo '<a href="'.get_bloginfo('url').'?pagename='.$opts['mashup_page']."&lat=$lat&lon=$lon\">$text</a>";
			}
		}
	}
} // class GeoMashup

add_action('wp_head', array('GeoMashup', 'wp_head'));
add_action('admin_menu', array('GeoMashup', 'admin_menu'));
add_filter('the_content', array('GeoMashup', 'the_content'));

?>
