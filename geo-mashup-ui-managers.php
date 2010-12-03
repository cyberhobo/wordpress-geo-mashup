<?php
/** 
 * Geo Mashup "core" implementation of location management user interfaces.
 *
 * Theoretically, everything done here could be done in a separate plugin.
 *
 * @package GeoMashup
 */

/**
 * A base class for managing user interfaces for collecting and storing location.
 *
 * This could be extended to make the existing editor work for new objects in a separate plugin.
 *
 * @package GeoMashup
 * @since 1.3
 * @access public
 */
class GeoMashupUIManager {
	/**
	 * Retrieve a single instaniated subclass by name.
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param string $name The class name of the manager.
	 * @return GeoMashupUIManager The singleton object.
	 */
	function &get_instance( $name ) {
		static $instances = array();

		if ( ! isset( $instances[$name] ) ) {
			$instances[$name] = new $name();
		}
		return $instances[$name];
	}

	/**
	 * Queue UI styles to match the jQuery version.
	 * 
	 * @since 1.3
	 * @access public
	 */
	function enqueue_jquery_styles() {
		wp_enqueue_style( 'jquery-smoothness', trailingslashit( GEO_MASHUP_URL_PATH ) . 'jquery-ui.1.7.smoothness.css', false, GEO_MASHUP_VERSION, 'screen' );
	}

