<?php 
/*
Plugin Name: Geo Mashup
Plugin URI: https://wordpress.org/plugins/geo-mashup/
Description: Save location for posts and pages, or even users and comments. Display these locations on Google, Leaflet, and OSM maps. Make WordPress into your GeoCMS.
Version: 1.8.7
Author: Dylan Kuhn
Text Domain: GeoMashup
Domain Path: /lang
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 3.0
License: GPL2+
*/

/*  Copyright 2015  Dylan Kuhn  (email : cyberhobo@cyberhobo.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2 or later, as
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
	 */
	private static $add_loader_script = false;

	/**
	 * The basename of the Geo Mashup Search plugin when deactivated.
	 * 
	 * @since 1.5
	 */
	private static $deactivate_geo_search_basename = '';

	/**
	 * Load Geo Mashup.
	 * 
	 * Initializations that can be done before init(). 
	 *
	 * @since 1.2
	 */
	public static function load() {
		GeoMashup::load_constants();
		load_plugin_textdomain( 'GeoMashup', '', path_join( GEO_MASHUP_DIRECTORY, 'lang' ) );

		GeoMashup::load_dependencies();
		GeoMashup::load_hooks();
	}

	/**
	 *	Test to see if the current request is for the Geo Mashup options page.
	 *
	 * @since 1.4
	 *
	 * @return bool Whether this is a an options page request.
	 */
	public static function is_options_page() {
		// We may need this before $pagenow is set, but maybe this method won't always work?
		return ( is_admin() and isset($_GET['page']) and GEO_MASHUP_PLUGIN_NAME === $_GET['page'] );
	}

	/**
	 * WordPress init action.
	 *
	 * init {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action},
	 * called by WordPress.
	 *
	 * @since 1.2
	 */
	public static function init() {
		if ( GEO_MASHUP_DB_VERSION != GeoMashupDB::installed_version() ) {
			// We're active but not installed - try once more to install
			GeoMashupDB::install();
		}
		GeoMashup::load_styles();
		GeoMashup::load_scripts();
	}

	/**
	 * Load relevant dependencies.
	 * 
	 * @since 1.2
	 */
	private static function load_dependencies() {
		global $geo_mashup_options;
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-options.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/gm-location-query.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/post-query.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-db.php' );
		include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-ui-managers.php' );

		if ( $geo_mashup_options->get( 'overall', 'enable_geo_search' ) == 'true' )
			include_once( GEO_MASHUP_DIR_PATH . '/geo-mashup-search.php' );

	}

	/**
	 * Load relevant hooks.
	 * 
	 * @since 1.2
	 */
	private static function load_hooks() {
		global $geo_mashup_options;

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'wp_scheduled_delete', array( __CLASS__, 'action_wp_scheduled_delete' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'dependent_init' ), -1 );
		add_action( 'wp_ajax_geo_mashup_query', array( __CLASS__, 'geo_query') );
		add_action( 'wp_ajax_nopriv_geo_mashup_query', array( __CLASS__, 'geo_query') );
		add_action( 'wp_ajax_geo_mashup_kml_attachments', array( __CLASS__, 'ajax_kml_attachments') );
		add_action( 'wp_ajax_nopriv_geo_mashup_kml_attachments', array( __CLASS__, 'ajax_kml_attachments') );
		add_action( 'wp_ajax_geo_mashup_suggest_custom_keys', array( 'GeoMashupDB', 'post_meta_key_suggest' ) );
		
		register_activation_hook( __FILE__, array( __CLASS__, 'activation_hook' ) );

		if (is_admin()) {


			// To add Geo Mashup settings page
			add_action('admin_menu', array(__CLASS__, 'admin_menu'));

			// To make important announcements
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

			// To add plugin listing links
			add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		} else {

			// This is a non-admin request

			if ($geo_mashup_options->get('overall','add_category_links') == 'true') {
				// To add map links to a category list - flaky, requires non-empty category description
				add_filter('list_cats', array(__CLASS__, 'list_cats'), 10, 2);
			}

			// To output location meta tags in the page head
			add_action('wp_head', array(__CLASS__, 'wp_head'));

			// To add footer output (like scripts)
			add_action( 'wp_footer', array( __CLASS__, 'wp_footer' ) );

			// To allow shortcodes in the text widget
			if ( ! has_filter( 'widget_text', 'do_shortcode' ) ) {
				add_filter( 'widget_text', 'do_shortcode', 11 );
			}

			// To add the GeoRSS namespace to feeds (not available for RSS 0.92)
			add_action('rss2_ns', array(__CLASS__, 'rss_ns'));
			add_action('atom_ns', array(__CLASS__, 'rss_ns'));

			// To add GeoRSS location to feeds
			add_action('rss2_item', array(__CLASS__, 'rss_item'));
			add_action('atom_entry', array(__CLASS__, 'rss_item'));

			// To add custom renderings
			add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
			add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );

			include_once( GEO_MASHUP_DIR_PATH . '/shortcodes.php');
	
		}
	}

	/**
	 * Define Geo Mashup constants. 
	 * 
	 * @since 1.2
	 */
	private static function load_constants() {
		define('GEO_MASHUP_PLUGIN_NAME', plugin_basename(__FILE__));
		define('GEO_MASHUP_DIR_PATH', dirname( __FILE__ ));
		define('GEO_MASHUP_DIRECTORY', dirname( GEO_MASHUP_PLUGIN_NAME ) );
		define('GEO_MASHUP_URL_PATH', trim( plugin_dir_url( __FILE__ ), '/' ) );
		define('GEO_MASHUP_MAX_ZOOM', 20);
		define('GEO_MASHUP_VERSION', '1.8.7');
		define('GEO_MASHUP_DB_VERSION', '1.3');
	}

	/**
	 * Load relevant scripts.
	 * 
	 * @since 1.2
	 */
	private static function load_scripts() {
		if ( self::is_options_page() ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'suggest' );
			if ( isset( $_POST['geo_mashup_run_tests'] ) ){
				self::register_script( 'qunit', 'js/qunit.js', array( 'jquery' ), GEO_MASHUP_VERSION, true );
				self::register_script( 'qunit-close-enough', 'js/qunit-close-enough.js', array( 'qunit' ), GEO_MASHUP_VERSION, true );
				self::register_script( 'geo-mashup-tests', 'js/qunit-tests.js', array( 'qunit-close-enough' ), GEO_MASHUP_VERSION, true );
				wp_enqueue_script( 'geo-mashup-tests' );
				include_once( GEO_MASHUP_DIR_PATH . '/tests.php' );
			}
		}
	}

	/**
	 * Load relevant styles.
	 * 
	 * @since 1.2
	 */
	private static function load_styles() {
		if ( self::is_options_page() ) {
			self::register_style( 'jquery-smoothness', 'css/jquery-ui.1.7.smoothness.css', array(), GEO_MASHUP_VERSION, 'screen' );
			wp_enqueue_style( 'jquery-smoothness' );
			if ( isset( $_POST['geo_mashup_run_tests'] ) ){
				self::register_style( 'qunit', 'css/qunit.css', array(), GEO_MASHUP_VERSION, 'screen' );
				wp_enqueue_style( 'qunit' );
			}
		}
	}

	/**
	 * WordPress hook to perform activation tasks.
	 * 
	 * @since 1.4
	 */
	public static function activation_hook() {
		global $geo_mashup_options;
		GeoMashupDB::install();
		if ( '' == $geo_mashup_options->get( 'overall', 'version' ) and '' != $geo_mashup_options->get( 'overall', 'google_key' ) ) {
			// Upgrading from a pre-1.4 version - don't set the default provider to Google v3
			$geo_mashup_options->set_valid_options(
				array(
					'overall' => array(
						'map_api' => 'google',
						'version' => GEO_MASHUP_VERSION
					)
				)
			);
			$geo_mashup_options->save();
		}
	}

	/**
	 * WordPress action to supply an init action for plugins that would like to use Geo Mashup APIs.
	 * 
	 * @since 1.4
	 * @uses do_action() geo_mashup_init Fired when Geo Mashup is loaded and ready.
	 */
	public static function dependent_init() {
		global $geo_mashup_options;

		do_action( 'geo_mashup_init' );

		if ( class_exists( 'GeoMashupSearch' ) and defined( 'GeoMashupSearch::VERSION' ) ) {

			// The old search plugin is active - enable native geo search and flag for deactivation
			self::$deactivate_geo_search_basename = GeoMashupSearch::get_instance()->basename;
			$geo_mashup_options->set_valid_options(
				array(
					'overall' => array(
						'enable_geo_search' => 'true',
					)
				)
			);
			$geo_mashup_options->save();
		}


	}

	/**
	 * WordPress action to remove expired Geo Mashup transients.
	 * 
	 * @since 1.4.6
	 * @uses apply_filters() geo_mashup_disable_scheduled_delete A way to disable the scheduled delete.
	 */
	public static function action_wp_scheduled_delete() {
		global $wpdb, $_wp_using_ext_object_cache;

		
		if ( $_wp_using_ext_object_cache || apply_filters( 'geo_mashup_disable_scheduled_delete', false ) || defined( 'GEO_MASHUP_DISABLE_SCHEDULED_DELETE' ) )
			return;

		$time = time();
		$expired = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_gm%' AND option_value < {$time};" );

		foreach( $expired as $transient ) {
			$key = str_replace('_transient_timeout_', '', $transient);
			delete_transient($key);
		}
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->options}" );
	}

	/**
	 * Register the Geo Mashup script appropriate for the request.
	 *
	 * @since 1.4
	 * 
	 * @param string $handle Global tag for the script.
	 * @param string $src Path to the script from the root directory of Geo Mashup.
	 * @param array $deps Array of dependency handles.
	 * @param string|bool $ver Script version.
	 * @param bool $in_footer Whether the script can be loaded in the footer.
	 */
	public static function register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		// Use the minified version if SCRIPT_DEBUG is not set and it exists
		if ( ( !defined( 'SCRIPT_DEBUG' ) or !SCRIPT_DEBUG ) and '.js' === substr( $src, -3 ) ) {
			$min_src = substr( $src, 0, -3 ) . '.min.js';
			if ( is_readable( $min_src ) )
				$src = $min_src;
		}
		wp_register_script(
				$handle, 
				plugins_url( $src, __FILE__ ), 
				$deps, 
				$ver, 
				$in_footer );
	}

	/**
	 * Register the Geo Mashup style appropriate for the request.
	 *
	 * @since 1.4
	 *
	 * @param string $handle Global tag for the style.
	 * @param string $src Path to the stylesheet from the root directory of Geo Mashup.
	 * @param array $deps Array of dependency handles.
	 * @param string|bool $ver Script version.
	 * @param string $media Stylesheet media target.
	 */
	public static function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		// Use the minified version if SCRIPT_DEBUG is not set and it exists
		if ( ( !defined( 'SCRIPT_DEBUG' ) or !SCRIPT_DEBUG ) and '.css' === substr( $src, -4 ) ) {
			$min_src = substr( $src, 0, -3 ) . '.min.css';
			if ( is_readable( $min_src ) )
				$src = $min_src;
		}

		wp_register_style( $handle, plugins_url( $src, __FILE__ ), $deps, $ver, $media );
	}

	/**
	 * WordPress action to add things like scripts to the footer.
	 * 
	 * @since 1.4
	 */
	public static function wp_footer() {
		if ( self::$add_loader_script ) {
			self::register_script( 
				'geo-mashup-loader', 
				'js/loader.js', 
				array(), 
				GEO_MASHUP_VERSION, 
				true );
				
			wp_print_scripts( 'geo-mashup-loader' );
		}
	}

	/**
	 * WordPress filter to add Geo Mashup query variables.
	 *
	 * @since 1.3
	 */
	public static function query_vars( $public_query_vars ) {
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
	 * 	'default-templates/info-window.php' in the geo-mashup plugin directory
	 *
	 * @since 1.4
	 * 
	 * @param string $template_base The base name of the template.
	 * @return string The file path of the template found.
	 */
	public static function locate_template( $template_base ) {
		global $geo_mashup_custom;
		$template = locate_template( array("geo-mashup-$template_base.php") );
		if ( empty( $template ) and isset( $geo_mashup_custom ) and $geo_mashup_custom->file_url( $template_base . '.php' ) ) {
			$template = path_join( $geo_mashup_custom->dir_path, $template_base . '.php' );
		}
		if ( empty( $template ) or !is_readable( $template ) ) {
			$template = path_join( GEO_MASHUP_DIR_PATH, "default-templates/$template_base.php" );
		}
		if ( empty( $template ) or !is_readable( $template ) ) {
			// If all else fails, just use the default info window template
			$template = path_join( GEO_MASHUP_DIR_PATH, 'default-templates/info-window.php' );
		}
		return $template;
	}

	/**
	 * WordPress action to deliver templated Geo Mashup content.
	 *
	 * @since 1.3
	 *
	 * @uses geo_query
	 * @uses render_map
	 */
	public static function template_redirect() {
		$geo_mashup_content = get_query_var( 'geo_mashup_content' );
		if ( ! empty( $geo_mashup_content ) ) {

			// The parameter's purpose is to get us here, we can remove it now
			unset( $_GET['geo_mashup_content'] );

			// Call the function corresponding to the content request
			// This provides some security, as only implemented methods will be executed
			$method = str_replace( '-', '_', $geo_mashup_content );
			call_user_func( array( __CLASS__, $method ) );
			exit();
		}
	}

	/**
	 * Process an AJAX geo query.
	 *
	 * @since 1.3
	 * @uses geo-query.php
	 */
	public static function geo_query() {
		require_once( 'geo-query.php' );
		exit();
	}

	/**
	 * Process an iframe map request.
	 *
	 * @since 1.3
	 * @uses render-map.php
	 */
	private static function render_map() {
		require_once( 'render-map.php' );
		GeoMashupRenderMap::render_map();
		exit();
	}

	/**
	 * WordPress action to perform an ajax edit operation and echo results.
	 *
	 * @since 1.3
	 */
	public static function ajax_edit() {
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
	 *
	 * @param bool $yes_or_no Whether to activate the join or not.
	 */
	public static function join_post_queries( $yes_or_no ) {
		GeoMashupDB::join_post_queries( $yes_or_no );
	}

	/**
	 * Helper to turn a string of key-value pairs into an associative array.
	 *
	 * @since 1.0
	 *
	 * @param string $glue1 Pair separator.
	 * @param string $glue2 Key/value separator.
	 * @param string $str String to explode. 
	 * @return array The associative array.
	 */
	public static function explode_assoc($glue1, $glue2, $str) {
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
	 *
	 * @param string $inner_glue Key/value separator.
	 * @param string $outer_glue Pair separator.
	 * @param array $array Array to implode.
	 * @param mixed $skip_empty Whether to include empty values in output.
	 * @param mixed $urlencoded Whether to URL encode the output.
	 * @return string The imploded string.
	 */
	public static function implode_assoc($inner_glue, $outer_glue, $array, $skip_empty=false, $urlencoded=false) {
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
	 * Guess the best language code for the current context.
	 * 
	 * Takes some plugins and common practices into account.
	 * 
	 * @since 1.4
	 * 
	 * @return string Language code.
	 */
	public static function get_language_code() {
		if ( isset( $_GET['lang'] ) ) {
			// A language override technique is to use this querystring parameter
			$language_code = $_GET['lang'];
		} else if ( function_exists( 'qtrans_getLanguage' ) ) {
			// qTranslate integration
			$language_code = qtrans_getLanguage();
		} else if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			// WPML integration
			$language_code = ICL_LANGUAGE_CODE;
		} else {
			$language_code = get_locale();
		}
		return $language_code;
	}

	/**
	 * Get post types available for location searches.
	 * 
	 * This includes post types included via the located_post_types option,
	 * as well as post types registered with exclude_from_search set to 
	 * <em>false</em>.
	 * 
	 * @since 1.4.5
	 * 
	 * @return array Array of post type strings.
	 */
	public static function get_searchable_post_types() {
		global $geo_mashup_options;
		$located_types = $geo_mashup_options->get( 'overall', 'located_post_types' );
		$searchable_types = array_keys( get_post_types( array('exclude_from_search' => false) ) );
		return array_unique( array_merge( $located_types, $searchable_types ) );
	}

	/**
	 * Get an array of URLs of KML or KMZ attachments for a post.
	 * 
	 * @since 1.1
	 *
	 * @param int $post_id
	 * @return array Array of URL strings.
	 */
	public static function get_kml_attachment_urls($post_id) {
		if ( empty( $post_id ) ) {
			return array();
		}
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_mime_type' => array(
				'application/vnd.google-earth.kml+xml',
				'application/vnd.google-earth.kmz',
				'application/octet-stream'
			),
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$urls = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$url = wp_get_attachment_url( $attachment->ID ); 
				// Backwards compatibility: include KML attachments with the incorrect octet-stream mime type
				if ( 'application/octet-stream' != $attachment->post_mime_type or 'kml' == substr( $url, -3 ) ) {
					array_push( $urls, $url );
				}
			}
		}
		return $urls;
	}

	/**
	 * Echo a JSONP array of URLs of KML or KMZ attachments for posts.
	 *
	 * since 1.4
	 */
	public static function ajax_kml_attachments() {
		$urls = array(); 
		if ( !empty( $_REQUEST['post_ids'] ) ) {
			$post_ids = array_map( 'intval', explode( ',', $_REQUEST['post_ids'] ) );
			foreach( $post_ids as $post_id ) {
				$urls = array_merge( $urls, self::get_kml_attachment_urls( $post_id ) );
			}
		}
		$json = json_encode( $urls );
		if ( isset( $_REQUEST['callback'] ) )
			$json = $_REQUEST['callback'] . '(' . $json . ')';
		echo $json;
		exit();
	}

	/**
	 * WordPress action to add relevant geo meta tags to the document head.
	 *
	 * wp_head {@link http://codex.wordpress.org/Plugin_API/Action_Reference#TemplateActions action},
	 * called by WordPress.
	 *
	 * @since 1.0
	 */
	public static function wp_head() {
		global $wp_query;

		if (is_single())
		{
			$loc = GeoMashupDB::get_object_location( 'post', $wp_query->post->ID );
			if (!empty($loc)) {
				$title = esc_html(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo '<meta name="ICBM" content="' . esc_attr( $loc->lat . ', ' . $loc->lng ) . '" />' . "\n";
				echo '<meta name="dcterms.title" content="' . esc_attr( $title ) . '" />' . "\n";
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
	 * @uses GeoMashupDB::get_object_locations()
	 * @uses apply_filters() the_title Filter post titles.
	 * @uses apply_filters() geo_mashup_locations_json_object Filter each location associative array before conversion to JSON.
	 * @global array $geo_mashup_options
	 *
	 * @param string|array $query_args Query variables for GeoMashupDB::get_object_locations().
	 * @param string $format (optional) 'JSON' (default) or ARRAY_A
	 * @return string Queried object locations JSON ( { "object" : [...] } ).
	 */
	public static function get_locations_json( $query_args, $format = 'JSON' ) {
		$default_args = array( 'object_name' => 'post' );
		$query_args = wp_parse_args( $query_args, $default_args );
		$json_objects = array();
		$objects = GeoMashupDB::get_object_locations( $query_args );
		if ( $objects ) {
			foreach ($objects as $object) {
				$obj = self::augment_map_object_location( $query_args['object_name'], $object );
				if ($obj) {
					$json_objects[] = $obj;
				}
			}
		}
		if ( ARRAY_A == $format ) 
			return array( 'objects' => $json_objects );
		else
			return json_encode( array( 'objects' => $json_objects ) );
	}

	/**
	 * Convert deprecated attribute names.
	 *
	 * @since 1.3
	 * 
	 * @param array $atts Attributes to modify.
	 */
	private static function convert_map_attributes( &$atts ) {
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
	 * Augment an object location for display.
	 *
	 * Adds term data, object type, author, and label.
	 * @since 1.5
	 *
	 * @param string $object_name The object type, e.g. 'post', 'user', etc.
	 * @param object $object_location The object location data.
	 * @return array The augmented object location data.
	 */
	private static function augment_map_object_location( $object_name, $object_location ) {
		global $geo_mashup_options;

		$term_ids_by_taxonomy = array();
		$author_name = '';
		if ( 'post' == $object_name ) {

			// Filter the title
			$object_location->label = sanitize_text_field( apply_filters( 'the_title', $object_location->label, $object_location->object_id ) );

			// Add terms
			if ( defined( 'GEO_MASHUP_DISABLE_CATEGORIES' ) and GEO_MASHUP_DISABLE_CATEGORIES ) 
				$include_taxonomies = array();
			else
				$include_taxonomies = $geo_mashup_options->get( 'overall', 'include_taxonomies' );
			
			foreach( $include_taxonomies as $include_taxonomy ) {
				$term_ids_by_taxonomy[$include_taxonomy] = array();
				// Not using wp_get_object_terms(), which doesn't allow for persistent caching
				$tax_terms = get_the_terms( $object_location->object_id, $include_taxonomy );
				if ( $tax_terms ) {
					// terms are sometimes indexed in order, sometimes by id, so wp_list_pluck() doesn't work
					foreach ( $tax_terms as $term ) {
						$term_ids_by_taxonomy[$include_taxonomy][] = $term->term_id;
					}
				}
			}

			// Add post author name
			if ( defined( 'GEO_MASHUP_DISABLE_AUTHOR_NAME' ) and GEO_MASHUP_DISABLE_AUTHOR_NAME ) 
				$author = null;
			else
				$author = get_userdata( $object_location->post_author );

			if ( empty( $author ) ) 
				$author_name = '';
			else 
				$author_name = $author->display_name;
		}

		$augmented_object = array(
			'object_name' => $object_name,
			'object_id' => $object_location->object_id,
			// We should be able to use real UTF-8 characters in titles
			// Helps with the spelling-out of entities in tooltips
			'title' => html_entity_decode( $object_location->label, ENT_COMPAT, 'UTF-8' ),
			'lat' => $object_location->lat,
			'lng' => $object_location->lng,
			'author_name' => $author_name,
			'terms' => $term_ids_by_taxonomy,
		);

		// Allow companion plugins to add data with legacy filter name
		return apply_filters( 'geo_mashup_locations_json_object', $augmented_object, $object_location );
	}

	/**
	 * Build the data for a javascript map.
	 *
	 * Parameters are used both to retrieve data and as options to
	 * eventually pass to the javascript.
	 *
	 * @since 1.4
	 * @uses GeoMashup::get_locations_json()
	 *
	 * @global array $geo_mashup_options
	 * @global object $geo_mashup_custom
	 * @param array $query Query parameters
	 * @return array Map data ready to be rendered.
	 */
	public static function build_map_data( $query ) {
		global $geo_mashup_options, $geo_mashup_custom;
		$defaults = array(
			'map_api' => $geo_mashup_options->get( 'overall', 'map_api' )
		);
		$query = wp_parse_args( $query, $defaults );
		$object_id = isset( $query['object_id'] ) ? $query['object_id'] : 0;
		unset( $query['object_id'] );

		$map_data = $query + array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'siteurl' => home_url( '/' ), // qTranslate doesn't work with get_option( 'home' )
			'url_path' => GEO_MASHUP_URL_PATH,
			'template_url_path' => get_stylesheet_directory_uri()
		);
		if ( isset( $geo_mashup_custom ) ) {
			$map_data['custom_url_path'] = $geo_mashup_custom->url_path;
		}

		$map_content = ( isset( $query['map_content'] ) ) ? $query['map_content'] : null;
		$object_name = ( isset( $query['object_name'] ) ) ? $query['object_name'] : 'post';

		if ( $map_content == 'single') {

			$object_location = GeoMashupDB::get_object_location( $object_name, $object_id );
			if ( !empty( $object_location ) ) {
				$augmented_location = self::augment_map_object_location( $object_name, $object_location );
				$map_data['object_data'] = array( 'objects' => array( $augmented_location ) );
			}

			$options = $geo_mashup_options->get( 'single_map' );
			$map_data = array_merge ( $options, $map_data );

			if ( 'post' == $object_name ) {
				$kml_urls = self::get_kml_attachment_urls( $object_id );
				if (count($kml_urls)>0) {
					$map_data['load_kml'] = array_pop( $kml_urls );
				}
			}

		} else { // $map_content != 'single'

			$map_data['context_object_id'] = $object_id;

			if ( $map_content == 'contextual' ) {

				$options = $geo_mashup_options->get( 'context_map' );
				// If desired we could make these real options
				$options['auto_info_open'] = 'false';

			} else { // $map_content == 'global'

				$options = $geo_mashup_options->get( 'global_map' );

				// Term options handled during render
				unset( $options['term_options'] );

				if ( empty( $query['show_future'] ) )
					$query['show_future'] = $options['show_future'];

				if ( is_null( $map_content ) ) 
					$options['map_content'] = 'global';

			}

			// Determine which taxonomies to include, if any
			if ( ( defined( 'GEO_MASHUP_DISABLE_CATEGORIES' ) and GEO_MASHUP_DISABLE_CATEGORIES ) )
				$options['include_taxonomies'] = array();
			else
				$options['include_taxonomies'] = $geo_mashup_options->get( 'overall', 'include_taxonomies' );

			if ( isset( $options['add_google_bar'] ) and 'true' == $options['add_google_bar'] ) {
				$options['adsense_code'] = $geo_mashup_options->get( 'overall', 'adsense_code' );
			}

			// We have a lot map control parameters that don't effect the locations query,
			// but only the relevant ones are used
			$map_data['object_data'] = self::get_locations_json( $query, ARRAY_A );

			// Incorporate parameters from the query and options
			$map_data = array_merge( $query, $map_data );
			$map_data = array_merge( $options, $map_data );

		} // $map_content != 'single'

		return $map_data;
	}

	/**
	 * Make an URL with querystring added to the site's home URL.
	 *
	 * @since 1.4
	 *
	 * @static
	 * @param array $query Associative array of querystring parameters.
	 * @return string HTML-ready URL.
	 */
	public static function build_home_url( $query = array() ) {

		// We want domain changes or language parameters from WPML
		// It won't provide them for any path but '/'
		$home_url = home_url( '/' );
		$home_url_parts = parse_url( $home_url );

		// Language plugins may also add query parameters to home_url(). We'll add to these.
		$home_url_query_parts = array();
		if ( !empty( $home_url_parts['query'] ) ) {
			wp_parse_str( $home_url_parts['query'], $home_url_query_parts );
			$query = array_merge( $home_url_query_parts, $query );
		}

		if ( !empty( $query ) )
			$home_url = htmlspecialchars( add_query_arg( $query, $home_url ) );

		return $home_url;
	}

	/**
	 * The map template tag.
	 *
	 * Returns HTML for a Google map. Must use with echo in a template: echo GeoMashup::map();.
	 *
	 * @since 1.0
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#map tag parameter documentation
	 * @uses $_SERVER['QUERY_STRING'] The first global map on a page uses query string parameters like tag parameters.
	 * @uses apply_filters() geo_mashup_static_map Modify a static map image generated by geo mashup.
	 * @staticvar $map_number Used to index maps per request.
	 *
	 * @param string|array $atts Template tag parameters.
	 * @return string The HTML for the requested map.
	 */
	public static function map( $atts = null ) {
		global $wp_query, $in_comment_loop, $geo_mashup_options;
		static $map_number = 1;

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

		self::convert_map_attributes( $atts );

		// Default query is for posts
		$object_name = ( isset( $atts['object_name'] ) ) ? $atts['object_name'] : 'post';

		// Map content type isn't required, if empty we'll choose one
		$map_content = isset( $atts['map_content'] ) ? $atts['map_content'] : null;

		// Find the ID and location of the container object if it exists
		if ( 'post' == $object_name and $wp_query->in_the_loop ) {

			$context_object_id = $wp_query->post->ID;

		} else if ( 'comment' == $object_name and $in_comment_loop ) {
			
			$context_object_id = get_comment_ID();

		} else if ( 'user' == $object_name and $wp_query->post ) {

			$context_object_id = $wp_query->post->post_author;

		}

		if ( empty( $atts['object_id'] ) ) {

			if ( ! empty( $context_object_id ) ) {

				// If we found a context object, we'll query for that by default
				$atts['object_id'] = $context_object_id;
				$context_location = GeoMashupDB::get_object_location( $object_name, $context_object_id );

			} else if ( 'single' == $map_content and 'post' == $object_name ) {

				// In secondary post loops we won't find a context object
				// but can at least allow explicit single maps
				$atts['object_id'] = get_the_ID();
			}

		}

		if ( empty( $map_content ) and !empty( $atts['object_ids'] ) ) {

			$map_content = 'global';

		}

		if ( empty( $map_content ) ) {

			if ( empty( $context_object_id ) ) {
				$map_content = 'contextual';
			} else if ( empty( $context_location ) ) {
				// Not located, go global
				$map_content = 'global';
			} else {
				// Located, go single
				$map_content = 'single';
			}

		} else if ( $map_content instanceof WP_Query ) {

			// We've been given a post query, put its contents in a global map
			$atts['object_ids'] = implode( ',', wp_list_pluck( $map_content->posts, 'ID' ) );
			$map_content = 'global';

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
				$single_location = GeoMashupDB::get_object_location( $object_name, $atts['object_id'] );
				if ( empty( $single_location ) ) {
					return '<!-- ' . __( 'Geo Mashup omitted a map for an object with no location', 'GeoMashup' ) . '-->';
				}
				break;

			case 'global':
				if ( isset( $_GET['template'] ) and 'full-post' == $_GET['template'] ) {
					// Global maps tags in response to a full-post query can infinitely nest, prevent this
					return '<!-- ' . __( 'Geo Mashup map omitted to avoid nesting maps', 'GeoMashup' ) . '-->';
				}
				$atts['map_content'] = 'global';

				// Global maps on a page will make use of query string arguments unless directed otherwise
				$ignore_url = false;
				if ( isset( $atts['ignore_url'] ) && 'true' == $atts['ignore_url'] ) {
					$ignore_url = true;
					unset( $atts['ignore_url'] );
				}
				if ( isset($_SERVER['QUERY_STRING']) and !$ignore_url )
					$atts = wp_parse_args( $_SERVER['QUERY_STRING'], $atts );

				$atts += $geo_mashup_options->get( 'global_map', $click_to_load_options );

				// Don't query more than max_posts
				$max_posts = $geo_mashup_options->get( 'global', 'max_posts' );
				if ( empty( $atts['limit'] ) and !empty( $max_posts ) )
					$atts['limit'] = $max_posts;
				break;

			default:
				return '<div class="gm-map"><p>Unrecognized value for map_content: "'.$map_content.'".</p></div>';
		}
		
		$click_to_load = $atts['click_to_load'];
		unset( $atts['click_to_load'] );
		$click_to_load_text = $atts['click_to_load_text'];
		unset( $atts['click_to_load_text'] );
		if ( !isset( $atts['name'] ) )
			$atts['name'] = 'gm-map-' . $map_number;

		$map_data = self::build_map_data( $atts );
		if ( empty( $map_data['object_data']['objects'] ) and !isset( $map_data['load_empty_map'] ) )
			return '<!-- ' . __( 'Geo Mashup omitted a map with no located objects found.', 'GeoMashup' ) . '-->';
		unset( $map_data['load_empty_map'] );

		$width_units = ( '%' === substr( $map_data['width'], -1 ) ) ? '%' : 'px';
		$width = intval( $map_data['width'] );
		$width_style = $width . $width_units;
		$height_units = ( '%' === substr( $map_data['height'], -1 ) ) ? '%' : 'px';
		$height = intval( $map_data['height'] );
		$height_style = $height . $height_units;

		$map_image = '';
		if ( $static ) {
			// Static maps have a limit of 50 markers: http://code.google.com/apis/maps/documentation/staticmaps/#Markers
			$atts['limit'] = empty( $atts['limit'] ) ? 50 : $atts['limit'];

			if ( !empty( $map_data['object_data']['objects'] ) ) {
				$map_image = '<img src="http://maps.google.com/maps/api/staticmap?size='.$width.'x'.$height;
				if ( count( $map_data['object_data']['objects'] ) == 1) {
					$map_image .= '&amp;center=' . $map_data['object_data']['objects'][0]['lat'] . ',' .
						$map_data['object_data']['objects'][0]['lng'];
				}
				$map_image .= '&amp;zoom=' . $map_data['zoom'] . '&amp;markers=size:small|color:red';
				foreach( $map_data['object_data']['objects'] as $location ) {
					// TODO: Try to use the correct color for the category? Draw category lines?
					$map_image .= '|' . $location['lat'] . ',' . $location['lng'];
				}
				$map_image .= '" alt="geo_mashup_map"';
				if ($click_to_load == 'true') {
					$map_image .= '" title="'.$click_to_load_text.'"';
				}
				$map_image .= ' />';
				$map_image = apply_filters('geo_mashup_static_map', $map_image, $map_data, array('click_to_load' => $click_to_load, 'click_to_load_text' => $click_to_load_text));
			}
		}

		$atts_md5 =  md5( serialize( $atts ) );
		set_transient( 'gmm' . $atts_md5, $map_data, 20 );

		$src_args = array(
			'geo_mashup_content' => 'render-map',
			'map_data_key' => $atts_md5,
		);

		if ( !empty( $atts['lang'] ) )
			$src_args['lang'] = $atts['lang'];

		if ( isset( $atts['object_ids'] ) and strlen( $atts['object_ids'] ) > 1800 ) {
			// Try to shorten the URL a bit
			if ( !class_exists( 'GM_Int_list' ) )
				include GEO_MASHUP_DIR_PATH . '/gm-int-list.php';
			$id_list = new GM_Int_List( $atts['object_ids'] );
			$atts['oids'] = $id_list->compressed();
			unset( $atts['object_ids'] );
		}

		$iframe_src = self::build_home_url( $src_args + $atts );

		$content = "";

		if ($click_to_load == 'true') {
			if ( is_feed() ) {
				$content .= "<a href=\"{$iframe_src}\">$click_to_load_text</a>";
			} else {
				self::$add_loader_script = true;
				$style = "height: {$height_style}; width: {$width_style}; background-color: #ddd;".
					"background-image: url(".GEO_MASHUP_URL_PATH."/images/wp-gm-pale.png);".
					"background-repeat: no-repeat;background-position:center; cursor: pointer;";
				$content = "<div class=\"gm-map\" style=\"$style\" " .
					"onclick=\"GeoMashupLoader.addMapFrame(this,'$iframe_src','{$height_style}','{$width_style}','{$map_data['name']}')\">";
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
			$content =  "<div class=\"gm-map\"><iframe name=\"{$map_data['name']}\" src=\"{$iframe_src}\" " .
				"style=\"height: $height_style; width: $width_style; border: none; overflow: hidden;\"></iframe></div>";
		}
		$map_number++;
		return apply_filters( 'geo_mashup_map_content', $content, $map_data );
	}

	/**
	 * Full post template tag.
	 *
	 * Returns a placeholder where a related map should display the full post content 
	 * of the currently selected marker.
	 *
	 * @since 1.1
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#Full_Post
	 * 
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	public static function full_post($args = null) {
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
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#Category_Name
	 * 
	 * @param string|array $option_args Template tag arguments.
	 * @return string Category name.
	 */
	public static function category_name($option_args = null) {
		$category_name = '';
		if (is_string($option_args)) {
			$option_args = wp_parse_args($option_args);
		}
		if (is_page() && isset($_SERVER['QUERY_STRING'])) {
			$option_args = $option_args + self::explode_assoc('=','&amp;',$_SERVER['QUERY_STRING']);
		}
		if (isset($option_args['map_cat'])) {
			$category_name = get_cat_name($option_args['map_cat']);
		}
		return $category_name;
	}

	/**
	 * Term legend template tag.
	 * 
	 * Returns a placeholder where a related map should display a legend for the 
	 * terms of the displayed content.
	 * 
	 * @since 1.5
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#term-legend
	 * 
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	public static function term_legend($args = null) {
		$args = wp_parse_args($args);

		$id = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) 
			$id = $args['for_map'];

		if ( !empty( $args['taxonomy'] ) ) 
			$id .= '-' . esc_attr ( $args['taxonomy'] );

		$id .= '-legend';

		$classes = array();

		if ( !empty( $args['noninteractive'] ) && 'false' != $args['noninteractive'] )
			$classes[] = 'noninteractive';

		if ( !empty( $args['check_all'] ) && 'true' != $args['check_all'] )
			$classes[] = 'check-all-off';

		if ( !empty( $args['default_off'] ) && 'false' != $args['default_off'] )
			$classes[] = 'default-off';

		if ( !empty( $args['format'] ) ) 
			$classes[] = 'format-' . esc_attr( $args['format'] );
		
		if ( isset( $args['titles'] ) ) {
			if ( $args['titles'] && 'false' != $args['titles'] )
				$classes[] = 'titles-on';
			else
				$classes[] = 'titles-off';
		}

		return '<div id="' . $id . '" class="' . implode( ' ', $classes ) . '"></div>';
	}

	/**
	 * Category (term) legend template tag.
	 *
	 * Returns a placeholder where a related map should display a legend for the 
	 * terms of the displayed content. Used to display only categories, now displays
	 * all included terms.
	 *
	 * @since 1.1
	 * @deprecated 
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#category-legend
	 * 
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	public static function category_legend($args = null) {
		return self::term_legend( $args );
	}

	/**
	 * If the option is set, add a map link to category lists.
	 *
	 * list_cats {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Category_Filters filter}
	 * called by WordPress.
	 *
	 * @since 1.0
	 */
	public static function list_cats($content, $category = null) {
		global $geo_mashup_options;

		if ( $category and 'category' == $category->taxonomy ) {
			$count = GeoMashupDB::category_located_post_count( $category->cat_ID );
			// Add map link only if there are geo-located posts to see
			if ($count) {
				// This feature doesn't work unless there is a category description
				if ( empty( $category->description ) ) {
					return $content . $geo_mashup_options->get('overall', 'category_link_separator') . 
						__( 'You must add a description to this category to use this Geo Mashup feature.', 'GeoMashup' );
				}
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
	 * WordPress action to add the Geo Mashup Options settings admin page.
	 *
	 * admin_menu {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 */
	public static function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page(__('Geo Mashup Options','GeoMashup'), __('Geo Mashup','GeoMashup'), 'manage_options', __FILE__, array( __CLASS__, 'options_page'));
		}
	}

	/**
	 * WordPress action to display important messages in the admin.
	 * 
	 * admin_notices {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Advanced_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.3
	 */
	public static function admin_notices() {
		global $geo_mashup_options;

		$message = array();
		if ( !empty( self::$deactivate_geo_search_basename ) ) {
			deactivate_plugins( GeoMashupSearch::get_instance()->basename );
			$message_format = __( 'Geo Mashup now includes search, deactivating the old %s plugin. It\'s safe to delete it.', 'GeoMashup' );
			$message[] = sprintf( $message_format, self::$deactivate_geo_search_basename );
		}

		if ( ! self::is_options_page() ) {
			// We're not looking at the settings, but it may be important to do so
			$google_key = $geo_mashup_options->get( 'overall', 'google_key' );
			if ( empty( $google_key ) and 'google' == $geo_mashup_options->get( 'overall', 'map_api' ) and current_user_can( 'manage_options' ) ) {
				$message_format = __( 'Geo Mashup requires a Google API key in the <a href="%s">settings</a> before it will work.', 'GeoMashup' );
				$message[] = sprintf( $message_format, admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) );
			}
			if ( GEO_MASHUP_DB_VERSION != GeoMashupDB::installed_version() and current_user_can( 'manage_options' ) ) {
				$message_format = __( 'Geo Mashup needs to upgrade its database, visit the <a href="%s">settings</a> to do it now.', 'GeoMashup' );
				$message[] = sprintf( $message_format, admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) );
			}
		}

		if ( ! empty( $message ) ) {
			echo '<div class="error fade"><p>' . implode( '</p><p>', $message ) . '</p></div>';
		}
	}

	/**
	 * WordPress action to add custom action links to the plugin listing.
	 * 
	 * plugin_action_links {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 */
	public static function plugin_action_links( $links, $file ) {
		if ( GEO_MASHUP_PLUGIN_NAME == $file ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=' . GEO_MASHUP_PLUGIN_NAME ) .'">' .
				__( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * WordPress action to add custom meta links to the plugin listing.
	 * 
	 * plugin_row_meta {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
	 * called by WordPress.
	 *
	 * @since 1.3
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( GEO_MASHUP_PLUGIN_NAME == $file ) {
			$links[] = '<a href="https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Getting-Started">' .
				__( 'Documentation', 'GeoMashup' ) . '</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=11045324">' .
				__( 'Donate', 'GeoMashup' ) . '</a>';
		}
		return $links;
	}

	/**
	 * WordPress action to produce the Geo Mashup Options admin page.
	 *
	 * Called by the WordPress admin.
	 * 
	 * @since 1.0
	 */
	public static function options_page() {
		include_once( path_join( GEO_MASHUP_DIR_PATH, 'options.php' ) );
		geo_mashup_options_page();
	}

	/**
	 * Get the location of the current loop object, if any.
	 *
	 * Does not work in non-global loops, such as are made with WP_Query.
	 *
	 * @since 1.3
	 *
	 * @param string $output ARRAY_A | ARRAY_N | OBJECT
	 * @param string $object_name Kind of object we're looking for, 'post', 'user', 'comment'.
	 * @return object|bool Location object or false if none.
	 */
	public static function current_location( $output = OBJECT, $object_name = '' ) {
		global $in_comment_loop, $in_user_loop, $user;

		$location = false;

		// Find the context
		if ( $object_name == 'comment' ) {
			$object_id =  get_comment_ID();
		} else if ( $object_name == 'user' ) {
			$object_id = $user->ID;
		} else if ( $object_name == 'post' ) {
			$object_id = get_the_ID();
		} else if ( $in_comment_loop ) {
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
			
		if ( $object_name && $object_id )
			$location = GeoMashupDB::get_object_location( $object_name, $object_id, $output );

		return $location;
	}

	/**
	 * Look at global loops first, then try to guess a current location if needed.
	 *
	 * @uses current_location()
	 *
	 * @param string $output ARRAY_A | ARRAY_N | OBJECT
	 * @param string $object_name Kind of object we're looking for, 'post', 'user', 'comment'.
	 * @return object|bool Location object or false if none.
	 */
	public static function current_location_guess( $output = OBJECT, $object_name = '' ) {
		global $post, $comment, $user;

		$location = self::current_location( $output, $object_name );
		if ( !$location ) {
			if ( $post and !in_array( $object_name, array( 'comment', 'user' ) ) )
				$location = GeoMashupDB::get_object_location( 'post', $post->ID, $output );
			if ( !$location and $comment and !in_array( $object_name, array( 'post', 'user' ) ) )
				$location = GeoMashupDB::get_object_location( 'comment', $comment->comment_ID, $output );
			if ( !$location and $user and !in_array( $object_name, array( 'post', 'comment' ) ) )
				$location = GeoMashupDB::get_object_location( 'user', $user->ID, $output );
		}
		return $location;
	}

	/**
	 * A template tag to insert location information.
	 *
	 * @since 1.3
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string The information requested, empty string if none.
	 */
	public static function location_info( $args = '' ) {
		/** @var $fields string|array  */
		/** @var $separator string  */
		/** @var $format string  */
		/** @var $object_name string  */
		/** @var $object_id int  */
		$defaults = array(
			'fields' => 'address', 
			'separator' => ',', 
			'format' => '',
			'object_name' => null, 
			'object_id' => null );
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );
		$info = '';

		if ( $object_name && $object_id )
			$location = GeoMashupDB::get_object_location( $object_name, $object_id, ARRAY_A );
		else
			$location = self::current_location_guess( ARRAY_A, $object_name );

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
	public static function post_link($option_args = '') {
		return self::show_on_map_link($option_args);
	}

	/**
	 * A template tag to return an URL for the current location on the 
	 * global map page. 
	 *
	 * @since 1.3
	 * @todo What would happen if the global map is not a post map?
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string The URL, empty if no current location is found.
	 */
	public static function show_on_map_link_url( $args = null ) {
		global $geo_mashup_options;

		$defaults = array( 'zoom' => '' );
		$args = wp_parse_args( $args, $defaults );

		$args = array_filter( $args );

		$url = '';
		$location = self::current_location_guess();

		if ( $location ) {
			$url = get_page_link($geo_mashup_options->get('overall', 'mashup_page'));

			if ( !$url )
				return '';

			$args['center_lat'] = $location->lat;
			$args['center_lng'] = $location->lng;

			if ( $geo_mashup_options->get( 'global_map', 'auto_info_open' ) == 'true' )
				$args['open_object_id'] = $location->object_id;

			$url = htmlentities( add_query_arg( $args, $url ) );
		}
		return $url;
	}

	/**
	 * A template tag to insert a link to the current location post on the 
	 * global map page. 
	 *
	 * @since 1.3
	 *
	 * @param string|array $args Tag arguments.
	 * @return string The link HTML, empty if no current location is found.
	 */
	public static function show_on_map_link( $args = null ) {
		$defaults = array( 'text' => __( 'Show on map', 'GeoMashup' ),
			 'display' => false,
			 'zoom' => '',
			 'show_icon' => true );
		$options = wp_parse_args($args, $defaults);
		$link = '';
		$url = self::show_on_map_link_url( array_intersect_key( $options, array( 'zoom' => true ) ) );
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
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#visible-posts-list
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	public static function visible_posts_list($args = null) {
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
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#list-located-posts
	 *
	 * @param string|array $option_args Template tag arguments.
	 * @return string List HTML.
	 */
	public static function list_located_posts( $option_args = null ) {
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
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#list-located-posts-by-area
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string List HTML.
	 */
	public static function list_located_posts_by_area( $args ) {
		static $instance_count = 1;
		
		$args = wp_parse_args( $args );

		if ( $instance_count > 1 )
			$id_suffix = '-' . $instance_count;
		else
			$id_suffix = '';

		$list_html = '<div id="gm-area-list' . $id_suffix . '" class="gm-area-list">';

		$countries = GeoMashupDB::get_distinct_located_values( 'country_code', array( 'object_name' => 'post' ) );
		$country_count = count( $countries );
		$country_heading = '';
		foreach ( $countries as $country ) {
			if ( $country_count > 1 ) {
				$country_name = GeoMashupDB::get_administrative_name( $country->country_code ); 
				$country_name = $country_name ? $country_name : $country->country_code;
				$country_heading = '<h3 id="' . $country->country_code . $id_suffix . '">' . $country_name . '</h3>';
			}

			$states = GeoMashupDB::get_distinct_located_values( 
				'admin_code', 
				array( 'country_code' => $country->country_code, 'object_name' => 'post' ) 
			);
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
						$list_html .= '<h4 id="' . $country->country_code . '-' . $state->admin_code . $id_suffix . '">' . $state_name . '</h4>';
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
	 * List nearby items.
	 *
	 * Returns an HTML list of objects near the current reference, the current post by default.
	 *
	 * @since 1.5
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#nearby-list
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string List HTML.
	 */
	 public static function nearby_list( $args = '' ) {

		if ( ! class_exists( 'GeoMashupSearch' ) )
			return __( 'Enable the geo search widget in the Geo Mashup settings to power the nearby list!', 'GeoMashup' );

		$default_args = array(
			'template' => 'nearby-list',
			'object_name' => 'post',
			'radius' => '50',
	 	);
		$args = wp_parse_args( $args, $default_args );
		$template = $args['template'];
		unset( $args['template'] );
		
		if ( !isset( $args['near_lat'] ) and !isset( $args['location_text'] ) ) {

			// Look near an object
			if ( isset( $args['object_id'] ) ) {

				// We were given an ID
				$object_id = $args['object_id'];
				unset( $args['object_id'] );

			} else {

				// Use the current loop ID
				$object_id = get_the_ID();

			}

			// Use the reference object location
			$near_location = GeoMashupDB::get_object_location( $args['object_name'], $object_id );
			if ( $near_location ) {
				$args['near_lat'] = $near_location->lat;
				$args['near_lng'] = $near_location->lng;
				if ( empty( $args['exclude_object_ids'] ) )
					$args['exclude_object_ids'] = $object_id;
				else
					$args['exclude_object_ids'] .= ',' . $object_id;
			}
		}

		$geo_search = new GeoMashupSearch( $args );
		ob_start();
		$geo_search->load_template( $template );
		return ob_get_clean();
	 }

	/**
	 * Post coordinates template tag.
	 *
	 * Get the coordinates of the current post. 
	 *
	 * @since 1.0
	 * @link http://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#post-coordinates
	 * @deprecated 1.3 Use GeoMashup::current_location()
	 *
	 * @param int $places Maximum number of decimal places to use.
	 * @return array Array containing 'lat' and 'lng' keys.
	 */
	public static function post_coordinates($places = 10) {
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
	 * WordPress action to emit GeoRSS namespace.
	 *
	 * rss_ns {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Feed_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 */
	public static function rss_ns() {
		echo 'xmlns:georss="http://www.georss.org/georss" ';
	}

	/**
	 * WordPress action to emit GeoRSS tags.
	 *
	 * rss_item {@link http://codex.wordpress.org/Plugin_API/Action_Reference#Feed_Actions action}
	 * called by WordPress.
	 *
	 * @since 1.0
	 */
	public static function rss_item() {
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
	 * @link https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference#tabbed-term-index
	 * @deprecated Use GeoMashup::tabbed_term_index()
	 *
	 * @param string|array $args Template tag arguments.
	 * @return string Placeholder HTML.
	 */
	public static function tabbed_category_index( $args ) {
		return self::tabbed_term_index( $args );
	}

	/**
	 * Tabbed term index template tag.
	 */
	public static function tabbed_term_index( $args ) {
		$args = wp_parse_args($args);

		$id = 'gm-map-1';
		if ( !empty( $args['for_map'] ) ) 
			$id = $args['for_map'];

		if ( !empty( $args['taxonomy'] ) ) 
			$id .= '-' . esc_attr ( $args['taxonomy'] );

		$id .= '-tabbed-index';

		$classes = array();

		if ( !empty( $args['show_inactive_tab_markers'] ) && 'false' != $args['show_inactive_tab_markers'] )
			$classes[] = 'show-inactive-tab-markers';

		if ( !empty( $args['start_tab_term'] ) ) 
			$classes[] = 'start-tab-term-' . absint( $args['start_tab_term'] );

		if ( !empty( $args['tab_index_group_size'] ) ) 
			$classes[] = 'tab-index-group-size-' . absint( $args['tab_index_group_size'] );

		if ( !empty( $args['disable_tab_auto_select'] ) && 'false' != $args['disable_tab_auto_select'] )
			$classes[] = 'disable-tab-auto-select';

		return '<div id="' . $id . '" class="' . implode( ' ', $classes ) . '"></div>';
	}
} // class GeoMashup
GeoMashup::load();
} // class exists
