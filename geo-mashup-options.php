<?php
/**
 * Management of Geo Mashup saved options.
 *
 * @package GeoMashup
 */

/**
 * A singleton to manage Geo Mashup saved options.
 * 
 * @since 1.2
 * @access public
 * @package GeoMashup
 */
class GeoMashupOptions {
	/**
	 * Valid options with default values.
	 * 
	 * @since 1.2
	 * @var array
	 */
	public $default_options = array (
		'overall' => array (
			'version' => '',
			'google_key' => '',
			'googlev3_key' => '',
			'mashup_page' => '',
			'category_link_separator' => '::',
			'category_link_text' => 'map',
			'category_zoom' => 'auto',
			'add_category_links' => 'false',
			'copy_geodata' => 'false',
			'theme_stylesheet_with_maps' => 'false',
			'located_post_types' => array( 'post', 'page' ),
			'include_taxonomies' => array( 'category' ),
			'located_object_name' => array( 
				'post' => 'deprecated',
				'user' => 'false',
				'comment' => 'false' ),
			'enable_reverse_geocoding' => 'true',
			'adsense_code' => 'partner-pub-5088093001880917',
			'geonames_username' => 'geomashup',
	 		'map_api' => 'googlev3',
			'import_custom_field' => '',
			'enable_geo_search' => 'false' ),
		'global_map' => array (
			'width' => '400',
			'height' => '400',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => 'auto',
			'background_color' => 'c0c0c0',
			'term_options' => array(),
			'map_control' => 'GSmallZoomControl3D',
			'add_map_type_control' => array(),
			'add_overview_control' => 'false',
			'add_google_bar' => 'false',
			'enable_scroll_wheel_zoom' => 'true',
			'show_post' => 'false',
			'show_future' => 'false',
			'marker_select_info_window' => 'true',
			'marker_select_highlight' => 'false',
			'marker_select_center' => 'false',
			'marker_select_attachments' => 'false',
			'max_posts' => '',
			'auto_info_open' => 'true',
			'click_to_load' => 'false',
			'click_to_load_text' => '',
	 		'cluster_max_zoom' => '',
	 		'cluster_lib' => 'clustermarker'	),
		'single_map' => array (
			'width' => '400',
			'height' => '400',
			'map_control' => 'GSmallZoomControl3D',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => '11',
			'background_color' => 'c0c0c0',
			'add_overview_control' => 'false',
			'add_map_type_control' => array(),
			'add_google_bar' => 'false',
			'enable_scroll_wheel_zoom' => 'true',
			'click_to_load' => 'false',
	 		'click_to_load_text' => '' ), 
		'context_map' => array (
			'width' => '200',
			'height' => '200',
			'map_control' => 'GSmallZoomControl3D',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => 'auto',
			'background_color' => 'c0c0c0',
			'add_overview_control' => 'false',
			'add_map_type_control' => array(),
			'add_google_bar' => 'false',
			'enable_scroll_wheel_zoom' => 'true',
			'marker_select_info_window' => 'true',
			'marker_select_highlight' => 'false',
			'marker_select_center' => 'false',
			'marker_select_attachments' => 'false',
			'click_to_load' => 'false',
	 		'click_to_load_text' => '' ) );

	/**
	 * Map of old option names to new ones.
	 * 
	 * @since 1.2
	 * @var array
	 */
	private $conversions = array (
		'google_key' => array ( 'overall', 'google_key' ),
		'mashup_page' => array ( 'overall', 'mashup_page' ),
		'category_link_separator' => array ( 'overall', 'category_link_separator' ),
		'category_link_text' => array ( 'overall', 'category_link_text' ),
		'category_zoom' => array ( 'overall', 'category_zoom' ),
		'add_category_links' => array ( 'overall', 'add_category_links' ),
		'theme_stylesheet_with_maps' => array ( 'overall', 'theme_stylesheet_with_maps' ),
		'map_width' => array ( 'global_map', 'width' ),
		'map_height' => array ( 'global_map', 'height' ),
		'map_type' => array ( 'global_map', 'map_type' ),
		'zoom_level' => array ( 'global_map', 'zoom' ),
		'category_color' => array ( 'global_map', 'category_color' ),
		'category_line_zoom' => array ( 'global_map', 'category_line_zoom' ),
		'map_control' => array ( 'global_map', 'map_control' ),
		'add_map_type_control' => array ( 'global_map', 'add_map_type_control' ),
		'add_overview_control' => array ( 'global_map', 'add_overview_control' ),
		'show_post' => array ( 'global_map', 'show_post' ),
		'show_future' => array ( 'global_map', 'show_future' ),
		'max_posts' => array ( 'global_map', 'max_posts' ),
		'auto_info_open' => array ( 'global_map', 'auto_info_open' ),
		'auto_open_info_window' => array ( 'global_map', 'auto_info_open' ),
		'click_to_load' => array ( 'global_map', 'click_to_load' ),
		'click_to_load_text' => array ( 'global_map', 'click_to_load_text' ),
		'in_post_map_control' => array ( 'single_map', 'map_control' ),
		'in_post_map_width' => array ( 'single_map', 'width' ),
		'in_post_map_height' => array ( 'single_map', 'height' ),
		'in_post_map_type' => array ( 'single_map', 'map_type' ),
		'in_post_zoom_level' => array ( 'single_map', 'zoom' ),
		'in_post_add_overview_control' => array ( 'single_map', 'add_overview_control' ),
		'in_post_add_map_type_control' => array ( 'single_map', 'add_map_type_control' ),
		'in_post_click_to_load' => array ( 'single_map', 'click_to_load' ),
		'in_post_click_to_load_text' => array ( 'single_map', 'click_to_load_text' ) );

