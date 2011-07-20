<?php
/**
 * Respond to a requests for a Geo Mashup map.
 *
 * @package GeoMashup
 */

/**
 * Static class for map rendering namespace
 * 
 * @access public
 * @since 1.4
 * @package GeoMashup
 * @static
 */
class GeoMashupRenderMap {
	/**
	 * Map data built for a request. 
	 * @var array 
	 */
	private static $map_data = null;

	/**
	 * Template tag for the current map script.
	 *
	 * @since 1.4
	 *
	 * @param string $element_id The DOM id of the map container.
	 * @return string The script tag to generate the map for this request.
	 */
	public static function map_script( $element_id ) {
		return '<script type="text/javascript">' . "\n" .
			'GeoMashup.createMap(document.getElementById("' . $element_id . '"), { ' .
			GeoMashup::implode_assoc( ':', ',', self::$map_data ) . ' });' .
			"\n" . '</script>';
	}

	/**
	 * Enqueue a style to be included in the map frame.
	 *
	 * Register external styles with wp_register_style().
	 * Register local styles with GeoMashup::register_style().
	 *
	 * @since 1.4
	 *
	 * @param string $handle The handle used to register the style.
	 */
	public static function enqueue_style( $handle ) {
		$styles = self::map_property( 'styles' );
		if ( is_null( $styles ) ) {
			$styles = array();
		}
		$styles[] = $handle;
		self::map_property( 'styles', $styles );
	}

	/**
	 * Enqueue a script to be included in the map frame.
	 *
	 * Register external scripts with wp_register_script().
	 * Register local scripts with GeoMashup::register_script().
	 *
	 * @since 1.4
	 *
	 * @param string $handle The handle used to register the script.
	 */
	public static function enqueue_script( $handle ) {
		$scripts = self::map_property( 'scripts' );
		if ( is_null( $scripts ) ) {
			$scripts = array();
		}
		$scripts[] = $handle;
		self::map_property( 'scripts', $scripts );
	}

	/**
	 * Print only resources queued for the Geo Mashup map frame.
	 * 
	 * Can be replaced with wp_head() to include all blog resources in
	 * map frames.
	 * 
	 * @since 1.4
	 */
	public static function head() {
		$styles = self::map_property( 'styles' );
		wp_print_styles( $styles );
		$scripts = self::map_property( 'scripts' );
		wp_print_scripts( $scripts );
	}

	/**
	 * Template tag to get a property of the map for the current request.
	 *
	 * Current properties available: height and width for inline map styles.
	 *
	 * @since 1.4
	 *
	 * @param string $name The name of the property to get.
	 * @param string $new_value Optional, sets the property if supplied.
	 * @return string|null The property value or null if not found.
	 */
	public static function map_property( $name, $new_value = null ) {
		static $properties = array();
		if ( !is_null( $new_value ) ) {
			$properties[$name] = $new_value;
		}
		return ( isset( $properties[$name] ) ? $properties[$name] : null );
	}

	/**
	 * Make sure a non-javascript item is double-quoted.
	 *
	 * @since 1.3
	 *
	 * @param mixed $item The value in question, may be modified.
	 * @param string $key The JSON key.
	 */
	private static function add_double_quotes(&$item,$key) {
		$quoted_keys = array ( 'background_color', 'show_future', 'map_control', 'map_content', 'map_type', 'legend_format', 'template' );
		if ( $key == 'post_data' ) {
			// don't quote
		} else if ( !is_numeric( $item ) && empty ( $item ) ) {
			$item = '""';
		} else if ( is_array( $item ) && isset( $item[0] ) ) {
			$item = '["' . implode( '","', $item ) . '"]';
		} else if ( is_string( $item ) && $item[0] != '{' && $item[0] != '[' ) {
			$item = '"'.$item.'"';
		}
	}

