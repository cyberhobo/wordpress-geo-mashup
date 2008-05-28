<?php

require('../../../wp-blog-header.php');
global $post;

status_header(200);

$link_dir = get_bloginfo('wpurl')."/wp-content/plugins/geo-mashup";
$geo_mashup_opts = get_settings('geo_mashup_options');
$packed = '';
if ($geo_mashup_opts['use_packed'] == 'true') $packed = 'packed/';

$map_opts = array(
	'addMapTypeControl' => ($geo_mashup_opts['add_map_type_control']?$geo_mashup_opts['add_map_type_control']:'false'),
	'linkDir' => "'$link_dir'",
	'mapControl' => "'{$geo_mashup_opts['map_control']}'",
	'addOverviewControl' => ($geo_mashup_opts['add_overview_control']?$geo_mashup_opts['add_overview_control']:'false'),
	'mapType' => "'{$geo_mashup_opts['map_type']}'",
	'zoom' => ($geo_mashup_opts['zoom_level']?$geo_mashup_opts['zoom_level']:'5'),
	'markerMinZoom' => ($geo_mashup_opts['marker_min_zoom']?$geo_mashup_opts['marker_min_zoom']:'0'),
	'maxPosts' => ($geo_mashup_opts['max_posts']?$geo_mashup_opts['max_posts']:'false'),
	'showPostHere' => ($geo_mashup_opts['show_post']?$geo_mashup_opts['show_post']:'false'),
	'autoOpenInfoWindow' => ($geo_mashup_opts['auto_info_open']?$geo_mashup_opts['auto_info_open']:'false'),
	'infoWindowWidth' => ($geo_mashup_opts['info_window_width']?$geo_mashup_opts['info_window_width']:'false'),
	'infoWindowHeight' => ($geo_mashup_opts['info_window_height']?$geo_mashup_opts['info_window_height']:'false'));

$post = null;
if (strlen($_GET['postIDs']) > 0)
{
	if (strpos($_GET['postIDs'],',') > 0) {
		$map_opts['postData'] = GeoMashup::getLocationsJson($_GET);
	} else {
		$post = get_post($_GET['postIDs']);
		unset($_GET['postIDs']);
	}
}

if ($post)
{
	$kml_urls = GeoMashup::get_kml_attachment_urls($post->ID);
	if (count($kml_urls)>0)
	{
		$map_opts['loadKml'] = '\''.array_pop($kml_urls).'\'';
	}
	if ($post->post_type == 'post')
	{
		$map_opts = $map_opts + GeoMashup::post_coordinates();
		$map_opts['inPost'] = 'true';
	}
	else if ($post->post_type = 'page')
	{
		$map_opts['postData'] = GeoMashup::getLocationsJson($_GET);
	}
}

$width = $_GET['width'];
if (!is_numeric($width)) { 
	if (is_page($post)) $width = $geo_mashup_opts['in_post_map_width'];
	else $width = $geo_mashup_opts['map_width'];
}
$height = $_GET['height'];
if (!is_numeric($height))
{
	if (is_page($post)) $height = $geo_mashup_opts['in_post_map_height'];
	else $height = $geo_mashup_opts['map_height'];
}

$category_select = "SELECT * 
	FROM $wpdb->terms t
	JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id
	WHERE taxonomy='category'";
$categories = $wpdb->get_results($category_select);
$category_opts = '{';
if ($categories)
{
	$cat_comma = '';
	foreach($categories as $category) {
		$category_opts .= $cat_comma.'"'.addslashes($category->name).'":{';
		$opt_comma = '';
		if ($geoMashupOpts['category_color'][$category->slug]) {
			$category_opts .= '"color_name":"'.$geoMashupOpts['category_color'][$category->slug].'"';
			$opt_comma = ',';
		}
		if ($geoMashupOpts['category_line_zoom'][$category->slug]) {
			$category_opts .= $opt_comma.'"max_line_zoom":"'.$geoMashupOpts['category_line_zoom'][$category->slug].'"';
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
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $geo_mashup_opts['google_key'];?>" 
						type="text/javascript"></script>
		<?php if (is_readable('custom.js')): ?>
		<script src="<?php echo $packed; ?>custom.js" type="text/javascript"></script>
		<?php endif; ?>
		<script src="<?php echo $packed; ?>geo-mashup.js" type="text/javascript"></script>
		<?php if (is_readable('map-style.css')): ?>
		<link rel="stylesheet" type="text/css" href="map-style.css" />
		<?php endif; ?>
		<style type="text/css">
			v\:* { behavior:url(#default#VML); }
			#geoMashup {
				<?php if ($width): ?> 
				width:<?php echo $width; ?>px;
				<?php endif; ?>
				<?php if ($height): ?> 
				height:<?php echo $height; ?>px;
				<?php endif; ?>
			}
		</style>
	</head>
	<body>
	<div id="geoMashup"></div>
	</body>	
	<script type="text/javascript">
		//<![CDATA[
		GeoMashup.createMap(document.getElementById('geoMashup'), { <?php echo GeoMashup::implode_assoc(':',',',$map_opts); ?> });	
		//]]>
	</script>
</html>
