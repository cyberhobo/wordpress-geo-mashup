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
	global $wp_scripts, $geo_mashup_options, $geo_mashup_custom;

	$map_api = $geo_mashup_options->get( 'overall', 'map_api' );
	if ( 'openlayers' == $map_api ) {
		wp_enqueue_script( 'mxn-openlayers' );
	} else {
		wp_enqueue_script( 'google-jsapi' );
	}

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

	// Resolve custom javascript
	$custom_js_url_path = '';
	if ( isset( $geo_mashup_custom ) ) {
		$custom_js_url_path = $geo_mashup_custom->file_url( 'custom.js' );
	} else if ( is_readable( 'custom.js' ) ) {
		$custom_js_url_path = trailingslashit( GEO_MASHUP_URL_PATH ) . 'custom.js';
	}
					 
	$map_properties = array ( 
		'siteurl' => get_option( 'siteurl' ),
		'home_url' => get_option( 'home' ),
		'url_path' => GEO_MASHUP_URL_PATH,
 		'template_url_path' => get_template_directory_uri() );
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
		array_push( $option_keys, 'marker_select_info_window', 'marker_select_highlight', 
			'marker_select_center', 'marker_select_attachments' );

		if ( $map_content == 'contextual' ) {
			$options = $geo_mashup_options->get ( 'context_map', $option_keys );
			// If desired we could make these real options
			$options['auto_info_open'] = 'false';
		} else {
			array_push ( $option_keys, 'max_posts', 'show_post', 'auto_info_open', 'cluster_max_zoom' );
			$options = $geo_mashup_options->get ( 'global_map', $option_keys );
			if ( is_null ( $map_content ) ) {
				$options['map_content'] = 'global';
			}
		} 
		$map_properties['object_data'] = GeoMashup::get_locations_json($_GET);
		unset($_GET['object_ids']);
		$map_properties = array_merge ( $options, $map_properties );
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
			<?php wp_head(); ?>

			<script src="<?php echo trailingslashit( GEO_MASHUP_URL_PATH ); ?>geo-mashup.js?v=<?php echo GEO_MASHUP_VERSION; ?>" type="text/javascript"></script>
			<?php if ( 'google' == $map_api ) : ?>
			<script src="<?php echo trailingslashit( GEO_MASHUP_URL_PATH ); ?>geo-mashup-google.js?v=<?php echo GEO_MASHUP_VERSION; ?>" type="text/javascript"></script>
			<?php elseif ( 'openlayers' == $map_api ) : ?>
			<script src="<?php echo trailingslashit( GEO_MASHUP_URL_PATH ); ?>geo-mashup-mxn.js?v=<?php echo GEO_MASHUP_VERSION; ?>" type="text/javascript"></script>
			<?php endif; ?>

			<?php if ( $custom_js_url_path ): ?>
			<script src="<?php echo $custom_js_url_path; ?>" type="text/javascript"></script>
			<?php endif; ?>

			<?php if ( !empty( $map_properties['cluster_max_zoom'] ) ) : ?>
			<script src="<?php echo trailingslashit( GEO_MASHUP_URL_PATH ); ?>mapiconmaker.js?v=1.1" type="text/javascript"></script>
			<script src="<?php echo trailingslashit( GEO_MASHUP_URL_PATH ); ?>ClusterMarker.js?v=1.3.2" type="text/javascript"></script>
			<?php endif; ?>

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
				<?php _e( 'This map requires JavaScript. You may have to enable it in your settings.', 'GeoMashup' ); ?>
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
