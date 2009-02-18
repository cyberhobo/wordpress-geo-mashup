<?php

require_once ( '../../../wp-blog-header.php' );

status_header ( 200 );
geo_mashup_render_map ( );

function add_double_quotes(&$item,$key) {
	$quoted_keys = array ( 'background_color', 'show_future', 'map_control', 'map_content' );
	if ( $key == 'post_data' ) {
		// don't quote
	} else if ( empty ( $item ) ) {
		$item = '""';
	} else if ( in_array ( $key, $quoted_keys ) || is_int(strpos($item,',')) || is_int(strpos($item,':'))) {
		$item = '"'.$item.'"';
	}
}

function geo_mashup_render_map ( ) {
	global $post, $geo_mashup_options;
	$template_url_path = get_bloginfo( 'template_directory' );
	$map_properties = array ( 
		'url_path' => GEO_MASHUP_URL_PATH,
 		'template_url_path' => $template_url_path );

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

	$categories = get_categories( );
	$category_opts = '{';
	if (is_array($categories))
	{
		$cat_comma = '';
		$category_color = $geo_mashup_options->get('global_map', 'category_color');
		$category_line_zoom = $geo_mashup_options->get('global_map', 'category_line_zoom');
		foreach($categories as $category) {
			$category_opts .= $cat_comma.'"'.$category->cat_ID.'":{"name":"' . addslashes( $category->name ) . '"';
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
	<html xmlns="http://www.w3.org/1999/xhtml"
				xmlns:v="urn:schemas-microsoft-com:vml">
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Geo Mashup Map</title>
			<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $geo_mashup_options->get('overall', 'google_key');?>" 
							type="text/javascript"></script>
			<?php if (is_readable('custom.js')): ?>
			<script src="custom.js" type="text/javascript"></script>
			<?php endif; ?>
			<script src="geo-mashup.js?v=<?php echo GEO_MASHUP_VERSION; ?>" type="text/javascript"></script>
			<?php
				if ($geo_mashup_options->get('overall', 'theme_stylesheet_with_maps') == 'true')
				{
					echo '<link rel="stylesheet" href="';
					echo bloginfo('stylesheet_url');
					echo '" type="text/css" media="screen" />';
				}
			?>
			
			<?php 		
				// find the css file needed
				if (is_readable(TEMPLATEPATH . '/map-style.css'))
				{
					echo '<link rel="stylesheet" type="text/css" href="' . $template_url_path . '/map-style.css' . '" />';
				}
				else
				{
					if (is_readable('map-style.css'))
					{
						echo '<link rel="stylesheet" type="text/css" href="map-style.css" />';
					}
				}
			?>
			
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
