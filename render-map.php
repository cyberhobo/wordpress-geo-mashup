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
			'GeoMashup.createMap(document.getElementById("' . $element_id . '"), ' .
			self::$map_data . ' );' .  "\n" . '</script>';
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
	 * Resolve and queue map styles.
	 * 
	 * @since 1.5
	 */
	private static function enqueue_styles() {
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
			GeoMashup::register_style( 'geo-mashup-map-style', 'css/map-style-default.css', GEO_MASHUP_VERSION );
		} else {
			 wp_register_style( 'geo-mashup-map-style', $style_url_path, GEO_MASHUP_VERSION );
		}
		wp_enqueue_style( 'geo-mashup-map-style' );
		self::enqueue_style( 'geo-mashup-map-style' );
	}

	/**
	 * Retrieve any cached map data, or build it.
	 * 
	 * @since 1.5
	 * @return array Map data for the current query.
	 */
	public static function get_map_data() {

		$map_data = null;

		if ( isset( $_GET['map_data_key'] ) ) {
			// Map data is cached in a transient
			$map_data = get_transient( 'gmm' . $_GET['map_data_key'] );
		}

		if ( !$map_data and isset( $_GET['map_content'] ) ) {

			// Try building the map data from the query string
			if ( isset( $_GET['oids'] ) ) {
				if ( !class_exists( 'GM_Int_list' ) )
					include GEO_MASHUP_DIR_PATH . '/gm-int-list.php';
				$list = new GM_Int_List( $_GET['oids'] );
				$_GET['object_ids'] = $list->expanded();
				unset( $_GET['oids'] );
			}

			$map_data = GeoMashup::build_map_data( $_GET );
		}

		return $map_data;
	}

	/**
	 * Resolve and queue map scripts.
	 * 
	 * @since 1.5
	 * @global object $geo_mashup_options
	 * @global object $geo_mashup_custom
	 * 
	 * @param array $map_data Map data for the current query.
	 */
	private static function enqueue_scripts( $map_data ) {
		global $geo_mashup_options, $geo_mashup_custom;

		// Queue scripts
		$mashup_dependencies = array( 'jquery' );
		$language_code = GeoMashup::get_language_code();
		$load_markerclusterer = false;

		if ( 'google' == $map_data['map_api'] ) {
			// Google v2 base
			$google_2_url = '//maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=' .
				$geo_mashup_options->get( 'overall', 'google_key' );
			if ( ! empty( $language_code ) ) {
				$google_2_url .= '&amp;hl=' . substr( $language_code, 0, 2 );
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
					'//cdnjs.cloudflare.com/ajax/libs/openlayers/2.13.1/OpenLayers.js',
					null, 
					'latest', 
					true );
					
			wp_register_script( 
					'openstreetmap', 
					'//www.openstreetmap.org/openlayers/OpenStreetMap.js',
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
			$google_3_url = '//maps.google.com/maps/api/js';
			$googlev3_key = $geo_mashup_options->get( 'overall', 'googlev3_key' );
			if ( ! empty( $googlev3_key ) ) {
				$google_3_url = add_query_arg( 'key', $googlev3_key, $google_3_url );
			}
			if ( ! empty( $language_code ) ) {
				$google_3_url = add_query_arg( 'language', substr( $language_code, 0, 2 ), $google_3_url );
			}
			$load_markerclusterer = (bool)( 'single' != $map_data['map_content'] and !empty( $map_data['cluster_max_zoom'] ) );

			wp_register_script( 
					'google-maps-3', 
					$google_3_url, 
					'', 
					'', 
					true );

			if ( $load_markerclusterer ) {

				// Queue clustering scripts
				GeoMashup::register_script(
						'markerclusterer',
						'js/markerclusterer.js',
						array( 'google-maps-3' ),
						'2.0.3',
						true );

				GeoMashup::register_script(
						'geo-mashup-modernizr',
						'js/modernizr.js',
						array(),
						'2.6.2',
						true );

				GeoMashup::register_script(
					'geo-mashup-markerclusterer',
					'js/geo-mashup-markerclusterer.js',
					array( 'geo-mashup-mxn', 'geo-mashup-modernizr' ),
					GEO_MASHUP_VERSION,
					true );
				$mashup_dependencies[] = 'markerclusterer';
			}


			GeoMashup::register_script( 
					'mxn-googlev3', 
					'js/mxn/mxn.googlev3.core.js', 
					array( 'mxn-core', 'google-maps-3' ), 
					GEO_MASHUP_VERSION, 
					true );
					
			GeoMashup::register_script( 
					'mxn-googlev3-gm', 
					'js/mxn/mxn.googlev3.geo-mashup.js', 
					array( 'mxn-googlev3' ), 
					GEO_MASHUP_VERSION, 
					true );
			$mashup_dependencies[] = 'mxn-googlev3-gm';

		} else if ( 'leaflet' == $map_data['map_api'] ) {

			wp_register_script(
					'leaflet',
					'//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js',
					null,
					'0.6.4',
					true );

			wp_register_style(
					'leaflet',
					'//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css',
					null,
					'0.6.4' );

			self::enqueue_style( 'leaflet' );

			GeoMashup::register_script(
					'mxn-leaflet',
					'js/mxn/mxn.leaflet.core.js',
					array( 'mxn-core', 'leaflet' ),
					GEO_MASHUP_VERSION,
					true );

			GeoMashup::register_script(
					'mxn-leaflet-kml',
					'js/leaflet/KML.js',
					array( 'mxn-leaflet' ),
					GEO_MASHUP_VERSION,
					true );

			GeoMashup::register_script(
					'mxn-leaflet-gm',
					'js/mxn/mxn.leaflet.geo-mashup.js',
					array( 'mxn-leaflet-kml' ),
					GEO_MASHUP_VERSION,
					true );

			$mashup_dependencies[] = 'mxn-leaflet-gm';
		}

		// Geo Mashup scripts
		GeoMashup::register_script( 
				'geo-mashup', 
				'js/geo-mashup.js', 
				$mashup_dependencies, 
				GEO_MASHUP_VERSION,
				true );
				
		$feature_dependencies = array( 'geo-mashup' );

		if ( ! empty( $map_data['include_taxonomies'] ) ) {
			GeoMashup::register_script(
					'geo-mashup-taxonomy',
					'js/taxonomy.js',
					array( 'geo-mashup' ),
					GEO_MASHUP_VERSION,
					true );
			$feature_dependencies[] = 'geo-mashup-taxonomy';
		}

		GeoMashup::register_script(
				$mashup_script, 
				'js/' . $mashup_script . '.js', 
				$feature_dependencies, 
				GEO_MASHUP_VERSION,
				true );
				
		wp_enqueue_script( $mashup_script );
		self::enqueue_script( $mashup_script );

		if ( $load_markerclusterer ) {
			self::enqueue_script( 'geo-mashup-markerclusterer' );
		}

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

		// A general hook for rendering customizations
		do_action( 'geo_mashup_render_map', $mashup_script );

	}

	/**
	 * Extract data from the map that may be needed in the frame template.
	 * 
	 * @since 1.5
	 * @global object $geo_mashup_options
	 * 
	 * @param array $map_data Map data for the current query, modified to 
	 *                        remove template-only data.
	 */
	private static function extract_template_properties( &$map_data ) {

		// Set properties for the template
		self::map_property( 'name', $map_data['name'] );
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

		// Add background color if specified
		if ( !empty( $map_data['background_color'] ) ) {
			self::map_property( 'background_color', '#' . $map_data['background_color'] );
			// Only google v2 maps need the parameter
			if ( 'google' != $map_data['map_api'] )
				unset( $map_data['background_color'] );
		}

	}

	/**
	 * Add term data for filtering, legends, and such.
	 * 
	 * @since 1.5
	 * @global object $geo_mashup_options
	 * 
	 * @param array $map_data Map data for the current query, modified to 
	 *                        add term structure data.
	 */
	private static function add_term_properties( &$map_data ) {
		global $geo_mashup_options;

		// Ignore if term properties are already set (allow test data override)
		if ( isset( $map_data['term_properties'] ) )
			return;

		$term_properties = array();

		if ( 'single' != $map_data['map_content'] and !empty( $map_data['include_taxonomies'] ) ) {

			// Add saved term options to other term properties needed by the map

			$map_data['check_all_label'] = __( 'Check/Uncheck All', 'GeoMashup' );

			$term_options = $geo_mashup_options->get( 'global_map', 'term_options' );

			foreach( $map_data['include_taxonomies'] as $include_taxonomy ) {

				$taxonomy_object = get_taxonomy( $include_taxonomy );
				$terms = get_terms( $include_taxonomy, array( 'hide_empty' => false ) );
				$term_properties[$include_taxonomy] = array(
					'label' => $taxonomy_object->label,
					'terms' => array()
				);

				if (is_array($terms)) {
					foreach($terms as $term) {

						$parent_id = '';
						if ( !empty( $term->parent ) ) 
							$parent_id = $term->parent;
						
						$term_id = $term->term_id;
						$term_properties[$include_taxonomy]['terms'][$term_id] = array(
							'name' => esc_js( $term->name ),
							'parent_id' => $parent_id,
						);

						if ( !empty( $term_options[$include_taxonomy]['color'][$term->slug] ) )
							$term_properties[$include_taxonomy]['terms'][$term_id]['color'] = $term_options[$include_taxonomy]['color'][$term->slug]; 

						if ( !empty( $term_options[$include_taxonomy]['line_zoom'][$term->slug] ) )
							$term_properties[$include_taxonomy]['terms'][$term_id]['line_zoom'] = $term_options[$include_taxonomy]['line_zoom'][$term->slug]; 

						if ( defined( 'GEO_MASHUP_TERM_ORDER_FIELD' ) and property_exists( $term, GEO_MASHUP_TERM_ORDER_FIELD ) ) {
							$order_field = GEO_MASHUP_TERM_ORDER_FIELD;
							$term_properties[$include_taxonomy]['terms'][$term_id]['order'] = $term->$order_field; 
						}

					} // end foreach taxonomy term

				} // end if taxonomy has terms

			} // end foreach included taxonomy

		} // end else (there are term opts)

		$map_data['term_properties'] = $term_properties;

	}

	/**
	 * Render the requested map.
	 *
	 * @since 1.4
	 * @uses do_action() geo_mashup_render_map Customize things (like scripts and styles) before the template is loaded. The mashup script (google v2 or mxn) is sent as a parameter.
	 */
	public static function render_map() {

		self::enqueue_styles();

		$map_data = self::get_map_data();
		if ( empty( $map_data ) ) {
			status_header( 500 );
			_e( 'WordPress transients may not be working. Try deactivating or reconfiguring caching plugins.', 'GeoMashup' );
			echo ' <a href="https://github.com/cyberhobo/wordpress-geo-mashup/issues/425" target="_top">issue 425</a>';
			exit();
		}

		self::extract_template_properties( $map_data );

		self::enqueue_scripts( $map_data );

		self::add_term_properties( $map_data );

		// Store the properties for use by the template tag GeoMashupRenderMap::map_script
		self::$map_data = json_encode( $map_data );

		// Load the template
		status_header ( 200 );
		load_template( GeoMashup::locate_template( 'map-frame' ) );
	}
}
