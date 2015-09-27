<?php

/**
 * Container class for a an object location query.
 *
 * @since 1.7.0
 */
class GM_Location_Query {

	static private $no_results = array( '', '', '', '' );

	/**
	 * @var array query args
	 */
	private $query_args;

	/**
	 * Gets the default query arguments.
	 * @return array
	 */
	static public function get_defaults() {
		return array(
			'object_name' => null,
			'minlat' => null,
			'maxlat' => null,
			'minlon' => null,
			'maxlon' => null,
			'radius_km' => null,
			'radius_mi' => null,
			'near_lat' => null,
			'near_lng' => null,
			'admin_code' => null,
			'sub_admin_code' => null,
			'country_code' => null,
			'locality_name' => null,
			'saved_name' => null,
		);
	}

	/**
	 * Constructor.
	 *
	 * Parses a compact location query and sets defaults.
	 *
	 * @since 1.7.0
	 * @access public
	 *
	 * @param array $loc_query A loc query:
	 *  array(
	 *      'near_lat' => 39.5,
	 *      'near_lng' => -119.1,
	 *      'radius_km' => 50,
	 *  )
	 */
	public function __construct( $loc_query ) {
		$default_args = self::get_defaults();
		$this->query_args = wp_parse_args( $loc_query, $default_args );
	}


	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @param string $primary_table
	 * @param string $primary_id_column
	 * @return array columns, join, where, groupby
	 */
	public function get_sql( $primary_table, $primary_id_column ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		if ( empty( $this->query_args ) )
			return self::$no_results;

		if ( empty( $this->query_args['object_name'] ) )
			$this->query_args['object_name'] = GeoMashupDB::table_to_object_name( $primary_table );

		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$relationship_table = $wpdb->prefix . 'geo_mashup_location_relationships';

		$cols = ", $location_table.lat" .
			", $location_table.lng" .
			", $location_table.address" .
			", $location_table.saved_name " .
			", $location_table.postal_code " .
			", $location_table.admin_code " .
			", $location_table.sub_admin_code " .
			", $location_table.country_code " .
			", $location_table.locality_name ";
		$join = " INNER JOIN $relationship_table ON $relationship_table.object_id = $primary_table.$primary_id_column
			INNER JOIN $location_table ON $location_table.id = $relationship_table.location_id";

		$where = array(
			$wpdb->prepare( "$relationship_table.object_name=%s", $this->query_args['object_name'] ),
		);
		$groupby= '';

		// Check for a radius query
		if ( ! empty( $this->query_args['radius_mi'] ) ) {
			$this->query_args['radius_km'] = 1.609344 * floatval( $this->query_args['radius_mi'] );
		}
		if ( ! empty( $this->query_args['radius_km'] ) and is_numeric( $this->query_args['near_lat'] ) and is_numeric( $this->query_args['near_lng'] ) ) {
			// Earth radius = 6371 km, 3959 mi
			$near_lat = floatval( $this->query_args['near_lat'] );
			$near_lng = floatval( $this->query_args['near_lng'] );
			$radius_km = floatval( $this->query_args['radius_km'] );
			$cols .= ", 6371 * 2 * ASIN( SQRT( POWER( SIN( RADIANS( $near_lat - $location_table.lat ) / 2 ), 2 ) +
				COS( RADIANS( $near_lat ) ) * COS( RADIANS( $location_table.lat ) ) *
				POWER( SIN( RADIANS( $near_lng - $location_table.lng ) / 2 ), 2 ) ) ) AS distance_km";
			$groupby = "$primary_table.$primary_id_column HAVING distance_km < $radius_km";
			// approx 111 km per degree latitude
			$this->query_args['minlat'] = $near_lat - ( $radius_km / 111 );
			$this->query_args['maxlat'] = $near_lat + ( $radius_km / 111 );
			$this->query_args['minlon'] = $near_lng - ( $radius_km / ( abs( cos( deg2rad( $near_lat ) ) ) * 111 ) );
			$this->query_args['maxlon'] = $near_lng + ( $radius_km / ( abs( cos( deg2rad( $near_lat ) ) ) * 111 ) );
		}

		// Ignore nonsense bounds
		if ( $this->query_args['minlat'] && $this->query_args['maxlat'] && $this->query_args['minlat'] > $this->query_args['maxlat'] ) {
			$this->query_args['minlat'] = $this->query_args['maxlat'] = null;
		}
		if ( $this->query_args['minlon'] && $this->query_args['maxlon'] && $this->query_args['minlon'] > $this->query_args['maxlon'] ) {
			$this->query_args['minlon'] = $this->query_args['maxlon'] = null;
		}

		// Build bounding where clause
		if ( is_numeric( $this->query_args['minlat'] ) ) $where[] = "$location_table.lat > {$this->query_args['minlat']}";
		if ( is_numeric( $this->query_args['minlon'] ) ) $where[] = "$location_table.lng > {$this->query_args['minlon']}";
		if ( is_numeric( $this->query_args['maxlat'] ) ) $where[] = "$location_table.lat < {$this->query_args['maxlat']}";
		if ( is_numeric( $this->query_args['maxlon'] ) ) $where[] = "$location_table.lng < {$this->query_args['maxlon']}";

		$where_fields = array( 'sub_admin_code', 'admin_code', 'country_code', 'postal_code', 'geoname', 'locality_name', 'saved_name' );
		foreach ( $where_fields as $field ) {
			if ( !empty( $this->query_args[$field] ) )
				$where[] = $wpdb->prepare( "$location_table.$field = %s", $this->query_args[$field] );
		}

		if ( count( $where ) === 1 and empty( $groupby ) )
			return self::$no_results;

		$where = ' AND ( ' . implode( " AND ", $where ) . ' )';

		return array( $cols, $join, $where, $groupby );
	}

}
