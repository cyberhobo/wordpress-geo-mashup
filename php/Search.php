<?php

namespace GeoMashup;

use GeoMashup;
use GeoMashupDB;
use WP_User;

class Search {
	const MILES_PER_KILOMETER = 0.621371;
	const NAUTICAL_MILES_PER_KILOMETER = 0.5399565;

	/**
	 * Plugin URL path.
	 * @deprecated Use GEO_MASHUP_URL_PATH.
	 */
	public $url_path;

	private $results;
	private $result;
	private $current_result;
	private $result_count;
	private $query_vars = array();
	private $near_location;
	private $units;
	private $max_km;
	private $distance_factor;

	/**
	 * Constructor.
	 *
	 * Sets up the query if included.
	 *
	 * @param string|array $query Search parameters.
	 *
	 * @since 1.5
	 */
	public function __construct( $query ) {

		// Back compat
		/** @noinspection PhpDeprecationInspection */
		$this->url_path = GEO_MASHUP_URL_PATH;

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Run a search query.
	 *
	 * @param string|array $args Search parameters.
	 *
	 * @return array Search results.
	 * @since 1.5
	 * @uses apply_filters() geo_mashup_search_query_args Filter the geo query arguments.
	 *
	 */
	public function query( $args ) {

		$default_args                      = array(
			'object_name'        => 'post',
			'object_ids'         => null,
			'exclude_object_ids' => null,
			'units'              => 'km',
			'location_text'      => '',
			'radius'             => null,
			'sort'               => 'distance_km ASC',
		);
		$this->query_vars                  = wp_parse_args( $args, $default_args );
		$this->query_vars['location_text'] = apply_filters( 'geo_mashup_search_query_location_text', $this->query_vars['location_text'] );

		$near_lat = $near_lng = $location_text = $geolocation = $radius = $map_terms = $map_cat = $map_post_type = null;
		/** @var string $units */
		/** @var string $object_name */
		/** @var string $taxonomy */
		extract( $this->query_vars, EXTR_OVERWRITE );

		$this->results         = array();
		$this->result_count    = 0;
		$this->result          = null;
		$this->current_result  = - 1;
		$this->units           = $units;
		$this->max_km          = 20000;
		$this->distance_factor = $this->distance_factor_for($units);
		$this->near_location   = GeoMashupDB::blank_location( ARRAY_A );

		$geo_query_args = wp_array_slice_assoc(
			$this->query_vars,
			array( 'object_name', 'sort', 'exclude_object_ids', 'limit' )
		);

		if ( ! empty( $near_lat ) && ! empty( $near_lng ) ) {

			$this->near_location['lat'] = $near_lat;
			$this->near_location['lng'] = $near_lng;

		} else if ( ! empty( $location_text ) ) {

			$geocode_text = empty( $geolocation ) ? $location_text : $geolocation;

			if ( ! GeoMashupDB::geocode( $geocode_text, $this->near_location ) ) {
				// No search center was found, we can't continue
				return $this->results;
			}

		} else {

			// No coordinates to search near
			return $this->results;

		}

		$radius_km = $this->max_km;

		if ( ! empty( $radius ) ) {
			$radius_km = abs( $radius ) / $this->distance_factor;
		}

		$geo_query_args['radius_km'] = $radius_km;
		$geo_query_args['near_lat']  = $this->near_location['lat'];
		$geo_query_args['near_lng']  = $this->near_location['lng'];

		//Set tax_query
		if ( isset( $map_terms ) ) {

			if ( 'all' !== $map_terms ) {

				$geo_query_args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
						'terms'    => $map_terms,
						'field'    => 'term_id',
					)
				);
			} else {
				$geo_query_args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
						'operator' => 'EXISTS'
					)
				);
			}
		}

		// Backward compatibility for categories
		if ( isset( $map_cat ) ) {
			$geo_query_args['map_cat'] = $map_cat;
		}

		// Set post_type
		if ( $object_name === 'post' && isset( $map_post_type ) ) {
			$geo_query_args['map_post_type'] = $map_post_type;
		}

		$geo_query_args = apply_filters( 'geo_mashup_search_query_args', $geo_query_args );

		$this->results      = GeoMashupDB::get_object_locations( $geo_query_args );
		$this->result_count = count( $this->results );
		if ( $this->result_count > 0 ) {
			$this->max_km = $this->results[ $this->result_count - 1 ]->distance_km;
		} else {
			$this->max_km = $radius_km;
		}

		return $this->results;
	}

	/**
	 * Output search results.
	 *
	 * @param string $template
	 */
	public function load_template( $template = 'search-results' ) {

		// Define variables for the template
		/** @var $object_name */
		/** @var $object_ids */
		/** @var $units */
		/** @var $location_text */
		/** @var $radius */
		/** @var $sort */
		extract( $this->query_vars, EXTR_OVERWRITE );

		extract( [
			'search_text'       => $location_text,
			'distance_factor'   => $this->distance_factor,
			'near_location'     => $this->near_location,
			'result_count'      => $this->result_count,
			'geo_mashup_search' => &$this,
			'approximate_zoom'  => absint( log( 10000 / $this->max_km, 2 ) )
		], EXTR_OVERWRITE );

		// Buffer output from the template
		$template = GeoMashup::locate_template( $template );

		// Load the template with local variables
		/** @noinspection PhpIncludeInspection */
		require( $template );
	}

	/**
	 * Whether there are more results to loop through.
	 *
	 * @return boolean True if there are more results, otherwise false.
	 */
	public function have_posts() {
		if ( $this->current_result + 1 < $this->result_count ) {
			return true;
		}

		if ( $this->current_result + 1 === $this->result_count && $this->result_count > 0 ) {
			wp_reset_postdata();
		}

		return false;
	}

	/**
	 * Get an array of the post IDs found.
	 *
	 * @return array Post IDs.
	 */
	public function get_the_IDs() {
		return wp_list_pluck( $this->results, 'object_id' );
	}

	/**
	 * Get a comma separated list of the post IDs found.
	 *
	 * @noinspection PhpUnused
	 * @return string ID list.
	 */
	public function get_the_ID_list() {
		return implode( ',', $this->get_the_IDs() );
	}

	/**
	 * Get a comma separated list of the post IDs found.
	 *
	 * @return WP_User User data if found.
	 */
	public function get_userdata() {
		$this->current_result ++;
		$this->result = $this->results[ $this->current_result ];
		$user         = null;
		if ( $this->result ) {
			$user = get_userdata( $this->result->object_id );
		}

		return $user;
	}

	/**
	 * Set up the the current post to use in the results loop.
	 */
	public function the_post() {
		global $post;
		$this->current_result ++;
		$this->result = $this->results[ $this->current_result ];
		if ( $this->result ) {
			$post = get_post( $this->result->object_id );
			setup_postdata( $post );
		}
	}

	/**
	 * Display or retrieve the distance from the search point to the current result.
	 *
	 * @param string|array $args Tag arguments
	 *
	 * @return null|string Null on failure or display, string if echo is false.
	 * @noinspection PhpUnused
	 */
	public function the_distance( $args = '' ) {

		if ( empty( $this->result ) ) {
			return null;
		}

		$default_args = array(
			'decimal_places' => 2,
			'append_units'   => true,
			'echo'           => true
		);
		$args         = wp_parse_args( $args, $default_args );
		/** @var $decimal_places */
		/** @var $append_units */
		/** @var $echo */
		extract( $args, EXTR_OVERWRITE );
		$factor   = ( 'km' === $this->units ) ? 1 : self::MILES_PER_KILOMETER;
		$distance = round( $this->result->distance_km * $factor, $decimal_places );
		$distance = number_format_i18n( $distance, $decimal_places );
		if ( $append_units ) {
			$distance .= ' ' . $this->units;
		}
		if ( $echo ) {
			echo $distance;
			return null;
		}

		return $distance;
	}

	/**
	 * Add a script to modify form behavior.
	 *
	 * @param string $handle Handle the script was registered with
	 * @noinspection PhpUnused
	 */
	public function enqueue_script( $handle ) {
		// As of WP 3.3 we can enqueue scripts any time
		wp_enqueue_script( $handle );
	}

	private function distance_factor_for($units) {
		if ( 'km' === $units ) {
			return 1;
		}

		if ( 'nm' === $units ) {
			return self::NAUTICAL_MILES_PER_KILOMETER;
		}

		return self::MILES_PER_KILOMETER;
	}
}
