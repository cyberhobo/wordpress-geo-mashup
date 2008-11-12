<?php

function geo_mashup_options_page()
{
	global $wpdb, $geo_mashup_options;

	if (isset($_POST['submit'])) {
		// Process option updates
		check_admin_referer('geo-mashup-update-options');
		$geo_mashup_options->set_valid_options ( $_POST );
		if ($geo_mashup_options->save()) {
			echo '<div class="updated"><p>'.__('Options updated.', 'GeoMashup').'</p></div>';
		}
	}

	if ( !empty ( $geo_mashup_options->corrupt_options ) ) {
		// Options didn't load correctly
		$message .= ' ' . __('Saved options may be corrupted, try updating again. Corrupt values: ') . 
			'<code>' . $geo_mashup_options->corrupt_options . '</code>';
		echo '<div class="updated"><p>'.$message.'</p></div>';
	}

	if ( !empty ( $geo_mashup_options->validation_errors ) ) {
		// There were invalid options
		echo '<div class="updated"><p>' .
			__('Some invalid options will not be used. You may see this for new options after upgrading, just do an update.', 'GeoMashup');
		echo '<ul>';
		foreach ( $geo_mashup_options->validation_errors as $message ) {
			echo "<li>$message</li>";
		}
		echo '</ul></p></div>';
	}

	// Create form elements
	$pageSlugOptions = "";
	$pageSlugs = $wpdb->get_results("SELECT DISTINCT ID, post_name FROM $wpdb->posts " .
		"WHERE post_status='static' OR post_type='page' ORDER BY post_name");
	if ($pageSlugs) {
		foreach($pageSlugs as $slug) {
			$selected = "";
			if ($slug->ID == $geo_mashup_options->get ( 'overall', 'mashup_page' )) {
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
			<th>'.__('Show Connecting Line Until Zoom Level (0-20 or none)','GeoMashup')."</th></tr>\n";
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
				if ($name == $geo_mashup_options->get ( 'global_map', 'category_color', $category->slug ) ) {
					$colorOptions .= ' selected="true"';
				}
				$colorOptions .= ' style="background-color:'.$rgb.'">'. __($name,'GeoMashup').'</option>';
			}
			$category_line_zoom = '';
			$category_line_zoom = $geo_mashup_options->get ( 'global_map', 'category_line_zoom', $category->slug );
  
			$categoryTable .= '<tr><td>' . $category->name . '</td><td><select id="category_color_' .
				$category->slug . '" name="global_map[category_color][' . $category->slug . ']">'.$colorOptions.
				'</select></td><td><input id="category_line_zoom_' . $category->slug . 
				'" name="global_map[category_line_zoom]['.$category->slug.']" value="'.$category_line_zoom.
				'" type="text" size="2" maxlength="2" /></td></tr>'."\n";
		}
	}
	$categoryTable .= "</table>\n";

	$mapTypeOptions = "";
	$mapTypes = Array(
		'G_NORMAL_MAP' => __('Roadmap', 'GeoMashup'), 
		'G_SATELLITE_MAP' => __('Satellite', 'GeoMashup'),
		'G_HYBRID_MAP' => __('Hybrid', 'GeoMashup'),
		'G_PHYSICAL_MAP' => __('Terrain', 'GeoMashup'),
		'G_SATELLITE_3D_MAP' => __('Google Earth', 'GeoMashup'));
	foreach($mapTypes as $type => $label) {
		$selected = "";
		if ($type == $geo_mashup_options->get ( 'global_map', 'map_type' )) {
			$selected = ' selected="true"';
		}
		$mapTypeOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		$in_post_selected = "";
		if ($type == $geo_mashup_options->get ( 'single_map', 'map_type' )) {
			$in_post_selected = ' selected="true"';
		}
		$inPostMapTypeOptions .= '<option value="'.$type.'"'.$in_post_selected.'>'.$label."</option>\n";
		$context_selected = "";
		if ($type == $geo_mashup_options->get ( 'context_map', 'map_type' )) {
			$context_selected = ' selected="true"';
		}
		$contextMapTypeOptions .= '<option value="'.$type.'"'.$context_selected.'>'.$label."</option>\n";
	}
	$mapControlOptions = "";
	$inPostMapControlOptions = "";
	$contextMapControlOptions = "";
	$mapControls = Array(
		'GSmallZoomControl' => __('Small Zoom', 'GeoMashup'),
		'GSmallMapControl' => __('Small Pan/Zoom', 'GeoMashup'),
		'GLargeMapControl' => __('Large Pan/Zoom', 'GeoMashup'));
	foreach($mapControls as $type => $label) {
		$selected = "";
		if ($type == $geo_mashup_options->get ( 'global_map', 'map_control' )) {
			$selected = ' selected="true"';
		}
		$mapControlOptions .= '<option value="'.$type.'"'.$selected.'>'.$label."</option>\n";
		$in_post_selected = "";
		if ($type == $geo_mashup_options->get ( 'single_map', 'map_control' )) {
			$in_post_selected = ' selected="true"';
		}
		$inPostMapControlOptions .= '<option value="'.$type.'"'.$in_post_selected.'>'.$label."</option>\n";
		$context_selected = "";
		if ($type == $geo_mashup_options->get ( 'context_map', 'map_control' )) {
			$context_selected = ' selected="true"';
		}
		$contextMapControlOptions .= '<option value="'.$type.'"'.$context_selected.'>'.$label."</option>\n";
	}

	if ($geo_mashup_options->get ( 'global_map', 'add_map_type_control' ) == 'true') {
		$mapTypeChecked = ' checked="true"';
	} else {
		$mapTypeChecked = '';
	}

	if ($geo_mashup_options->get ( 'single_map', 'add_map_type_control' ) == 'true') {
		$inPostMapTypeChecked = ' checked="true"';
	} else {
		$inPostMapTypeChecked = '';
	}

	if ($geo_mashup_options->get ( 'context_map', 'add_map_type_control' ) == 'true') {
		$contextMapTypeChecked = ' checked="true"';
	} else {
		$contextMapTypeChecked = '';
	}

	if ($geo_mashup_options->get ( 'global_map', 'add_overview_control' ) == 'true') {
		$overviewChecked = ' checked="true"';
	} else {
		$overviewmapChecked = '';
	}

	if ($geo_mashup_options->get ( 'single_map', 'add_overview_control' ) == 'true') {
		$inPostOverviewChecked = ' checked="true"';
	} else {
		$inPostOverviewmapChecked = '';
	}

	if ($geo_mashup_options->get ( 'context_map', 'add_overview_control' ) == 'true') {
		$contextOverviewChecked = ' checked="true"';
	} else {
		$contextOverviewmapChecked = '';
	}

	if ($geo_mashup_options->get ( 'overall', 'add_category_links' ) == 'true') {
		$categoryLinksChecked = ' checked="true"';
	} else {
		$categoryLinksChecked = '';
	}

	if ($geo_mashup_options->get ( 'global_map', 'show_post' ) == 'true') {
		$showPostChecked = ' checked="true"';
	} else {
		$showPostChecked = '';
	}

	if ($geo_mashup_options->get ( 'global_map', 'click_to_load' ) == 'true') {
		$clickToLoadChecked = ' checked="true"';
	} else {
		$clickToLoadChecked = '';
	}

	if ($geo_mashup_options->get ( 'single_map', 'click_to_load' ) == 'true') {
		$inPostClickToLoadChecked = ' checked="true"';
	} else {
		$inPostClickToLoadChecked = '';
	}
	
	if ($geo_mashup_options->get ( 'context_map', 'click_to_load' ) == 'true') {
		$contextClickToLoadChecked = ' checked="true"';
	} else {
		$contextClickToLoadChecked = '';
	}
	
	$showFutureOptions = "";
	$futureOptions = Array(
		'true' => __('Yes', 'GeoMashup'),
		'false' => __('No', 'GeoMashup'),
		'only' => __('Only', 'GeoMashup'));
	foreach($futureOptions as $value => $label) {
		$selected = "";
		if ($value == $geo_mashup_options->get ( 'global_map', 'show_future' )) {
			$selected = ' selected="true"';
		}
		$showFutureOptions .= '<option value="'.$value.'"'.$selected.'>'.$label."</option>\n";
	}

	if ($geo_mashup_options->get ( 'global_map', 'excerpt_format' ) == 'text') {
		$textExcerptChecked = ' checked="true"';
		$htmlExcerptChecked = '';
	} else {
		$textExcerptChecked = '';
		$htmlExcerptChecked = ' checked="true"';
	}
	if ($geo_mashup_options->get ( 'global_map', 'auto_info_open' ) == 'true') {
		$autoInfoOpenChecked = ' checked="true"';
	} else {
		$autoInfoOpenChecked = '';
	}
	
	if ($geo_mashup_options->get ( 'overall', 'theme_stylesheet_with_maps' ) == 'true') {
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
			<li><a href="#geo-mashup-context-map-settings"><span><?php _e('Contextual Maps', 'GeoMashup'); ?></span></a></li>
			</ul>
			<?php wp_nonce_field('geo-mashup-update-options'); ?>
			<fieldset id="geo-mashup-overall-settings">
				<p><?php _e('Overall Geo Mashup Settings', 'GeoMashup'); ?></p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th width="33%" scope="row"><?php _e('Google Maps Key', 'GeoMashup'); ?></th>
						<td><input id="google_key" name="overall[google_key]" type="text" size="40" value="<?php echo $geo_mashup_options->get ( 'overall', 'google_key' ); ?>" />
						<a href="http://maps.google.com/apis/maps/signup.html"><?php _e('Get yours here', 'GeoMashup'); ?></a></td>
					</tr>
					<tr>
						<th scope="row" title="<?php _e('Generated links go here','GeoMashup'); ?>"><?php _e('Global Mashup Page', 'GeoMashup'); ?></th>
						<td>
							<select id="mashup_page" name="overall[mashup_page]"><?php echo $pageSlugOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Use Theme Style Sheet with Maps', 'GeoMashup'); ?></th>
						<td><input id="theme_stylesheet_with_maps" name="overall[theme_stylesheet_with_maps]" type="checkbox" value="true"<?php echo $themestylesheetwithmaps; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Category Links', 'GeoMashup'); ?></th>
						<td><input id="add_category_links" name="overall[add_category_links]" type="checkbox" value="true"<?php echo $categoryLinksChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Category Link Separator', 'GeoMashup'); ?></th>
						<td><input id="category_link_separator" name="overall[category_link_separator]" type="text" size="3" value="<?php echo $geo_mashup_options->get ( 'overall', 'category_link_separator' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Category Link Text', 'GeoMashup'); ?></th>
						<td><input id="category_link_text" name="overall[category_link_text]" type="text" size="5" value="<? echo $geo_mashup_options->get ( 'overall', 'category_link_text' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Single Category Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="category_zoom" name="overall[category_zoom]" type="text" size="2" value="<?php echo $geo_mashup_options->get ( 'overall', 'category_zoom' ); ?>" />
						<?php _e('0 (max zoom out) - 20 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?>" /></div>
			</fieldset>
			<fieldset id="geo-mashup-single-map-settings">
				<p><?php _e('Default settings for maps of a single located post.', 'GeoMashup'); ?></p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th scope="row"><?php _e('Map Width', 'GeoMashup'); ?></th>
						<td><input id="in_post_map_width" name="single_map[width]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'single_map', 'width' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Height', 'GeoMashup'); ?></th>
						<td><input id="in_post_map_height" name="single_map[height]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'single_map', 'height' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Control', 'GeoMashup'); ?></th>
						<td>
							<select id="in_post_map_control" name="single_map[map_control]"><?php echo $inPostMapControlOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Map Type Control', 'GeoMashup'); ?></th>
						<td><input id="in_post_add_map_type_control" name="single_map[add_map_type_control]" type="checkbox" value="true"<?php echo $inPostMapTypeChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Overview Control', 'GeoMashup'); ?></th>
						<td><input id="in_post_add_overview_control" name="single_map[add_overview_control]" type="checkbox" value="true"<?php echo $inPostOverviewChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Map Type', 'GeoMashup'); ?></th>
						<td>
							<select id="in_post_map_type" name="single_map[map_type]"><?php echo $inPostMapTypeOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="in_post_zoom" name="single_map[zoom]" type="text" size="2" value="<?php echo $geo_mashup_options->get ( 'single_map', 'zoom' ); ?>" />
						<?php _e('0 (max zoom out) - 20 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load', 'GeoMashup'); ?></th>
						<td>
							<input id="in_post_click_to_load" name="single_map[click_to_load]" type="checkbox" value="true"<?php echo $inPostClickToLoadChecked; ?> />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load Text', 'GeoMashup'); ?></th>
						<td><input id="in_post_click_to_load_text" name="single_map[click_to_load_text]" type="text" size="50" value="<?php echo $geo_mashup_options->get ( 'single_map', 'click_to_load_text' ); ?>" /></td>
					</tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?>" /></div>
			</fieldset>
			<fieldset id="geo-mashup-global-map-settings">
				<p><?php _e('Default settings for global maps of located posts.', 'GeoMashup'); ?></legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th width="33%" scope="row"><?php _e('Post Excerpt Format', 'GeoMashup'); ?></th>
						<td><input name="global_map[excerpt_format]" type="radio" value="text"<?php echo $textExcerptChecked; ?> />
						<?php echo _e('Text', 'GeoMashup'); ?><input name="global_map[excerpt_format]" type="radio" value="html"<?php echo $htmlExcerptChecked; ?> />
						<?php echo _e('HTML', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th width="33%" scope="row"><?php _e('Post Excerpt Length', 'GeoMashup'); ?></th>
						<td><input id="excerpt_length" name="global_map[excerpt_length]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'global_map', 'excerpt_length' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Width', 'GeoMashup'); ?></th>
						<td><input id="map_width" name="global_map[width]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'global_map', 'width' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Height', 'GeoMashup'); ?></th>
						<td><input id="map_height" name="global_map[height]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'global_map', 'height' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Control', 'GeoMashup'); ?></th>
						<td>
							<select id="map_control" name="global_map[map_control]"><?php echo $mapControlOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Map Type Control', 'GeoMashup'); ?></th>
						<td><input id="add_map_type_control" name="global_map[add_map_type_control]" type="checkbox" value="true"<?php echo $mapTypeChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Overview Control', 'GeoMashup'); ?></th>
						<td><input id="add_overview_control" name="global_map[add_overview_control]" type="checkbox" value="true"<?php echo $overviewChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Map Type', 'GeoMashup'); ?></th>
						<td>
							<select id="map_type" name="global_map[map_type]"><?php echo $mapTypeOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="zoom" name="global_map[zoom]" type="text" size="2" value="<?php echo $geo_mashup_options->get ( 'global_map', 'zoom' ); ?>" />
						<?php _e('0 (max zoom out) - 20 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Hide Markers Until Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="marker_min_zoom" name="global_map[marker_min_zoom]" type="text" size="2" value="<?php echo $geo_mashup_options->get ( 'global_map', 'marker_min_zoom' ); ?>" />
						<?php _e('0 (max zoom out) - 20 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Show Only Most Recent Posts', 'GeoMashup'); ?></th>
						<td><input id="max_posts" name="global_map[max_posts]" type="text" size="4" value="<?php echo $geo_mashup_options->get ( 'global_map', 'max_posts' ); ?>" />
						<?php _e('Number of posts to show, leave blank for all', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Show Future Posts', 'GeoMashup'); ?></th>
						<td><select id="show_future" name="global_map[show_future]"><?php echo $showFutureOptions; ?></select></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Automatically Open Linked Post Info Window', 'GeoMashup'); ?></th>
						<td><input id="auto_info_open" name="global_map[auto_info_open]" type="checkbox" value="true"<?php echo $autoInfoOpenChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load', 'GeoMashup'); ?></th>
						<td><input id="click_to_load" name="global_map[click_to_load]" type="checkbox" value="true"<?php echo $clickToLoadChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load Text', 'GeoMashup'); ?></th>
						<td><input id="click_to_load_text" name="global_map[click_to_load_text]" type="text" size="50" value="<?php echo $geo_mashup_options->get ( 'global_map', 'click_to_load_text' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Enable Full Post Display', 'GeoMashup'); ?></th>
						<td><input id="show_post" name="global_map[show_post]" type="checkbox" value="true"<?php echo $showPostChecked; ?> /></td>
					</tr>
					<tr><td colspan="2" align="center"><?php echo $categoryTable; ?>
					</td></tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?>" /></div>
			</fieldset>
			<fieldset id="geo-mashup-context-map-settings">
				<p><?php _e('Default settings for contextual maps, which include just the posts shown on a page, for example.', 'GeoMashup'); ?></p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th scope="row"><?php _e('Map Width', 'GeoMashup'); ?></th>
						<td><input id="context_map_width" name="context_map[width]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'context_map', 'width' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Height', 'GeoMashup'); ?></th>
						<td><input id="context_map_height" name="context_map[height]" type="text" size="5" value="<?php echo $geo_mashup_options->get ( 'context_map', 'height' ); ?>" /><?php _e('px', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Map Control', 'GeoMashup'); ?></th>
						<td>
							<select id="context_map_control" name="context_map[map_control]"><?php echo $contextMapControlOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Map Type Control', 'GeoMashup'); ?></th>
						<td><input id="context_add_map_type_control" name="context_map[add_map_type_control]" type="checkbox" value="true"<?php echo $contextMapTypeChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Add Overview Control', 'GeoMashup'); ?></th>
						<td><input id="context_add_overview_control" name="context_map[add_overview_control]" type="checkbox" value="true"<?php echo $contextOverviewChecked; ?> /></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Map Type', 'GeoMashup'); ?></th>
						<td>
							<select id="context_map_type" name="context_map[map_type]"><?php echo $contextMapTypeOptions; ?></select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Default Zoom Level', 'GeoMashup'); ?></th>
						<td><input id="context_zoom" name="context_map[zoom]" type="text" size="2" value="<?php echo $geo_mashup_options->get ( 'context_map', 'zoom' ); ?>" />
						<?php _e('0 (max zoom out) - 20 (max zoom in)', 'GeoMashup'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load', 'GeoMashup'); ?></th>
						<td>
							<input id="context_click_to_load" name="context_map[click_to_load]" type="checkbox" value="true"<?php echo $contextClickToLoadChecked; ?> />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Click To Load Text', 'GeoMashup'); ?></th>
						<td><input id="context_click_to_load_text" name="context_map[click_to_load_text]" type="text" size="50" value="<?php echo $geo_mashup_options->get ( 'context_map', 'click_to_load_text' ); ?>" /></td>
					</tr>
				</table>
				<div class="submit"><input type="submit" name="submit" value="<?php _e('Update Options', 'GeoMashup'); ?>" /></div>
			</fieldset>
		</form>
		<p><a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation"><?php _e('Geo Mashup Documentation', 'GeoMashup'); ?></a></p>
	</div>
<?php
}
?>
