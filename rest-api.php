<?php

/** @noinspection AutoloadingIssuesInspection */

/**
 * Integration with the WordPress REST API.
 *
 * @since 1.11.0
 */
class GeoMashupRestAPI {

	/**
	 * Register the Geo Mashup REST API elements.
	 *
	 * Call from the rest_api_init hook.
	 *
	 * @since 1.11.0
	 */
	public static function init() {
		register_rest_field(
			array( 'post', 'comment' ),
			'geo',
			array(
				'get_callback' => array( __CLASS__, 'get_geo' ),
				'schema'       => self::geo_schema(),
			)
		);
	}

	/**
	 * Add the geo field to a WordPress object.
	 *
	 * The get_callback for register_rest_field().
	 *
	 * @since 1.11.0
	 *
	 * @param array $object The WordPress object data as an associative array.
	 *
	 * @return array|null The geo field data for the given object.
	 */
	public static function get_geo( array $object ) {
		$object_type = func_get_arg( 3 );

		$location = GeoMashupDB::get_object_location( $object_type, $object['id'] );

		if ( empty( $location ) ) {
			return null;
		}

		return array(
			'latitude'    => (float) $location->lat,
			'longitude'   => (float) $location->lng,
			'description' => $location->address,
		);
	}

	/**
	 * @since 1.11.0
	 * @return array The geo field schema.
	 */
	public static function geo_schema() {
		return array(
			'readonly'    => true,
			'description' => __( 'Geo Mashup coordinates associated with the object.', 'GeoMashup' ),
			'type'        => 'object',
			'properties'  => array(
				'latitude'    => array(
					'description' => __( 'The decimal latitude in the WGS 84 datum.', 'GeoMashup' ),
					'type'        => 'number',
					'minimum'     => - 90,
					'maximum'     => 90,
				),
				'longitude'   => array(
					'description' => __( 'The decimal longitude in the WGS 84 datum.', 'GeoMashup' ),
					'type'        => 'number',
					'minimum'     => - 180,
					'maximum'     => 180,
				),
				'description' => array(
					'description' => __( 'An address or general description of the location.', 'GeoMashup' ),
					'type'        => 'string'
				)
			)
		);
	}
}