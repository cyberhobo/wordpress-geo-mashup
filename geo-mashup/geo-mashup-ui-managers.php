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
	 *
	 * @param string $name The class name of the manager.
	 * @return GeoMashupUIPostManager|GeoMashupUIUserManager|GeoMashupUICommentManager The singleton object.
	 */
	public static function &get_instance( $name ) {
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
	 */
	public function enqueue_jquery_styles() {
		GeoMashup::register_style( 'jquery-smoothness', 'css/jquery-ui.1.7.smoothness.css', false, GEO_MASHUP_VERSION, 'screen' );
		wp_enqueue_style( 'jquery-smoothness' );
	}

	/**
	 * Queue styles and scripts for the location editor form.
	 *
	 * @since 1.3
	 */
	public function enqueue_form_client_items() {
		global $geo_mashup_options, $geo_mashup_custom;
		
		GeoMashup::register_style( 'geo-mashup-edit-form', 'css/location-editor.css', false, GEO_MASHUP_VERSION, 'screen' );
		wp_enqueue_style( 'geo-mashup-edit-form' );

		GeoMashup::register_script( 
				'mxn', 
				'js/mxn/mxn.js', 
				null, 
				GEO_MASHUP_VERSION , 
				true);
				
		GeoMashup::register_script( 
				'mxn-core', 
				'js/mxn/mxn.core.js', 
				array( 'mxn' ), 
				GEO_MASHUP_VERSION, 
				true );

		$map_api = $geo_mashup_options->get( 'overall', 'map_api' );
		$copy_geodata = $geo_mashup_options->get( 'overall', 'copy_geodata' );
		$geonames_username = $geo_mashup_options->get( 'overall', 'geonames_username' );
		$ajax_nonce = wp_create_nonce('geo-mashup-ajax-edit');
		$ajax_url = admin_url( 'admin-ajax.php' );
		$geo_mashup_url_path = GEO_MASHUP_URL_PATH;
		wp_localize_script( 'mxn-core', 'geo_mashup_location_editor_settings', compact( 'map_api', 'copy_geodata', 'ajax_url', 'geo_mashup_url_path', 'geonames_username' ) );
		$required_scripts = array( 'jquery');
		if ( 'google' == $map_api ) {
			wp_register_script( 
			  'google-maps-2', 
			  'http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=' . $geo_mashup_options->get( 'overall', 'google_key' ) . '&amp;hl=' . GeoMashup::get_language_code(),
			  null, 
			  '', 
			  true );
			 
			GeoMashup::register_script( 
				'mxn-google-2', 
				'js/mxn/mxn.google.core.js', 
				array( 'mxn-core', 'google-maps-2' ), 
				GEO_MASHUP_VERSION, 
				true );
				
			GeoMashup::register_script( 
				'mxn-google-2-gm', 
				'js/mxn/mxn.google.geo-mashup.js', 
				array( 'mxn-google-2' ), 
				GEO_MASHUP_VERSION, 
				true );
				
			$required_scripts[] = 'mxn-google-2-gm';
		} else if ( 'googlev3' == $map_api ) {
			$scheme = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' );
			wp_register_script( 
					'google-maps-3',
					$scheme .
						'://maps.google.com/maps/api/js?sensor=false&amp;language=' .
						GeoMashup::get_language_code(),
					null, 
					'', 
					true );
					
			GeoMashup::register_script( 
					'mxn-google-3', 
					'js/mxn/mxn.googlev3.core.js', 
					array( 'mxn-core', 'google-maps-3' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			GeoMashup::register_script( 
					'mxn-google-3-gm', 
					'js/mxn/mxn.googlev3.geo-mashup.js', 
					array( 'mxn-google-3' ), 
					GEO_MASHUP_VERSION, 
					true );
							
			$required_scripts[] = 'mxn-google-3-gm';
		} else if ( 'openlayers' == $map_api ) {
			wp_register_script( 
					'openlayers', 
					'http://openlayers.org/api/OpenLayers.js', 
					null, 
					'latest', 
					true );
					
			wp_register_script( 
					'openstreetmap', 
					'http://www.openstreetmap.org/openlayers/OpenStreetMap.js',
					 array( 'openlayers' ),
					'latest',
					 true );
					 
			GeoMashup::register_script( 
					'mxn-openlayers',
					'js/mxn/mxn.openlayers.core.js', 
					array( 'mxn-core', 'openstreetmap' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			GeoMashup::register_script( 
					'mxn-openlayers-gm', 
					'js/mxn/mxn.openlayers.geo-mashup.js', 
					array( 'mxn-openlayers' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			$required_scripts[] = 'mxn-openlayers-gm';
		}

		GeoMashup::register_script( 
				'geo-mashup-location-editor', 
				'js/location-editor.js', 
				$required_scripts, 
				GEO_MASHUP_VERSION, 
				true );
				
		wp_enqueue_script( 'geo-mashup-location-editor' );

		GeoMashup::register_script( 
				'jquery-ui-datepicker', 
				'js/jquery-ui.1.7.3.datepicker.js', 
				array( 'jquery', 'jquery-ui-core'), 
				'1.7.3', 
				true );
				
		wp_enqueue_script( 'jquery-ui-datepicker' );

		if ( isset( $geo_mashup_custom ) ) {
			$custom_url = $geo_mashup_custom->file_url( 'location-editor.js' );
			if ( ! empty( $custom_url ) ) {
				wp_enqueue_script( 
					'geo-mashup-location-editor-custom', 
					$custom_url, 
					array( 'geo-mashup-location-editor' ),
					null,
					true );
			}
		}
	}

	/**
	 * Determine the appropriate action from posted data.
	 * 
	 * @since 1.3
	 */
	private function get_submit_action() {

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
	 * @uses GeoMashupDB::set_object_location()
	 * @uses GeoMashupDB::delete_location()
	 *
	 * @param string $object_name The name of the object being edited.
	 * @param string $object_id The ID of the object being edited.
	 * @return bool|WP_Error True or a WordPress error.
	 */
	public function save_posted_object_location( $object_name, $object_id ) {

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
					if ( !empty( $_POST['geo_mashup_null_fields'] ) )
						$post_location['set_null'] = $_POST['geo_mashup_null_fields'];
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
		// If geodata was manually updated but Geo Mashup location was not,
		// they may be out of sync now. Allowing that for now.
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
	 * @uses parent::get_instance()
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	public static function get_instance() {
		return parent::get_instance( 'GeoMashupUserUIManager' );
	}

	/**
	 * PHP5 Constructor
	 *
	 * @since 1.3
	 * @access private
	 */
	public function __construct() {
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
	 * @global array $geo_mashup_options 
	 * @global string $pagenow The WordPress-supplied requested filename.
	 * @uses apply_filters geo_mashup_load_user_editor Returns a boolean that loads the editor when true.
	 */
	public function init() {
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
	 * @uses edit-form.php
	 */
	public function print_form() {
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
	 * @uses parent::save_posted_object_location()
	 *
	 * @param id $user_id 
	 * @return bool|WP_Error
	 */
	public function save_posted_object_location( $user_id ) {
		return parent::save_posted_object_location( 'user', $user_id );
	}

	/**
	 * When a user is saved, also save any posted location.
	 *
	 * save_user {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @return bool|WP_Error 
	 */
	public function save_user() {
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
	 */
	private $inline_location;

	/**
	 * Get the single instance of this class.
	 * 
	 * @since 1.3
	 * @uses parent::get_instance()
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	public static function get_instance() {
		return parent::get_instance( 'GeoMashupPostUIManager' );
	}

	/**
	 * PHP5 Constructor
	 *
	 * @since 1.3
	 */
	public function __construct() {
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
	 * @global array $geo_mashup_options 
	 * @global string $pagenow The WordPress-supplied requested filename.
	 * @uses apply_filters geo_mashup_load_location_editor Returns a boolean that loads the editor when true.
	 */
	public function init() {
		global $geo_mashup_options, $pagenow;

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

		if ( 'async-upload.php' === $pagenow ) {

			// Flash upload display
			add_filter( 'media_meta', array( &$this, 'media_meta' ), 10, 2 );

		} else if ( 'upload.php' === $pagenow or 'media-upload.php' === $pagenow ) {

			// Browser upload display
			add_action( 'admin_print_scripts', array( &$this, 'admin_print_scripts' ) );

		}

	}

	/**
	 * Queue scripts if the post type is enabled.
	 *
	 * Monitor for checking post type: http://core.trac.wordpress.org/ticket/14886
	 *
	 * @since 1.4
	 * @uses apply_filters geo_mashup_load_location_editor intended for enabling a front end interface
	 *
	 * @global array $geo_mashup_options
	 * @global string $pagenow
	 * @global object $post
	 */
	public function enqueue_scripts() {
		global $geo_mashup_options, $pagenow, $post;

		// The location editor works only on posts
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

		} 
	}

	/**
	 * Add a location meta box to the post editors.
	 * 
	 * admin_menu {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by Wordpress.
	 *
	 * @since 1.3
	 */
	public function admin_menu() {
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
	 * @uses edit-form.php
	 */
	public function print_form() {
		global $post_ID;

		include_once( GEO_MASHUP_DIR_PATH . '/edit-form.php');
		geo_mashup_edit_form( 'post', $post_ID, get_class( $this ) );
	}

	/**
	 * Save a posted post or page location.
	 * 
	 * @since 1.3
	 * @uses parent::save_posted_object_location()
	 *
	 * @param id $post_id 
	 * @return bool|WP_Error
	 */
	public function save_posted_object_location( $post_id ) {
		return parent::save_posted_object_location( 'post', $post_id );
	}

	/**
	 * When a post is saved, save any posted location for it.
	 * 
	 * save_post {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @uses GeoMashupDB::set_object_location()
	 *
	 * @param id $post_id 
	 * @param object $post 
	 * @return bool|WP_Error
	 */
	public function save_post($post_id, $post) {
		if ( 'revision' == $post->post_type ) {
			return;
		}

		// WP has already saved the post - allow location saving without added capability checks

		if ( !empty( $this->inline_location ) ) {
			$geo_date = '';
			if ( isset( $this->inline_location['geo_date'] ) ) {
				$geo_date = $this->inline_location['geo_date'];
				unset( $this->inline_location['geo_date'] );
			}
			$location_id = GeoMashupDB::set_object_location( 'post', $post_id, $this->inline_location, true, $geo_date );
			if ( is_wp_error( $location_id ) ) {
				update_post_meta( $post_id, 'geo_mashup_save_location_error', $location_id->get_error_message() );
			}
			$this->inline_location = null;
		}

		delete_transient( 'gm_uploaded_kml_url' );

		return $this->save_posted_object_location( $post_id );
	}

	/**
	 * Extract inline save location shortcodes from post content before it is saved.
	 *
	 * content_save_pre {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by Wordpress.
	 * 
	 * @since 1.3
	 */
	public function content_save_pre( $content ) {
		// Piggyback on the shortcode interface to find inline tags [geo_mashup_save_location ...] 
		add_shortcode( 'geo_mashup_save_location', 'is_null' );
		$pattern = get_shortcode_regex( );
		return preg_replace_callback('/'.$pattern.'/s', array( &$this, 'replace_save_pre_shortcode' ), $content);
	}

	/**
	 * Store the inline location from a save location shortcode before it is removed.
	 * 
	 * @since 1.3
	 *
	 * @param array $shortcode_match 
	 * @return The matched content, or an empty string if it was a save location shortcode.
	 */
	public function replace_save_pre_shortcode( $shortcode_match ) {
		$content = $shortcode_match[0];
		$tag_index = array_search( 'geo_mashup_save_location',  $shortcode_match ); 
		if ( $tag_index !== false ) {
			// There is an inline location - save the attributes
			$this->inline_location = shortcode_parse_atts( stripslashes( $shortcode_match[$tag_index+1] ) );

			// If lat and lng are missing, try to geocode based on address
			$success = false;
			if ( ( empty( $this->inline_location['lat'] ) or empty( $this->inline_location['lng'] ) ) and !empty( $this->inline_location['address'] ) ) {
				$query = $this->inline_location['address'];
				$this->inline_location = GeoMashupDB::blank_object_location( ARRAY_A );
				$success = GeoMashupDB::geocode( $query, $this->inline_location );
				if ( !$success ) {
					// Delay and try again
					sleep( 1 );
					$success = GeoMashupDB::geocode( $query, $this->inline_location );
				}
			} else if ( is_numeric ( $this->inline_location['lat'] ) and is_numeric( $this->inline_location['lng'] ) ) {
				// lat and lng were supplied
				$success = true;
			}

			if ( $success ) {
				// Remove the tag
				$content = '';
			} else {
				$message = ( is_wp_error( GeoMashupDB::$geocode_error ) ? GeoMashupDB::$geocode_error->get_error_message() : __( 'Address not found - try making it less detailed', 'GeoMashup' ) );
				$content = str_replace( ']', ' geocoding_error="' . $message . '"]', $content );
				$this->inline_location = null;
			}
		} 
		return $content;
	}

	/**
	 * Add AJAX uploaded KML to the location editor map.
	 *
	 * media_meta {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 */
	public function media_meta( $content, $post ) {
		// Only chance to run some javascript after an ajax upload?
		if ( 'attachment' == $post->post_type ) {
			$url = wp_get_attachment_url( $post->ID );
			if ( '.km' == substr( $url, -4, 3 ) ) {
				$content .= '<script type="text/javascript"> ' .
					'if (\'GeoMashupLocationEditor\' in parent) parent.GeoMashupLocationEditor.loadKml(\''.$url.'\');' .
					'</script>';
			}
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
	 */
	public function admin_print_scripts( $not_used ) {
		// Load any uploaded KML into the search map - only works with browser uploader
		
		// See if wp_upload_handler found uploaded KML
		$kml_url = get_transient( 'gm_uploaded_kml_url' );
		if (strlen($kml_url) > 0) {
			// Load the KML in the location editor
			echo '
				<script type="text/javascript"> 
					if (\'GeoMashupLocationEditor\' in parent) parent.GeoMashupLocationEditor.loadKml(\'' . $kml_url . '\');
				</script>';
			delete_transient( 'gm_uploaded_kml_url' );
		}
	}

	/**
	 * Add geo mime types to allowable uploads.
	 * 
	 * upload_mimes {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 *
	 * @since 1.3
	 */
	public function upload_mimes( $mimes ) {
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
	 */
	public function wp_handle_upload( $args ) {
		delete_transient( 'gm_uploaded_kml_url' );
		if ( is_array( $args ) && isset( $args['file'] ) ) {
			if ( stripos( $args['file'], '.km' ) == strlen( $args['file'] ) - 4 ) {
				set_transient( 'gm_uploaded_kml_url', $args['url'] );
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
	 * Whether to put the comment form script in the footer.
	 *
	 * @since 1.4
	 */
	private $add_form_script = false;

	/**
	 * Get the single instance of this class.
	 * 
	 * @since 1.3
	 *
	 * @return GeoMashupPostUIManager The instance.
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new GeoMashupCommentUIManager();
		}
		return $instance;
	}

	/**
	 * PHP5 Constructor
	 *
	 * @since 1.3
	 */
	public function __construct() {
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
	 * @global array $geo_mashup_options 
	 * @uses apply_filters geo_mashup_load_comment_editor Returns a boolean that loads the editor when true.
	 */
	public function init() {
		global $geo_mashup_options;

		$load_comment_editor = ( !is_admin() && $geo_mashup_options->get( 'overall', 'located_object_name', 'comment' ) == 'true' ); 
		$load_comment_editor = apply_filters( 'geo_mashup_load_comment_editor', $load_comment_editor );

		// If enabled, register all the interface elements
		if ( $load_comment_editor ) {

			// Form generation
			add_action( 'comment_form', array( &$this, 'print_form' ) );

			// Form script
			add_action( 'wp_footer', array( &$this, 'wp_footer' ) );

			// Form processing
			add_action( 'comment_post', array( &$this, 'save_comment'), 10, 2 );

			wp_enqueue_script( 'geo-mashup-loader' );
		}
	}

	/**
	 * Print the comment location editor form.
	 *
	 * @since 1.3
	 * @access public
	 */
	public function print_form() {
		$this->add_form_script = true;

		// If there's a logged in user with a location, use that as a default.
		// The client-side location will override it if available
		$user = wp_get_current_user();
		if ( $user ) 
			$default_location = GeoMashupDB::get_object_location( 'user', $user->ID );
		if ( !$default_location )
			$default_location = GeoMashupDB::blank_object_location();
		$default_summary = ( empty( $default_location->locality_name ) ? '' : $default_location->locality_name . ', ' ) .
				( empty( $default_location->admin_code ) ? '' : $default_location->admin_code );

		// Print the form
		printf( '<label id="geo-mashup-summary-label" for="geo-mashup-summary-input" style="display:none;">%s</label>', __( 'Written from (location)', 'GeoMashup' ) );
		printf( '<input id="geo-mashup-summary-input" style="display:none;" type="text" size="25" value="%s" />', $default_summary );
		printf( '<img id="geo-mashup-busy-icon" style="display:none;" src="%s" alt="%s" />', path_join( GEO_MASHUP_URL_PATH, 'images/busy_icon.gif' ), __( 'Loading...', 'GeoMashup' ) );
		$input_format = '<input id="geo-mashup-%s-input" name="comment_location[%s]" type="hidden" value="%s" />';
		printf( $input_format, 'lat', 'lat', $default_location->lat );
		printf( $input_format, 'lng', 'lng', $default_location->lng );
	}

	/**
	 * Print the form script in the footer if it's needed.
	 *
	 * @since 1.4
	 */
	public function wp_footer() {
		global $geo_mashup_options;
		if ( $this->add_form_script ) {
			GeoMashup::register_script( 
					'geo-mashup-comment-form', 
					'js/comment-form.js', 
					array( 'jquery' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			wp_localize_script( 'geo-mashup-comment-form', 'geo_mashup_comment_form_settings', array( 'geonames_username' => $geo_mashup_options->get( 'overall', 'geonames_username' ) ) );
			wp_print_scripts( 'geo-mashup-comment-form' );
		}
	}

	/**
	 * When a comment is saved, save any posted location with it.
	 *
	 * save_comment {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 *
	 * @since 1.3
	 * @uses GeoMashupDB::set_object_location()
	 */
	public function save_comment( $comment_id = 0, $approval = '' ) {
		if ( !$comment_id || 'spam' === $approval || empty( $_POST['comment_location'] ) || !is_array( $_POST['comment_location'] ) ) {
			return false;
		}

		GeoMashupDB::set_object_location( 'comment', $comment_id, $_POST['comment_location'] );
	}
}

// Instantiate
GeoMashupCommentUIManager::get_instance();
