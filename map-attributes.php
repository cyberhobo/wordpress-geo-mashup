<?php

class GeoMashupMapAttributes {
	/** @var int Used to index maps per request */
	protected static $map_number = 1;

	/** @var GeoMashupOptions */
	protected $options;
	/** @var boolean */
	protected $in_comment_loop;
	/** @var WP_Query */
	protected $wp_query;
	/** @var array Attribute values */
	protected $values;
	/** @var boolean */
	protected $static;
	/** @var WP_Error */
	protected $error;
	/** @var string */
	protected $click_to_load;
	/** @var string */
	protected $click_to_load_text;

	public function __construct( $options, $wp_query, $in_comment_loop ) {
		$this->options         = $options;
		$this->wp_query        = $wp_query;
		$this->in_comment_loop = $in_comment_loop;
	}

	/**
	 * @since 1.11.3
	 *
	 * @param array|null $values
	 */
	public function build( $values = null ) {

		$this->values = wp_parse_args( $values );

		if ( ! isset( $this->values['name'] ) ) {
			$this->values['name'] = 'gm-map-' . self::$map_number;
			self::$map_number ++;
		}

		$this->maybe_add_language_attribute();

		$this->convert_map_attributes();

		$this->parse_static_attributes();

		$this->add_map_content_attributes();

		$this->click_to_load = $this->values['click_to_load'];
		unset( $this->values['click_to_load'] );

		$this->click_to_load_text = $this->values['click_to_load_text'];
		unset( $this->values['click_to_load_text'] );

	}

	/**
	 * @since 1.11.3
	 * @return WP_Error
	 */
	public function error() {
		return $this->error;
	}

	/**
	 * @since 1.11.3
	 * @return bool
	 */
	public function is_static() {
		return $this->static;
	}

	/**
	 * @since 1.11.3
	 * @return string
	 */
	public function click_to_load() {
		return $this->click_to_load;
	}

	/**
	 * @since 1.11.3
	 * @return string
	 */
	public function click_to_load_text() {
		return $this->click_to_load_text;
	}

	/**
	 * @since 1.11.3
	 * @return array
	 */
	public function values() {
		return $this->values;
	}

	/**
	 * @since 1.11.3
	 */
	public function maybe_compress_object_ids() {
		if ( ! isset( $this->values['object_ids'] ) || strlen( $this->values['object_ids'] ) <= 1800 ) {
			return;
		}

		if ( ! class_exists( 'GM_Int_list' ) ) {
			include GEO_MASHUP_DIR_PATH . '/gm-int-list.php';
		}

		$id_list              = new GM_Int_List( $this->values['object_ids'] );
		$this->values['oids'] = $id_list->compressed();
		unset( $this->values['object_ids'] );
	}

	/**
	 * @since 1.11.3
	 */
	protected function parse_static_attributes() {

		$this->static = ( ! empty( $this->values['static'] ) && 'true' === $this->values['static'] );

		unset( $this->values['static'] );

		if ( $this->static ) {

			// Static maps have a limit of 50 markers: http://code.google.com/apis/maps/documentation/staticmaps/#Markers
			$this->values['limit'] = empty( $this->values['limit'] ) ? 50 : $this->values['limit'];

		}

	}

