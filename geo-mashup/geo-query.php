<?php

require('../../../wp-blog-header.php');

header('Content-type: text/xml; charset='.get_settings('blog_charset'), true);
header('Cache-Control: no-cache;', true);
header('Expires: -1;', true);

echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'."\n";

$opts = get_settings('geo_mashup_options');
$post_id =$_GET['post_id'];
if (is_numeric($post_id)) { 
	queryPost($post_id);
} else {
	queryLocations();
}

function trimHtml($html, $length) {
	$end_pos = 0;
	$text_len = 0;
	$tag_count = 0;
	while ($text_len<$length) {
		if ($html[$end_pos] == '<') $tag_count++;
		else if ($html[$end_pos] == '>') $tag_count--;
		$end_pos++;
		if ($tag_count == 0) $text_len++;
	}
	return substr($html,0,$end_pos);
}

function queryPost($post_id) {
	global $wpdb, $opts;
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
		if ($opts['excerpt_format']=='html') {
			$excerpt = htmlspecialchars(balanceTags(trimHtml(apply_filters('the_content',$post->post_content),$opts['excerpt_length'])));
		} else {
			$excerpt = htmlspecialchars(substr(strip_tags($post->post_content),0,$opts['excerpt_length']));
		}
		echo '<author>'.htmlspecialchars($author).'</author>'.
			'<pubDate>'.$post->post_date.'</pubDate>'.
			'<title>'.htmlspecialchars($post->post_title).'</title>'.
			'<link>'.get_permalink($post_id).'</link>'.
			'<description>'.$excerpt.'</description>';
	}
	echo '</item></channel>';
}

function queryLocations() {
	global $wpdb, $opts;
	echo "<markers>\n";

	// Construct the query 
	$fields = 'ID, meta_value';
	$tables = $wpdb->postmeta.
		' INNER JOIN '. $wpdb->posts.
		' ON ' . $wpdb->postmeta .' .post_id = ' . $wpdb->posts .'.ID';
	$where = 'meta_key=\'_geo_location\''.
		' AND post_status=\'publish\''.
		' AND length(meta_value)>1';

	if ($opts['show_future'] != 'true') {
		$where .= ' AND post_date<NOW()';
	}

	$minlat = $_GET['minlat'];
	if (is_numeric($minlat)) {
		$minlat = mysql_real_escape_string($minlat);
	}
	$minlon = $_GET['minlon'];
	if (is_numeric($minlon)) {
		$minlon = mysql_real_escape_string($minlon);
	}
	$maxlat = $_GET['maxlat'];
	if (is_numeric($maxlat)) {
		$maxlat = mysql_real_escape_string($maxlat);
	}
	$maxlon = $_GET['maxlon'];
	if (is_numeric($maxlon)) {
		$maxlon = mysql_real_escape_string($maxlon);
	}
	// Ignore nonsense bounds
	if ($minlat && $maxlat && $minlat>$maxlat) {
		$minlat = $maxlat = 0;
	}
	if ($minlon && $maxlon && $minlon>$maxlon) {
		$minlon = $maxlon = 0;
	}
	// Build bounding where clause
	if ($minlat) $where .= " AND substring_index(meta_value,',',1)>$minlat";
	if ($minlon) $where .= " AND substring_index(meta_value,',',-1)>$minlon";
	if ($maxlat) $where .= " AND substring_index(meta_value,',',1)<$maxlat";
	if ($maxlon) $where .= " AND substring_index(meta_value,',',-1)<$maxlon";

	$cat = $_GET['cat'];
	if (is_numeric($cat)) {
		$cat = mysql_real_escape_string($cat);
		$tables .= ' INNER JOIN '.$wpdb->post2cat.
			' ON '.$wpdb->posts.'.ID = '.$wpdb->post2cat.'.post_id';
		$where .= " AND category_id=$cat";
	}

	$query_string .= "SELECT $fields FROM $tables WHERE $where ORDER BY ID DESC";

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
			echo '<marker post_id="'.$row->ID.'" lat="'.$lat.'" lon="'.$lon."\" />\n";
		}
	}
	echo "</markers>\n";
}
?>

