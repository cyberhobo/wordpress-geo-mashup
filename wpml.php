<?php
/**
 * Manage integration with WPML.
 * @package GeoMashup
 * @since 1.9.0
 */

class GeoMashupWPML {
    /** @var array */
    protected static $meta_queue = array();

	/**
	 * Load WPML integrations.
	 * @since 1.9.0
	 */
	public static function load() {
		add_filter( 'geo_mashup_get_language_code', array( __CLASS__, 'get_language_code' ) );
		add_filter( 'geo_mashup_locations_join', array( __CLASS__, 'augment_locations_join_clause' ), 10, 2 );
		add_filter( 'geo_mashup_locations_where', array( __CLASS__, 'augment_locations_where_clause' ), 10, 2 );
		add_filter( 'geo_mashup_results_page_id', array( __CLASS__, 'translate_results_page_id' ) );
		add_filter( 'wpml_duplicate_generic_string', array( __CLASS__, 'queue_post_meta' ), 10, 3 );
	}

	/**
	 * Use WPML's language code unless the lang querystring parameter is present.
	 * @since 1.9.0
	 * @param string $code
	 * @return string
	 */
	public static function get_language_code( $code ) {
		return isset( $_GET['lang'] ) ? $_GET['lang'] : ICL_LANGUAGE_CODE;
	}

	/**
	 * Add WPML tables to post location query join clause, changing posts table references to our alias.
	 * @since 1.9.0
	 * @param string $join
	 * @param array $query_args
	 * @return string
	 */
	public static function augment_locations_join_clause( $join, $query_args ) {
		global $wpdb, $wpml_query_filter;

		if ( self::suppress_query_filters() ) {
			return $join;
		}

		if ( 'post' != $query_args['object_name'] ) {
			return $join;
		}

		// Apply post query filters,
		$join = $wpml_query_filter->filter_single_type_join( $join, 'any' );
		return str_replace( $wpdb->posts . '.', 'o.', $join );
	}

	/**
	 * Add WPML conditions to post location query where clause, changing posts table references to our alias.
	 *
	 * Also removes an interfering WPML filter.
	 * @since 1.9.0
	 * @param string $where
	 * @param array $query_args
	 * @return string
	 */
	public static function augment_locations_where_clause( $where, $query_args ) {
		global $wpdb, $wpml_query_filter;

		if ( self::suppress_query_filters() ) {
			return $where;
		}

		if ( 'post' != $query_args['object_name'] ) {
			return $where;
		}

		$where = $wpml_query_filter->filter_single_type_where(
			$where,
			$GLOBALS['geo_mashup_options']->get( 'overall', 'located_post_types' )
		);
		$where = str_replace( $wpdb->posts . '.', 'o.', $where );

		remove_filter( 'get_translatable_documents', array( __CLASS__, 'wpml_filter_get_translatable_documents' ) );

		return $where;
	}

	/**
	 * @since 1.10.0
	 * @param int $page_id
	 * @return int
	 */
	public static function translate_results_page_id( $page_id ) {
		return apply_filters( 'wpml_object_id', $page_id, 'page' );
	}

	/**
	 * Collect geodata when WPML adds metadata without firing WP hooks.
	 *
	 * @param mixed $meta_value
	 * @param string $language
	 * @param array $context
	 *
	 * @return mixed
	 */
	public static function queue_post_meta( $meta_value, $language, $context )
    {
        self::$meta_queue[] = array(
            'post_id' => $context['post_id'],
            'key'     => $context['key'],
            'value'   => $meta_value,
        );
        // Our added post meta action won't work until the post cache is cleared
        // and it seems safer to do that on this action, near where WPML does it
        add_action('icl_make_duplicate', array(__CLASS__, 'add_queued_meta', 10, 4));

        return $meta_value;
    }

    /**
     * Clean the post cache and run our post meta action.
     *
     * @param int $master_post_id
     * @param string $lang
     * @param array $post_array
     * @param int $id
     *
     * @return mixed
     */
    public static function add_queued_meta( $master_post_id, $lang, $post_array, $id ) {
	    clean_post_cache( $id );
	    foreach (self::$meta_queue as $meta) {
            GeoMashupDB::action_added_post_meta( null, $meta['post_id'], $meta['key'], $meta['value'] );
        }
	}

	/**
	 * Whether WPML post location query filters have been suppressed.
	 * @since 1.9.0
	 * @return bool
	 */
	protected static function suppress_query_filters() {
		return defined( 'GEO_MASHUP_SUPPRESS_POST_FILTERS' ) && GEO_MASHUP_SUPPRESS_POST_FILTERS;
	}
}