<?php
/**
 * Respond to Geo Mashup AJAX requests for object locations.
 *
 * @since 1.0
 * @package GeoMashup
 */

if ( ( isset( $_GET['output'] ) and 'json' == $_GET['output'] ) or empty( $_GET['object_ids'] ) ) {
	GeoMashupQuery::generate_location_json( );
} else {
	GeoMashupQuery::generate_object_html( );
}

/**
 * Class for query handling namespace
 *
 * @since 1.2
 * @package GeoMashup
 * @access public
 * @static
 */
class GeoMashupQuery {

	/**
	 * Strip content in square brackets.
	 *
	 * Shortcodes are not registered in the bare-bones query environments, 
	 * but we can strip all bracketed content.
	 *
	 * @since 1.3
	 *
	 * @param string $content Content to strip square brackets from
	 * @return string Content minus square brackets
	 */
	public static function strip_brackets( $content ) {
		return preg_replace( '/\[.*?\]/', '', $content );
	}

	/**
	 * Strip map shortcodes.
	 * 
	 * @since 1.3
	 *
	 * @param string $content 
	 * @return string Content without map shortcodes.
	 */
	public static function strip_map_shortcodes( $content ) {
		return preg_replace( '/\[geo_mashup_map.*?\]/', '', $content );
	}

	/**
	 * Use templates to output content for objects.
	 * 
	 * @since 1.3
	 */
	public static function generate_object_html( ) {
		global $geo_mashup_options, $geo_mashup_custom, $comments, $users;

		$object_ids = $_GET['object_ids'];
		if ( !is_array( $object_ids ) ) {
			$object_ids = split( ',', $object_ids );
		}
		$object_name = ( isset( $_GET['object_name'] ) ) ? $_GET['object_name'] : 'post';
		$template_base = ( isset( $_GET['template'] ) ) ? $_GET['template'] : '';

		switch ( $object_name ) {
			case 'post':
				$query_vars = array( 
					'post__in' => $object_ids, 
					'post_type' => GeoMashup::get_searchable_post_types(), 
					'post_status' => 'publish,future' 
				);
				// Don't filter this query through other plugins (e.g. event-calendar)
				$query_vars['suppress_filters'] = true;
				// No sticky posts please
				if ( function_exists( 'get_queried_object' ) )
					$query_vars['ignore_sticky_posts'] = true;
				else
					$query_vars['caller_get_posts'] = true; // Necessary only for WP 3.0 support
				// Don't limit the number of results
				$query_vars['posts_per_page'] = -1;

				query_posts( $query_vars );
				
				if ( have_posts() ) {
					status_header(200);
				}
				$template_base = ( empty( $template_base ) ) ? 'info-window' : $template_base;
				break;

			case 'comment':
				$comments = GeoMashupDB::get_comment_in( array( 'comment__in' => $object_ids ) );
				if ( !empty( $comments ) ) {
					status_header(200);
				}
				$template_base = ( empty( $template_base ) ) ? 'comment' : $template_base;
				break;

			case 'user':
				$users = GeoMashupDB::get_user_in( array( 'user__in' => $object_ids ) );
				if (!empty( $users ) ) {
					status_header(200);
				}
				$template_base = ( empty( $template_base ) ) ? 'user' : $template_base;
				break;
		}

		load_template( GeoMashup::locate_template( $template_base ) );
	}

	/** 
	 * Set the comment global. 
	 *
	 * Not sure why WP 2.7 comment templating requires this for callbacks, but it does.
	 *
	 * @since 1.3
	 *
	 * @param object $comment The comment object to make global.
	 */
	public static function set_the_comment( $comment ) {
		$GLOBALS['comment'] = $comment;
	}

	/** 
	 * Wrap access to comments global.
	 *
	 * @since 1.3
	 *
	 * @return bool Whether there are any comments to be listed.
	 */
	public static function have_comments( ) {
		global $comments;

		return ( !empty( $comments ) );
	}

	/**
	 * A wrapper for wp_list_comments when it exists,
	 * otherwise a simple comment loop.
	 *
	 * @since 1.3
	 * @see wp_list_comments()
	 *
	 * @param string|array $args Formatting options
	 */
	public static function list_comments( $args = '' ) {
		global $wp_query, $comments, $in_comment_loop;

		if ( function_exists( 'wp_list_comments' ) ) {
			wp_list_comments( $args, $comments );
		} else {
			if ( empty( $comments ) ) {
				return;
			}
			$args = wp_parse_args( $args );
			$in_comment_loop = true;
			foreach( $comments as $comment) {
				if ( !empty( $args['callback'] ) ) {
					call_user_func( $args['callback'], $comment, $args, 1 );
				} else {
					echo '<p>' . esc_html( $comment->comment_author ) . ':<br/>' . esc_html( $comment->comment_content ) . '</p>';
				}
			}
			$in_comment_loop = false;
		}
	}

	/** 
	 * Set the user global. 
	 *
	 * Probably only Geo Mashup using it here for a templated list of users.
	 *
	 * @since 1.3
	 *
	 * @param object $user The user object to make global.
	 */
	public static function set_the_user( $user ) {
		$GLOBALS['user'] = $user;
	}

	/** 
	 * Wrap access to users global. 
	 *
	 * Probably only Geo Mashup using it here for a templated list of users.
	 *
	 * @since 1.3
	 *
	 * @returns bool Whether there are any users to be listed.
	 */
	public static function have_users( ) {
		global $users;

		return ( !empty( $users ) );
	}

	/**
	 * A simple user loop that takes a callback option for formatting.
	 *
	 * @since 1.3
	 *
	 * @param string|array $args Formatting options
	 */
	public static function list_users( $args = '' ) {
		global $wp_query, $users, $in_user_loop;

		if ( empty( $users ) ) {
			return;
		}
		$defaults = array( 'callback' => '' );
		$args = wp_parse_args( $args, $defaults );
		$in_user_loop = true;
		foreach( $users as $user) {
			if ( !empty( $args['callback'] ) ) {
				call_user_func( $args['callback'], $user, $args );
			} else {
				echo '<p>' . esc_html( $user->display_name ) .
					( empty( $user->user_url ) ? '' : ' - ' . $user->url ) . '</p>';
			}
		}
		$in_user_loop = false;
	}


	/**
	 * Run a query for object locations from GET parameters and print JSON results.
	 * 
	 * @since 1.2
	 */
	public static function generate_location_json( ) {
		/* TODO: Try to track modification?
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
		header('Content-type: application/json; charset='.get_option('blog_charset'), true);
		header('Cache-Control: no-cache;', true);
		header('Expires: -1;', true);

		$json = GeoMashup::get_locations_json($_REQUEST);
		if ( isset( $_REQUEST['callback'] ) )
			$json = $_REQUEST['callback'] . '(' . $json . ')';
		echo $json;
	}
}
