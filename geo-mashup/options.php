<?php

function geo_mashup_options_page()
{
	global $wpdb;

	if (isset($_POST['submit'])) {
		// Process option updates
		unset($_POST['submit']);
		check_admin_referer('geo-mashup-update-options');
		GeoMashup::$options = GeoMashup::$default_options;
		unset(GeoMashup::$options['using_defaults']);
		foreach($_POST as $name => $value) {
			GeoMashup::$options[$name] = $value;
		}
		update_option('geo_mashup_options', GeoMashup::$options);
		echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
	}

	// Warn if using defaults
	if (isset(GeoMashup::$options['using_defaults'])) {
		$message = __('Using default settings.', 'GeoMashup');
		if (isset(GeoMashup::$options['failed_options'])) {
			$message .= ' '.__('Saved options may be corrupted: ').GeoMashup::$options['failed_options'];
		}
		echo '<div class="updated"><p>'.$message.'</p></div>';
	}

	// Create form elements
	$pageSlugOptions = "";
	$pageSlugs = $wpdb->get_results("SELECT DISTINCT ID, post_name FROM $wpdb->posts " .
		"WHERE post_status='static' OR post_type='page' ORDER BY post_name");
	if ($pageSlugs) {
		foreach($pageSlugs as $slug) {
			$selected = "";
			if ($slug->ID == GeoMashup::$options['mashup_page']) {
				$selected = ' selected="true"';
			}
			$pageSlugOptions .= '<option value="'.$slug->ID.'"'.$selected.'>'.$slug->post_name."</option>\n";
		}
	} else {
		$pageSlugOptions = '<option value="">No pages found</option>';
	}

	// Create marker and color arrays
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
				if (is_array(GeoMashup::$options['category_color']) && $name == GeoMashup::$options['category_color'][$category->slug]) {
					$colorOptions .= ' selected="true"';
				}
				$colorOptions .= ' style="background-color:'.$rgb.'">'. __($name,'GeoMashup').'</option>';
			}
			$category_line_zoom = '';
			if (is_array(GeoMashup::$options['category_line_zoom'])) {
				$category_line_zoom = GeoMashup::$options['category_line_zoom'][$category->slug];
			}
  
			$categoryTable .= '<tr><td>' . $category->name . '</td><td><select id="category_color_' .
				$category->slug . '" name="category_color[' . $category->slug . ']">'.$colorOptions.
				'</select></td><td><input id="category_line_zoom_' . $category->slug . 
				'" name="category_line_zoom['.$category->slug.']" value="'.$category_line_zoom.
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
		if ($type == GeoMashup::$options['map_type']) {
			$selected = ' selected="true"';
		}
		$mapTypeOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		$in_post_selected = "";
		if ($type == GeoMashup::$options['in_post_map_type']) {
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
		if ($type == GeoMashup::$options['map_control']) {
			$selected = ' selected="true"';
		}
		$mapControlOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		$in_post_selected = "";
		if ($type == GeoMashup::$options['in_post_map_control']) {
			$in_post_select = ' selected="true"';
		}
		$inPostMapControlOptions .= '<option value="'.$type.'"'.$in_post_selected.'>'.$label."</option>\n";
	}

	if (GeoMashup::$options['add_map_type_control'] == 'true') {
		$mapTypeChecked = ' checked="true"';
	} else {
		$mapTypeChecked = '';
	}

	if (GeoMashup::$options['in_post_add_map_type_control'] == 'true') {
		$inPostMapTypeChecked = ' checked="true"';
	} else {
		$inPostMapTypeChecked = '';
	}

	if (GeoMashup::$options['add_overview_control'] == 'true') {
		$overviewChecked = ' checked="true"';
	} else {
		$overviewmapChecked = '';
	}

	if (GeoMashup::$options['in_post_add_overview_control'] == 'true') {
		$inPostOverviewChecked = ' checked="true"';
	} else {
		$inPostOverviewmapChecked = '';
	}

	if (GeoMashup::$options['add_category_links'] == 'true') {
		$categoryLinksChecked = ' checked="true"';
	} else {
		$categoryLinksChecked = '';
	}

	if (GeoMashup::$options['show_post'] == 'true') {
		$showPostChecked = ' checked="true"';
	} else {
		$showPostChecked = '';
	}

	if (GeoMashup::$options['click_to_load'] == 'true') {
		$clickToLoadChecked = ' checked="true"';
	} else {
		$clickToLoadChecked = '';
	}

	if (GeoMashup::$options['in_post_click_to_load'] == 'true') {
		$inPostClickToLoadChecked = ' checked="true"';
	} else {
		$inPostClickToLoadChecked = '';
	}
	
	$showFutureOptions = "";
	$futureOptions = Array(
		'true' => __('Yes', 'GeoMashup'),
		'false' => __('No', 'GeoMashup'),
		'only' => __('Only', 'GeoMashup'));
	foreach($futureOptions as $value => $label) {
		$selected = "";
		if ($value == GeoMashup::$options['show_future']) {
			$selected = ' selected="true"';
		}
		$showFutureOptions .= '<option value="'.$value.'"'.$selected.'>'.$label."</option>\n";
	}

	if (GeoMashup::$options['excerpt_format'] == 'text') {
		$textExcerptChecked = ' checked="true"';
		$htmlExcerptChecked = '';
	} else {
		$textExcerptChecked = '';
		$htmlExcerptChecked = ' checked="true"';
	}
	if (GeoMashup::$options['auto_info_open'] == 'true') {
		$autoInfoOpenChecked = ' checked="true"';
	} else {
		$autoInfoOpenChecked = '';
	}
	
	if (GeoMashup::$options['theme_stylesheet_with_maps'] == 'true') {
		$themestylesheetwithmaps = ' checked="true"';
	} else {
		$themestylesheetwithmaps = '';
	}

	// Now for the HTML