	/**
	 * Options keys whose values aren't predictable.
	 * 
	 * @since 1.2
	 * @var array
	 */
	private $freeform_option_keys = array ( 'term_options', 'add_map_type_control', 'located_post_types', 'include_taxonomies' );

	/**
	 * Valid map types.
	 * 
	 * @since 1.2
	 * @var array
	 */
	public $valid_map_types = array ( 'G_NORMAL_MAP', 'G_SATELLITE_MAP', 'G_HYBRID_MAP', 'G_PHYSICAL_MAP', 'G_SATELLITE_3D_MAP' );

	/**
	 * Saved option values.
	 *
	 * Use the GeoMashupOptions::get() method for access.
	 * 
	 * @since 1.2
	 * @var array
	 */
	private $options;

	/**
	 * Old option values that can't be converted.
	 * 
	 * @since 1.2
	 * @var array
	 */
	public $corrupt_options = '';

	/**
	 * Validation messages.
	 * 
	 * @since 1.2
	 * @var array
	 */
	public $validation_errors = array();

	/**
	 * PHP5 constructor
	 *
	 * Should be used only in this file.
	 * 
	 * @since 1.2
	 */
	function __construct() {
		$shared_google_api_key = get_option ( 'google_api_key' );
		if ( $shared_google_api_key ) {
			$this->default_options['overall']['google_key'] = $shared_google_api_key;
		}
		$this->options = $this->default_options;
		$settings = get_option ( 'geo_mashup_options' );
		if ( is_array ( $settings ) ) {
			$settings = $this->convert_old_settings ( $settings );
			$this->options = $this->valid_options ( $settings, $this->default_options, $add_missing = true );
		} else {
			if ( is_string ( $settings ) && !empty ( $settings ) ) {
				$this->corrupt_options = $settings;
			}
		}
	}

	/**
	 * Change old option names.
	 * 
	 * @since 1.2
	 *
	 * @param array $settings Existing settings.
	 * @return array Converted settings.
	 */
	private function convert_old_settings( $settings ) {
		foreach ( $this->conversions as $old_key => $new_keys ) {
			if ( isset ( $settings[$old_key] ) ) {
				$settings[$new_keys[0]][$new_keys[1]] = $settings[$old_key];
				unset ( $settings[$old_key] );
			}
		}

		// In 1.4 different post types can be located, not just posts
		if ( isset( $settings['overall']['located_object_name']['post']) and 'true' == $settings['overall']['located_object_name']['post'] ) {
			$settings['overall']['located_object_name']['post'] = 'deprecated';
			if ( empty( $settings['overall']['located_post_types']['post'] ) )
				$settings['overall']['located_post_types'] = array( 'post', 'page' );
		}

		// In 1.5 we set options for any taxonomy, not just category
		if ( isset( $settings['global_map']['category_color'] ) ) { 
			$settings['global_map']['term_options']['category']['color'] = $settings['global_map']['category_color']; 
			unset( $settings['global_map']['category_color'] );
		}
		if ( isset( $settings['global_map']['category_line_zoom'] ) ) {
			$settings['global_map']['term_options']['category']['line_zoom'] = $settings['global_map']['category_line_zoom']; 
			unset( $settings['global_map']['category_line_zoom'] );
		}
		return $settings;
	}

