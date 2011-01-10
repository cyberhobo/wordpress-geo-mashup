<?php 
/*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Save location for posts and pages, or even users and comments. Display these locations on Google maps. Make WordPress into your GeoCMS.
Version: 1.4alpha10
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 3.0
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
	 * Whether to add the click-to-load map script.
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 */
	static $add_loader_script = false;

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

	function is_options_page() {
		global $pagenow;
		return ( is_admin() and isset($_GET['page']) and GEO_MASHUP_PLUGIN_NAME === $_GET['page'] );
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
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-options.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-db.php' );
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

		add_action( 'plugins_loaded', array( 'GeoMashup', 'dependent_init' ), -1 );
		add_action( 'wp_ajax_geo_mashup_query', array( 'GeoMashup', 'geo_query') );
		add_action( 'wp_ajax_nopriv_geo_mashup_query', array( 'GeoMashup', 'geo_query') );
		add_action( 'wp_ajax_geo_mashup_suggest_custom_keys', array( 'GeoMashupDB', 'post_meta_key_suggest' ) );

		if (is_admin()) {

			register_activation_hook( __FILE__, array( 'GeoMashup', 'activation_hook' ) );

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

			// To add footer output (like scripts)
			add_action( 'wp_footer', array( 'GeoMashup', 'wp_footer' ) );

			// To allow shortcodes in the text widget
			if ( ! has_filter( 'widget_text', 'do_shortcode' ) ) {
				add_filter( 'widget_text', 'do_shortcode', 11 );
			}

			// To add the GeoRSS namespace to feeds (not available for RSS 0.92)
			add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
			add_action('atom_ns', array('GeoMashup', 'rss_ns'));

			// To add GeoRSS location to feeds
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
		define('GEO_MASHUP_VERSION', '1.3.98.10');
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
		if ( self::is_options_page() )
			wp_enqueue_script( 'jquery-ui-tabs' );
	}

	/**
	 * Load relevant styles.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function load_styles() {
		if ( self::is_options_page() ) {
			self::register_style( 'jquery-smoothness', 'jquery-ui.1.7.smoothness.css', array(), GEO_MASHUP_VERSION, 'screen' );
			wp_enqueue_style( 'jquery-smoothness' );
			wp_enqueue_script( 'suggest' );
		}
	}

	/**
	 * Perform one-time activation tasks.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 */
	function activation_hook() {
		// check to see if this plugin will even work
		if (version_compare("5.0", phpversion(), ">")) {
			deactivate_plugins(basename(__FILE__)); // Deactivate ourself
			wp_die( __( 'Sorry, Geo Mashup now requires PHP 5. Ask your host how to do this, or use an earlier version of Geo Mashup.', 'GeoMashup' ) );
		}
		GeoMashupDB::install();
	}

	/**
	 * Supply an init action for plugins that would like to use Geo Mashup APIs.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 * @uses do_action() geo_mashup_init Fired when Geo Mashup is loaded and ready.
	 */
	function dependent_init() {
		do_action( 'geo_mashup_init' );
	}

	/**
	 * Register the Geo Mashup script appropriate for the request.
	 * 
	 * @param string $handle Global tag for the script.
	 * @param string $src Path to the script from the root directory of Geo Mashup.
	 * @param array $deps Array of dependency handles.
	 * @param string $ver Script version.
	 * @param bool $in_footer Whether the script can be loaded in the footer.
	 */
	function register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		// TODO: choose a dev or minified version
		wp_register_script( $handle, plugins_url( $src, __FILE__ ), $deps, $ver, $in_footer );
	}

	/**
	 * Register the Geo Mashup style appropriate for the request.
	 *
	 * @param string $handle Global tag for the style.
	 * @param string $src Path to the stylesheet from the root directory of Geo Mashup.
	 * @param array $deps Array of dependency handles.
	 * @param string $ver Script version.
	 * @param bool $media Stylesheet media target.
	 */
	function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		// TODO: choose a dev or minified version
		wp_register_style( $handle, plugins_url( $src, __FILE__ ), $deps, $ver, $media );
	}

	/**
	 * Add things like scripts to the footer.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 */
	function wp_footer() {
		if ( self::$add_loader_script ) {
			self::register_script( 'geo-mashup-loader', 'geo-mashup-loader.js', array(), GEO_MASHUP_VERSION, true );
			wp_print_scripts( 'geo-mashup-loader' );
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
	 *	Locate a Geo Mashup template.
	 * 
	 * Geo Mashup looks for templates given a certain base name. Given a base 
	 * name of 'info-window', it will return the first of:
	 * 	'geo-mashup-info-window.php' in the active theme directory
	 * 	'info-window.php' in the geo-mashup-custom plugin directory
	 * 	'info-window-default.php' in the geo-mashup plugin directory
	 * 
	 * @param string $template_base The base name of the template.
	 * @return string The file path of the template found.
	 */
	function locate_template( $template_base ) {
		global $geo_mashup_custom;
		$template = locate_template( array("geo-mashup-$template_base.php") );
		if ( empty( $template ) && isset( $geo_mashup_custom ) && $geo_mashup_custom->file_url( $template_base . '.php' ) ) {
			$template = path_join( $geo_mashup_custom->dir_path, $template_base . '.php' );
		}
		if ( !is_readable( $template ) ) {
			$template = path_join( GEO_MASHUP_DIR_PATH, $template_base . '-default.php' );
		}
		if ( !is_readable( $template ) ) {
			$template = path_join( GEO_MASHUP_DIR_PATH, 'info-window-default.php' );
		}
		return $template;
	}

	/**
	 * Deliver templated Geo Mashup content.
	 *
	 * template_redirect {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 *
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
		exit();
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
		GeoMashupRenderMap::render_map();
		exit();
	}

	/**
	 * Perform an ajax edit operation and echo results.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function ajax_edit() {
		check_ajax_referer( 'geo-mashup-edit', 'geo_mashup_nonce' );
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

		echo json_encode( array( 'status' => $status ) );
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
	 * @deprecated use WP / PHP json_encode
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
			'post_mime_type' => 'application/vnd.google-earth.kml+xml,application/vnd.google-earth.kmz',
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$urls = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				array_push( $urls, wp_get_attachment_url( $attachment->ID ) );
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
				echo '<meta name="ICBM" content="' . esc_attr( $loc->lat . ', ' . $loc->lng ) . '" />' . "\n";
				echo '<meta name="DC.title" content="' . esc_attr( $title ) . '" />' . "\n";
				echo '<meta name="geo.position" content="' .  esc_attr( $loc->lat . ';' . $loc->lng ) . '" />' . "\n";
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
	 * @uses apply_filters() the_title Filter post titles.
	 * @uses apply_filters() geo_mashup_locations_json_object Filter each location associative array before conversion to JSON.
	 *
	 * @param string|array $query_args Query variables for GeoMashupDB::get_object_locations().
	 * @param string $format (optional) 'JSON' (default) or ARRAY_A
	 * @return string Queried object locations JSON ( { "object" : [...] } ).
	 */
	function get_locations_json( $query_args, $format = 'JSON' ) {
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

					// Filter the title
					$object->label = apply_filters( 'the_title', $object->label, $object->object_id );

					// Only know about post categories now, but could abstract to objects
					$categories = get_the_category( $object->object_id );
					foreach ($categories as $category) {
						$category_ids[] = $category->cat_ID;
					}

					// Only posts have KML attachments
					$attachments = GeoMashup::get_kml_attachment_urls( $object->object_id );

					// Include post author
					$author = get_userdata( $object->post_author );
					if ( empty( $author ) ) {
						$author_name = '';
					} else {
						$author_name = $author->display_name;
					}
				}

				$json_object = array(
					'object_name' => $query_args['object_name'],
					'object_id' => $object->object_id,
					// We should be able to use real UTF-8 characters in titles
					// Helps with the spelling-out of entities in tooltips
					// PHP4 screams about multibyte characters - make it shut up with @
					'title' => @html_entity_decode( $object->label, ENT_COMPAT, 'UTF-8' ),
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
		if ( ARRAY_A == $format ) 
			return array( 'objects' => $json_objects );
		else
			return json_encode( array( 'objects' => $json_objects ) );
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
	 * Build the data for a javascript map.
	 *
	 * Parameters are used both to retrieve data and as options to
	 * eventually pass to the javascript.
	 *
	 * @since 1.4
	 * @access public
	 * @static
	 * @uses GeoMashup::get_locations_json()
	 *
	 * @global array $geo_mashup_options
	 * @global object $geo_mashup_custom
	 * @param array $query Query parameters
	 * @return array Map data ready to be rendered.
	 */
	function build_map_data( $query ) {
		global $geo_mashup_options, $geo_mashup_custom;
		$defaults = array(
			'map_api' => $geo_mashup_options->get( 'overall', 'map_api' )
		);
		$query = wp_parse_args( $query, $defaults );
		$object_id = isset( $query['object_id'] ) ? $query['object_id'] : 0;
		unset( $query['object_id'] );

		$map_data = $query + array(
			'siteurl' => home_url(), // qTranslate doesn't work with get_option( 'home' )
			'url_path' => GEO_MASHUP_URL_PATH,
			'template_url_path' => get_template_directory_uri()
		);
		if ( isset( $geo_mashup_custom ) ) {
			$map_data['custom_url_path'] = $geo_mashup_custom->url_path;
		}

		$map_content = ( isset( $query['map_content'] ) ) ? $query['map_content'] : null;
		$object_name = ( isset( $query['object_name'] ) ) ? $query['object_name'] : 'post';

		if ( $map_content == 'single') {
			$location = GeoMashupDB::get_object_location( $object_name, $object_id, ARRAY_A );
			$options = $geo_mashup_options->get( 'single_map' );
			if ( !empty( $location ) ) {
				$map_data['object_data'] = array( 'objects' => array( $location ) );
				$map_data['center_lat'] = $location['lat'];
				$map_data['center_lng'] = $location['lng'];
			}
			$map_data = array_merge ( $options, $map_data );
			if ( 'post' == $object_name ) {
				$kml_urls = GeoMashup::get_kml_attachment_urls( $object_id );
				if (count($kml_urls)>0) {
					$map_data['load_kml'] = array_pop( $kml_urls );
				}
			}
		} else {
			// Map content is not single
			$map_data['context_object_id'] = $object_id;

			if ( $map_content == 'contextual' ) {
				$options = $geo_mashup_options->get( 'context_map' );
				// If desired we could make these real options
				$options['auto_info_open'] = 'false';
			} else {
				$options = $geo_mashup_options->get( 'global_map' );
				// Category options done during render
				unset( $options['category_color'] );
				unset( $options['category_line_zoom'] );
				if ( empty( $query['show_future'] ) )
					$query['show_future'] = $options['show_future'];
				if ( is_null( $map_content ) ) 
					$options['map_content'] = 'global';
			}

			if ( isset( $options['add_google_bar'] ) and 'true' == $options['add_google_bar'] ) {
				$options['adsense_code'] = $geo_mashup_options->get( 'overall', 'adsense_code' );
			}

			// We have a lot map control parameters that don't effect the locations query,
			// but only the relevant ones are used
			$map_data['object_data'] = GeoMashup::get_locations_json( $query, ARRAY_A );

			// Incorporate parameters from the query and options
			$map_data = array_merge( $query, $map_data );
			$map_data = array_merge( $options, $map_data );
		}
		return $map_data;
	}

	/**
	 * The map template tag.
	 *
	 * Returns HTML for a Google map. Must use with echo in a template: echo GeoMashup::map();.
	 *
	 * @since 1.0
	 * @access public
	 * @static
	 * @link http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Map tag parameter documentation
	 * @uses $_SERVER['QUERY_STRING'] The first global map on a page uses query string parameters like tag parameters.
	 * @staticvar $map_number Used to index maps per request.
	 *
	 * @param string|array $atts Template tag parameters.
	 * @return string The HTML for the requested map.
	 */
	function map( $atts = null ) {
		global $wp_query, $in_comment_loop, $geo_mashup_options;
		static $map_number = 0;

		$map_number++;
		$atts = wp_parse_args( $atts );
		$static = (bool)( !empty( $atts['static'] ) and 'true' == $atts['static'] );
		unset( $atts['static'] );
		if ( empty( $atts['lang'] ) ) {
			if ( function_exists( 'qtrans_getLanguage' ) ) {
				// qTranslate integration
				$atts['lang'] = qtrans_getLanguage();
			} else if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				// WPML integration
				$atts['lang'] = ICL_LANGUAGE_CODE;
			}
		}
		$click_to_load_options = array( 'click_to_load', 'click_to_load_text' );

		GeoMashup::convert_map_attributes( $atts );

		// Default query is for posts
		$object_name = ( isset( $atts['object_name'] ) ) ? $atts['object_name'] : 'post';

		// Find the ID and location of the container object if it exists
		if ( 'post' == $object_name and $wp_query->in_the_loop ) {

			$context_object_id = $wp_query->post->ID;

		} else if ( 'comment' == $object_name and $in_comment_loop ) {
			
			$context_object_id = get_comment_ID();

		} else if ( 'user' == $object_name and $wp_query->post ) {

			$context_object_id = $wp_query->post->post_author;

		}
		if ( ! empty( $context_object_id ) ) {
			
			$atts['object_id'] = $context_object_id;
			$context_location = GeoMashupDB::get_object_location( $object_name, $context_object_id );

		}

		// Map content type isn't required, so resolve it
		$map_content = isset( $atts['map_content'] ) ? $atts['map_content'] : null;

		if ( empty ( $map_content ) ) {

			if ( empty( $context_object_id ) ) {
				$map_content = 'contextual';
			} else if ( empty( $context_location ) ) {
				// Not located, go global
				$map_content = 'global';
			} else {
				// Located, go single
				$map_content = 'single';
			}

		}

		switch ($map_content) {
			case 'contextual':
				$atts['map_content'] = 'contextual';
				$atts += $geo_mashup_options->get( 'context_map', $click_to_load_options );
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
				$atts['object_ids'] = implode( ',', $object_ids );
				break;

			case 'single':
				$atts['map_content'] = 'single';
				$atts += $geo_mashup_options->get( 'single_map', $click_to_load_options );
				if ( empty( $atts['object_id'] ) ) {
					return '<!-- ' . __( 'Geo Mashup found no current object to map', 'GeoMashup' ) . '-->';
				}
				if ( empty( $context_location ) ) {
					return '<!-- ' . __( 'Geo Mashup omitted a map for an object with no location', 'GeoMashup' ) . '-->';
				}
				break;

			case 'global':
				if ( isset( $_GET['template'] ) and 'full-post' == $_GET['template'] ) {
					// Global maps tags in response to a full-post query can infinitely nest, prevent this
					return '<!-- ' . __( 'Geo Mashup map omitted to avoid nesting maps', 'GeoMashup' ) . '-->';
				}
				$atts['map_content'] = 'global';
				if ( isset($_SERVER['QUERY_STRING']) and 1 == $map_number ) {
					// The first global map on a page will make use of query string arguments
					$atts = wp_parse_args( $_SERVER['QUERY_STRING'], $atts );
				} 
				$atts += $geo_mashup_options->get( 'global_map', $click_to_load_options );
				break;

			default:
				return '<div class="gm-map"><p>Unrecognized value for map_content: "'.$map_content.'".</p></div>';
		}
		
		$click_to_load = $atts['click_to_load'];
		unset( $atts['click_to_load'] );
		$click_to_load_text = $atts['click_to_load_text'];
		unset( $atts['click_to_load_text'] );
		$name = 'gm-map-' . $map_number;
		if ( isset( $atts['name'] ) )
			$name = $atts['name'];
		unset($atts['name']);

		$map_data = GeoMashup::build_map_data( $atts );
		if ( empty( $map_data['object_data']['objects'] ) and !isset( $map_data['load_empty_map'] ) )
			return '<!-- ' . __( 'Geo Mashup omitted a map with no located objects found.', 'GeoMashup' ) . '-->';
		unset( $map_data['load_empty_map'] );

		$map_image = '';
		if ( $static ) {
			// Static maps have a limit of 50 markers: http://code.google.com/apis/maps/documentation/staticmaps/#Markers
			$atts['limit'] = empty( $atts['limit'] ) ? 50 : $atts['limit'];

			if ( !empty( $map_data['object_data']['objects'] ) ) {
				$image_width = str_replace( '%', '', $map_data['width'] );
				$image_height = str_replace( '%', '', $map_data['height'] );
				$map_image = '<img src="http://maps.google.com/maps/api/staticmap?size='.$image_width.'x'.$image_height;
				if ( count( $map_data['object_data']['objects'] ) == 1) {
					$map_image .= '&amp;center=' . $map_data['object_data']['objects'][0]['lat'] . ',' .
						$map_data['object_data']['objects'][0]['lng'];
				}
				$map_image .= '&amp;sensor=false&amp;zoom=' . $map_data['zoom'] . '&amp;markers=size:small|color:red';
				foreach( $map_data['object_data']['objects'] as $location ) {
					// TODO: Try to use the correct color for the category? Draw category lines?
					$map_image .= '|' . $location['lat'] . ',' . $location['lng'];
				}
				$map_image .= '" alt="geo_mashup_map"';
				if ($click_to_load == 'true') {
					$map_image .= '" title="'.$click_to_load_text.'"';
				}
				$map_image .= ' />';
			}
		}

		$atts_md5 =  md5( serialize( $atts ) );
		set_transient( 'gmm' . $atts_md5, $map_data, 20 );
		set_transient( 'gmp' . $atts_md5, $atts, 60*60*24 );

		$iframe_src =  home_url( '?geo_mashup_content=render-map&amp;map_data_key=' . $atts_md5 );
		if ( !empty( $atts['lang'] ) )
			$iframe_src .= '&amp;lang=' . $atts['lang'];
			
		$content = "";

		if ($click_to_load == 'true') {
			if ( is_feed() ) {
				$content .= "<a href=\"{$iframe_src}\">$click_to_load_text</a>";
			} else {
				self::$add_loader_script = true;
				$style = "height:{$map_data['height']}px;width:{$map_data['width']}px;background-color:#ddd;".
					"background-image:url(".GEO_MASHUP_URL_PATH."/images/wp-gm-pale.png);".
					"background-repeat:no-repeat;background-position:center;cursor:pointer;";
				$content = "<div class=\"gm-map\" style=\"$style\" " .
					"onclick=\"GeoMashupLoader.addMapFrame(this,'$iframe_src','{$map_data['height']}','{$map_data['width']}','$name')\">";
				if ( $static ) {
					// TODO: test whether click to load really works with a static map
					$content .= $map_image . '</div>';
				} else {
					$content .= "<p style=\"text-align:center;\">$click_to_load_text</p></div>";
				}
			}
		} else if ( $static ) {
			$content = "<div class=\"gm-map\">$map_image</div>";
		} else {
			$content =  "<div class=\"gm-map\"><iframe name=\"{$name}\" src=\"{$iframe_src}\" " .
				"height=\"{$map_data['height']}\" width=\"{$map_data['width']}\" marginheight=\"0\" marginwidth=\"0\" ".
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
		// It's nice if click-to-load works in the full post display
		self::$add_loader_script = true;

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
			add_options_page(__('Geo Mashup Options','GeoMashup'), __('Geo Mashup','GeoMashup'), 'manage_options', __FILE__, array('GeoMashup', 'options_page'));
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
		if ( ! self::is_options_page() ) {
			// We're not looking at the settings, but it may be important to do so
			$google_key = $geo_mashup_options->get( 'overall', 'google_key' );
			if ( empty( $google_key ) and 'google' == $geo_mashup_options->get( 'overall', 'map_api' ) and current_user_can( 'manage_options' ) ) {
				$message = __( 'Geo Mashup requires a Google API key in the <a href="%s">settings</a> before it will work.', 'GeoMashup' );
				$message = sprintf( $message, admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) );
			}
			if ( GEO_MASHUP_DB_VERSION != GeoMashupDB::installed_version() and current_user_can( 'manage_options' ) ) {
				$message = __( 'Geo Mashup needs to upgrade its database, visit the <a href="%s">settings</a> to do it now.', 'GeoMashup' );
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
						$admin_name = GeoMashupDB::get_cached_administrative_name( $location['country_code'], $location['admin_code'] );
						if ( empty( $admin_name ) ) {
							$subdiv = GeoMashupDB::get_geonames_subdivision( $location['lat'], $location['lng'] );
							if ( empty( $subdiv ) ) {
								array_push( $values, $admin_code );
							} else {
								array_push( $values, GeoMashupDB::get_administrative_name( $subdiv['country_code'], $subdiv['admin_code'] ) );
							}
						} else {
							array_push( $values, $admin_name );
						}
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