	/**
	 * Add a default 'lang' attribute if there is none and a language plugin can provide one.
	 * @since 1.11.3
	 */
	protected function maybe_add_language_attribute() {
		if ( ! empty( $this->values['lang'] ) ) {
			return;
		}
		if ( function_exists( 'qtrans_getLanguage' ) ) {
			// qTranslate integration
			$this->values['lang'] = qtrans_getLanguage();
		} else if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			// WPML integration
			$this->values['lang'] = ICL_LANGUAGE_CODE;
		}
	}

	/**
	 * Convert deprecated attribute names.
	 *
	 * @since 1.11.3
	 */
	protected function convert_map_attributes() {
		$attribute_conversions = array(
			'auto_open_info_window' => 'auto_info_open',
			'open_post_id'          => 'open_object_id'
		);
		foreach ( $attribute_conversions as $old_key => $new_key ) {
			if ( isset( $this->values[ $old_key ] ) ) {
				if ( ! isset( $this->values[ $new_key ] ) ) {
					$this->values[ $new_key ] = $this->values[ $old_key ];
				}
				unset( $this->values[ $old_key ] );
			}
		}
	}

	/**
	 * @since 1.9.0
	 */
	protected function add_map_content_attributes() {

		// Default query is for posts
		$object_name = isset( $this->values['object_name'] ) ? $this->values['object_name'] : 'post';

		$map_content = $this->resolve_map_content( $object_name );

		$click_to_load_options = array( 'click_to_load', 'click_to_load_text' );

		if ( 'contextual' === $map_content ) {

			$this->add_contextual_attributes( $object_name, $click_to_load_options );

		} else if ( 'single' === $map_content ) {

			$this->add_single_attributes( $object_name, $click_to_load_options );

		} else if ( 'global' === $map_content ) {

			$this->add_global_attributes( $click_to_load_options );

		} else {

			$this->error = new WP_Error(
				'geo_mashup_map_content_attributes',
				'Unrecognized value for map_content: "' . $map_content . '"'
			);

		}
	}

	/**
	 * If no object ID has been supplied, try to determine one contextually.
	 * @since 1.9.0
	 *
	 * @param string $object_name
	 *
	 * @return string
	 */
	protected function resolve_map_content( $object_name ) {

		// Map content type isn't required, if empty we'll choose one
		$map_content = isset( $this->values['map_content'] ) ? $this->values['map_content'] : null;

		$context_object_id = $this->context_object_id( $object_name );

		$context_location = null;

		if ( ! empty( $context_object_id ) && empty( $this->values['object_id'] ) ) {
			// If we found a context object, we'll query for that by default
			$this->values['object_id'] = $context_object_id;
			$context_location          = GeoMashupDB::get_object_location( $object_name, $context_object_id );
		}

		if ( 'single' === $map_content && 'post' === $object_name && empty( $this->values['object_id'] ) ) {
			// In secondary post loops we won't find a context object
			// but can at least allow explicit single maps
			$this->values['object_id'] = get_the_ID();
		}

		if ( empty( $map_content ) && ! empty( $this->values['object_ids'] ) ) {
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
			$this->values['object_ids'] = implode( ',', wp_list_pluck( $map_content->posts, 'ID' ) );
			$map_content                = 'global';

		}

		return $map_content;
	}

	/**
	 * Find the ID and location of the container object if it exists
	 * @since 1.11.3
	 *
	 * @param string $object_name
	 *
	 * @return int|null|string
	 */
	protected function context_object_id( $object_name ) {

		if ( 'post' === $object_name && $this->wp_query->in_the_loop ) {
			return $this->wp_query->post->ID;
		}

		if ( 'comment' === $object_name && $this->in_comment_loop ) {
			return get_comment_ID();
		}

		if ( 'user' === $object_name && $this->wp_query->post ) {
			return $this->wp_query->post->post_author;
		}

		return null;
	}

	/**
	 * @since 1.11.3
	 *
	 * @param string $object_name
	 * @param array $click_to_load_options
	 */
	protected function add_contextual_attributes( $object_name, $click_to_load_options ) {

		$this->values['map_content'] = 'contextual';
		$this->values                = array_merge(
			$this->options->get( 'context_map', $click_to_load_options ),
			$this->values
		);

		$object_ids = array();

		if ( 'comment' === $object_name ) {
			$context_objects = $this->wp_query->comments;
		} else {
			$context_objects = $this->wp_query->posts;
		}

		if ( ! is_array( $context_objects ) ) {
			$this->values['error_comment'] = '<!-- ' .
			                                 __( 'Geo Mashup found no objects to map in this context', 'GeoMashup' ) .
			                                 '-->';

			return;
		}

		foreach ( $context_objects as $context_object ) {
			$object_ids[] = $this->object_id_by_name( $object_name, $context_object );
		}

		$this->values['object_ids'] = implode( ',', $object_ids );
	}

	/**
	 * Get a context object's ID according to name.
	 * @since 1.11.3
	 *
	 * @param string $object_name
	 * @param object $context_object
	 *
	 * @return int|null
	 */
	protected function object_id_by_name( $object_name, $context_object ) {
		if ( 'post' === $object_name ) {
			return $context_object->ID;
		}
		if ( 'user' === $object_name ) {
			return $context_object->post_author;
		}
		if ( 'comment' === $object_name ) {
			return $context_object->comment_ID;
		}

		return null;
	}

	/**
	 * @since 1.11.3
	 *
	 * @param string $object_name
	 * @param array $click_to_load_options
	 */
	protected function add_single_attributes( $object_name, $click_to_load_options ) {

		$this->values['map_content'] = 'single';

		/** @noinspection AdditionOperationOnArraysInspection */
		$this->values += $this->options->get( 'single_map', $click_to_load_options );
		if ( empty( $this->values['object_id'] ) ) {
			$this->error = new WP_Error(
				'geo_mashup_single_attribute_error',
				__( 'Geo Mashup found no current object to map', 'GeoMashup' )
			);

			return;
		}

		$single_location = GeoMashupDB::get_object_location( $object_name, $this->values['object_id'] );
		if ( empty( $single_location ) ) {
			$this->error = new WP_Error(
				'geo_mashup_single_attribute_error',
				__( 'Geo Mashup omitted a map for an object with no location', 'GeoMashup' )
			);
		}
	}

	/**
	 * @since 1.11.3
	 *
	 * @param array $click_to_load_options
	 */
	protected function add_global_attributes( $click_to_load_options ) {

		if ( isset( $_GET['template'] ) && 'full-post' === $_GET['template'] ) {
			// Global maps tags in response to a full-post query can infinitely nest, prevent this
			$this->error = new WP_Error(
				'geo_mashup_global_attribute_error',
				__( 'Geo Mashup map omitted to avoid nesting maps', 'GeoMashup' )
			);

			return;
		}
		$this->values['map_content'] = 'global';

		$this->add_whitelisted_query_string_parameters();

		$this->values = array_merge(
			$this->options->get( 'global_map', $click_to_load_options ),
			$this->values
		);

		// Don't query more than max_posts
		$max_posts = $this->options->get( 'global', 'max_posts' );
		if ( ! empty( $max_posts ) && empty( $this->values['limit'] ) ) {
			$this->values['limit'] = $max_posts;
		}
	}

	/**
	 * @since 1.11.3
	 */
	protected function add_whitelisted_query_string_parameters() {

		// Global maps on a page will make use of query string arguments unless directed otherwise
		$ignore_url = false;
		if ( isset( $this->values['ignore_url'] ) && 'true' === $this->values['ignore_url'] ) {
			$ignore_url = true;
			unset( $this->values['ignore_url'] );
		}

		if ( $ignore_url || ! isset( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}

		$whitelist = array(
			'admin_code',
			'country_code',
			'exclude_object_ids',
			'limit',
			'locality_name',
			'map_cat',
			'map_content',
			'map_offset',
			'map_post_type',
			'minlat',
			'maxlat',
			'minlon',
			'maxlon',
			'near_lat',
			'near_lng',
			'object_name',
			'object_id',
			'object_ids',
			'postal_code',
			'radius_km',
			'radius_mi',
			'saved_name',
			'show_future',
			'sort',
			'tax_query',
			'add_map_control',
			'add_google_bar',
			'add_map_type_control',
			'add_overview_control',
			'auto_info_open',
			'auto_zoom_max',
			'background_color',
			'center_lat',
			'center_lng',
			'cluster_max_zoom',
			'enable_scroll_wheel_zoom',
			'enable_street_view',
			'height',
			'load_empty_map',
			'load_kml',
			'map_control',
			'map_type',
			'marker_min_zoom',
			'marker_select_info_window',
			'marker_select_highlight',
			'marker_select_center',
			'marker_select_attachments',
			'open_object_id',
			'static',
			'width',
			'zoom'
		);

		$allowed_parameters = array_intersect_key( wp_parse_args( $_SERVER['QUERY_STRING'] ), array_flip( $whitelist ) );

		$this->values = array_merge( $this->values, $allowed_parameters );
	}
}