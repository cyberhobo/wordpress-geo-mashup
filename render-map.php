<?php

require ( '../../../wp-blog-header.php' );

status_header ( 200 );
geo_mashup_render_map ( );

function add_double_quotes(&$item,$key) {
	if ( $key == 'post_data' ) {
		// don't quote
	} else if ( empty ( $item ) ) {
		$item = '""';
	} else if ($key == 'show_future' || $key == 'map_control' || is_int(strpos($item,',')) || is_int(strpos($item,':'))) {
		$item = '"'.$item.'"';
	}
}

function geo_mashup_render_map ( ) {
	global $wpdb, $post, $geo_mashup_options;
	$map_opts = array ( 'link_dir' => GEO_MASHUP_URL_PATH );

	$map_content = null;
	if (strlen($_GET['map_content']) > 0) {
		$map_content = $_GET['map_content'];
		unset($_GET['map_content']);
	}

	$option_keys = array ( 'width', 'height', 'map_control', 'map_type', 'add_map_type_control', 'add_overview_control' );
	if ( $map_content == 'single') {
		// Use single located page or post settings
		$post = get_post($_GET['post_id']);
		unset($_GET['post_id']);
		$settings = $geo_mashup_options->get ( 'single_map', $option_keys );
		$map_opts = array_merge ( GeoMashup::post_coordinates() , $map_opts );
		$map_opts = array_merge ( $settings, $map_opts );
		$map_opts['in_post'] = 'true';
		$kml_urls = GeoMashup::get_kml_attachment_urls($post->ID);
		if (count($kml_urls)>0) {
			$map_opts['load_kml'] = array_pop ( $kml_urls );
		}
	} else {
		// Map content is not single
		if ( $map_content == 'global' ) {
			array_push ( $option_keys, 'marker_min_zoom', 'max_posts', 'show_post', 'auto_info_open' );
			$settings = $geo_mashup_options->get ( 'global_map', $option_keys );
		} else if ( $map_content == 'contextual' ) {
			$settings = $geo_mashup_options->get ( 'context_map', $option_keys );
			// If desired we could make these real options
			$settings['marker_min_zoom'] = '';
			$settings['auto_info_open'] = 'false';
		}
		$map_opts['post_data'] = GeoMashup::getLocationsJson($_GET);
		unset($_GET['post_ids']);
		$map_opts = array_merge ( $settings, $map_opts );
	}

	$map_opts = array_merge($map_opts, $_GET);
	array_walk($map_opts, 'add_double_quotes');

	$width = $map_opts['width'];
	unset($map_opts['width']);
	$height = $map_opts['height'];
	unset($map_opts['height']);

	$category_select = "SELECT * 
		FROM $wpdb->terms t
		JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id
		WHERE taxonomy='category'";
	$categories = $wpdb->get_results($category_select);
	$category_opts = '{';
	if (is_array($categories))
	{
		$cat_comma = '';
		foreach($categories as $category) {
			$category_opts .= $cat_comma.'"'.addslashes($category->name).'":{';
			$category_color = $geo_mashup_options->get('global_map', 'category_color');
			$opt_comma = '';
			if ( !empty( $category_color[$category->slug] ) ) {
				$category_opts .= '"color_name":"'.$category_color[$category->slug].'"';
				$opt_comma = ',';
			}
			$category_line_zoom = $geo_mashup_options->get('global_map', 'category_line_zoom');
			if ( !empty( $category_line_zoom[$category->slug] ) ) {
				$category_opts .= $opt_comma.'"max_line_zoom":"'.$category_line_zoom[$category->slug].'"';
			}
			$category_opts .= '}';
			$cat_comma = ',';
		}
	}
	$category_opts .= '}';
	$map_opts['category_opts'] = $category_opts;

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
			<script src="geo-mashup.js" type="text/javascript"></script>
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
				$template_web_path = get_bloginfo('template_directory');
				
				if (is_readable(TEMPLATEPATH . '/map-style.css'))
				{
					echo '<link rel="stylesheet" type="text/css" href="' . $template_web_path . '/map-style.css' . '" />';
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
				#geoMashup {
					width:<?php echo $width; ?>px;
					height:<?php echo $height; ?>px;
				}
			</style>
		</head>
		<body>
		<div id="geoMashup"></div>
		<script type="text/javascript">
			//<![CDATA[
			GeoMashup.createMap(document.getElementById('geoMashup'), { <?php echo GeoMashup::implode_assoc(':',',',$map_opts); ?> });	
			//]]>
		</script>
		</body>	
	</html>
<?php
}
?>
