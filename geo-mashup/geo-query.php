<?php

require_once('../../../wp-blog-header.php');

status_header(200);

$post_id =$_GET['post_id'];
if (is_numeric($post_id)) { 
	GeoMashupQuery::query_post($post_id);
} else {
	GeoMashupQuery::query_locations();
}

/**
 * GeoMashupQuery - static class provides namespace 
 */
class GeoMashupQuery {

	function trim_html($html, $length) {
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

	function strip_geo_mashup_shortcodes($content) {
		return preg_replace('/\[geo_mashup.*?\]/','',$content);
	}

	function excerpt_html($content) {
		global $geo_mashup_options;

		// Geo Mashup shortcodes in excerpts can cause an infinite recursion of frames - remove them
		$content = GeoMashupQuery::strip_geo_mashup_shortcodes($content);
		$content = apply_filters('the_content', $content);
		$content = GeoMashupQuery::trim_html($content,$geo_mashup_options->get('global_map', 'excerpt_length'));
		$content = balanceTags($content, true);
		$content = htmlspecialchars($content);
		return $content;
	}

	function excerpt_text($content) {
		global $geo_mashup_options;

		$content = strip_tags($content);
		$content = substr($content,0,$geo_mashup_options->get('global_map', 'excerpt_length'));
		$content = htmlspecialchars($content);
		return $content;
	}

	function query_post($post_id) {
		global $geo_mashup_options;

		header('Content-type: text/xml; charset='.get_settings('blog_charset'), true);
		header('Cache-Control: no-cache;', true);
		header('Expires: -1;', true);

		echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'."\n";

		echo '<channel><title>GeoMashup Query</title><item>';
		$post = get_post( $post_id );
		if (!$post) {
			echo '<title>Post'.$post_id.'not found</title>';
		} else {
			$categories = get_the_category( $post_id );
			foreach ($categories as $category) {
				echo '<category>'.$category->name.'</category>';
			}
			$author = get_author_name( $post->post_author );
			if ($geo_mashup_options->get('global_map', 'excerpt_format')=='html') {
				$excerpt = GeoMashupQuery::excerpt_html($post->post_content);
			} else {
				$excerpt = GeoMashupQuery::excerpt_text($post->post_content);
			}
			echo '<author>'.htmlspecialchars($author).'</author>'.
				'<pubDate>'.$post->post_date.'</pubDate>'.
				'<title>'.htmlspecialchars($post->post_title).'</title>'.
				'<link>'.get_permalink($post_id).'</link>'.
				'<description>'.$excerpt.'</description>';
		}
		echo '</item></channel>';
	}

	function query_locations() {
		header('Content-type: text/plain; charset='.get_settings('blog_charset'), true);
		header('Cache-Control: no-cache;', true);
		header('Expires: -1;', true);

		echo GeoMashup::get_post_locations_json($_GET);
	}
}
?>
