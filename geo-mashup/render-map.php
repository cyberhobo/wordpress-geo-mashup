<?php

require_once ( '../../../wp-blog-header.php' );

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

function geo_mashup_render_map ( ) {
	global $post, $geo_mashup_options, $geo_mashup_custom;

	// Resolve map style
	$style_file_path = trailingslashit( get_stylesheet_directory() ) . 'map-style.css';
	$style_url_path = '';
	if ( is_readable( $style_file_path ) ) {
		$style_url_path = get_stylesheet_directory_uri();
		$style_url_path = trailingslashit( $style_url_path ) . 'map-style.css';
	} else if ( isset( $geo_mashup_custom ) ) {
		$style_url_path = $geo_mashup_custom->file_url( 'map-style.css' );
	}
	if ( empty( $style_url_path ) ) {
		$style_url_path = 'map-style-default.css';
	} 

	// Resolve custom javascript
	$custom_js_url_path = '';
	if ( isset( $geo_mashup_custom ) ) {
		$custom_js_url_path = $geo_mashup_custom->file_url( 'custom.js' );
	} else if ( is_readable( 'custom.js' ) ) {
		$custom_js_url_path = 'custom.js';
	}
					 
	$map_properties = array ( 
		'url_path' => GEO_MASHUP_URL_PATH,
 		'template_url_path' => get_template_directory_uri() );
	if ( isset( $geo_mashup_custom ) ) {
		$map_properties['custom_url_path'] = $geo_mashup_custom->url_path;
	}

	$map_content = null;
	if (strlen($_GET['map_content']) > 0) {
		$map_content = $_GET['map_content'];
	}

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

	$option_keys = array ( 'width', 'height', 'map_control', 'map_type', 'add_map_type_control', 'add_overview_control' );
	if ( $map_content == 'single') {
		// Use single located page or post options
		$post = get_post($_GET['post_id']);
		unset($_GET['post_id']);
		$options = $geo_mashup_options->get ( 'single_map', $option_keys );
		$post_coordinates = GeoMashup::post_coordinates();
		if ( !empty( $post_coordinates ) ) {
			$map_properties['center_lat'] = $post_coordinates['lat'];
			$map_properties['center_lng'] = $post_coordinates['lng'];
		}
		$map_properties = array_merge ( $options, $map_properties );
		$kml_urls = GeoMashup::get_kml_attachment_urls($post->ID);
		if (count($kml_urls)>0) {
			$map_properties['load_kml'] = array_pop ( $kml_urls );
		}
	} else {
		// Map content is not single
		if ( $map_content == 'contextual' ) {
			$options = $geo_mashup_options->get ( 'context_map', $option_keys );
			// If desired we could make these real options
			$options['marker_min_zoom'] = '';
			$options['auto_info_open'] = 'false';
		} else {
			array_push ( $option_keys, 'marker_min_zoom', 'max_posts', 'show_post', 'auto_info_open' );
			$options = $geo_mashup_options->get ( 'global_map', $option_keys );
			if ( is_null ( $map_content ) ) {
				$options['map_content'] = 'global';
			}
		} 
		$map_properties['post_data'] = GeoMashup::get_post_locations_json($_GET);
		unset($_GET['post_ids']);
		$map_properties = array_merge ( $options, $map_properties );
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
			$category_opts .= $cat_comma.'"'.$category->term_id.'":{"name":"' . addslashes( $category->name ) . '"';
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
			<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $geo_mashup_options->get('overall', 'google_key');?>" 
							type="text/javascript"></script>
			<script src="geo-mashup.js?v=<?php echo GEO_MASHUP_VERSION; ?>" type="text/javascript"></script>

			<?php if ( $custom_js_url_path ): ?>
			<script src="<?php echo $custom_js_url_path; ?>" type="text/javascript"></script>
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
		<div id="geo-mashup"></div>
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
