<?php

require('../../../wp-blog-header.php');

header("Content-type: text/xml; charset=".get_settings('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'."\n";

$post_id =$_GET['post_id'];
if (is_numeric($post_id)) { 
	queryPost($post_id);
} else {
	queryLocations();
}

function queryPost($post_id) {
	global $wpdb;
	echo '<channel><title>GeoMashup Query</title><item>';
	$post = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID=$post_id");
	if (!$post) {
		echo '<title>Post'.$post_id.'not found</title>';
	} else {
		$cat_query = "SELECT cat_name FROM {$wpdb->post2cat},{$wpdb->categories} ".
			"WHERE category_id=cat_ID AND post_id=$post_id";
		$categories = $wpdb->get_col($cat_query);
		foreach ($categories as $category) {
			echo '<category>'.$category.'</category>';
		}
		$author = $wpdb->get_var("SELECT display_name FROM {$wpdb->users} WHERE ID={$post->post_author}");
		echo '<author>'.$author.'</author>'.
			'<pubDate>'.$post->post_date.'</pubDate>'.
			'<title>'.$post->post_title.'</title>'.
			'<link>'.$post->guid.'</link>'.
			'<description>'.htmlspecialchars(substr($post->post_content,0,255)).'</description>';
	}
	echo '</item></channel>';
}

function queryLocations() {
	global $wpdb;
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
}
?>

