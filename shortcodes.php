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
function geo_mashup_map($atts) {
	global $wp_query, $geo_mashup_options;

	$url_params = array();
	if (!is_array($atts)) {
		$atts = array();
	}
	// Map content type isn't required, so resolve it
	$map_content = $atts['map_content'];
	unset($atts['map_content']);

	if ( empty ( $map_content ) ) {
		if (!$wp_query->in_the_loop) {
			$map_content = 'contextual';
		} else {
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
	}
	
	$option_keys = array ( 'width', 'height', 'zoom', 'click_to_load', 'click_to_load_text' );
	switch ($map_content) {
		case 'contextual':
			$url_params['map_content'] = 'contextual';
			$url_params += $geo_mashup_options->get ( 'context_map', $option_keys );
			$comma = '';
			foreach ($wp_query->posts as $post) {
				$url_params['post_ids'] .= $comma.$post->ID;
				$comma = ',';
			}
			break;

		case 'single':
			$url_params['map_content'] = 'single';
			$url_params += $geo_mashup_options->get ( 'single_map', $option_keys );
			$url_params['post_id'] = $wp_query->post->ID;
			break;

		case 'global':
			$url_params['map_content'] = 'global';
			$option_keys[] = 'show_future';
			$url_params += $geo_mashup_options->get ( 'global_map', $option_keys );
			if (isset($_SERVER['QUERY_STRING'])) {
				$url_params = wp_parse_args($_SERVER['QUERY_STRING'],$url_params);
			} 
			break;

		default:
			return '<div class="geo_mashup_map"><p>Unrecognized value for map_content: "'.$map_content.'".</p></div>';
	}
	$url_params = array_merge($url_params, $atts);
	
	$click_to_load = $url_params['click_to_load'];
	unset($url_params['click_to_load']);
	$click_to_load_text = $url_params['click_to_load_text'];
	unset($url_params['click_to_load_text']);

	$map_image = '';
	if ($url_params['static'] == 'true') {
		$locations = GeoMashupDB::get_post_locations($url_params);
		if (!empty($locations)) {
			$map_image = '<img src="http://maps.google.com/staticmap?size='.$url_params['width'].'x'.$url_params['height'];
			if (count($locations) == 1) {
				$map_image .= '&amp;center='.$locations[0]->lat . ',' . $locations[0]->lng;
			}
			$map_image .= '&amp;zoom=' . $url_params['zoom'] . '&amp;markers=';
			$separator = '';
			foreach ($locations as $location) {
				// TODO: Try to use the correct color for the category? Draw category lines?
				$map_image .= $separator . $location->lat . ',' . $location->lng . ',smallred';
				$separator = '|';
			}
			$map_image .= '&amp;key='.$geo_mashup_options->get('overall', 'google_key').'" alt="geo_mashup_map"';
			if ($click_to_load == 'true') {
				$map_image .= '" title="'.$click_to_load_text.'"';
			}
			$map_image .= ' />';
		}
	}
				
	$iframe_src = GEO_MASHUP_URL_PATH . '/render-map.php?' . GeoMashup::implode_assoc('=', '&amp;', $url_params, false, true);
	$content = "";

	if ($click_to_load == 'true') {
		$style = "height:{$url_params['height']}px;width:{$url_params['width']}px;background-color:#ddd;".
			"background-image:url(".GEO_MASHUP_URL_PATH."/images/wp-gm-pale.png);".
			"background-repeat:no-repeat;background-position:center;cursor:pointer;";
		$content = "<div class=\"geo_mashup_map\" style=\"$style\" " .
			"onclick=\"GeoMashupLoader.addMapFrame(this,'$iframe_src',{$url_params['height']},{$url_params['width']})\">";
		if ($url_params['static'] == 'true') {
			// TODO: test whether click to load really works with a static map
			$content .= $map_image . '</div>';
		} else {
			$content .= "<p style=\"text-align:center;\">$click_to_load_text</p></div>";
		}
	} else if ($url_params['static'] == 'true') {
		$content = "<div class=\"geo_mashup_map\">$map_image</div>";
	} else {
		$content =  "<div class=\"geo_mashup_map\"><iframe src=\"{$iframe_src}\" height=\"{$url_params['height']}\" ".
			"width=\"{$url_params['width']}\" marginheight=\"0\" marginwidth=\"0\" ".
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
