<?php
class GeoMashupDB_Unit_Tests extends WP_UnitTestCase {

	const DELTA = 0.0001;

	function tearDown() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_administrative_names" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_location_relationships" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_locations" );
	}

	function test_activation_log() {
		$test_string = rand_str();
		GeoMashupDB::activation_log( $test_string, true );
		$this->assertContains( $test_string, GeoMashupDB::activation_log() );
	}
	
	function test_location_fields() {
		
		$blank_location = GeoMashupDB::blank_location();
		$this->assertObjectHasAttribute( 'lat', $blank_location );
		$this->assertTrue( GeoMashupDB::are_any_location_fields_empty( $blank_location ) );

		$test_location = $this->get_nv_test_location();
		$this->assertTrue( GeoMashupDB::are_any_location_fields_empty( $test_location ) );
		$this->assertFalse( GeoMashupDB::are_any_location_fields_empty( $test_location, array( 'lat', 'lng' ) ) );
	}

	function test_location_crud() {

		// create
		$location = $this->get_nv_test_location();
		$id = GeoMashupDB::set_location( $location, $do_lookups = false );
		$this->assertFalse( is_wp_error( $id ) );
		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// read
		$out = GeoMashupDB::get_location( $id );
		$this->assertEquals( $location->lat, $out->lat );
		$this->assertEquals( $location->lng, $out->lng );

		// read cache
		$lcache = wp_cache_get( $id, 'geo_mashup_locations' );
		$this->assertInstanceOf( 'stdClass', $lcache );
		$this->assertEquals( $id, $lcache->id );

		// update
		$out->locality_name = rand_str();
		$update_id = GeoMashupDB::set_location( $out, $do_lookups = false );
		$this->assertEquals( $update_id, $id );
		$out2 = GeoMashupDB::get_location( $id );
		$this->assertEquals( $id, $out2->id );
		$this->assertEquals( $out->locality_name, $out2->locality_name );

		// delete
		GeoMashupDB::delete_location( $id );
		$out = GeoMashupDB::get_location( $id );
		$this->assertNull( $out );
	}

	function test_object_location_crud() {

		$post_id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$location = $this->get_nv_test_location();

		// create
		$location_id = GeoMashupDB::set_object_location( 'post', $post_id, $location, $do_lookups = false );
		$this->assertFalse( is_wp_error( $location_id ) );
		$this->assertTrue( is_numeric( $location_id ) );
		$this->assertTrue( $location_id > 0 );

		// read
		$out = GeoMashupDB::get_object_location( 'post', $post_id );
		$this->assertNotNull( $out );
		$this->assertEquals( $location->lat, $out->lat );
		$this->assertEquals( $location->lng, $out->lng );

		// update
		$geo_date = '2013-01-04 00:00:00';
		$update_id = GeoMashupDB::set_object_location( 'post', $post_id, $location_id, $do_lookups = false, $geo_date );
		$this->assertFalse( is_wp_error( $update_id ) );
		$out = GeoMashupDB::get_object_location( 'post', $post_id );
		$this->assertEquals( $out->geo_date, $geo_date );

		// delete
		$rows_affected = GeoMashupDB::delete_object_location( 'post', $post_id );
		$this->assertEquals( $rows_affected, 1 );
		$out = GeoMashupDB::get_object_location( 'post', $post_id );
		$this->assertNull( $out );
	}

	function test_radius_query() {
		$posts = $this->factory->post->create_many( 2 );

		// Two points in Nevada about 15km apart
		$coordinates = array( 
			array( 
				'lat' => 39.255651,
				'lng' => -118.951142,
			), 
			array(
				'lat' => 39.160414,
				'lng' => -118.821366,
			)
		);
		$post_locs = array();
		$post_locs[0] = GeoMashupDB::set_object_location( 'post', $posts[0], $coordinates[0], $do_lookups = false );
		$post_locs[1] = GeoMashupDB::set_object_location( 'post', $posts[1], $coordinates[1], $do_lookups = false );
		
		$no_results = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'near_lat' => 0,
			'near_lng' => 0,
			'radius_km' => 10,
			'sort' => 'distance_km ASC',
		) );
		$this->assertTrue( count( $no_results ) === 0 );

		$one_result = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'near_lat' => $coordinates[0]['lat'],
			'near_lng' => $coordinates[0]['lng'],
			'radius_km' => 10,
			'sort' => 'distance_km ASC',
		) );
		$this->assertTrue( count( $one_result ) === 1 );
		$this->assertEquals( $coordinates[0]['lat'], $one_result[0]->lat, '', self::DELTA );
		$this->assertEquals( $coordinates[0]['lng'], $one_result[0]->lng, '', self::DELTA );
		$this->assertEquals( $one_result->object_id, $post_locs[0]->ID );

		$two_results = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'near_lat' => $coordinates[0]['lat'],
			'near_lng' => $coordinates[0]['lng'],
			'radius_km' => 20,
			'sort' => 'distance_km ASC',
		) );
		$this->assertTrue( count( $two_results ) === 2 );
		$this->assertEquals( $coordinates[1]['lat'], $two_results[1]->lat, '', self::DELTA );
		$this->assertEquals( $coordinates[1]['lng'], $two_results[1]->lng, '', self::DELTA );
		$this->assertEquals( $post_locs[1]->ID, $two_results->object_id );
	}

	function test_query_offset() {
		$posts = $this->factory->post->create_many( 2 );
		$post_locs = array();
		$post_locs[0] = GeoMashupDB::set_object_location( 
			'post', 
			$posts[0], 
			$this->rand_location(),
			$do_lookups = false
		);
		$post_locs[1] = GeoMashupDB::set_object_location( 
			'post', 
			$posts[1], 
			$this->rand_location(),
			$do_lookups = false
		);

		$result = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'sort' => 'object_id ASC',
			'map_offset' => 1,
			'limit' => 1,
		) );

		$this->assertTrue( count( $result ) === 1 );
		$this->assertEquals( $posts[1], $result[0]->object_id );
		$this->assertEquals( $post_locs[1], $result[0]->id );
	}

	function test_copy_from_geodata() {
		global $geo_mashup_options;
		$geo_mashup_options->set_valid_options( array(
			'overall' => array( 'copy_geodata' => 'true' )
		) );
		GeoMashupDB::add_geodata_sync_hooks();

		$post_id = $this->factory->post->create();

		$loc = $this->rand_location();
		$geo_date = '2013-01-05 00:00:00';
		update_post_meta( $post_id, 'geo_date', $geo_date );
		update_post_meta( $post_id, 'geo_latitude', $loc->lat );
		update_post_meta( $post_id, 'geo_longitude', $loc->lng );

		$out = GeoMashupDB::get_object_location( 'post', $post_id );
		$this->assertEquals( $geo_date, $out->geo_date );
		$this->assertEquals( $loc->lat, $out->lat, '', self::DELTA );
		$this->assertEquals( $loc->lng, $out->lng, '', self::DELTA );
	}

	function test_copy_to_geodata() {
		global $geo_mashup_options;
		$geo_mashup_options->set_valid_options( array(
			'overall' => array( 'copy_geodata' => 'true' )
		) );
		GeoMashupDB::add_geodata_sync_hooks();

		$post_id = $this->factory->post->create();

		$loc = $this->rand_location();
		$loc_id = GeoMashupDB::set_object_location( 'post', $post_id, $loc, $do_lookups = false );
		
		$sync_lat = get_post_meta( $post_id, 'geo_latitude', true );
		$this->assertEquals( $loc->lat, $sync_lat, '', self::DELTA );

		$sync_lng = get_post_meta( $post_id, 'geo_longitude', true );
		$this->assertEquals( $loc->lng, $sync_lng, '', self::DELTA ); 

		$geo_date = get_post_meta( $post_id, 'geo_date', true );
		$this->assertFalse( empty( $geo_date ) );
	}

	function test_auto_post_join() {

		// A query for post locations should honor posts_where and related filters
		$posts = array(
			$this->factory->post->create_and_get(),
			$this->factory->post->create_and_get(),
			$this->factory->post->create_and_get(),
		);
		GeoMashupDB::set_object_location( 
			'post', 
			$posts[0]->ID, 
			$this->rand_location(),
			$do_lookups = false
		);
		GeoMashupDB::set_object_location( 
			'post', 
			$posts[1]->ID, 
			$this->rand_location(),
			$do_lookups = false
		);
		GeoMashupDB::set_object_location( 
			'post', 
			$posts[2]->ID, 
			$this->rand_location(),
			$do_lookups = false
		);

		$where_filter = create_function( 
			'$where', 
			'return $where .= \' AND post_title="' . $posts[1]->post_title . '"\';'
		);
		add_filter( 'posts_where', $where_filter );

		// An open query should only return post index 1 of the 3
		$results = GeoMashupDB::get_object_locations();
		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $posts[1]->ID, $results[0]->object_id );

		// Explicitly suppress post filters
		$results = GeoMashupDB::get_object_locations( 'suppress_filters=1' );
		$this->assertEquals( 3, count( $results ) );

		// Disable post filters
		define( 'GEO_MASHUP_SUPPRESS_POST_FILTERS', true );
		$results = GeoMashupDB::get_object_locations();
		$this->assertEquals( 3, count( $results ) );
	}

	private function get_nv_test_location() {
		$location = GeoMashupDB::blank_location();
		$location->lat = 40;
		$location->lng = -119;
		return $location;
	}

	private function rand_location() {
		$location = GeoMashupDB::blank_location();
		$location->lat = rand( -9000, 9000 ) / 100;
		$location->lng = rand( -180000, 180000 ) / 100;
		return $location;
	}

}
