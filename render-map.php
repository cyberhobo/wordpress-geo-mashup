<?php

require('../../../wp-blog-header.php');
global $post;

status_header(200);

function add_double_quotes(&$item,$key) {
	if ($key == 'show_future' || $key == 'map_control' || is_int(strpos($item,',')) || is_int(strpos($item,':'))) {
		$item = '"'.$item.'"';
	}
}

$map_opts = array('link_dir' => '\''.GeoMashup::$url_path.'\'');

$map_content = null;
if (strlen($_GET['map_content']) > 0) {
	$map_content = $_GET['map_content'];
	unset($_GET['map_content']);
}

if ($map_content == 'single')
{
	// Use single located page or post settings
	$post = get_post($_GET['post_id']);
	unset($_GET['post_id']);
	$settings = array(
		'width' => GeoMashup::$options['in_post_map_width'],
		'height' => GeoMashup::$options['in_post_map_height'],
		'map_control' => "'".GeoMashup::$options['in_post_map_control']."'",
		'add_map_type_control' => (GeoMashup::$options['in_post_add_map_type_control']?GeoMashup::$options['in_post_add_map_type_control']:'false'),
		'add_overview_control' => (GeoMashup::$options['in_post_add_overview_control']?GeoMashup::$options['in_post_add_overview_control']:'false'),
		'map_type' => "'".GeoMashup::$options['in_post_map_type']."'");
	$map_opts = array_merge(GeoMashup::post_coordinates(),$map_opts);
	$map_opts = array_merge($settings,$map_opts);
	$map_opts['in_post'] = 'true';
	$kml_urls = GeoMashup::get_kml_attachment_urls($post->ID);
	if (count($kml_urls)>0) {
		$map_opts['load_kml'] = '\''.array_pop($kml_urls).'\'';
	}
} else {
	// Map content is not single
	if ($map_content == 'global') {
		$settings = array(
			'width' => GeoMashup::$options['map_width'],
			'height' => GeoMashup::$options['map_height'],
			'add_map_type_control' => (GeoMashup::$options['add_map_type_control']?GeoMashup::$options['add_map_type_control']:'false'),
			'map_control' => "'".GeoMashup::$options['map_control']."'",
			'add_overview_control' => (GeoMashup::$options['add_overview_control']?GeoMashup::$options['add_overview_control']:'false'),
			'map_type' => "'".GeoMashup::$options['map_type']."'",
			'marker_min_zoom' => (GeoMashup::$options['marker_min_zoom']?GeoMashup::$options['marker_min_zoom']:'0'),
			'max_posts' => (GeoMashup::$options['max_posts']?GeoMashup::$options['max_posts']:'false'),
			'show_post_here' => (GeoMashup::$options['show_post']?GeoMashup::$options['show_post']:'false'),
			'auto_open_info_window' => (GeoMashup::$options['auto_info_open']?GeoMashup::$options['auto_info_open']:'false'),
			'info_window_width' => (GeoMashup::$options['info_window_width']?GeoMashup::$options['info_window_width']:'false'),
			'info_window_height' => (GeoMashup::$options['info_window_height']?GeoMashup::$options['info_window_height']:'false'));
	} else {
		//TODO: contextual defaults
		$settings = array(
			'width' => GeoMashup::$options['map_width'],
			'height' => GeoMashup::$options['map_height']);
	}
	$map_opts['post_data'] = GeoMashup::getLocationsJson($_GET);
	unset($_GET['post_ids']);
	$map_opts = array_merge($settings,$map_opts);
}

$get_opts = $_GET;
array_walk($get_opts, 'add_double_quotes');
$map_opts = array_merge($map_opts, $get_opts);

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
		$opt_comma = '';
		if (is_array(GeoMashup::$options['category_color']) && GeoMashup::$options['category_color'][$category->slug]) {
			$category_opts .= '"color_name":"'.GeoMashup::$options['category_color'][$category->slug].'"';
			$opt_comma = ',';
		}
		if (GeoMashup::$options['category_line_zoom'][$category->slug]) {
			$category_opts .= $opt_comma.'"max_line_zoom":"'.GeoMashup::$options['category_line_zoom'][$category->slug].'"';
		}
		$category_opts .= '}';
		$cat_comma = ',';
	}
}
$category_opts .= '}';
$map_opts['categoryOpts'] = $category_opts;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Geo Mashup Map</title>
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo GeoMashup::$options['google_key'];?>" 
						type="text/javascript"></script>
		<?php if (is_readable('custom.js')): ?>
		<script src="custom.js" type="text/javascript"></script>
		<?php endif; ?>
		<script src="geo-mashup.js" type="text/javascript"></script>
		<?php
			if (GeoMashup::$options['theme_stylesheet_with_maps'] == 'true')
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
