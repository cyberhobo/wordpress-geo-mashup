<?php
/*
Plugin Name: Geo Mashup
Plugin URI: http://www.cyberhobo.net/downloads/geo-mashup-plugin/
Description: Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. For WordPress 1.5.1 or higher. Minimal instructions and configuration will be in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 0.2 Beta
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

You should have received a copy of the GNU General Public
License along with this program; if not, write to the Free
Software Foundation, Inc., 59 Temple Place, Suite 330,
Boston, MA 02111-1307 USA
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

		$linkDir = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
		if ($opts['google_key']) {
			// Generate the mashup javascript
			// ABQIAAAA6EmEUs9xswJKO1QE3fbmcRRbGpYcqmS-5O7vGRdcs734576xTRQFXBBqKQ9s3AlJAHsF2deBq7cgbg
			echo '
			<script src="http://maps.google.com/maps?file=api&amp;v=1&amp;key='.$opts['google_key'].'" type="text/javascript"></script>
			<script type="text/javascript" src="'.$linkDir.'/geo-mashup.js"></script>
			<script type="text/javascript">
				GeoMashup.linkDir = "'.$linkDir.'";
				GeoMashup.rssUri = "'.get_bloginfo('wpurl').'/wp-rss2.php";';
			if ($opts['map_type']) {
				echo '
				GeoMashup.defaultMapType = '.$opts['map_type'].';';
			}
			if ($opts['zoom_level']) {
				echo '
				GeoMashup.defaultZoom = '.$opts['zoom_level'].';';
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
			</script>';
		}
	}

	function the_content($content = '') {
		$opts = get_settings('geo_mashup_options');
		if (is_page($opts['mashup_page'])) {
			if ($opts['google_key']) {
				$height = $opts['map_height'] ? $opts['map_height'] : '500';
				$width = $opts['map_width'] ? $opts['map_width'] : '400';
				$content .= '<div id="geoMashup" style="width:' . $width .
					'px; height:'.$height.'px;"></div>';
			} else {
				$content .= 'The Google Mashup plugin needs a 
					<a href="http://maps.google.com/apis/maps/signup.html">Google API Key</a> set
					in the <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">
					plugin options</a> before it will work.';
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
		$opts = get_settings('geo_mashup_options');
		foreach($_POST as $name => $value) {
			switch($name) {
				case 'google_key':
					$opts['google_key'] = $value;
					break;

				case 'map_width':
					$opts['map_width'] = $value;
					break;

				case 'map_height':
					$opts['map_height'] = $value;
					break;

				case 'mashup_page':
					$opts['mashup_page'] = $value;
					break;

				case 'map_type':
					$opts['map_type'] = $value;
					break;

				case 'zoom_level':
					$opts['zoom_level'] = $value;
					break;
			}
		}
		if (isset($_POST['submit'])) {
			echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
			update_option('geo_mashup_options', $opts);
		}
		$mapTypeOptions = "";
		$mapTypes = Array(
			'G_MAP_TYPE' => 'Roadmap',
			'G_SATELLITE_TYPE' => 'Satellite',
			'G_HYBRID_TYPE' => 'Hybrid');
		foreach($mapTypes as $type => $label) {
			$selected = "";
			if ($type == $opts['map_type']) {
				$selected = ' selected="true"';
			}
			$mapTypeOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		}
		echo '
		<div class="wrap">
			<form method="post">
				<h2>'.__('Geo Mashup Options', 'GeoMashup').'</h2>
				<fieldset>
					<legend>'.__('Settings', 'GeoMashup').'</legend>
					<table width="100%" cellspacing="2" cellpadding="5" class="editform">
						<tr>
							<th width="33%" scope="row">'.__('Google Maps Key', 'GeoMashup').'</th>
							<td><input id="google_key" name="google_key" type="text" size="40" value="'.$opts['google_key'].'" />
							<a href="http://maps.google.com/apis/maps/signup.html">'.__('Get yours here', 'GeoMashup').'</a></td>
						</tr>
						<tr>
							<th scope="row">'.__('Page Slug', 'GeoMashup').'</th>
							<td><input id="mashup_page" name="mashup_page" type="text" size="30" value="'.$opts['mashup_page'].'" /></td>
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
					</table>
				</fieldset>
				<div class="submit"><input type="submit" name="submit" value="'.__('Update Options', 'GeoMashup').'" /></div>
				<fieldset>
					<legend>' . __('Minimal Instructions') . '</legend>
					<ol>
						<li>Make sure you have the <a href="http://www.asymptomatic.net/wp-hacks">Geo Plugin</a> 
						installed and at least one post with coordinates entered.</a></li>
						<li>Get your Google Maps API key and enter it above.</li>
						<li>Create a new page. Make sure the page slug matches the setting above.</li>
						<li>Edit your page template so the body tag looks like this:
							<code>&lt;body &lt;?php GeoMashup::body_attribute(); ?&gt;&gt;</code>
						</li>
						<li>View the page and play with your new blog map!</li>
						<li>If nothing shows up, you may have to add <code>&lt;?php wp_head();?&gt;</code> in 
							your page template header.</li>
					</ol>
				</fieldset>
			</form>
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
			echo 'onload="GeoMashup.loadMap()"';
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
	function post_link($text) {
		$lat = get_Lat();
		$lon = get_Lon();
		if ($lat && $lon) {
			$opts = get_settings('geo_mashup_options');
			echo '<a href="'.get_bloginfo('wpurl').'/'.$opts['mashup_page']."?lat=$lat&lon=$lon\">$text</a>";
		}
	}
} // class GeoMashup

add_action('wp_head', array('GeoMashup', 'wp_head'));
add_action('admin_menu', array('GeoMashup', 'admin_menu'));
add_filter('the_content', array('GeoMashup', 'the_content'));

?>
