<?php

require_once('../../../wp-blog-header.php');

/**
 * Establish locate_template for WP 2.6.
 */
if ( !function_exists( 'locate_template' ) ) {
	function locate_template($template_names, $load = false) {
		if (!is_array($template_names))
			return '';

		$located = '';
		foreach($template_names as $template_name) {
			if ( file_exists(STYLESHEETPATH . '/' . $template_name)) {
				$located = STYLESHEETPATH . '/' . $template_name;
				break;
			} else if ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {
				$located = TEMPLATEPATH . '/' . $template_name;
				break;
			}
		}

		if ($load && '' != $located)
			load_template($located);

		return $located;
	}
}

if ( empty( $_GET['post_ids'] ) ) {
	GeoMashupQuery::generate_location_json( );
} else {
	GeoMashupQuery::generate_post_html( );
}

/**
 * GeoMashupQuery - static class provides namespace 
 */
class GeoMashupQuery {

	function generate_post_html( ) {
		global $geo_mashup_options, $geo_mashup_custom;

		$post_ids = $_GET['post_ids'];
		if ( !is_array( $post_ids ) ) {
			$post_ids = split( ',', $post_ids );
		}

		$query_vars = array( 'post__in' => $post_ids, 'post_type' => 'any', 'post_status' => 'publish,future' );
		// Don't filter this query through other plugins (e.g. event-calendar)
		$query_vars['suppress_filters'] = true;
		// No sticky posts please
		$query_vars['caller_get_posts'] = true;

		query_posts( $query_vars );
		
		if ( have_posts() ) {
			status_header(200);
		}

		if ( empty( $_GET['template'] ) ) {
			$template_base = 'info-window';
		} else {
			$template_base = $_GET['template'];
		}

		$template = locate_template( array("geo-mashup-$template_base.php") );
		if ( empty( $template ) && isset( $geo_mashup_custom ) && $geo_mashup_custom->file_url( $template_base . '.php' ) ) {
			$template = trailingslashit( $geo_mashup_custom->dir_path ) . $template_base . '.php';
		}
		if ( !is_readable( $template ) ) {
			$template = $template_base . '-default.php';
		}
		if ( !is_readable( $template ) ) {
			$template = 'info-window-default.php';
		}
		load_template( $template );
	}

	function generate_location_json( ) {
		/*
		if ( !empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			$http_time = strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
			$mod_time = strtotime( $post->post_modified_gmt . ' GMT' );
			if ($mod_time <= $http_time) {
				return status_header(304); // Not modified
			}
		}

		status_header(200);
		header( 'Last-Modified: ' . mysql2date( 'D, d M Y H:i:s', $post->post_modified_gmt, false ) . ' GMT' );
		header( 'Content-type: text/xml; charset='.get_settings('blog_charset'), true);
		header( 'Cache-control: max-age=300, must-revalidate', true);
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 300 ) . " GMT" );
		header( 'Pragma:' );
		 */
		status_header(200);
		header('Content-type: text/plain; charset='.get_settings('blog_charset'), true);
		header('Cache-Control: no-cache;', true);
		header('Expires: -1;', true);

		echo GeoMashup::get_post_locations_json($_GET);
	}
}
?>