?>
	<div class="wrap">
		<h2><?php _e('Geo Mashup Plugin Options', 'GeoMashup'); ?></h2>
		<form method="post" id="geo-mashup-settings-form">
			<ul>
			<li><a href="#geo-mashup-overall-settings"><span><?php _e('Overall', 'GeoMashup'); ?></span></a></li>
			<li><a href="#geo-mashup-global-map-settings"><span><?php _e('Global Maps', 'GeoMashup'); ?></span></a></li>
			<li><a href="#geo-mashup-single-map-settings"><span><?php _e('Single Maps', 'GeoMashup'); ?></span></a></li>
			</ul>
			<?php wp_nonce_field('geo-mashup-update-options'); ?>
			<fieldset id="geo-mashup-overall-settings">
				<p><?php _e('Overall Geo Mashup Settings', 'GeoMashup'); ?></p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th width="33%" scope="row"><?php _e('Google Maps Key', 'GeoMashup'); ?></th>
						<td><input id="google_key" name="google_key" type="text" size="40" value="<?php echo GeoMashup::$options['google_key']; ?>" />
						<a href="http://maps.google.com/apis/maps/signup.html"><?php _e('Get yours here', 'GeoMashup'); ?></a></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Use Theme Style Sheet with Maps', 'GeoMashup'); ?></th>
						<td><input id="theme_stylesheet_with_maps" name="theme_stylesheet_with_maps" type="checkbox" value="true"<?php echo $themestylesheetwithmaps; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Category Links', 'GeoMashup'); ?></th>
						<td><input id="add_category_links" name="add_category_links" type="checkbox" value="true"<?php echo $categoryLinksChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Category Link Separator', 'GeoMashup'); ?></th>
						<td><input id="category_link_separator" name="category_link_separator" type="text" size="3" value="<?php echo GeoMashup::$options['category_link_separator']; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Category Link Text', 'GeoMashup'); ?></th>
						<td><input id="category_link_text" name="category_link_text" type="text" size="5" value="<? echo GeoMashup::$options['category_link_text']; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Single Category Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="category_zoom" name="category_zoom" type="text" size="2" value="<?php echo GeoMashup::$options['category_zoom']; ?>" />
						<?php _e('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?> &raquo;" /></div>
			</fieldset>
			<fieldset id="geo-mashup-single-map-settings">
				<p><?php _e('Default settings for maps of a single located post.', 'GeoMashup'); ?></p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th scope="row"><?php _e('In-Post Map Width', 'GeoMashup'); ?></th>
						<td><input id="in_post_map_width" name="in_post_map_width" type="text" size="5" value="<?php echo GeoMashup::$options['in_post_map_width']; ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('In-Post Map Height', 'GeoMashup'); ?></th>
						<td><input id="in_post_map_height" name="in_post_map_height" type="text" size="5" value="<?php echo GeoMashup::$options['in_post_map_height']; ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Control', 'GeoMashup'); ?></th>
						<td>
							<select id="in_post_map_control" name="in_post_map_control"><?php echo $inPostMapControlOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Map Type Control', 'GeoMashup'); ?></th>
						<td><input id="in_post_add_map_type_control" name="in_post_add_map_type_control" type="checkbox" value="true"<?php echo $inPostMapTypeChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Overview Control', 'GeoMashup'); ?></th>
						<td><input id="in_post_add_overview_control" name="in_post_add_overview_control" type="checkbox" value="true"<?php echo $inPostOverviewChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Map Type', 'GeoMashup'); ?></th>
						<td>
							<select id="in_post_map_type" name="in_post_map_type"><?php echo $inPostMapTypeOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="in_post_zoom_level" name="in_post_zoom_level" type="text" size="2" value="<?php echo GeoMashup::$options['in_post_zoom_level']; ?>" />
						<?php _e('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load', 'GeoMashup'); ?></th>
						<td>
							<input id="in_post_click_to_load" name="in_post_click_to_load" type="checkbox" value="true"<?php echo $inPostClickToLoadChecked; ?> />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load Text', 'GeoMashup'); ?></th>
						<td><input id="in_post_click_to_load_text" name="in_post_click_to_load_text" type="text" size="50" value="<?php echo GeoMashup::$options['in_post_click_to_load_text']; ?>" /></td>
					</tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?> &raquo;" /></div>
			</fieldset>
			<fieldset id="geo-mashup-global-map-settings">
				<p><?php _e('Default settings for global maps of located posts.', 'GeoMashup'); ?></legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th scope="row" title="<?php _e('Generated links go here','GeoMashup'); ?>"><?php _e('Global Mashup Page', 'GeoMashup'); ?></th>
						<td>
							<select id="mashup_page" name="mashup_page"><?php echo $pageSlugOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th width="33%" scope="row"><?php _e('Post Excerpt Format', 'GeoMashup'); ?></th>
						<td><input name="excerpt_format" type="radio" value="text"<?php echo $textExcerptChecked; ?> />
						<?php echo _e('Text', 'GeoMashup'); ?><input name="excerpt_format" type="radio" value="html"<?php echo $htmlExcerptChecked; ?> />
						<?php echo _e('HTML', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th width="33%" scope="row"><?php _e('Post Excerpt Length', 'GeoMashup'); ?></th>
						<td><input id="excerpt_length" name="excerpt_length" type="text" size="5" value="<?php echo GeoMashup::$options['excerpt_length']; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Width', 'GeoMashup'); ?></th>
						<td><input id="map_width" name="map_width" type="text" size="5" value="<?php echo GeoMashup::$options['map_width']; ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Height', 'GeoMashup'); ?></th>
						<td><input id="map_height" name="map_height" type="text" size="5" value="<?php echo GeoMashup::$options['map_height']; ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Control', 'GeoMashup'); ?></th>
						<td>
							<select id="map_control" name="map_control"><?php echo $mapControlOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Map Type Control', 'GeoMashup'); ?></th>
						<td><input id="add_map_type_control" name="add_map_type_control" type="checkbox" value="true"<?php echo $mapTypeChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Overview Control', 'GeoMashup'); ?></th>
						<td><input id="add_overview_control" name="add_overview_control" type="checkbox" value="true"<?php echo $overviewChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Map Type', 'GeoMashup'); ?></th>
						<td>
							<select id="map_type" name="map_type"><?php echo $mapTypeOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="zoom_level" name="zoom_level" type="text" size="2" value="<?php echo GeoMashup::$options['zoom_level']; ?>" />
						<?php _e('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Hide Markers Until Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="marker_min_zoom" name="marker_min_zoom" type="text" size="2" value="<?php echo GeoMashup::$options['marker_min_zoom']; ?>" />
						<?php _e('0 (max zoom out) - 17 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Show Only Most Recent Posts', 'GeoMashup'); ?></th>
						<td><input id="max_posts" name="max_posts" type="text" size="4" value="<?php echo GeoMashup::$options['max_posts']; ?>" />
						<?php _e('Number of posts to show, leave blank for all', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Show Future Posts', 'GeoMashup'); ?></th>
						<td><select id="show_future" name="show_future"><?php echo $showFutureOptions; ?></select></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Automatically Open Linked Post Info Window', 'GeoMashup'); ?></th>
						<td><input id="auto_info_open" name="auto_info_open" type="checkbox" value="true"<?php echo $autoInfoOpenChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load', 'GeoMashup'); ?></th>
						<td><input id="click_to_load" name="click_to_load" type="checkbox" value="true"<?php echo $clickToLoadChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load Text', 'GeoMashup'); ?></th>
						<td><input id="click_to_load_text" name="click_to_load_text" type="text" size="50" value="<?php echo GeoMashup::$options['click_to_load_text']; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Enable Full Post Display', 'GeoMashup'); ?></th>
						<td><input id="show_post" name="show_post" type="checkbox" value="true"<?php echo $showPostChecked; ?> /></td>
					</tr>
					<tr><td colspan="2" align="center"><?php echo $categoryTable; ?>
					</td></tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?> &raquo;" /></div>
			</fieldset>
		</form>
		<p><a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation"><?php _e('Geo Mashup Documentation', 'GeoMashup'); ?></a></p>
	</div>
<?php
}
?>
