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

if ($minlat) {
	$query_string .= " AND substring_index(meta_value,',',1)>$minlat";
}
if ($minlon) {
	$query_string .= " AND substring_index(meta_value,',',-1)>$minlon";
}
if ($maxlat) {
	$query_string .= " AND substring_index(meta_value,',',1)<$maxlat";
}
if ($maxlon) {
	$query_string .= " AND substring_index(meta_value,',',-1)<$maxlon";
}

$query_string .= " ORDER BY meta_id DESC";

if (!($minlat && $maxlat && $minlon && $maxlon)) {
	// limit is not geographic, so limit number of results
	$query_string .= " LIMIT 0,10";
}

//echo $query_string."\n";
$wpdb->query($query_string);

if ($wpdb->last_result) {
	foreach ($wpdb->last_result as $row) {
		list($lat,$lon) = split(',',$row->meta_value);
		echo '<marker post_id="'.$row->post_id.'" lat="'.$lat.'" lon="'.$lon."\" />\n";
	}
}
echo "</markers>\n";

?>
