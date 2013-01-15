<?php

class GeoMashup_Unit_Tests extends WP_UnitTestCase {

	const DELTA = 0.0001;

	function tearDown() {
		global $wpdb;
		parent::tearDown();
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_administrative_names" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_location_relationships" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_locations" );
	}

	function test_geo_mashup_class_exists() {
		$this->assertTrue( class_exists( 'GeoMashup' ), 'The GeoMashup class is missing.' );
	}

	function test_explode_assoc() {
		$result = GeoMashup::explode_assoc( '=', '&', 'a=1&10=&b=test' );
		$this->assertEquals( array( 'a' => '1', 10 => '', 'b' => 'test' ), $result );
	}
	
	function test_empty_map() {
		$output = GeoMashup::map();
		$this->assertStringMatchesFormat( '<!--%s-->', $output );
	}

	function test_build_home_url() {
		$url = GeoMashup::build_home_url();
		$this->assertStringMatchesFormat( 
			'%s/', 
			$url,
			'An empty home URL should have a trailing slash!' 
		);

		$args = array( 
			'arg1' => 10,
			'arg2' => '"a value"',
		);

		$url = GeoMashup::build_home_url( $args );
		$url_parts = parse_url( $url );
		$this->assertNotEmpty( $url_parts['query'] );
		$parsed_args = wp_parse_args( htmlspecialchars_decode( $url_parts['query'] ) );
		$this->assertEquals( $args, $parsed_args );

		// It must work with an ampersand entity specified as separator also 
		ini_set( 'arg_separator.output', '&amp;' );
		$url = GeoMashup::build_home_url( $args );
		$url_parts = parse_url( $url );
		$this->assertNotEmpty( $url_parts['query'] );
		$parsed_args = wp_parse_args( htmlspecialchars_decode( $url_parts['query'] ) );
		$this->assertEquals( $args, $parsed_args );

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

	/**
	* issue 550
	*/
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

	/**
	* issue 607
	*/
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

	/**
	* issue 507
	*/
	function test_combine_import_fields() {
		// This test calls geocoding services, but allows for failure
		global $geo_mashup_options;

		$geo_mashup_options->set_valid_options( array( 'overall' => array( 'import_custom_field' => 'geo1, geo2' ) ) );
		GeoMashupDB::add_geodata_sync_hooks();

		$post_id = $this->factory->post->create();
		$this->assertEmpty( GeoMashupDB::get_object_location( 'post', $post_id ) );

		update_post_meta( $post_id, 'geo1', 'Paris' );
		$this->assertEmpty( GeoMashupDB::get_object_location( 'post', $post_id ) );

		update_post_meta( $post_id, 'geo2', 'France' );
		$error = get_post_meta( $post_id, 'geocoding_error', true );
		if ( $error ) {

			// If we can't geocode, no location is saved
			$this->assertEmpty( GeoMashupDB::get_object_location( 'post', $post_id ) );

		} else {

			$location = GeoMashupDB::get_object_location( 'post', $post_id );
			$this->assertEquals( 48, intval( $location->lat ) );
			$this->assertEquals( 2, intval( $location->lng ) );

			// A second update overwrites if successful
			update_post_meta( $post_id, 'geo2', 'Texas' );
			$error = get_post_meta( $post_id, 'geocoding_error', true );
			if ( $error ) {

				// Failed geocoding leaves the first result
				$location = GeoMashupDB::get_object_location( 'post', $post_id );
				$this->assertEquals( 48, intval( $location->lat ) );
				$this->assertEquals( 2, intval( $location->lng ) );

			} else {

				// Location is updated to Paris, TX
				$location = GeoMashupDB::get_object_location( 'post', $post_id );
				$this->assertEquals( 33, intval( $location->lat ) );
				$this->assertEquals( -95, intval( $location->lng ) );

			}
		}
	}

	function test_tax_query() {
		$taxonomy = WP_UnitTest_Factory_For_Term::DEFAULT_TAXONOMY;

		$bare_post_ids = $this->factory->post->create_many( 2 );
		GeoMashupDB::set_object_location( 'post', $bare_post_ids[0], $this->rand_location(), false );
		GeoMashupDB::set_object_location( 'post', $bare_post_ids[1], $this->rand_location(), false );

		$tag1_post_ids = $this->factory->post->create_many( 2 );
		wp_set_post_terms( $tag1_post_ids[0], 'tag1' );
		GeoMashupDB::set_object_location( 'post', $tag1_post_ids[0], $this->rand_location(), false );
		wp_set_post_terms( $tag1_post_ids[1], 'tag1' );
		GeoMashupDB::set_object_location( 'post', $tag1_post_ids[1], $this->rand_location(), false );

		$tag2_post_ids = $this->factory->post->create_many( 2 );
		wp_set_post_terms( $tag2_post_ids[0], 'tag2' );
		GeoMashupDB::set_object_location( 'post', $tag2_post_ids[0], $this->rand_location(), false );
		wp_set_post_terms( $tag2_post_ids[1], 'tag2' );
		GeoMashupDB::set_object_location( 'post', $tag2_post_ids[1], $this->rand_location(), false );

		$tag1_locs = GeoMashupDB::get_object_locations( array( 
			'tax_query' => array(
				array( 
					'taxonomy' => $taxonomy,
					'terms' => 'tag1',
					'field' => 'slug',
				)
			)
		) );
		$this->assertEquals( 2, count( $tag1_locs ) );
		$this->assertContains( $tag1_post_ids[0], wp_list_pluck( $tag1_locs, 'object_id' ) );
		$this->assertContains( $tag1_post_ids[1], wp_list_pluck( $tag1_locs, 'object_id' ) );

		$tag_locs = GeoMashupDB::get_object_locations( array( 
			'tax_query' => array(
				array( 
					'taxonomy' => $taxonomy,
					'terms' => array( 'tag1' , 'tag2' ),
					'field' => 'slug',
				)
			)
		) );
		$this->assertEquals( 4, count( $tag_locs ) );
		$this->assertContains( $tag1_post_ids[0], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( $tag1_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( $tag2_post_ids[0], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( $tag2_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );
					
	}
	/**
	* issue 581
	*/
	function test_static_map_filter() {
		$filter = create_function( 
			'$map_image, $map_data, $args',
			'return str_replace( "color:red", "color:blue", $map_image );'
		);

		add_filter( 'geo_mashup_static_map', $filter, 10, 3 );

		$post_id = $this->factory->post->create();
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );
		$static_map = GeoMashup::map( 'map_content=single&static=true&object_id=' . $post_id );
		$this->assertContains( 'color:blue', $static_map );
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
		$location->lng = rand( -18000, 18000 ) / 100;
		return $location;
	}

}