	/**
	 * Queue styles and scripts for the location editor form.
	 *
	 * @since 1.3
	 * @access public
	 */
	function enqueue_form_client_items() {	
		global $geo_mashup_options, $geo_mashup_custom;

		wp_enqueue_style( 
			'geo-mashup-edit-form', 
			path_join( GEO_MASHUP_URL_PATH, 'edit-form.css' ), 
			false, 
			GEO_MASHUP_VERSION, 
			'screen' 
		);

		wp_register_script( 'mxn', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.js' ), null, GEO_MASHUP_VERSION );
		wp_register_script( 'mxn-core', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.core.js' ), array( 'mxn' ), GEO_MASHUP_VERSION );

		$map_api = $geo_mashup_options->get( 'overall', 'map_api' );
		$ajax_nonce = wp_create_nonce('geo-mashup-ajax-edit');
		$ajax_url = admin_url( 'admin-ajax.php' );
		$geo_mashup_url_path = GEO_MASHUP_URL_PATH;
		wp_localize_script( 'mxn-core', 'geo_mashup_location_editor_settings', compact( 'map_api', 'ajax_url', 'geo_mashup_url_path' ) );
		$required_scripts = array( 'jquery');
		if ( 'google' == $map_api ) {
			wp_register_script( 'google-maps-2', 'http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=' . 
				$geo_mashup_options->get( 'overall', 'google_key' ) );
			wp_register_script( 'mxn-google-2', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.google.core.js' ), array( 'mxn-core', 'google-maps-2' ), GEO_MASHUP_VERSION );
			wp_register_script( 'mxn-google-2-gm', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.google.geo-mashup.js' ), array( 'mxn-google-2' ), GEO_MASHUP_VERSION );
			$required_scripts[] = 'mxn-google-2-gm';
		} else if ( 'googlev3' == $map_api ) {
			wp_register_script( 'google-maps-3', 'http://maps.google.com/maps/api/js?sensor=false' );
			wp_register_script( 'mxn-google-3', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.googlev3.core.js' ), array( 'mxn-core', 'google-maps-3' ), GEO_MASHUP_VERSION );
			wp_register_script( 'mxn-google-3-gm', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.googlev3.geo-mashup.js' ), array( 'mxn-google-3' ), GEO_MASHUP_VERSION );
			$required_scripts[] = 'mxn-google-3-gm';
		} else if ( 'openlayers' == $map_api ) {
			wp_register_script( 'openlayers', 'http://openlayers.org/api/OpenLayers.js', null, 'latest' );
			wp_register_script( 'openstreetmap', 'http://www.openstreetmap.org/openlayers/OpenStreetMap.js', array( 'openlayers' ), 'latest' );
			wp_register_script( 'mxn-openlayers', path_join( GEO_MASHUP_URL_PATH, 'mxn/mxn.openlayers.core.js' ), array( 'mxn-core', 'openstreetmap' ), GEO_MASHUP_VERSION );
			$required_scripts[] = 'mxn-openlayers';
		}

		wp_enqueue_script( 
			'geo-mashup-location-editor', 
			path_join( GEO_MASHUP_URL_PATH, 'geo-mashup-location-editor.js' ), 
			$required_scripts, 
			GEO_MASHUP_VERSION 
		);

		wp_enqueue_script( 'jquery-ui-datepicker', trailingslashit( GEO_MASHUP_URL_PATH ) . 'jquery-ui.1.7.datepicker.min.js', array( 'jquery', 'jquery-ui-core'), '1.7' );

		if ( isset( $geo_mashup_custom ) ) {
			$custom_url = $geo_mashup_custom->file_url( 'location-editor.js' );
			if ( ! empty( $custom_url ) ) {
				wp_enqueue_script( 'geo-mashup-location-editor-custom', $custom_url, array( 'geo-mashup-location-editor' ) );
			}
		}
	}

	/**
	 * Determine the appropriate action from posted data.
	 * 
	 * @since 1.3
	 * @access private
	 */
	function get_submit_action() {

		$action = null;

		if ( isset( $_POST['geo_mashup_add_location'] ) or isset( $_POST['geo_mashup_update_location'] ) ) {

			// Clients without javascript may need server side geocoding
			if ( ! empty( $_POST['geo_mashup_search'] ) and isset( $_POST['geo_mashup_no_js'] ) and 'true' == $_POST['geo_mashup_no_js'] ) {

				$action = 'geocode';

			} else {

				$action = 'save';

			}

		} else if ( isset( $_POST['geo_mashup_changed'] ) and 'true' == $_POST['geo_mashup_changed'] and ! empty( $_POST['geo_mashup_location'] ) ) {

			// The geo mashup submit button wasn't used, but a change was made and the post saved
			$action = 'save';
				 
		} else if ( isset( $_POST['geo_mashup_delete_location'] ) ) {

			$action = 'delete';

		} else if ( ! empty( $_POST['geo_mashup_location_id'] ) and empty( $_POST['geo_mashup_location'] ) ) {

			// There was a location, but it was cleared before this save
			$action = 'delete';

		}
		return $action;
	}

	/**
	 * Save an object location from data posted by the location editor.
	 * 
	 * @since 1.3
	 * @access public
	 * @uses GeoMashupDB::set_object_location()
	 * @uses GeoMashupDB::delete_location()
	 *
	 * @param string $object_name The name of the object being edited.
	 * @param string $object_id The ID of the object being edited.
	 * @return bool|WP_Error True or a WordPress error.
	 */
	function save_posted_object_location( $object_name, $object_id ) {

		// Check the nonce
		if ( empty( $_POST['geo_mashup_nonce'] ) || !wp_verify_nonce( $_POST['geo_mashup_nonce'], 'geo-mashup-edit' ) ) {
			return new WP_Error( 'invalid_request', __( 'Object location not saved - invalid request.', 'GeoMashup' ) );
		}
		
		$action = $this->get_submit_action();

		if ( 'save' == $action or 'geocode' == $action ) {

			$date_string = $_POST['geo_mashup_date'] . ' ' . $_POST['geo_mashup_hour'] . ':' . 
				$_POST['geo_mashup_minute'] . ':00';
			$geo_date = date( 'Y-m-d H:i:s', strtotime( $date_string ) );

			$post_location = array();
			// If PHP has added slashes, WP will do it again before saving
			$post_location['saved_name'] = stripslashes( $_POST['geo_mashup_location_name'] );

			if ( 'geocode' == $action ) {

				$status = GeoMashupDB::geocode( $_POST['geo_mashup_search'], $post_location );
				if ( $status != 200 ) {
					$post_location = array();
				}

			} else {

				if ( ! empty( $_POST['geo_mashup_select'] ) ) {
					$selected_items = explode( '|', $_POST['geo_mashup_select'] );
					$post_location = intval( $selected_items[0] );
				} else { 
					$post_location['id'] = $_POST['geo_mashup_location_id'];
					list( $lat, $lng ) = split( ',', $_POST['geo_mashup_location'] );
					$post_location['lat'] = trim( $lat );
					$post_location['lng'] = trim( $lng );
					$post_location['geoname'] = $_POST['geo_mashup_geoname'];
					$post_location['address'] = stripslashes( $_POST['geo_mashup_address'] );
					$post_location['postal_code'] = $_POST['geo_mashup_postal_code'];
					$post_location['country_code'] = $_POST['geo_mashup_country_code'];
					$post_location['admin_code'] = $_POST['geo_mashup_admin_code'];
					$post_location['sub_admin_code'] = $_POST['geo_mashup_sub_admin_code'];
					$post_location['locality_name'] = $_POST['geo_mashup_locality_name'];
				}
			}
			
			if ( ! empty( $post_location ) ) {
				$error = GeoMashupDB::set_object_location( $object_name, $object_id, $post_location, true, $geo_date );
				if ( is_wp_error( $error ) ) 
					return $error;
			}

		} else if ( 'delete' == $action ) {

			$error = GeoMashupDB::delete_object_location( $object_name, $object_id );
			if ( is_wp_error( $error ) ) 
				return $error;

		} 

		return true;
	}
}

/**
 * A manager for user location user interfaces.
 *
 * Singleton instantiated immediately.
 *
 * @package GeoMashup
 * @since 1.3
 * @access public
 */
class GeoMashupUserUIManager extends GeoMashupUIManager {
	/**
	 * Get the single instance of this class.
	 * 
	 * @since 1.3
	 * @access public
	 * @static
	 * @uses parent::get_instance()
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	function get_instance() {
		return parent::get_instance( 'GeoMashupUserUIManager' );
	}

	/**
	 * PHP4 Constructor
	 *
	 * @since 1.3
	 * @access private
	 */
	function GeoMashupUserUIManager() {
		// Global $geo_mashup_options is available, but a better pattern might
		// be to wait until init to be sure
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Initialize for use in relevant requests.
	 *
	 * init {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @global array $geo_mashup_options 
	 * @global string $pagenow The WordPress-supplied requested filename.
	 * @uses apply_filters geo_mashup_load_user_editor Returns a boolean that loads the editor when true.
	 */
	function init() {
		global $geo_mashup_options, $pagenow;

		// Enable this interface when the option is set and we're on a destination page
		$enabled = is_admin() &&
			$geo_mashup_options->get( 'overall', 'located_object_name', 'user' ) == 'true' &&
			preg_match( '/(user-edit|user-new|profile).php/', $pagenow );
		$enabled = apply_filters( 'geo_mashup_load_user_editor', $enabled );

		// If enabled, register all the interface elements
		if ( $enabled ) { 

			// Form generation
			add_action( 'show_user_profile', array( &$this, 'print_form' ) );
			add_action( 'edit_user_profile', array( &$this, 'print_form' ) );
			// MAYBEDO: add location to registration page?

			// Form processing
			add_action( 'personal_options_update', array( &$this, 'save_user'));
			add_action( 'edit_user_profile_update', array( &$this, 'save_user'));

			$this->enqueue_jquery_styles();
			$this->enqueue_form_client_items();
		}
	}

	/**
	 * Print the user location editor form.
	 * 
	 * @since 1.3
	 * @access public
	 * @uses edit-form.php
	 */
	function print_form() {
		global $user_id;

		include_once( GEO_MASHUP_DIR_PATH . '/edit-form.php');
		if ( isset( $_GET['user_id'] ) ) {
			$object_id = $_GET['user_id'];
		} else {
			$object_id = $user_id;
		}
		echo '<h3>' . __( 'Location', 'GeoMashup' ) . '</h3>';
		geo_mashup_edit_form( 'user', $object_id, get_class( $this ) );
	}

	/**
	 * Save a posted user location.
	 * 
	 * @since 1.3
	 * @access public
	 * @uses parent::save_posted_object_location()
	 *
	 * @param id $user_id 
	 * @return bool|WP_Error
	 */
	function save_posted_object_location( $user_id ) {
		return parent::save_posted_object_location( 'user', $user_id );
	}

	/**
	 * When a user is saved, also save any posted location.
	 *
	 * save_user {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @return bool|WP_Error 
	 */
	function save_user() {
		if ( empty( $_POST['user_id'] ) ) {
			return false;
		}

		$user_id = $_POST['user_id'];

		if ( !is_numeric( $user_id ) ) {
			return $user_id;
		}

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return $user_id;
		}

		return $this->save_posted_object_location( $user_id );
	}
}

// Instantiate
GeoMashupUserUIManager::get_instance();

/**
 * A manager for post/page location user interfaces.
 *
 * Singleton instantiated immediately.
 *
 * @package GeoMashup
 * @since 1.3
 * @access public
 */
class GeoMashupPostUIManager extends GeoMashupUIManager {
	/**
	 * Location found in geo_mashup_save_location shortcode.
	 * 
	 * @since 1.3
	 * @var array
	 * @access private
	 */
	var $inline_location;

	/**
	 * Get the single instance of this class.
	 * 
	 * @since 1.3
	 * @access public
	 * @static
	 * @uses parent::get_instance()
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	function get_instance() {
		return parent::get_instance( 'GeoMashupPostUIManager' );
	}

	/**
	 * PHP4 Constructor
	 *
	 * @since 1.3
	 * @access private
	 */
	function GeoMashupPostUIManager() {
		// Global $geo_mashup_options is available, but a better pattern might
		// be to wait until init to be sure
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Initialize for use in relevant post editing requests.
	 *
	 * init {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @global array $geo_mashup_options 
	 * @global string $pagenow The WordPress-supplied requested filename.
	 * @uses apply_filters geo_mashup_load_location_editor Returns a boolean that loads the editor when true.
	 */
	function init() {
		global $geo_mashup_options;

		// Uploadable geo content type expansion always enabled
		add_filter( 'upload_mimes', array( &$this, 'upload_mimes' ) );

		// Queue inline location handlers - these could be used in nearly any request

		// Pre-save filter checks saved content for inline location tags
		add_filter( 'content_save_pre', array( &$this, 'content_save_pre') );

		// Save post handles both inline and form processing
		add_action( 'save_post', array( &$this, 'save_post'), 10, 2 );

		// Browser upload processing
		add_filter( 'wp_handle_upload', array( &$this, 'wp_handle_upload' ) );

		// Enable front or back end ajax edits
		add_action( 'wp_ajax_nopriv_geo_mashup_edit', array( 'GeoMashup', 'ajax_edit' ) );
		add_action( 'wp_ajax_geo_mashup_edit', array( 'GeoMashup', 'ajax_edit' ) );

		// Form generation
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		// Queue scripts later, when we can determine post type, front or back end
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	/**
	 * Queue scripts if the post type is enabled.
	 *
	 * Monitor for checking post type: http://core.trac.wordpress.org/ticket/14886
	 *
	 * @since 1.4
	 * @access private
	 * @uses apply_filters geo_mashup_load_location_editor intended for enabling a front end interface
	 *
	 * @global array $geo_mashup_options
	 * @global string $pagenow
	 * @global object $post
	 */
	function enqueue_scripts() {
		global $geo_mashup_options, $pagenow, $post;

		// Must be editing a post
		if ( empty( $post ) )
			return null;

		$load_location_editor = (
				is_admin() and
				preg_match( '/(post|page)(-new|).php/', $pagenow ) and
				in_array( $post->post_type, $geo_mashup_options->get( 'overall', 'located_post_types' ) )
				);
		$load_location_editor = apply_filters( 'geo_mashup_load_location_editor', $load_location_editor );

		// If we're on a post editing page, queue up the form interface elements
		if ( $load_location_editor ) {

			$this->enqueue_jquery_styles();
			$this->enqueue_form_client_items();

		} else if ( 'async-upload.php' === $pagenow ) {

			// Flash upload display
			add_filter( 'media_meta', array( &$this, 'media_meta' ), 10, 2 );

		} else if ( 'upload.php' === $pagenow ) {

			// Browser upload display
			add_action( 'admin_print_scripts', array( &$this, 'admin_print_scripts' ) );

		}
	}

	/**
	 * Add a location meta box to the post editors.
	 * 
	 * admin_menu {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by Wordpress.
	 *
	 * @since 1.3
	 * @access private
	 */
	function admin_menu() {
		global $geo_mashup_options;
		// Not adding a menu, but at this stage add_meta_box is defined, so we can add the location form
		foreach ( $geo_mashup_options->get( 'overall', 'located_post_types' ) as $post_type ) {
			add_meta_box( 'geo_mashup_post_edit', __( 'Location', 'GeoMashup' ), array( &$this, 'print_form' ), $post_type, 'advanced' );
		}
	}

	/**
	 * Print the post editor form.
	 * 
	 * @since 1.3
	 * @access public
	 * @uses edit-form.php
	 */
	function print_form() {
		global $post_ID;

		include_once( GEO_MASHUP_DIR_PATH . '/edit-form.php');
		geo_mashup_edit_form( 'post', $post_ID, get_class( $this ) );
	}

	/**
	 * Save a posted post or page location.
	 * 
	 * @since 1.3
	 * @access public
	 * @uses parent::save_posted_object_location()
	 *
	 * @param id $post_id 
	 * @return bool|WP_Error
	 */
	function save_posted_object_location( $post_id ) {
		return parent::save_posted_object_location( 'post', $post_id );
	}

	/**
	 * When a post is saved, save any posted location for it.
	 * 
	 * save_post {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @uses GeoMashupDB::set_object_location()
	 *
	 * @param id $post_id 
	 * @param object $post 
	 * @return bool|WP_Error
	 */
	function save_post($post_id, $post) {
		if ( 'revision' == $post->post_type ) {
			return;
		}

		// WP has already saved the post - allow location saving without added capability checks

		if ( !empty( $this->inline_location ) ) {
			GeoMashupDB::set_object_location( 'post', $post_id, $this->inline_location );
			$this->inline_location = null;
		}

		update_option('geo_mashup_temp_kml_url','');

		return $this->save_posted_object_location( $post_id );
	}

	/**
	 * Extract inline save location shortcodes from post content before it is saved.
	 *
	 * content_save_pre {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by Wordpress.
	 * 
	 * @since 1.3
	 * @access private
	 */
	function content_save_pre( $content ) {
		// Piggyback on the shortcode interface to find inline tags [geo_mashup_save_location ...] 
		add_shortcode( 'geo_mashup_save_location', 'is_null' );
		$pattern = get_shortcode_regex( );
		return preg_replace_callback('/'.$pattern.'/s', array( &$this, 'replace_save_pre_shortcode' ), $content);
	}

	/**
	 * Store the inline location from a save location shortcode before it is removed.
	 * 
	 * @since 1.3
	 * @access private
	 *
	 * @param array $shortcode_match 
	 * @return The matched content, or an empty string if it was a save location shortcode.
	 */
	function replace_save_pre_shortcode( $shortcode_match ) {
		$content = $shortcode_match[0];
		$tag_index = array_search( 'geo_mashup_save_location',  $shortcode_match ); 
		if ( $tag_index !== false ) {
			// There is an inline location - save the attributes
			$this->inline_location = shortcode_parse_atts( stripslashes( $shortcode_match[$tag_index+1] ) );

			// If lat and lng are missing, try to geocode based on address
			$status = 200;
			if ( ( empty( $this->inline_location['lat'] ) or empty( $this->inline_location['lng'] ) ) and !empty( $this->inline_location['address'] ) ) {
				$query = $this->inline_location['address'];
				$status = GeoMashupDB::geocode( $query, $this->inline_location );
				// Check for the "too many queries" statuses
				if ( $status == 604 or $status == 620 ) {
					// Delay and try again
					sleep( 1 );
					$status = GeoMashupDB::geocode( $query, $this->inline_location );
				}
			}

			if ( 200 == $status ) {
				// Remove the tag
				$content = '';
			}
		} 
		return $content;
	}

	/**
	 * Add Flash-uploaded KML to the location editor map.
	 *
	 * media_meta {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 */
	function media_meta( $content, $post ) {
		// Only chance to run some javascript after a flash upload?
		if (strlen($post->guid) > 0) {
			$content .= '<script type="text/javascript"> ' .
				'if (parent.GeoMashupLocationEditor) parent.GeoMashupLocationEditor.loadKml(\''.$post->guid.'\');' .
				'</script>';
		}
		return $content;
	}

	/**
	 * Add Browser-uploaded KML to the location editor map.
	 *
	 * admin_print_scripts {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 */
	function admin_print_scripts( $not_used ) {
		// Load any uploaded KML into the search map - only works with browser uploader
		
		// See if wp_upload_handler found uploaded KML
		$kml_url = get_option( 'geo_mashup_temp_kml_url' );
		if (strlen($kml_url) > 0) {
			// Load the KML in the location editor
			echo '
				<script type="text/javascript"> 
					if (parent.GeoMashupLocationEditor) parent.GeoMashupLocationEditor.loadKml(\'' . $kml_url . '\');
				</script>';
			update_option( 'geo_mashup_temp_kml_url', '' );
		}
	}

	/**
	 * Add geo mime types to allowable uploads.
	 * 
	 * upload_mimes {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 */
	function upload_mimes( $mimes ) {
		$mimes['kml'] = 'application/vnd.google-earth.kml+xml';
		$mimes['kmz'] = 'application/vnd.google-earth.kmz';
		$mimes['gpx'] = 'application/octet-stream';
		return $mimes;
	}

	/**
	 * If an upload is KML, put the URL in an option to be loaded in the response 
	 * 
	 * wp_handle_upload {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 */
	function wp_handle_upload( $args ) {
		// TODO: use transient API instead of option
		update_option( 'geo_mashup_temp_kml_url', '' );
		if ( is_array( $args ) && isset( $args['file'] ) ) {
			if ( stripos( $args['file'], '.kml' ) == strlen( $args['file'] ) - 4 ) {
				update_option( 'geo_mashup_temp_kml_url', $args['url'] );
			}
		}
		return $args;
	}
}

// Instantiate
GeoMashupPostUIManager::get_instance();

/**
 * A manager for comment location user interfaces.
 *
 * Singleton instantiated immediately.
 *
 * @package GeoMashup
 * @since 1.3
 * @access public
 */
class GeoMashupCommentUIManager {
	/**
	 * Get the single instance of this class.
	 * 
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new GeoMashupCommentUIManager();
		}
		return $instance;
	}

	/**
	 * PHP4 Constructor
	 *
	 * @since 1.3
	 * @access private
	 */
	function GeoMashupCommentUIManager() {
		// Global $geo_mashup_options is available, but a better pattern might
		// be to wait until init to be sure
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Initialize for use in relevant requests.
	 *
	 * init {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @global array $geo_mashup_options 
	 * @uses apply_filters geo_mashup_load_comment_editor Returns a boolean that loads the editor when true.
	 */
	function init() {
		global $geo_mashup_options;

		$load_comment_editor = ( !is_admin() && $geo_mashup_options->get( 'overall', 'located_object_name', 'comment' ) == 'true' ); 
		$load_comment_editor = apply_filters( 'geo_mashup_load_comment_editor', $load_comment_editor );

		// If enabled, register all the interface elements
		if ( $load_comment_editor ) {

			// Form generation
			add_action( 'comment_form', array( &$this, 'print_form' ) );

			// Form processing
			add_action( 'comment_post', array( &$this, 'save_comment'), 10, 2 );

			// Google JSAPI provides client location by IP
			wp_enqueue_script( 'google-jsapi' );
			wp_enqueue_script( 'geo-mashup-loader' );
		}
	}

	/**
	 * Print the comment location editor form.
	 *
	 * @since 1.3
	 * @access public
	 */
	function print_form()
	{
		// If there's a logged in user with a location, use that as a default.
		// The client-side location will override it if available
		$default_lat = $default_lng = '';
		$user = wp_get_current_user();
		if ( $user ) {
			$location = GeoMashupDB::get_object_location( 'user', $user->ID );
			if ( $location ) {
				$default_lat = $location->lat;
				$default_lng = $location->lng;
			}
		}

		// Print the form
		$input_format = '<input id="geo_mashup_%s_input" name="comment_location[%s]" type="hidden" value="%s" />';
		printf( $input_format, 'lat', 'lat', $default_lat );
		printf( $input_format, 'lng', 'lng', $default_lng );
		printf( $input_format, 'country_code', 'country_code', '' );
		printf( $input_format, 'locality_name', 'locality_name', '' );
		printf( $input_format, 'address', 'address', '' );
	}

	/**
	 * When a comment is saved, save any posted location with it.
	 *
	 * save_comment {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @access private
	 * @uses GeoMashupDB::set_object_location()
	 */
	function save_comment( $comment_id = 0, $approval = '' ) {
		if ( !$comment_id || 'spam' === $approval || empty( $_POST['comment_location'] ) || !is_array( $_POST['comment_location'] ) ) {
			return false;
		}

		GeoMashupDB::set_object_location( 'comment', $comment_id, $_POST['comment_location'] );
	}
}

// Instantiate
GeoMashupCommentUIManager::get_instance();

?>