	/**
	 * Write current values to the database.
	 * 
	 * @since 1.2
	 *
	 * @return bool Success or failure.
	 */
	public function save() {
		$saved = false;
		if ($this->options == $this->valid_options ( $this->options ) ) {
			$saved = update_option('geo_mashup_options', $this->options);
			
			// Share our Google API key
			$google_api_key = $this->options['overall']['google_key'];
			if ( !empty ( $google_api_key ) && !get_option ( 'google_api_key' ) ) {
				update_option( 'google_api_key', $google_api_key );
			}
		}
		return $saved;
	}

	/**
	 * Get a saved option value.
	 * 
	 * <code>
	 * $single_map_options_array = $geo_mashup_options->get( 'single_map' );
	 * $google_key = $geo_mashup_options->get( 'overall', 'google_key' );
	 * $add_global_satellite_map = $geo_mashup_options->get( 'global_map', 'add_map_type_control', 'G_SATELLITE_MAP' );
	 * </code>
	 *
	 * @since 1.2
	 *
	 * @param string $key1 Highest level key.
	 * @param string $key2 Second level key.
	 * @param string $key3 Third level key.
	 * @return string|array The option value or values.
	 */
	public function get( $key1, $key2 = null, $key3 = null ) {
		$subset = array();
		if ( is_null ( $key2 ) ) {
			// Getting first dimension options
			if ( is_array ( $key1 ) ) {
				foreach ( $key1 as $key ) $subset[$key] = isset( $this->options[$key] ) ? $this->options[$key] : null;
				return $subset;
			} else {
				return isset( $this->options[$key1] ) ? $this->options[$key1] : null;
			}
		} else if ( is_null ( $key3 ) ) {
			// Getting second dimension options
			if ( is_array ( $key2 ) ) {
				foreach ( $key2 as $key ) $subset[$key] = isset( $this->options[$key1][$key] ) ? $this->options[$key1][$key] : null;
				return $subset;
			} else {
				return isset( $this->options[$key1][$key2] ) ? $this->options[$key1][$key2] : null;
			} 
		} else {
			// Getting third dimension options
			if ( is_array ( $key3 ) ) {
				foreach ( $key3 as $key ) $subset[$key] = isset( $this->options[$key1][$key2][$key] ) ? $this->options[$key1][$key2][$key] : null;
				return $subset;
			} else {
				return isset( $this->options[$key1][$key2][$key3] ) ? $this->options[$key1][$key2][$key3] : null;
			}
		}
	}

	/**
	 * Import valid options from an array.
	 * 
	 * @since 1.2
	 *
	 * @param array $option_array Associative array of option names and values.
	 */
	public function set_valid_options( $option_array ) {
		$this->validation_errors = array ( );
		$this->options = $this->valid_options ( $option_array );
	}

	/**
	 * Remove invalid option keys from an array, and replace invalid values with defaults.
	 *
	 * @since 1.2
	 *
	 * @param array $option_array An array of options to validate.
	 * @param array $defaults Optional array of valid default values.
	 * @param boolean $add_missing Set if input is not from a form, so missing values are not unchecked checkboxes and should get default values.
	 * @return array Valid options.
	 */
	private function valid_options( $option_array, $defaults = null, $add_missing = false ) {
		$valid_options = array ( );
		if ( is_null ( $defaults ) ) {
			$defaults = ( empty ( $this->options ) ) ? $this->default_options : $this->options;
		}
		if ( !is_array( $option_array ) ) return $defaults;

		foreach ( $defaults as $key => $default_value ) {
			if ( isset( $option_array[$key] ) && $this->is_valid ( $key, $option_array[$key] ) ) {
				if ( is_array ( $option_array[$key] ) && !in_array ( $key, $this->freeform_option_keys ) ) {
					// Validate options in sub-arrays, except freeform array options, whose keys aren't known
					$valid_options[$key] = $this->valid_options ( $option_array[$key], $default_value );
				} else {
					// Use the valid non-array value
					$valid_options[$key] = $option_array[$key];
				}
			} else {
				// Value in question is invalid
				if ( ! $add_missing and empty ( $option_array[$key] ) and in_array ($default_value, array ( 'true', 'false' ) ) ) {
					// Convert empty booleans to false to handle unchecked checkboxes
					$valid_options[$key] = 'false';
				} else { 
					$valid_options[$key] = $default_value;
				}
			}
		}
		return $valid_options;
	}

