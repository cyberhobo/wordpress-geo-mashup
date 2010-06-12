<?php 
/*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Save location for posts and pages, or even users and comments. Display these locations on Google maps. Make WordPress into your GeoCMS.
Version: 1.4alpha4
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 2.8
*/

/*  Copyright 2010  Dylan Kuhn  (email : cyberhobo@cyberhobo.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The main Geo Mashup plugin file loaded by WordPress.
 *
 * @package GeoMashup
 */

if ( !class_exists( 'GeoMashup' ) ) {
/**
 * The Geo Mashup static class.
 *
 * Used primarily for namespace, with methods called using the scope operator,
 * like echo GeoMashup::map();
 *
 * @package GeoMashup
 * @since 1.0
 * @access public
 * @static
 */
class GeoMashup {

	/**
	 * Load Geo Mashup.
	 * 
	 * Initializations that can be done before init(). 
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load() {
		GeoMashup::load_constants();
		load_plugin_textdomain('GeoMashup', 'wp-content/plugins/'.GEO_MASHUP_DIRECTORY, GEO_MASHUP_DIRECTORY);

		GeoMashup::load_dependencies();
		GeoMashup::load_hooks();
	}

	/**
	 * init {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action},
	 * called by WordPress.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function init() {
		GeoMashup::load_styles();
		GeoMashup::load_scripts();
	}

	/**
	 * Load relevant dependencies.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_dependencies() {
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-db.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-options.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-ui-managers.php' );
		if ( !is_admin() ) {
			include_once( GEO_MASHUP_DIR_PATH . '/shortcodes.php');
		}
	}

	/**
	 * Load relevant hooks.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_hooks() {
		global $geo_mashup_options;

		if (is_admin()) {

			// To upgrade tables
			register_activation_hook( __FILE__, array( 'GeoMashupDB', 'install' ) );

			// To add Geo Mashup settings page
			add_action('admin_menu', array('GeoMashup', 'admin_menu'));

			// To make important announcements
			add_action( 'admin_notices', array( 'GeoMashup', 'admin_notices' ) );

			// To add plugin listing links
			add_filter( 'plugin_action_links', array( 'GeoMashup', 'plugin_action_links' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( 'GeoMashup', 'plugin_row_meta' ), 10, 2 );

		} else {

			// This is a non-admin request

			if ($geo_mashup_options->get('overall','add_category_links') == 'true') {
				// To add map links to a category list - flaky, requires non-empty category description
				add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
			}

			// To output location meta tags in the page head
			add_action('wp_head', array('GeoMashup', 'wp_head'));

			// To allow shortcodes in the text widget
			if ( ! has_filter( 'widget_text', 'do_shortcode' ) ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}

			// To add the GeoRSS namespace to RSS feeds
			add_action('rss_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
			add_action('atom_ns', array('GeoMashup', 'rss_ns'));

			// To add GeoRSS location to RSS feeds
			add_action('rss_item', array('GeoMashup', 'rss_item'));
			add_action('rss2_item', array('GeoMashup', 'rss_item'));
			add_action('atom_entry', array('GeoMashup', 'rss_item'));

			// To add custom renderings
			add_filter( 'query_vars', array( 'GeoMashup', 'query_vars' ) );
			add_action( 'template_redirect', array( 'GeoMashup', 'template_redirect' ) );

		}
	}

	/**
	 * Define Geo Mashup constants. 
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_constants() {
		define('GEO_MASHUP_PLUGIN_NAME', plugin_basename(__FILE__));
		define('GEO_MASHUP_DIR_PATH', dirname( __FILE__ ));
		define('GEO_MASHUP_DIRECTORY', substr(GEO_MASHUP_PLUGIN_NAME, 0, strpos(GEO_MASHUP_PLUGIN_NAME, '/')));
		if ( defined( 'WPMU_PLUGIN_URL' ) &&  strpos( __FILE__, substr( MUPLUGINDIR, strpos( MUPLUGINDIR, '/' ) ) ) !== false ) {
			// We are in an mu-plugins directory
			define('GEO_MASHUP_URL_PATH', WPMU_PLUGIN_URL . '/' . GEO_MASHUP_DIRECTORY);
		} else {
			// We're in the usual plugin directory
			define('GEO_MASHUP_URL_PATH', WP_PLUGIN_URL . '/' . GEO_MASHUP_DIRECTORY);
		}
		define('GEO_MASHUP_MAX_ZOOM', 20);
		// Make numeric versions: -.02 for alpha, -.01 for beta
		define('GEO_MASHUP_VERSION', '1.3.98.4');
		define('GEO_MASHUP_DB_VERSION', '1.3');
	}

	/**
	 * Load relevant scripts.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_scripts() {
		global $geo_mashup_options;

		// Register scripts that other plugins might use
		wp_register_script( 'google-jsapi', 'http://www.google.com/jsapi?key='.$geo_mashup_options->get('overall', 'google_key') );

		if (is_admin()) {
			if ( isset($_GET['page']) &&  GEO_MASHUP_PLUGIN_NAME === $_GET['page'] ) {
				wp_enqueue_script( 'jquery-ui-tabs' );
			}
		} else {
			// The loader script is tiny and handles click-to-load maps that could be on any front end page
			wp_enqueue_script( 'geo-mashup-loader', GEO_MASHUP_URL_PATH.'/geo-mashup-loader.js', array( 'google-jsapi' ), GEO_MASHUP_VERSION);
		}
	}

	/**
	 * Load relevant styles.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_styles() {
		if (is_admin()) {
			if ( isset($_GET['page']) && GEO_MASHUP_PLUGIN_NAME === $_GET['page'] ) {
				$tabs_css = trailingslashit( GEO_MASHUP_URL_PATH ) . 'jquery-ui.1.7.smoothness.css';
				wp_enqueue_style( 'jquery-smoothness', $tabs_css, false, GEO_MASHUP_VERSION, 'screen' );
			}
		}
	}

	/**
	 * Add Geo Mashup query variables.
	 *
	 * query_vars {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function query_vars( $public_query_vars ) {
		$public_query_vars[] = 'geo_mashup_content';
		return $public_query_vars;
	}

	/**
	 * Deliver templated Geo Mashup content and AJAX responses.
	 *
	 * template_redirect {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @uses ajax_edit
	 * @uses geo_query
	 * @uses render_map
	 */
	function template_redirect() {
		$geo_mashup_content = get_query_var( 'geo_mashup_content' );
		if ( ! empty( $geo_mashup_content ) ) {

			// The parameter's purpose is to get us here, we can remove it now
			unset( $_GET['geo_mashup_content'] );

			// Call the function corresponding to the content request
			// This provides some security, as only implemented methods will be executed
			$method = str_replace( '-', '_', $geo_mashup_content );
			call_user_func( array( 'GeoMashup', $method ) );
			exit();
		}
	}

	/**
	 * Process an AJAX geo query.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 * @uses geo-query.php
	 */
	function geo_query() {
		require_once( 'geo-query.php' );
	}

	/**
	 * Process an iframe map request.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 * @uses render-map.php
	 */
	function render_map() {
		require_once( 'render-map.php' );
	}

	/**
	 * Perform an ajax edit operation and echo results.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function ajax_edit() {
		check_ajax_referer( 'geo-mashup-ajax-edit', '_wpnonce' );
		unset( $_GET['_wpnonce'] );

		$status = array( 'request' => 'ajax-edit', 'code' => 200 );
		if ( isset( $_POST['geo_mashup_object_id'] ) ) {
			$status['object_id'] = $_POST['geo_mashup_object_id'];
		} else {
			$status['code'] = 400;
			$status['message'] = __( 'No object id posted.', 'GeoMashup' );
			$status['object_id'] = '?';
		}

		/** @todo add an option for a user capability check here? */

		if ( 200 == $status['code'] and ! empty( $_POST['geo_mashup_ui_manager'] ) ) {
			$ui_manager = GeoMashupUIManager::get_instance( $_POST['geo_mashup_ui_manager'] );
			$result = $ui_manager->save_posted_object_location( $status['object_id'] );
			if ( is_wp_error( $result ) ) {
				$status['code'] = 500;
				$status['message'] = $result->get_error_message();
			}
		}

		if ( 200 == $status['code'] ) {
			if ( ! empty( $_REQUEST['geo_mashup_update_location'] ) ) {
				$status['message'] = __( 'Location updated.', 'GeoMashup' );
			} else if ( ! empty( $_REQUEST['geo_mashup_delete_location'] ) ) {
				$status['message'] = __( 'Location deleted.', 'GeoMashup' );
			} else if ( ! empty( $_REQUEST['geo_mashup_add_location'] ) ) {
				$status['message'] = __( 'Location added.', 'GeoMashup' );
			}
		} 

		echo GeoMashup::json_encode( array( 'status' => $status ) );
		exit();
	}

	/**
	 * Toggle limiting of query_posts to located posts only, 
	 * with Geo Mashup query extensions.
	 *
	 * When enabled, only posts with locations will be returned from
	 * WordPress query_posts() and related functions. Also adds Geo Mashup
	 * public query variables.
	 *
	 * Caution - what if a future Geo Mashup incorporates multiple locations per object?
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param bool $yes_or_no Whether to activate the join or not.
	 */
	function join_post_queries( $yes_or_no ) {
		GeoMashupDB::join_post_queries( $yes_or_no );
	}

	/**
	 * Helper to turn a string of key-value pairs into an associative array.
	 *
	 * @since 1.0
	 * @access public
	 * @static
	 *
	 * @param string $glue1 Pair separator.
	 * @param string $glue2 Key/value separator.
	 * @param string $str String to explode. 
	 * @return array The associative array.
	 */
	function explode_assoc($glue1, $glue2, $str) {
		$array2=explode($glue2, $str);
		foreach($array2 as  $val) {
			$pos=strpos($val,$glue1);
			$key=substr($val,0,$pos);
			$array3[$key] =substr($val,$pos+1,strlen($val));
		}
		return $array3;
	}

	/**
	 * Helper to turn an associative array into a string of key-value pairs.
	 * 
	 * @since 1.0
	 * @access public
	 * @static
	 *
	 * @param string $inner_glue Key/value separator.
	 * @param string $outer_glue Pair separator.
	 * @param array $array Array to implode.
	 * @param mixed $skip_empty Whether to include empty values in output.
	 * @param mixed $urlencoded Whetern to URL encode the output.
	 * @return string The imploded string.
	 */
	function implode_assoc($inner_glue, $outer_glue, $array, $skip_empty=false, $urlencoded=false) {
	$output = array();
		foreach($array as $key=>$item) {
			if (!$skip_empty || isset($item)) {
				if ($urlencoded)
					$output[] = preg_replace('/\s/', ' ', $key.$inner_glue.urlencode($item));
				else
					$output[] = preg_replace('/\s/', ' ', $key.$inner_glue.$item);
			}
		}
		return implode($outer_glue, $output);
	}

	/**
	 * Encode an item as a JSON string.
	 *
	 * I believe WP 2.9 will include a function like this.
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param scalar|array Scalars are quoted, number-indexed arrays become JSON arrays, string-indexed arrays become JSON objects.
	 * @return string JSON encoded string, empty if not encodable.
	 */
	function json_encode( $item ) {
		$json = '';
		if ( is_scalar( $item ) ) {

			$json =  '"' . esc_js( trim( (string) $item, '"' ) ) . '"';

		} else if ( is_array( $item ) ) {

			$keys = array_keys( $item );
			if ( ! empty( $keys ) && is_string( $keys[0] ) ) {

				// String indexes to JSON object
				$json_properties = array();
				foreach( $item as $name => $value ) {
					$json_properties[] = '"' . $name . '":' . GeoMashup::json_encode( $value );
				}
				$json = '{' . implode( ',', $json_properties ) . '}';

			} else {

				// Numeric indexes to JSON array
				$json = '[' . implode( ',', array_map( array( 'GeoMashup', 'json_encode' ), $item ) ) . ']';

			}
		}
		return $json;
	}

	/**
	 * Get an array of URLs of KML or KMZ attachments for a post.
	 * 
	 * @since 1.1
	 * @access public
	 * @static
	 *
	 * @param id $post_id 
	 * @return array Array of URL strings.
	 */
	function get_kml_attachment_urls($post_id) {
		if ( empty( $post_id ) ) {
			return array();
		}
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => null,
			'post_status' => null,
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$urls = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$attachment_url = $attachment->guid;
				$dot_pos = stripos( $attachment_url, '.kml');
				if ( $dot_pos === false ) {
					$dot_pos = stripos( $attachment_url, '.kmz');
				}
				if ( $dot_pos == strlen( $attachment_url ) - 4) {
					array_push($urls,$attachment_url);
				}
			}
		}
		return $urls;
	}

	/**
	 * Add relevant geo meta tags to the document head.
	 * 
	 * wp_head {@link http://codex.wordpress.org/Plugin_API/Action_Reference#TemplateActions action},
	 * called by WordPress.
	 * 
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function wp_head() {
		global $wp_query;

		if (is_single())
		{
			$loc = GeoMashupDB::get_object_location( 'post', $wp_query->post->ID );
			if (!empty($loc)) {
				$title = esc_html(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo '<meta name="ICBM" content="' . attribute_escape( $loc->lat . ', ' . $loc->lng ) . '" />' . "\n";
				echo '<meta name="DC.title" content="' . attribute_escape( $title ) . '" />' . "\n";
				echo '<meta name="geo.position" content="' .  attribute_escape( $loc->lat . ';' . $loc->lng ) . '" />' . "\n";
			}
		}
		else
		{
			$saved_locations = GeoMashupDB::get_saved_locations( );
			if ( !empty( $saved_locations ) )
			{
				foreach ( $saved_locations as $saved_location ) {
					if ( $saved_location->saved_name == 'default' ) {
						$title = esc_html(convert_chars(strip_tags(get_bloginfo('name'))));
						echo '<meta name="ICBM" content="' . attribute_escape( $saved_location->lat . ', ' . $saved_location->lon ) . '\" />'. "\n";
						echo '<meta name="DC.title" content="' . attribute_escape( $title ) . '" />' . "\n";
						echo '<meta name="geo.position" content="' . attribute_escape( $saved_location->lat . ';' . $saved_location->lon ) . '\" />' . "\n";
					}
				}
			}
		}
	}

	/**
	 * Query object locations and return JSON.
	 *
	 * Offers customization per object location via a filter, geo_mashup_locations_json_object.
	 * 
	 * @since 1.2
	 * @access public
	 * @static
	 * @uses GeoMashupDB::get_object_locations()
	 * @filter geo_mashup_locations_json_object Filter each location associative array before conversion to JSON.
	 *
	 * @param string|array $query_args Query variables for GeoMashupDB::get_object_locations().
	 * @return string Queried object locations JSON ( { "object" : [...] } ).
	 */
	function get_locations_json( $query_args ) {
		$default_args = array( 'object_name' => 'post' );
		$query_args = wp_parse_args( $query_args, $default_args );
		$json_objects = array();
		$objects = GeoMashupDB::get_object_locations( $query_args );
		if ( $objects ) {
			foreach ($objects as $object) {
				$category_ids = array();
				$author_name = '';
				$attachments = array();
				if ( 'post' == $query_args['object_name'] ) {

					// Only know about post categories now, but could abstract to objects
					$categories = get_the_category( $object->object_id );
					foreach ($categories as $category) {
						$category_ids[] = $category->cat_ID;
					}

					// Only posts have KML attachments
					$attachments = GeoMashup::get_kml_attachment_urls( $object->object_id );

					// Include post author
					$author = get_userdata( $object->post_author );
					$author_name = $author->display_name;
				}

				$json_object = array(
					'object_name' => $query_args['object_name'],
					'object_id' => $object->object_id,
					// We should be able to use real UTF-8 characters in titles
					// Helps with the spelling-out of entities in tooltips
					'title' => html_entity_decode( $object->label, ENT_COMPAT, 'UTF-8' ),
					'lat' => $object->lat,
					'lng' => $object->lng,
					'author_name' => $author_name,
					'categories' => $category_ids,
					'attachment_urls' => $attachments
				);

				// Allow companion plugins to add data
				$json_object = apply_filters( 'geo_mashup_locations_json_object', $json_object, $object );

				$json_objects[] = $json_object;
			}
		}
		return GeoMashup::json_encode( array( 'objects' => $json_objects ) );
	}

	/**
	 * Convert depricated attribute names.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 * 
	 * @param array $atts Attributes to modify.
	 */
	function convert_map_attributes( &$atts ) {
		$attribute_conversions = array( 
			'auto_open_info_window' => 'auto_info_open',
			'open_post_id' => 'open_object_id'
		);
		foreach ( $attribute_conversions as $old_key => $new_key ) {
			if ( isset( $atts[$old_key] ) ) {
				if ( ! isset( $atts[$new_key] ) ) {
					$atts[$new_key] = $atts[$old_key];
				}
				unset( $atts[$old_key] );
			}
		}
	}

	/**
	 * The map template tag.
	 *
	 * Returns HTML for a Google map. Must use with echo in a template: echo GeoMashup::map();.
	 *
	 * @since 1.0
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Map
	 *
	 * @param string|array $atts Template tag parameters.
	 * @return string The HTML for the requested map.
	 */
	function map( $atts = null ) {
		global $wp_query, $in_comment_loop, $geo_mashup_options;
		static $map_number = 0;

		$map_number++;
		$url_params = array();
		$atts = wp_parse_args( $atts );

		GeoMashup::convert_map_attributes( $atts );

		// Default query is for posts
		$object_name = ( isset( $atts['object_name'] ) ) ? $atts['object_name'] : 'post';

		// Map content type isn't required, so resolve it
		$map_content = isset( $atts['map_content'] ) ? $atts['map_content'] : null;
		unset($atts['map_content']);

		if ( empty ( $map_content ) ) {
			if ( ( 'post' == $object_name && !$wp_query->in_the_loop ) || ( 'comment' == $object_name && !$in_comment_loop ) ) {
				$map_content = 'contextual';
			} else {
				if ( 'user' == $object_name ) {
					$context_object_id = $wp_query->post->post_author;
				} else if ( 'comment' == $object_name ) {
					$context_object_id = get_comment_ID();
				} else {
					$context_object_id = $wp_query->post->ID;
				}
				$location = GeoMashupDB::get_object_location( $object_name, $context_object_id );
				if ( empty( $location ) ) {
					// Not located, go global
					$map_content = 'global';
				} else {
					// Located, go single
					$map_content = 'single';
				}
			}
		}
		
		$single_option_keys = array ( 'width', 'height', 'zoom', 'background_color', 'click_to_load', 'click_to_load_text' );
		$global_option_keys = array_merge( $single_option_keys, array( 'show_future', 'marker_select_info_window', 'marker_select_center', 
			'marker_select_highlight', 'marker_select_attachments' ) );
		$contextual_option_keys = array_diff( $global_option_keys, array( 'show_future' ) );
		switch ($map_content) {
			case 'contextual':
				$url_params['map_content'] = 'contextual';
				$url_params += $geo_mashup_options->get ( 'context_map', $contextual_option_keys );
				$object_ids = array();
				if ( 'comment' == $object_name ) {
					$context_objects = $wp_query->comments;
				} else {
					$context_objects = $wp_query->posts;
				}
				if ( !is_array( $context_objects ) ) {
					return '<!-- ' . __( 'Geo Mashup found no objects to map in this context', 'GeoMashup' ) . '-->';
				}
				foreach ( $context_objects as $context_object ) {
					if ( 'post' == $object_name ) {
						$object_ids[] = $context_object->ID;
					} else if ( 'user' == $object_name ) {
						$object_ids[] = $context_object->post_author;
					} else if ( 'comment' == $object_name ) {
						$object_ids[] = $context_object->comment_ID;
					}
				}
				$url_params['object_ids'] = implode( ',', $object_ids );
				break;

			case 'single':
				$url_params['map_content'] = 'single';
				$url_params += $geo_mashup_options->get ( 'single_map', $single_option_keys );
				if ( 'post' == $object_name ) {
					$url_params['object_id'] = $wp_query->post->ID;
				} else if ( 'user' == $object_name ) {
					$url_params['object_id'] = $wp_query->post->post_author;
				} else if ( 'comment' == $object_name ) {
					$url_params['object_id'] = get_comment_ID();
					if ( empty( $url_params['object_id'] ) ) { 
						return '<!-- ' . __( 'Geo Mashup found no current object to map', 'GeoMashup' ) . '-->';
					}
					$location = GeoMashupDB::get_object_location( $object_name, $object_id ); 
					if ( empty( $location ) ) {
						return '<!-- ' . __( 'Geo Mashup ommitted a map for an object with no location', 'GeoMashup' ) . '-->';
					}
				}
				break;

			case 'global':
				$url_params['map_content'] = 'global';
				$url_params += $geo_mashup_options->get ( 'global_map', $global_option_keys );
				if (isset($_SERVER['QUERY_STRING'])) {
					$url_params = wp_parse_args($_SERVER['QUERY_STRING'],$url_params);
				} 
				// Un-savable options
				if (isset($atts['start_tab_category_id'])) {
					$url_params['start_tab_category_id'] = $atts['start_tab_category_id'];
				}
				if (isset($atts['tab_index_group_size'])) {
					$url_params['tab_index_group_size'] = $atts['tab_index_group_size'];
				}
				if (isset($atts['show_inactive_tab_markers'])) {
					$url_params['show_inactive_tab_markers'] = $atts['show_inactive_tab_markers'];
				}
				break;

			default:
				return '<div class="gm-map"><p>Unrecognized value for map_content: "'.$map_content.'".</p></div>';
		}
		$url_params = array_merge($url_params, $atts);
		
		$click_to_load = $url_params['click_to_load'];
		unset($url_params['click_to_load']);
		$click_to_load_text = $url_params['click_to_load_text'];
		unset($url_params['click_to_load_text']);
		$name = 'gm-map-' . $map_number;
		if (isset($url_params['name'])) {
			$name = $url_params['name'];
		}
		unset($url_params['name']);

		$map_image = '';
		if ( isset($url_params['static']) && 'true' === $url_params['static'] ) {
			// Static maps have a limit of 50 markers: http://code.google.com/apis/maps/documentation/staticmaps/#Markers
			$url_params['limit'] = empty( $url_params['limit'] ) ? 50 : $url_params['limit'];

			$locations = GeoMashupDB::get_object_locations( $url_params );
			if (!empty($locations)) {
				$map_image = '<img src="http://maps.google.com/maps/api/staticmap?size='.$url_params['width'].'x'.$url_params['height'];
				if (count($locations) == 1) {
					$map_image .= '&amp;center='.$locations[0]->lat . ',' . $locations[0]->lng;
				}
				$map_image .= '&amp;sensor=false&amp;zoom=' . $url_params['zoom'] . '&amp;markers=size:small|color:red';
				foreach ($locations as $location) {
					// TODO: Try to use the correct color for the category? Draw category lines?
					$map_image .= '|' . $location->lat . ',' . $location->lng;
				}
				$map_image .= '&amp;key='.$geo_mashup_options->get('overall', 'google_key').'" alt="geo_mashup_map"';
				if ($click_to_load == 'true') {
					$map_image .= '" title="'.$click_to_load_text.'"';
				}
				$map_image .= ' />';
			}
		}
					
		$iframe_src = get_bloginfo( 'url' ) . '?geo_mashup_content=render-map&amp;' . 
			GeoMashup::implode_assoc('=', '&amp;', $url_params, false, true);
		$content = "";

		if ($click_to_load == 'true') {
			if ( is_feed() ) {
				$content .= "<a href=\"{$iframe_src}\">$click_to_load_text</a>";
			} else {
				$style = "height:{$url_params['height']}px;width:{$url_params['width']}px;background-color:#ddd;".
					"background-image:url(".GEO_MASHUP_URL_PATH."/images/wp-gm-pale.png);".
					"background-repeat:no-repeat;background-position:center;cursor:pointer;";
				$content = "<div class=\"gm-map\" style=\"$style\" " .
					"onclick=\"GeoMashupLoader.addMapFrame(this,'$iframe_src','{$url_params['height']}','{$url_params['width']}','$name')\">";
				if ( isset($url_params['static']) &&  'true' === $url_params['static'] ) {
					// TODO: test whether click to load really works with a static map
					$content .= $map_image . '</div>';
				} else {
					$content .= "<p style=\"text-align:center;\">$click_to_load_text</p></div>";
				}
			}
		} else if ( isset($url_params['static']) &&  'true' === $url_params['static'] ) {
			$content = "<div class=\"gm-map\">$map_image</div>";
		} else {
			$content =  "<div class=\"gm-map\"><iframe name=\"{$name}\" src=\"{$iframe_src}\" " .
				"height=\"{$url_params['height']}\" width=\"{$url_params['width']}\" marginheight=\"0\" marginwidth=\"0\" ".
				"scrolling=\"no\" frameborder=\"0\"></iframe></div>";
		}
		return $content;
	}

	/**
	 * Full post template tag.
	 *
	 * Returns a placeholder where a related map should display the full post content 
	 * of the currently selected marker.
	 *
	 * @since 1.1
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Full_Post
	 * 
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	function full_post($args = null) {
		$args = wp_parse_args($args);
		$for_map = 'gm';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	

		return '<div id="' . $for_map . '-post"></div>';
	}

	/**
	 * Category name template tag.
	 *
	 * If there is a map_cat parameter, return the name of that category.
	 *
	 * @since 1.1
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Category_Name 
	 * 
	 * @param string|array $option_args Template tag arguments.
	 * @return string Category name.
	 */
	function category_name($option_args = null) {
		$category_name = '';
		if (is_string($option_args)) {
			$option_args = wp_parse_args($option_args);
		}
		if (is_page() && isset($_SERVER['QUERY_STRING'])) {
			$option_args = $option_args + GeoMashup::explode_assoc('=','&amp;',$_SERVER['QUERY_STRING']);
		}
		if (isset($option_args['map_cat'])) {
			$category_name = get_cat_name($option_args['map_cat']);
		}
		return $category_name;
	}

	/**
	 * Category legend template tag.
	 *
	 * Returns a placeholder where a related map should display a legend for the 
	 * categories of the displayed content.
	 *
	 * @since 1.1
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Category_Legend
	 * 
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	function category_legend($args = null) {
		$args = wp_parse_args($args);
		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		return '<div id="' . $for_map . '-legend"></div>';
	}

	/**
	 * If the option is set, add a map link to category lists.
	 *
	 * list_cats {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Category_Filters filter}
	 * called by WordPress.
	 *
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function list_cats($content, $category = null) {
		global $geo_mashup_options;

		if ($category) {
			$count = GeoMashupDB::category_located_post_count( $category->cat_ID );
			// Add map link only if there are geo-located posts to see
			if ($count) {
				// This feature doesn't work unless there is a category description
				if ( empty( $category->description ) ) {
					return $content . $geo_mashup_options->get('overall', 'category_link_separator') . 
						__( 'You must add a description to this category to use this Geo Mashup feature.', 'GeoMashup' );
				}
				$link = '';
				$url = get_page_link($geo_mashup_options->get('overall', 'mashup_page'));
				if (strstr($url,'?')) {
					$url .= '&amp;';
				} else {
					$url .= '?';
				}
				$link = '<a href="'.$url.'map_cat='.$category->cat_ID.'&amp;zoom='.$geo_mashup_options->get('overall', 'category_zoom').
					'" title="'.$geo_mashup_options->get('overall', 'category_link_text').'">';
				return $content.'</a>'.$geo_mashup_options->get('overall', 'category_link_separator').$link.
					$geo_mashup_options->get('overall', 'category_link_text');
			}
		}
		return $content;
	}

	/**
	 * Add the Geo Mashup Options settings admin page.
	 *
	 * admin_menu {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page(__('Geo Mashup Options','GeoMashup'), __('Geo Mashup','GeoMashup'), 8, __FILE__, array('GeoMashup', 'options_page'));
		}
	}

	/**
	 * Display important messages in the admin.
	 * 
	 * admin_notices {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function admin_notices() {
		global $geo_mashup_options;

		$message = '';
		$google_key = $geo_mashup_options->get( 'overall', 'google_key' );
		if ( empty( $google_key ) and current_user_can( 'manage_options' ) ) {
			if ( ! isset( $_GET['page'] ) or GEO_MASHUP_PLUGIN_NAME != $_GET['page'] ) {
				// We're not looking at the settings, but it's important to do so
				$message = __( 'Geo Mashup requires a Google API key in the <a href="%s">settings</a> before it will work.', 'GeoMashup' );
				$message = sprintf( $message, admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) );
			}
		}

		if ( ! empty( $message ) ) {
			echo '<div class="error fade"><p>' . $message . '</p></div>';
		}
	}

	/**
	 * Add custom action links to the plugin listing.
	 * 
	 * plugin_action_links {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function plugin_action_links( $links, $file ) {
		if ( GEO_MASHUP_PLUGIN_NAME == $file ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) .'">' .
				__( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * Add custom meta links to the plugin listing.
	 * 
	 * plugin_row_meta {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function plugin_row_meta( $links, $file ) {
		if ( GEO_MASHUP_PLUGIN_NAME == $file ) {
			$links[] = '<a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation">' .
				__( 'Documentation', 'GeoMashup' ) . '</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=11045324">' .
				__( 'Donate', 'GeoMashup' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Output the Geo Mashup Options admin page.
	 *
	 * Called by the WordPress admin.
	 * 
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function options_page() {
		include_once(dirname(__FILE__) . '/options.php');
		geo_mashup_options_page();
	}

	/**
	 * Get the location of the current loop object, if any.
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param string $output ARRAY_A | ARRAY_N | OBJECT
	 * @return object|bool Location object or false if none.
	 */
	function current_location( $output = OBJECT ) {
		global $in_comment_loop, $in_user_loop, $user;


		$location = false;

		if ( $in_comment_loop ) {
			$object_name = 'comment';
			$object_id = get_comment_ID();
		} else if ( $in_user_loop ) {
			$object_name = 'user';
			$object_id = $user->ID;
		} else if ( in_the_loop() ) {
			$object_name = 'post';
			$object_id = get_the_ID();
		} else {
			$object_name = $object_id = '';
		}
			
		if ( $object_name && $object_id ) {
			$location = GeoMashupDB::get_object_location( $object_name, $object_id, $output );
		}
		return $location;
	}

	/**
	 * A tag to insert the the onload call needed by IE 
	 * (and works in Firefox) in the body tag to load the 
	 * Google map. 
	 *
	 * @deprecated 1.0 No longer necessary.
	 *
	 */
	function body_attribute() {
	}

	/** 
	 * A template tag to insert location information.
	 *
	 * @since 1.3
	 * @access public 
	 * @static
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string The information requested, empty string if none.
	 */
	function location_info( $args = '' ) {
		$defaults = array( 
			'fields' => 'address', 
			'separator' => ',', 
			'format' => '',
			'object_name' => null, 
			'object_id' => null );
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );
		$info = '';

		if ( $object_name && $object_id ) {
			$location = GeoMashupDB::get_object_location( $object_name, $object_id, ARRAY_A );
		} else {
			$location = GeoMashup::current_location( ARRAY_A );
		}

		if ( !empty( $location ) ) {
			$fields = preg_split( '/\s*,\s*/', $fields );
			$values = array();
			foreach( $fields as $field ) {
				if ( isset( $location[$field] ) ) {
					array_push( $values, $location[$field] );
				} else {
					if ( 'country_name' == $field ) { 
						array_push( $values, GeoMashupDB::get_administrative_name( $location['country_code'] ) );
					} else if ( 'admin_name' == $field ) {
						array_push( $values, GeoMashupDB::get_administrative_name( $location['country_code'], $location['admin_code'] ) );
					} else {
						array_push( $values, '' );
					}
				} 
			}
			if ( empty( $format ) ) {
				$info = implode( $separator, $values );
			} else {
				$info = vsprintf( $format, $values );
			}
		}
		return $info;
	}	

	/**
	 * A template tag to insert a link to a post on the mashup.
	 * 
	 * @see show_on_map_link()
	 */
	function post_link($option_args = '') {
		return GeoMashup::show_on_map_link($option_args);
	}

	/**
	 * A template tag to return an URL for the current location on the 
	 * global map page. 
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @return string The URL, empty if no current location is found.
	 */
	function show_on_map_link_url( $args = null ) {
		global $geo_mashup_options;

		$defaults = array( 'zoom' => '' );
		$args = wp_parse_args( $args, $defaults );

		$url = '';
		$location = GeoMashup::current_location();
		if ( $location ) {
			$url = get_page_link($geo_mashup_options->get('overall', 'mashup_page'));
			if ( !$url ) {
				return $url;
			}
			if ( strstr( $url, '?' ) ) {
				$url .= '&amp;';
			} else {
				$url .= '?';
			}
			$open = '';
			if ( $geo_mashup_options->get( 'global_map', 'auto_info_open' ) == 'true' ) {
				$open = '&open_object_id=' . $location->object_id;
			}
			$zoom = '';
			if ( !empty( $args['zoom'] ) ) {
				$zoom = '&zoom=' . urlencode( $args['zoom'] );
			}
			$url .= htmlentities("center_lat={$location->lat}&center_lng={$location->lng}$open$zoom");
		}
		return $url;
	}

	/**
	 * A template tag to insert a link to the current location post on the 
	 * global map page. 
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @return string The link HTML, empty if no current location is found.
	 */
	function show_on_map_link( $args = null ) {
		$defaults = array( 'text' => __( 'Show on map', 'GeoMashup' ),
			 'display' => false,
			 'zoom' => '',
			 'show_icon' => true );
		$options = wp_parse_args($args, $defaults);
		$link = '';
		$url = GeoMashup::show_on_map_link_url( $args );
		if ( $url ) {
			$icon = '';
			if ($options['show_icon'] && strcmp( $options['show_icon'], 'false' ) != 0) {
				$icon = '<img src="'.GEO_MASHUP_URL_PATH.
					'/images/geotag_16.png" alt="'.__('Geotag Icon','GeoMashup').'"/>';
			}
			$link = '<a class="gm-link" href="'.$url.'">'.
				$icon.' '.$options['text'].'</a>';
			if ($options['display']) {
				echo $link;
			}
		}
		return $link;
	}

	/** 
	 * Visible posts list template tag.
	 *
	 * Returns a placeholder where a related map should display a list
	 * of the currently visible posts.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Visible_Posts_List
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	function visible_posts_list($args = null) {
		$args = wp_parse_args($args);

		$list_html = '';

		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		if ( !empty( $args['heading_text'] ) ) {
			$heading_div = '<div id="' . $for_map . '-visible-list-header" style="display:none;">';
			$heading_tags = '<h2>';
			if ( !empty( $args['heading_tags'] ) ) {
				$heading_tags = $args['heading_tags'];
			}
			$list_html .= balanceTags( $heading_div . $heading_tags . $args['heading_text'], true );
		}
		$list_html .= '<div id="' . $for_map . '-visible-list"></div>';
		return $list_html;
	}

	/**
	 * List located posts template tag.
	 *
	 * Returns an HTML list of all located posts.
	 *
	 * @since 1.1
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#List_Located_Posts
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string List HTML.
	 */
	function list_located_posts( $option_args = null ) {
		$option_args = wp_parse_args( $option_args );
		$option_args['object_name'] = 'post';
		$list_html = '<ul class="gm-index-posts">';
		$locs = GeoMashupDB::get_object_locations( $option_args );
		if ($locs) {
			foreach ($locs as $loc) {
				$list_html .= '<li><a href="'.get_permalink($loc->object_id).'">'.
					$loc->label."</a></li>\n";
			}
		}
		$list_html .= '</ul>';
		return $list_html;
	}

	/**
	 * List located posts by area template tag.
	 *
	 * Returns an HTML list of all located posts by country and state. May try to look up 
	 * this information when absent.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#List_Located_Posts_By_Area
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string List HTML.
	 */
	function list_located_posts_by_area( $args ) {
		$args = wp_parse_args( $args );
		$list_html = '<div class="gm-area-list">';
		$countries = GeoMashupDB::get_distinct_located_values( 'country_code', array( 'object_name' => 'post' ) );
		$country_count = count( $countries );
		$country_heading = '';
		foreach ( $countries as $country ) {
			if ( $country_count > 1 ) {
				$country_name = GeoMashupDB::get_administrative_name( $country->country_code ); 
				$country_name = $country_name ? $country_name : $country->country_code;
				$country_heading = '<h3>' . $country_name . '</h3>';
			}
			$states = GeoMashupDB::get_distinct_located_values( 'admin_code', 
				array( 'country_code' => $country->country_code, 'object_name' => 'post' ) );
			if ( empty( $states ) ) {
				$states = array( (object) array( 'admin_code' => null ) );
			}
			foreach ($states as $state ) { 
				$location_query = array( 
					'object_name' => 'post',
					'country_code' => $country->country_code,
					'admin_code' => $state->admin_code,
					'sort' => 'post_title'
				);
				$post_locations = GeoMashupDB::get_object_locations( $location_query );
				if ( count( $post_locations ) > 0 ) {
					if ( ! empty( $country_heading ) ) {
						$list_html .= $country_heading;
						$country_heading = '';
					}
					if ( null != $states[0]->admin_code ) {
						$state_name = GeoMashupDB::get_administrative_name( $country->country_code, $state->admin_code );
						$state_name = $state_name ? $state_name : $state->admin_code;
						$list_html .= '<h4>' . $state_name . '</h4>';
					}
					$list_html .= '<ul class="gm-index-posts">';
					foreach ( $post_locations as $post_location ) { 
						$list_html .= '<li><a href="' . 
							get_permalink( $post_location->object_id ) .
							'">' .
							$post_location->label .
							'</a>';
						if ( isset( $args['include_address'] ) && $args['include_address'] == 'true' ) {
							$list_html .= '<p>' . $post_location->address . '</p>';
						}
						$list_html .= '</li>';
					}
					$list_html .= '</ul>';
				}
			}
		}
		$list_html .= '</div>';
		return $list_html;
	}

	/**
	 * Post coordinates template tag.
	 *
	 * Get the coordinates of the current post. 
	 *
	 * @since 1.0
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Post_Coordinates
	 * @deprecated 1.3 Use GeoMashup::current_location()
	 *
	 * @param string|array $places Maximum number of decimal places to use.
	 * @return array Array containing 'lat' and 'lng' keys.
	 */
	function post_coordinates($places = 10) {
		global $post;

		$location = GeoMashupDB::get_object_location( 'post', $post->ID );
		$coordinates = array();
		if ( !empty( $location ) ) {
			$lat = $location->lat;
			$lng = $location->lng;
			$lat_dec_pos = strpos($lat,'.');
			if ($lat_dec_pos !== false) {
				$lat = substr($lat, 0, $lat_dec_pos+$places+1);
			}
			$lng_dec_pos = strpos($lng,'.');
			if ($lng_dec_pos !== false) {
				$lng = substr($lng, 0, $lng_dec_pos+$places+1);
			}
			$coordinates['lat'] = $lat;
			$coordinates['lng'] = $lng;
		}
		return $coordinates;
	}

	/**
	 * Emit GeoRSS namespace.
	 *
	 * rss_ns {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Feed_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function rss_ns() {
		echo 'xmlns:georss="http://www.georss.org/georss" ';
	}

	/**
	 * Emit GeoRSS tags.
	 *
	 * rss_item {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Feed_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 * @access private
	 * @static
	 */
	function rss_item() {
		global $wp_query;

		// Using Simple GeoRSS for now
		$location = GeoMashupDB::get_object_location( 'post', $wp_query->post->ID );
		if ( !empty( $location ) ) {
			echo '<georss:point>' . esc_html( $location->lat . ' ' . $location->lng ) . '</georss:point>';
		}
	}

	/**
	 * Tabbed category index template tag.
	 *
	 * Returns a placeholder where a related map should display a list
	 * of map objects by category, organized into HTML suited for presentation 
	 * as tabs.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Visible_Posts_List
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	function tabbed_category_index( $args ) {
		$args = wp_parse_args($args);

		$for_map = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) {
			$for_map = $args['for_map'];
		}	
		
		return '<div id="' . $for_map . '-tabbed-index"></div>';
	}
} // class GeoMashup
GeoMashup::load();
} // class exists

add_action( 'init', array('GeoMashup', 'init') );

?>
