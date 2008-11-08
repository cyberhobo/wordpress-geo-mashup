<?php
/** 
 * Functions to implement Geo Mashup shortcodes
 */

add_shortcode('geo_mashup_map','geo_mashup_map');
add_shortcode('geo_mashup_show_on_map_link', 'geo_mashup_show_on_map_link');
add_shortcode('geo_mashup_full_post','geo_mashup_full_post');
add_shortcode('geo_mashup_category_name','geo_mashup_category_name');
add_shortcode('geo_mashup_category_legend','geo_mashup_category_legend');
add_shortcode('geo_mashup_list_located_posts','geo_mashup_list_located_posts');

/**
 * [geo_mashup_map ...]
 */
function geo_mashup_map($atts)
{
	global $wp_query, $geo_mashup_options;

	$options = array();
	if (!is_array($atts)) {
		$atts = array();
	}
	// Map content type isn't required, so resolve it
	$map_content = $atts['map_content'];
	unset($atts['map_content']);

	if ($wp_query->current_post == -1) {
		// We're not in a post or page 
		if (empty($map_content)) {
			$map_content = 'contextual';
		} 
	} else if (empty($map_content) || $map_content == 'contextual') {
		// We are in a post or page, see if it's located
		$coords = GeoMashup::post_coordinates();
		if (empty($coords)) {
			// Not located, go global
			$map_content = 'global';
		} else {
			// Located, go single
			$map_content = 'single';
		}
	}
	
	switch ($map_content) {
		case 'contextual':
			$options['map_content'] = 'contextual';
			$comma = '';
			while ($wp_query->have_posts()) {
				$wp_query->the_post();
				$options['post_ids'] .= $comma.$wp_query->post->ID;
				$comma = ',';
			}
			break;

		case 'single':
			$options['map_content'] = 'single';
			$options['width'] = $geo_mashup_options->get('single_map', 'width'); 
			$options['height'] = $geo_mashup_options->get('single_map', 'height');
			$options['zoom'] = $geo_mashup_options->get('single_map', 'zoom_level')?$geo_mashup_options->get('single_map', 'zoom_level'):10;
			$options['post_id'] = $wp_query->post->ID;
			$options['click_to_load'] = $geo_mashup_options->get('single_map', 'click_to_load');
			$options['click_to_load_text'] = $geo_mashup_options->get('single_map', 'click_to_load_text');
			break;

		case 'global':
			$options['map_content'] = 'global';
			$options['width'] = $geo_mashup_options->get('global_map', 'width');
			$options['height'] = $geo_mashup_options->get('global_map', 'height');
			$options['zoom'] = $geo_mashup_options->get('global_map', 'zoom_level')?$geo_mashup_options->get('global_map', 'zoom_level'):'5';
			$options['show_future'] = $geo_mashup_options->get('global_map', 'show_future');
			$options['click_to_load'] = $geo_mashup_options->get('global_map', 'click_to_load');
			$options['click_to_load_text'] = $geo_mashup_options->get('global_map', 'click_to_load_text');
			if (isset($_SERVER['QUERY_STRING'])) {
				$options = wp_parse_args($_SERVER['QUERY_STRING'],$options);
			} 
			break;

		default:
			return '<div class="geo_mashup_map"><p>Unrecognized value for map_content: "'.$map_content.'".</p></div>';
	}
	$options = array_merge($options, $atts);
	
	$click_to_load = $options['click_to_load'];
	unset($options['click_to_load']);
	$click_to_load_text = $options['click_to_load_text'];
	unset($options['click_to_load_text']);

	$map_image = '';
	if ($options['static'] == 'true') {
		$locations = GeoMashup::getLocations($options);
		if (!empty($locations)) {
			$map_image = '<img src="http://maps.google.com/staticmap?size='.$options['width'].'x'.$options['height'];
			if (count($locations) == 1) {
				$map_image .= '&amp;center='.$locations[0]->meta_value;
			}
			if ($options['zoom']) {
				$map_image .= '&amp;zoom='.$options['zoom'];
			}
			$map_image .= '&amp;markers=';
			$separator = '';
			foreach ($locations as $location) {
				// TODO: Try to use the correct color for the category? Draw category lines?
				$map_image .= $separator.$location->meta_value.',smallred';
				$separator = '|';
			}
			$map_image .= '&amp;key='.$geo_mashup_options->get('overall', 'google_key').'" alt="geo_mashup_map"';
			if ($click_to_load == 'true') {
				$map_image .= '" title="'.$click_to_load_text.'"';
			}
			$map_image .= ' />';
		}
	}
				
	$iframe_src = GEO_MASHUP_URL_PATH . '/render-map.php?' . GeoMashup::implode_assoc('=', '&amp;', $options, false, true);
	$content = "";

	if ($click_to_load == 'true') {
		$style = "height:{$options['height']}px;width:{$options['width']}px;background-color:#ddd;".
			"background-image:url($wpurl/wp-content/plugins/geo-mashup/images/wp-gm-pale.png);".
			"background-repeat:no-repeat;background-position:center;cursor:pointer;";
		$content = "<div class=\"geo_mashup_map\" style=\"$style\" " .
			"onclick=\"GeoMashupLoader.addMapFrame(this,'$iframe_src',{$options['height']},{$options['width']})\">";
		if ($options['static'] == 'true') {
			// TODO: test whether click to load really works with a static map
			$content .= $map_image . '</div>';
		} else {
			$content .= "<p style=\"text-align:center;\">$click_to_load_text</p></div>";
		}
	} else if ($options['static'] == 'true') {
		$content = "<div class=\"geo_mashup_map\">$map_image</div>";
	} else {
		$content =  "<div class=\"geo_mashup_map\"><iframe src=\"{$iframe_src}\" height=\"{$options['height']}\" ".
			"width=\"{$options['width']}\" marginheight=\"0\" marginwidth=\"0\" ".
			"scrolling=\"no\" frameborder=\"0\"></iframe></div>";
	}
	return $content;
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

?>