	/**
	 * Render the requested map.
	 *
	 * @since 1.4
	 */
	public static function render_map() {
		global $geo_mashup_options, $geo_mashup_custom;

		// Include theme stylesheet if requested
		if ( $geo_mashup_options->get('overall', 'theme_stylesheet_with_maps' ) == 'true' ) {
			wp_enqueue_style( 'theme-style', get_stylesheet_uri() );
			self::enqueue_style( 'theme-style' );
		}

		// Resolve map style
		$style_file_path = path_join( get_stylesheet_directory(), 'map-style.css' );
		$style_url_path = '';
		if ( is_readable( $style_file_path ) ) {
			$style_url_path = path_join( get_stylesheet_directory_uri(), 'map-style.css' );
		} else if ( isset( $geo_mashup_custom ) ) {
			$style_url_path = $geo_mashup_custom->file_url( 'map-style.css' );
		}
		if ( empty( $style_url_path ) ) {
			GeoMashup::register_style( 'geo-mashup-map-style', 'css/map-style-default.css' );
		} else {
			 wp_register_style( 'geo-mashup-map-style', $style_url_path );
		}
		wp_enqueue_style( 'geo-mashup-map-style' );
		self::enqueue_style( 'geo-mashup-map-style' );

		if ( isset( $_GET['map_data_key'] ) ) {
			// Map data is cached in a transient
			$map_data = get_transient( 'gmm' . $_GET['map_data_key'] );
			if ( !$map_data ) {
				$map_parameters = get_transient( 'gmp' . $_GET['map_data_key'] );
				if ( $map_parameters )
					$map_data = GeoMashup::build_map_data( $map_parameters );
				else
					$map_data = GeoMashup::build_map_data( '' );
			}
		} else {
			// Try building map data from the query string
			$map_data = GeoMashup::build_map_data( $_GET );
		}

		// Queue scripts
		$mashup_dependencies = array( 'jquery' );
		$language_code = GeoMashup::get_language_code();
		if ( 'google' == $map_data['map_api'] ) {
			// Google v2 base
			$google_2_url = 'http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=' .
				$geo_mashup_options->get( 'overall', 'google_key' );
			if ( ! empty( $language_code ) ) {
				$google_2_url .= '&amp;hl=' . $language_code;
			}
			wp_register_script( 
					'google-maps-2', 
					$google_2_url,
					'',
					'',
					true );
					
			$mashup_dependencies[] = 'google-maps-2';
			$mashup_script = 'geo-mashup-google';

			if ( !empty( $map_data['cluster_max_zoom'] ) ) {
				// Queue clustering scripts
				if ( 'clustermarker' == $map_data['cluster_lib'] ) {
					GeoMashup::register_script( 
							'mapiconmaker', 
							'js/mapiconmaker.js', 
							array( 'google-maps-2' ),
							'1.1',
							true );
							 
					GeoMashup::register_script( 
							'clustermarker', 
							'js/ClusterMarker.js', 
							array( 'mapiconmaker' ), 
							'1.3.2', 
							true );
							
					$mashup_dependencies[] = 'clustermarker';
				} else {
					$map_data['cluster_lib'] = 'markerclusterer';
					GeoMashup::register_script( 
							'markerclusterer', 
							'js/markerclusterer.js', 
							array( 'google-maps-2', 
							'geo-mashup-google' ), 
							'1.0',
							true );
							
					wp_enqueue_script( 'markerclusterer' );
					self::enqueue_script( 'markerclusterer' );
				}
			}
		} else {
			// Mapstraction base
			$mashup_script = 'geo-mashup-mxn';
			GeoMashup::register_script( 
					'mxn', 
					'js/mxn/mxn.js', 
					null, 
					GEO_MASHUP_VERSION,
					true );
					
			GeoMashup::register_script( 
					'mxn-core', 
					'js/mxn/mxn.core.js', 
					array( 'mxn' ), 
					GEO_MASHUP_VERSION,
					true );
		}

		// Mapstraction providers
		if ( 'openlayers' == $map_data['map_api'] ) {
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
					
			$mashup_dependencies[] = 'mxn-openlayers-gm';
		} else if ( 'googlev3' == $map_data['map_api'] ) {
			$google_3_url = 'http://maps.google.com/maps/api/js?sensor=false';
			if ( ! empty( $language_code ) ) {
				$google_3_url .= '&amp;language=' . $language_code;
			}
			wp_register_script( 
					'google-maps-3', 
					$google_3_url, 
					'', 
					'', 
					true );
					
			GeoMashup::register_script( 
					'mxn-googlev3', 
					'js/mxn/mxn.googlev3.core.js', 
					array( 'mxn-core', 'google-maps-3' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			$mashup_dependencies[] = 'mxn-googlev3';
		}

		// Geo Mashup scripts
		GeoMashup::register_script( 
				'geo-mashup', 
				'js/geo-mashup.js', 
				$mashup_dependencies, 
				GEO_MASHUP_VERSION,
				true );
				
		GeoMashup::register_script( 
				$mashup_script, 
				'js/' . $mashup_script . '.js', 
				array( 'geo-mashup' ), 
				GEO_MASHUP_VERSION,
				true );
				
		wp_enqueue_script( $mashup_script );
		self::enqueue_script( $mashup_script );

		// Custom javascript
		$custom_js_url_path = '';
		if ( isset( $geo_mashup_custom ) ) {
			$custom_js_url_path = $geo_mashup_custom->file_url( 'custom-' . $map_data['map_api'] . '.js' );
			if ( !$custom_js_url_path and 'google' != $map_data['map_api'] )
				$custom_js_url_path = $geo_mashup_custom->file_url( 'custom-mxn.js' );
			if ( !$custom_js_url_path )
				$custom_js_url_path = $geo_mashup_custom->file_url( 'custom.js' );
		} else if ( is_readable( 'custom.js' ) ) {
			$custom_js_url_path = path_join( GEO_MASHUP_URL_PATH, 'custom.js' );
		}
		if ( ! empty( $custom_js_url_path ) ) {
			wp_enqueue_script( 'geo-mashup-custom', $custom_js_url_path, array( $mashup_script ) );
			self::enqueue_script( 'geo-mashup-custom' );
		}

		// Set height and width properties for the template
		$width = $map_data['width'];
		if ( substr( $width, -1 ) != '%' ) {
			$width .= 'px';
		}
		unset( $map_data['width'] );
		self::map_property( 'width', $width );

		$height = $map_data['height'];
		if ( substr( $height, -1 ) != '%' ) {
			$height .= 'px';
		}
		unset( $map_data['height'] );
		self::map_property( 'height', $height );

		if ( isset( $map_data['object_data'] ) and is_array( $map_data['object_data'] ) )
			$map_data['object_data'] = json_encode( $map_data['object_data'] );
		array_walk( $map_data, array( 'GeoMashupRenderMap', 'add_double_quotes' ) );

		if ( 'single' == $map_data['map_content'] ) {
			$category_opts = '{}';
		} else {
			$categories = get_categories( array( 'hide_empty' => false ) );
			$category_opts = '{';
			if (is_array($categories))
			{
				$cat_comma = '';
				$category_color = $geo_mashup_options->get('global_map', 'category_color');
				$category_line_zoom = $geo_mashup_options->get('global_map', 'category_line_zoom');
				foreach($categories as $category) {
					$category_opts .= $cat_comma.'"'.$category->term_id.'":{"name":"' . esc_js( $category->name ) . '"';
					$parent_id = '';
					if ( !empty( $category->parent ) ) {
						$parent_id = $category->parent;
					}
					$category_opts .= ',"parent_id":"' . $parent_id . '"';
					if ( !empty( $category_color[$category->slug] ) ) {
						$category_opts .= ',"color_name":"'.$category_color[$category->slug].'"';
					}
					if ( !empty( $category_line_zoom[$category->slug] ) ) {
						$category_opts .= ',"max_line_zoom":"'.$category_line_zoom[$category->slug].'"';
					}
					$category_opts .= '}';
					$cat_comma = ',';
				}
			}
			$category_opts .= '}';
		}
		$map_data['category_opts'] = $category_opts;

		// Store the properties for use by the template tag GeoMashupRenderMap::map_script
		self::$map_data = $map_data;

		// Load the template
		status_header ( 200 );
		load_template( GeoMashup::locate_template( 'map-frame' ) );
	}
}