	/**
	 * Check an option key and value for validity.
	 * 
	 * @since 1.2
	 *
	 * @param string $key Option key.
	 * @param mixed $value Option value, modified in some cases.
	 * @return bool True if valid.
	 */
	public function is_valid( $key, &$value ) {
		switch ( $key ) {
			case 'map_type':
				if ( !in_array ( $value, $this->valid_map_types ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a valid map type (see documentation)', 'GeoMashup') );
					return false;
				}
				return true;

			case 'add_map_type_control':
				if ( !is_array ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be an array of valid map types (see documentation)', 'GeoMashup') );
					return false;
				}
				foreach( $value as $map_type ) {
					if ( !in_array ( $map_type, $this->valid_map_types ) ) {
						array_push ( $this->validation_errors, '"'. $map_type . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
							__(', which must be a valid map type (see documentation)', 'GeoMashup') );
						return false;
					}
				}
				return true;

			case 'map_control':
				$valid_map_controls = array ( 'GSmallZoomControl', 'GSmallMapControl', 'GLargeMapControl', 'GLargeMapControl3D', 'GSmallZoomControl3D' );
				if ( !in_array ( $value, $valid_map_controls ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a valid map control (see documentation)', 'GeoMashup') );
					return false;
				}
				return true;

			case 'map_api':
				$valid_apis = array( 'google', 'openlayers', 'googlev3' );
				if ( !in_array ( $value, $valid_apis ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a valid map provider.', 'GeoMashup') );
					return false;
				}
				return true;

			case 'cluster_lib':
				$valid_libs = array( 'clustermarker', 'markerclusterer' );
				if ( !in_array( $value, $valid_libs ) ) {
					array_push( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a valid clustering library.', 'GeoMashup') );
					return false;
				}
				return true;

			// strings without HTML
			case 'adsense_code':
				if ( empty( $value ) )
					$value = 'partner-pub-5088093001880917';
			case 'geonames_username':
				if ( empty( $value ) )
					$value = 'geomashup';
			case 'category_link_separator':
			case 'category_link_text':
			case 'click_to_load_text':
			case 'import_custom_field':
				if ( empty ( $value ) ) return true;
			case 'google_key':
			case 'googlev3_key':
			case 'version':
			case 'mashup_page':
				if ( !is_string ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a string', 'GeoMashup') );
					return false;
				}
				if ( preg_match( "/<.*>/", $value ) ) {
					array_push ( $this->validation_errors, '"'. esc_html( $value ) . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must not contain XML tags.', 'GeoMashup') );
					return false;
				}
				return true;

			// numbers
			case 'max_posts':
				if ( empty ( $value ) ) return true;
			case 'width':
			case 'height':
				if ( substr ( $value, -1 ) == '%' && is_numeric ( substr ( $value, 0, -1 ) ) ) return true;
				if ( !is_numeric ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a number', 'GeoMashup') );
					return false;
				}
				return true;

			// show_future: boolean or 'only'
			case 'show_future':
				if ( 'only' == $value ) 
					return true;

			// booleans
			case 'auto_info_open':
			case 'add_category_links':
			case 'add_overview_control':
			case 'add_google_bar':
			case 'copy_geodata':
			case 'enable_scroll_wheel_zoom':
			case 'marker_select_info_window':
			case 'marker_select_highlight':
			case 'marker_select_center':
			case 'marker_select_attachments':
			case 'theme_stylesheet_with_maps':
			case 'show_post':
			case 'click_to_load':
			case 'user':
			case 'comment':
			case 'enable_reverse_geocoding':
			case 'enable_geo_search':
				if ( empty ( $value ) ) {
					// fail quietly - it will be converted to false
					return false;
				} else if ( $value != 'true' && $value != 'false' ) { 
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be "true" or "false"', 'GeoMashup') );
					return false;
				}
				return true;

			// arrays
			case 'overall':
			case 'global_map':
			case 'single_map':
			case 'context_map':
			case 'term_options':
			case 'located_post_types':
			case 'include_taxonomies':
			case 'located_object_name':
				if ( !is_array ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be an array', 'GeoMashup') );
					return false;
				}
				return true;

			// zoom levels
			case 'cluster_max_zoom':
				if ( empty ( $value ) ) return true;
			case 'zoom':
			case 'category_zoom':
				if ( $value == 'auto' ) return true;
				if ( !is_numeric ( $value ) || $value < 0 || $value > GEO_MASHUP_MAX_ZOOM ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a number from 0 to ', 'GeoMashup') . GEO_MASHUP_MAX_ZOOM );
					return false;
				}
				return true;

			// deprecated
			case 'post':
				if ( 'deprecated' != $value ) {
					array_push( $this->validation_errors, '"'. $value . '" ' . __( 'is invalid for', 'GeoMashup' ) . ' ' . $key .
						__( ', which is a deprecated option', 'GeoMashup' ) );
					return false;
				}
			default:
				return false;
		}
	}
}

/**
 * @global GeoMashupOptions The global instance of the GeoMashupOptions class.
 */
global $geo_mashup_options;
$geo_mashup_options = new GeoMashupOptions();

