<?php

class GeoMashupTestCase extends WP_UnitTestCase {

	const DELTA = 0.0001;

	protected $added_filters;
	protected $data;

	function setUp() {
		parent::setUp();
		$this->added_filters = array();
		$this->data = new stdClass();
	}

	function tearDown() {
		global $wpdb;
		parent::tearDown();

		foreach( $this->added_filters as $filter ) {
			remove_filter( $filter['tag'], $filter['call'], $filter['priority'] );
		}
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_administrative_names" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_location_relationships" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_locations" );
	}

	protected function get_nv_test_location() {
		$location = GeoMashupDB::blank_location();
		$location->lat = 40;
		$location->lng = -119;
		return $location;
	}

	protected function rand_location( $decimal_places = 2 ) {
		$factor = pow( 10, $decimal_places );
		$location = GeoMashupDB::blank_location();
		$location->lat = rand( -90*$factor, 90*$factor ) / $factor;
		$location->lng = rand( -180*$factor, 180*$factor ) / $factor;
		return $location;
	}

	protected function generate_rand_located_posts( $count ) {
		$post_ids = $this->factory->post->create_many( $count );
		foreach( $post_ids as $post_id ) {
			GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );
		}
		return $post_ids;
	}

	protected function add_action( $tag, $call, $priority = 10, $accepted_args = 1 ) {
		return $this->add_filter( $tag, $call, $priority, $accepted_args );
	}

	protected function add_filter( $tag, $call, $priority = 10, $accepted_args = 1 ) {
		$this->added_filters[] = compact( 'tag', 'call', 'priority' );
		return add_filter( $tag, $call, $priority, $accepted_args );
	}
}