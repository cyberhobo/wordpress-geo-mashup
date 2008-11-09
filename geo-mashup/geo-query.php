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
	header('Content-type: text/plain; charset='.get_settings('blog_charset'), true);
	header('Cache-Control: no-cache;', true);
	header('Expires: -1;', true);

	echo GeoMashup::getLocationsJson($_GET);
}
?>
