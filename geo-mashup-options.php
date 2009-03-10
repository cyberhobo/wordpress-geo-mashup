<?php

class GeoMashupOptions {
	var $default_options = array (
		'overall' => array (
			'google_key' => '',
			'mashup_page' => '',
			'category_link_separator' => '::',
			'category_link_text' => 'map',
			'category_zoom' => '7',
			'add_category_links' => 'false',
			'theme_stylesheet_with_maps' => 'false' ),
		'global_map' => array (
			'width' => '400',
			'height' => '400',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => '7',
			'background_color' => 'c0c0c0',
			'excerpt_format' => 'text',
			'excerpt_length' => '250',
			'category_color' => array ( ),
			'category_line_zoom' => array ( ),
			'marker_min_zoom' => '',
			'map_control' => 'GSmallZoomControl3D',
			'add_map_type_control' => 'true',
			'add_overview_control' => 'false',
			'show_post' => 'false',
			'show_future' => 'false',
			'marker_min_zoom' => '',
			'max_posts' => '',
			'auto_info_open' => 'true',
			'click_to_load' => 'false',
			'click_to_load_text' => '' ),
		'single_map' => array (
			'width' => '400',
			'height' => '400',
			'map_control' => 'GSmallZoomControl3D',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => '11',
			'background_color' => 'c0c0c0',
			'add_overview_control' => 'false',
			'add_map_type_control' => 'true',
			'click_to_load' => 'false',
	 		'click_to_load_text' => '' ), 
		'context_map' => array (
			'width' => '200',
			'height' => '200',
			'map_control' => 'GSmallZoomControl3D',
			'map_type' => 'G_NORMAL_MAP',
			'zoom' => '7',
			'background_color' => 'c0c0c0',
			'add_overview_control' => 'false',
			'add_map_type_control' => 'false',
			'click_to_load' => 'false',
	 		'click_to_load_text' => '' ) );
	var $conversions = array (
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
		'excerpt_format' => array ( 'global_map', 'excerpt_format' ),
		'excerpt_length' => array ( 'global_map', 'excerpt_length' ),
		'category_color' => array ( 'global_map', 'category_color' ),
		'category_line_zoom' => array ( 'global_map', 'category_line_zoom' ),
		'marker_min_zoom' => array ( 'global_map', 'marker_min_zoom' ),
		'map_control' => array ( 'global_map', 'map_control' ),
		'add_map_type_control' => array ( 'global_map', 'add_map_type_control' ),
		'add_overview_control' => array ( 'global_map', 'add_overview_control' ),
		'show_post' => array ( 'global_map', 'show_post' ),
		'show_future' => array ( 'global_map', 'show_future' ),
		'marker_min_zoom' => array ( 'global_map', 'marker_min_zoom' ),
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
	var $options;
	var $corrupt_options = '';
	var $validation_errors = array();

	function GeoMashupOptions ( ) {
		$this->options = $this->default_options;
		$settings = get_settings ( 'geo_mashup_options' );
		if ( is_array ( $settings ) ) {
			$settings = $this->convert_old_settings ( $settings );
			$this->set_valid_options ( $settings );
		} else {
			$failed_options = $settings;
			if ( is_string ( $settings ) && !empty ( $settings ) ) {
				$this->corrupt_options = $settings;
			}
		}
	}

	function convert_old_settings ( $settings ) {
		foreach ( $this->conversions as $old_key => $new_keys ) {
			if ( isset ( $settings[$old_key] ) ) {
				$settings[$new_keys[0]][$new_keys[1]] = $settings[$old_key];
				unset ( $settings[$old_key] );
			}
		}
		return $settings;
	}

	function save ( ) {
		$saved = false;
		if ($this->options == $this->valid_options ( $this->options ) ) {
			$saved = update_option('geo_mashup_options', $this->options);
		}
		return $saved;
	}

	function get ( $key1, $key2 = null, $key3 = null ) {
		$subset = array();
		if ( is_null ( $key2 ) ) {
			// Getting first dimension options
			if ( is_array ( $key1 ) ) {
				foreach ( $key1 as $key ) $subset[$key] = $this->options[$key];
				return $subset;
			} else {
				return $this->options[$key1];
			}
		} else if ( is_null ( $key3 ) ) {
			// Getting second dimension options
			if ( is_array ( $key2 ) ) {
				foreach ( $key2 as $key ) $subset[$key] = $this->options[$key1][$key];
				return $subset;
			} else {
				return $this->options[$key1][$key2];
			} 
		} else {
			// Getting third dimension options
			if ( is_array ( $key3 ) ) {
				foreach ( $key3 as $key ) $subset[$key] = $this->options[$key1][$key2][$key];
				return $subset;
			} else {
				return $this->options[$key1][$key2][$key3];
			}
		}
	}

	function set_valid_options ( $option_array ) {
		$this->validation_errors = array ( );
		$this->options = $this->valid_options ( $option_array );
	}

	/**
	 * valid_options
	 * @param option_array An array of options to validate.
	 * @param defaults Opional array of valid default values.
	 * @return An array of all option values, with invalid keys eliminated and invalid values replaced by defaults.
	 */
	function valid_options ( $option_array, $defaults = null ) {
		$valid_options = array ( );
		if ( is_null ( $defaults ) ) {
			$defaults = ( empty ( $this->options ) ) ? $this->default_options : $this->options;
		}
		if ( !is_array( $option_array ) ) return $defaults;

		foreach ( $defaults as $key => $default_value ) {
			if ( $this->is_valid ( $key, $option_array[$key] ) ) {
				if ( is_array ( $option_array[$key] ) && !in_array ( $key, array ( 'category_color', 'category_line_zoom' ) ) ) {
					// Validate options in sub-arrays, except those based on blog categories
					$valid_options[$key] = $this->valid_options ( $option_array[$key], $default_value );
				} else {
					// Use the valid non-array value
					$valid_options[$key] = $option_array[$key];
				}
			} else {
				// Value in question is invalid
				if ( empty ( $option_array[$key] ) && in_array ($default_value, array ( 'true', 'false' ) ) ) {
					// Convert empty booleans to false 
					$valid_options[$key] = 'false';
				} else { 
					$valid_options[$key] = $default_value;
				}
			} 
		}
		return $valid_options;
	}

	function is_valid ( $key, $value ) {
		switch ( $key ) {
			case 'map_type':
				$valid_map_types = array ( 'G_NORMAL_MAP', 'G_SATELLITE_MAP', 'G_HYBRID_MAP', 'G_PHYSICAL_MAP', 'G_SATELLITE_3D_MAP' );
				if ( !in_array ( $value, $valid_map_types ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a valid map type (see documentation)', 'GeoMashup') );
					return false;
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

			case 'excerpt_format':
				if ( !in_array ( $value, array ( 'text', 'html' ) ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be "text" or "html"', 'GeoMashup') );
					return false;
				}
				return true;

			// strings
			case 'category_link_separator':
			case 'category_link_text':
			case 'click_to_load_text':
				if ( empty ( $value ) ) return true;
			case 'google_key':
			case 'mashup_page':
				if ( !is_string ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a string', 'GeoMashup') );
					return false;
				}
				return true;

			// numbers
			case 'max_posts':
				if ( empty ( $value ) ) return true;
			case 'width':
			case 'height':
			case 'excerpt_length':
				if ( !is_numeric ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a number', 'GeoMashup') );
					return false;
				}
				return true;

			// booleans
			case 'auto_info_open':
			case 'add_category_links':
			case 'add_map_type_control':
			case 'add_overview_control':
			case 'theme_stylesheet_with_maps':
			case 'show_post':
			case 'click_to_load':
			case 'show_future':
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
			case 'category_color':
			case 'category_line_zoom':
				if ( !is_array ( $value ) ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be an array', 'GeoMashup') );
					return false;
				}
				return true;

			// zoom levels
			case 'marker_min_zoom':
				if ( empty ( $value ) ) return true;
			case 'zoom':
			case 'category_zoom':
				if ( !is_numeric ( $value ) || $value < 0 || $value > GEO_MASHUP_MAX_ZOOM ) {
					array_push ( $this->validation_errors, '"'. $value . '" ' . __('is invalid for', 'GeoMashup') . ' ' . $key .
						__(', which must be a number from 0 to ', 'GeoMashup') . GEO_MASHUP_MAX_ZOOM );
					return false;
				}
				return true;

			default:
				return false;
		}
	}
}

global $geo_mashup_options;
$geo_mashup_options = new GeoMashupOptions ( );

?>
