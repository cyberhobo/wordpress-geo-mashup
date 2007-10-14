<?php

require('../../../wp-blog-header.php');

status_header(200);

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
	header('Content-type: text/xml; charset='.get_settings('blog_charset'), true);
	header('Cache-Control: no-cache;', true);
	header('Expires: -1;', true);

	echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'."\n";

	echo '<channel><title>GeoMashup Query</title><item>';
	$post = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID=$post_id");
	if (!$post) {
		echo '<title>Post'.$post_id.'not found</title>';
	} else {
		$cat_query = "SELECT name 
			FROM {$wpdb->terms} t
			JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id=$post_id
			AND		tt.taxonomy='category'";
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
	header('Content-type: text/plain; charset='.get_settings('blog_charset'), true);
	header('Cache-Control: no-cache;', true);
	header('Expires: -1;', true);

	echo '{ posts : [';

	// Construct the query 
	$fields = 'ID, post_title, meta_value';
	$tables = "$wpdb->postmeta pm
		INNER JOIN $wpdb->posts p
		ON pm.post_id = p.ID";
	$where = 'meta_key=\'_geo_location\''.
		' AND post_status=\'publish\''.
		' AND length(meta_value)>1';

	if ($opts['show_future'] != 'true') {
		$where .= ' AND post_date_gmt<DATE_ADD(\'1970-01-01\', INTERVAL UNIX_TIMESTAMP() SECOND )';
	}

	$minlat = $_GET['minlat'];
	if (is_numeric($minlat)) {
		$minlat = $wpdb->escape($minlat);
	}
	$minlon = $_GET['minlon'];
	if (is_numeric($minlon)) {
		$minlon = $wpdb->escape($minlon);
	}
	$maxlat = $_GET['maxlat'];
	if (is_numeric($maxlat)) {
		$maxlat = $wpdb->escape($maxlat);
	}
	$maxlon = $_GET['maxlon'];
	if (is_numeric($maxlon)) {
		$maxlon = $wpdb->escape($maxlon);
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
	$tables .= " JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID 
		JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
			AND tt.taxonomy='category'";
	if (is_numeric($cat)) {
		$cat = $wpdb->escape($cat);
		$where .= " AND tt.term_id=$cat";
	} 

	$query_string = "SELECT $fields FROM $tables WHERE $where ORDER BY post_date DESC";

	$all = 'true';
	$limit = $_GET['limit'];
	if (!($minlat && $maxlat && $minlon && $maxlon) && !$limit) {
		// result should contain all posts (possibly for a category)
		$all = 'false';
	} else if (is_numeric($limit) && $limit>0) {
		$limit = $wpdb->escape($limit);
		$query_string .= " LIMIT 0,$limit";
	}

	$wpdb->query($query_string);

	if ($wpdb->last_result) {
		$comma = '';
		$posts = $wpdb->last_result; 
		foreach ($posts as $post) {
			list($lat,$lng) = split(',',$post->meta_value);
			echo 	$comma.'{"post_id":"'.$post->ID.'","title":"'.addslashes($post->post_title).
				'","lat":"'.$lat.'","lng":"'.$lng.'","categories":[';
			$categories_sql = "SELECT name 
				FROM {$wpdb->term_relationships} tr
				JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
				JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				WHERE tt.taxonomy='category' 
				AND tr.object_id = {$post->ID}";
			$categories = $wpdb->get_col($categories_sql);
			$categories_comma = '';
			foreach ($categories as $category) {
				echo $categories_comma.'"'.addslashes($category).'"';
				$categories_comma = ',';
			}
			echo ']}';
			$comma = ',';
		}
	}
	echo ']}';
}
?>

