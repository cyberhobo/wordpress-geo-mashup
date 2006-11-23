<?php

require('../../../wp-blog-header.php');

header("Content-type: text/xml; charset=".get_settings('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'."\n";
echo "<markers>\n";

// Construct the query string.
$query_string = 'SELECT post_id, meta_value'.
	' FROM '.$wpdb->postmeta.
	' WHERE meta_key=\'_geo_location\''.
	' AND length(meta_value)>1';

$minlat = $_GET['minlat'];
if (is_numeric($minlat)) {
	$minlat = mysql_real_escape_string($minlat);
	$query_string .= " AND substring_index(meta_value,',',1)>$minlat";
}
$minlon = $_GET['minlon'];
if (is_numeric($minlon)) {
	$minlon = mysql_real_escape_string($minlon);
	$query_string .= " AND substring_index(meta_value,',',-1)>$minlon";
}
$maxlat = $_GET['maxlat'];
if (is_numeric($maxlat)) {
	$maxlat = mysql_real_escape_string($maxlat);
	$query_string .= " AND substring_index(meta_value,',',1)<$maxlat";
}
$maxlon = $_GET['maxlon'];
if (is_numeric($maxlon)) {
	$maxlon = mysql_real_escape_string($maxlon);
	$query_string .= " AND substring_index(meta_value,',',-1)<$maxlon";
}
$category = $_GET['category'];
if ($category) {
	$category = mysql_real_escape_string($category);
	$query_string .= " AND category_nicename='$category'";
}

$query_string .= " ORDER BY post_id DESC";

$limit = $_GET['limit'];
if (!($minlat && $maxlat && $minlon && $maxlon) && !$limit && !$category) {
	// limit is not geographic, so limit number of results
	$query_string .= " LIMIT 0,10";
} else if (is_numeric($limit) && $limit>0) {
	$limit = mysql_real_escape_string($limit);
	$query_string .= " LIMIT 0,$limit";
}

$wpdb->query($query_string);

if ($wpdb->last_result) {
	foreach ($wpdb->last_result as $row) {
		list($lat,$lon) = split(',',$row->meta_value);
		echo '<marker post_id="'.$row->post_id.'" lat="'.$lat.'" lon="'.$lon."\" />\n";
	}
}
echo "</markers>\n";

?>

