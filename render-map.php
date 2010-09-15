<?php
/**
 * Respond to a requests for Geo Mashup maps.
 *
 * @package GeoMashup
 */

status_header ( 200 );
geo_mashup_render_map ( );

/**
 * Make sure a non-javascript item is double-quoted.
 * 
 * @since 1.3
 * @access private
 *
 * @param mixed $item The value in question, may be modified.
 * @param string $key The JSON key.
 */
function add_double_quotes(&$item,$key) {
	$quoted_keys = array ( 'background_color', 'show_future', 'map_control', 'map_content', 'map_type', 'legend_format', 'template' );
	if ( $key == 'post_data' ) {
		// don't quote
	} else if ( !is_numeric( $item ) && empty ( $item ) ) {
		$item = '""';
	} else if ( is_array( $item ) && $item[0] ) {
		$item = '["' . implode( '","', $item ) . '"]';
	} else if ( is_string( $item ) && $item[0] != '{' && $item[0] != '[' ) {
		$item = '"'.$item.'"';
	}
}

/**
 * Print the requested map HTML.
 * 
 * Used in this file only.
 *
 * @since 1.3
 * @access private
 */
function geo_mashup_render_map ( ) {
	global $geo_mashup_options, $geo_mashup_custom;

	// Resolve map style
	$style_file_path = trailingslashit( get_template_directory() ) . 'map-style.css';
	$style_url_path = '';
	if ( is_readable( $style_file_path ) ) {
		$style_url_path = get_stylesheet_directory_uri();
		$style_url_path = trailingslashit( $style_url_path ) . 'map-style.css';
	} else if ( isset( $geo_mashup_custom ) ) {
		$style_url_path = $geo_mashup_custom->file_url( 'map-style.css' );
	}
	if ( empty( $style_url_path ) ) {
		$style_url_path = trailingslashit( GEO_MASHUP_URL_PATH ) . 'map-style-default.css';
	} 

	$map_api = $geo_mashup_options->get( 'overall', 'map_api' );
	$map_properties = array( 
		'siteurl' => get_bloginfo( 'url' ), // qTranslate doesn't work with get_option( 'home' )
		'url_path' => GEO_MASHUP_URL_PATH,
		'template_url_path' => get_template_directory_uri(),
		'map_api' => $map_api 
	);
	if ( isset( $geo_mashup_custom ) ) {
		$map_properties['custom_url_path'] = $geo_mashup_custom->url_path;
	}
	$map_content = ( isset( $_GET['map_content'] ) ) ?  $_GET['map_content'] : null;
	$object_name = ( isset( $_GET['object_name'] ) ) ? $_GET['object_name'] : 'post';

	if ( !empty( $_GET['lat'] ) ) {
		// Translate old querystring center argument
		$map_properties['center_lat'] = $_GET['lat'];
		unset( $_GET['lat'] );
	}

	if ( !empty( $_GET['lng'] ) ) {
		// Translate old querystring center argument
		$map_properties['center_lng'] = $_GET['lng'];
		unset( $_GET['lng'] );
	}

	$option_keys = array ( 'width', 'height', 'map_control', 'map_type', 'add_map_type_control', 'add_overview_control', 
		'add_google_bar', 'enable_scroll_wheel_zoom' );
	if ( $map_content == 'single') {
		$object_id = 0;
		if ( isset( $_GET['object_id'] ) ) {
			$object_id = $_GET['object_id']; 
			unset($_GET['object_id']);
		}
		$location = GeoMashupDB::get_object_location( $object_name, $object_id );
		$options = $geo_mashup_options->get ( 'single_map', $option_keys );
		if ( !empty( $location ) ) {
			$map_properties['center_lat'] = $location->lat;
			$map_properties['center_lng'] = $location->lng;
		}
		$map_properties = array_merge ( $options, $map_properties );
		if ( 'post' == $object_name ) {
			$kml_urls = GeoMashup::get_kml_attachment_urls( $object_id );
			if (count($kml_urls)>0) {
				$map_properties['load_kml'] = array_pop ( $kml_urls );
			}
		}
	} else {
		// Map content is not single
		if ( isset( $_GET['object_id'] ) ) {
			$map_properties['context_object_id'] = $_GET['object_id'];
			unset( $_GET['object_id'] );
		}
		array_push( $option_keys, 'marker_select_info_window', 'marker_select_highlight', 
			'marker_select_center', 'marker_select_attachments' );

		if ( $map_content == 'contextual' ) {
			$options = $geo_mashup_options->get ( 'context_map', $option_keys );
			// If desired we could make these real options
			$options['auto_info_open'] = 'false';
		} else {
			array_push ( $option_keys, 'max_posts', 'show_post', 'auto_info_open', 'cluster_max_zoom', 'cluster_lib' );
			$options = $geo_mashup_options->get ( 'global_map', $option_keys );
			if ( is_null ( $map_content ) ) {
				$options['map_content'] = 'global';
			}
		} 
		$map_properties['object_data'] = GeoMashup::get_locations_json($_GET);
		unset($_GET['object_ids']);
		$map_properties = array_merge ( $options, $map_properties );
	}

	// Queue scripts
	$mashup_dependencies = array( 'jquery' );
	$language_code = '';
	if ( function_exists( 'qtrans_getLanguage' ) ) {
		// qTranslate integration
		$language_code = qtrans_getLanguage();
	} else if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		// WPML integration
		$language_code = ICL_LANGUAGE_CODE;
	}

	if ( 'google' == $map_api ) {
		// Google v2 base
		$google_2_url = 'http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=' . 
			$geo_mashup_options->get( 'overall', 'google_key' );
		if ( ! empty( $language_code ) ) {
			$google_2_url .= '&amp;hl=' . $language_code;
		}
		wp_register_script( 'google-maps-2', $google_2_url );
		$mashup_dependencies[] = 'google-maps-2';
		$mashup_script = 'geo-mashup-google';

		if ( !empty( $map_properties['cluster_max_zoom'] ) ) {
			// Queue clustering scripts 
			if ( 'clustermarker' == $map_properties['cluster_lib'] ) {
				wp_register_script( 'mapiconmaker', path_join( GEO_MASHUP_URL_PATH, 'mapiconmaker.js' ), array( 'google-maps-2' ), '1.1' );
				wp_register_script( 'clustermarker', path_join( GEO_MASHUP_URL_PATH, 'ClusterMarker.js' ), array( 'mapiconmaker' ), '1.3.2' );
				$mashup_dependencies[] = 'clustermarker';
			} else {
				$map_properties['cluster_lib'] = 'markerclusterer';
				wp_enqueue_script( 'markerclusterer', path_join( GEO_MASHUP_URL_PATH, 'markerclusterer.js' ), array( 'google-maps-2', 'geo-mashup-google' ), '1.0' );
			}
		}
	} else {
		// Mapstraction base
		$mashup_script = 'geo-mashup-mxn';
		wp_register_script( 'mxn', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.js' ), null, GEO_MASHUP_VERSION );
		wp_register_script( 'mxn-core', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.core.js' ), array( 'mxn' ), GEO_MASHUP_VERSION );
	}

	// Mapstraction providers
	if ( 'openlayers' == $map_api ) { 
		wp_register_script( 'openlayers', 'http://openlayers.org/api/OpenLayers.js', null, 'latest' );
		wp_register_script( 'openstreetmap', 'http://www.openstreetmap.org/openlayers/OpenStreetMap.js', array( 'openlayers' ), 'latest' );
		wp_register_script( 'mxn-openlayers', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.openlayers.core.js' ), array( 'mxn-core', 'openstreetmap' ), GEO_MASHUP_VERSION );
		$mashup_dependencies[] = 'mxn-openlayers';
	} else if ( 'googlev3' == $map_api ) {
		$google_3_url = 'http://maps.google.com/maps/api/js?sensor=false';
		if ( ! empty( $language_code ) ) {
			$google_3_url .= '&amp;language=' . $language_code;
		}
		wp_register_script( 'google-maps-3', $google_3_url );
		wp_register_script( 'mxn-googlev3', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.googlev3.core.js' ), array( 'mxn-core', 'google-maps-3' ), GEO_MASHUP_VERSION );
		$mashup_dependencies[] = 'mxn-googlev3';
	}

	// Geo Mashup scripts
	wp_register_script( 'geo-mashup', path_join( GEO_MASHUP_URL_PATH, 'geo-mashup.js' ), $mashup_dependencies, GEO_MASHUP_VERSION );
	wp_enqueue_script( $mashup_script, path_join( GEO_MASHUP_URL_PATH, $mashup_script . '.js' ), array( 'geo-mashup' ), GEO_MASHUP_VERSION );

	// Custom javascript
	$custom_js_url_path = '';
	if ( isset( $geo_mashup_custom ) ) {
		$custom_js_url_path = $geo_mashup_custom->file_url( 'custom.js' );
	} else if ( is_readable( 'custom.js' ) ) {
		$custom_js_url_path = path_join( GEO_MASHUP_URL_PATH, 'custom.js' );
	}
	if ( ! empty( $custom_js_url_path ) ) {
		wp_enqueue_script( 'geo-mashup-custom', $custom_js_url_path, array( $mashup_script ) );
	}

	if ( 'true' == $map_properties['add_google_bar'] ) {
		$map_properties['adsense_code'] = $geo_mashup_options->get( 'overall', 'adsense_code' );
	}
	$map_properties = array_merge($map_properties, $_GET);
	$width = $map_properties['width'];
	if ( substr( $width, -1 ) != '%' ) {
		$width .= 'px';
	}
	unset( $map_properties['width'] );
	$height = $map_properties['height'];
	if ( substr( $height, -1 ) != '%' ) {
		$height .= 'px';
	}
	unset( $map_properties['height'] );

	array_walk($map_properties, 'add_double_quotes');

	$categories = get_categories( array( 'hide_empty' => false ) );
	$category_opts = '{';
	if (is_array($categories))
	{
		$cat_comma = '';
		$category_color = $geo_mashup_options->get('global_map', 'category_color');
		$category_line_zoom = $geo_mashup_options->get('global_map', 'category_line_zoom');
		foreach($categories as $category) {
			$category_opts .= $cat_comma.'"'.$category->term_id.'":{"name":"' . esc_js( $category->name ) . '"';
			$parent_id = '';
			if ( !empty( $category->parent ) ) {
				$parent_id = $category->parent;
			}
			$category_opts .= ',"parent_id":"' . $parent_id . '"';
			if ( !empty( $category_color[$category->slug] ) ) {
				$category_opts .= ',"color_name":"'.$category_color[$category->slug].'"';
			}
			if ( !empty( $category_line_zoom[$category->slug] ) ) {
				$category_opts .= ',"max_line_zoom":"'.$category_line_zoom[$category->slug].'"';
			}
			$category_opts .= '}';
			$cat_comma = ',';
		}
	}
	$category_opts .= '}';
	$map_properties['category_opts'] = $category_opts;

	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<!--[if lte IE 6]>
	<html xmlns:v="urn:schemas-microsoft-com:vml">
	<![endif]-->

		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Geo Mashup Map</title>
			<?php wp_print_scripts(); ?>

			<?php if ( $geo_mashup_options->get('overall', 'theme_stylesheet_with_maps' ) == 'true' ) : ?>
			<link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>" type="text/css" media="screen" />
			<?php endif; ?>

			<?php if ( $style_url_path ) : ?>
			<link rel="stylesheet" href="<?php echo $style_url_path; ?>" type="text/css" media="screen" />
			<?php endif; ?>
			<style type="text/css">
				v\:* { behavior:url(#default#VML); }
				#geo-mashup {
					width:<?php echo $width; ?>;
					height:<?php echo $height; ?>;
				}
			</style>
		</head>
		<body>
		<div id="geo-mashup">
			<noscript>
				<p><?php _e( 'This map requires JavaScript. You may have to enable it in your settings.', 'GeoMashup' ); ?></p>
			</noscript>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			GeoMashup.createMap(document.getElementById('geo-mashup'), { <?php echo GeoMashup::implode_assoc(':',',',$map_properties); ?> });	
			//]]>
		</script>
		</body>	
	</html>
<?php
}
?>
