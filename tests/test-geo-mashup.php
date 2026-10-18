<?php

class GeoMashup_Unit_Tests extends GeoMashupTestCase {

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
		$this->assertStringContainsString( $test_string, GeoMashupDB::activation_log() );
	}
	
	function test_location_fields() {
		
		$blank_location = GeoMashupDB::blank_location();
		$this->assertObjectHasProperty( 'lat', $blank_location );
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

		$delta = mt_rand( 1, 1000 ) / 10000000;
		$one_result = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'near_lat' => $coordinates[0]['lat'] + $delta,
			'near_lng' => $coordinates[0]['lng'] + $delta,
			'radius_km' => 0.5,
			'sort' => 'distance_km ASC',
		) );
		$this->assertTrue( count( $one_result ) === 1 );
		$this->assertEqualsWithDelta( $coordinates[0]['lat'], $one_result[0]->lat, self::DELTA );
		$this->assertEqualsWithDelta( $coordinates[0]['lng'], $one_result[0]->lng, self::DELTA );
		$this->assertEquals( $posts[0], $one_result[0]->object_id );

		$two_results = GeoMashupDB::get_object_locations( array(
			'object_name' => 'post',
			'near_lat' => $coordinates[0]['lat'] + $delta,
			'near_lng' => $coordinates[0]['lng'] + $delta,
			'radius_km' => 20,
			'sort' => 'distance_km ASC',
		) );
		$this->assertTrue( count( $two_results ) === 2 );
		$this->assertEqualsWithDelta( $coordinates[1]['lat'], $two_results[1]->lat, self::DELTA );
		$this->assertEqualsWithDelta( $coordinates[1]['lng'], $two_results[1]->lng, self::DELTA );
		$this->assertEquals( $posts[1], $two_results[1]->object_id );
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
		$this->assertEqualsWithDelta( $loc->lat, $out->lat, self::DELTA );
		$this->assertEqualsWithDelta( $loc->lng, $out->lng, self::DELTA );

		$user_id = $this->factory->user->create();
		$loc = $this->rand_location();
		$geo_date = '2013-12-30 00:00:00';
		update_user_meta( $user_id, 'geo_date', $geo_date );
		update_user_meta( $user_id, 'geo_latitude', $loc->lat );
		update_user_meta( $user_id, 'geo_longitude', $loc->lng );

		$out = GeoMashupDB::get_object_location( 'user', $user_id );
		$this->assertEquals( $geo_date, $out->geo_date );
		$this->assertEqualsWithDelta( $loc->lat, $out->lat, self::DELTA );
		$this->assertEqualsWithDelta( $loc->lng, $out->lng, self::DELTA );
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
		$this->assertEqualsWithDelta( $loc->lat, $sync_lat, self::DELTA );

		$sync_lng = get_post_meta( $post_id, 'geo_longitude', true );
		$this->assertEqualsWithDelta( $loc->lng, $sync_lng, self::DELTA ); 

		$geo_date = get_post_meta( $post_id, 'geo_date', true );
		$this->assertFalse( empty( $geo_date ) );

		$user_id = $this->factory->user->create();

		$loc = $this->rand_location();
		$loc_id = GeoMashupDB::set_object_location( 'user', $user_id, $loc, $do_lookups = false );

		$sync_lat = get_user_meta( $user_id, 'geo_latitude', true );
		$this->assertEqualsWithDelta( $loc->lat, $sync_lat, self::DELTA );

		$sync_lng = get_user_meta( $user_id, 'geo_longitude', true );
		$this->assertEqualsWithDelta( $loc->lng, $sync_lng, self::DELTA );

		$geo_date = get_user_meta( $user_id, 'geo_date', true );
		$this->assertFalse( empty( $geo_date ) );
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

				// Failed geocoding due to lack of internet leaves the first result
				$location = GeoMashupDB::get_object_location( 'post', $post_id );
				$this->assertEquals( 48, intval( $location->lat ), $error );
				$this->assertEquals( 2, intval( $location->lng ), $error );

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
		$this->assertContains( (string) $tag1_post_ids[0], wp_list_pluck( $tag1_locs, 'object_id' ) );
		$this->assertContains( (string) $tag1_post_ids[1], wp_list_pluck( $tag1_locs, 'object_id' ) );

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
		$this->assertContains( (string) $tag1_post_ids[0], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( (string) $tag1_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( (string) $tag2_post_ids[0], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( (string) $tag2_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );
					
	}

	function test_tax_cat_query() {
		$tag_taxonomy = WP_UnitTest_Factory_For_Term::DEFAULT_TAXONOMY;
		$cat_taxonomy = 'category';

		// Hierarchical terms must be inserted by ID
		$cat1_ids = wp_insert_term( 'category1', $cat_taxonomy );
		$cat1_term_id = $cat1_ids['term_id'];

		$bare_post_ids = $this->factory->post->create_many( 2 );
		GeoMashupDB::set_object_location( 'post', $bare_post_ids[0], $this->rand_location(), false );
		wp_set_post_terms( $bare_post_ids[1], $cat1_term_id, $cat_taxonomy );
		GeoMashupDB::set_object_location( 'post', $bare_post_ids[1], $this->rand_location(), false );

		$tag1_post_ids = $this->factory->post->create_many( 2 );
		wp_set_post_terms( $tag1_post_ids[0], 'tag1', $tag_taxonomy );
		GeoMashupDB::set_object_location( 'post', $tag1_post_ids[0], $this->rand_location(), false );
		wp_set_post_terms( $tag1_post_ids[1], 'tag1', $tag_taxonomy );
		wp_set_post_terms( $tag1_post_ids[1], $cat1_term_id, $cat_taxonomy );
		GeoMashupDB::set_object_location( 'post', $tag1_post_ids[1], $this->rand_location(), false );

		$tag2_post_ids = $this->factory->post->create_many( 2 );
		wp_set_post_terms( $tag2_post_ids[0], 'tag2', $tag_taxonomy );
		GeoMashupDB::set_object_location( 'post', $tag2_post_ids[0], $this->rand_location(), false );
		wp_set_post_terms( $tag2_post_ids[1], 'tag2', $tag_taxonomy );
		wp_set_post_terms( $tag2_post_ids[1], $cat1_term_id, $cat_taxonomy );
		GeoMashupDB::set_object_location( 'post', $tag2_post_ids[1], $this->rand_location(), false );

		$query = array(
			'map_cat' => 'category1',
			'tax_query' => array(
				array(
					'taxonomy' => $tag_taxonomy,
					'terms' => 'tag1',
					'field' => 'slug',
				)
			)
		);

		$tag1_locs = GeoMashupDB::get_object_locations( $query );
		$this->assertEquals( 1, count( $tag1_locs ) );
		$this->assertContains( (string) $tag1_post_ids[1], wp_list_pluck( $tag1_locs, 'object_id' ) );

		$tag_locs = GeoMashupDB::get_object_locations( array(
			'map_cat' => 'category1',
			'tax_query' => array(
				array(
					'taxonomy' => $tag_taxonomy,
					'terms' => array( 'tag1' , 'tag2' ),
					'field' => 'slug',
				)
			)
		) );
		$this->assertEquals( 2, count( $tag_locs ) );
		$this->assertContains( (string) $tag1_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );
		$this->assertContains( (string) $tag2_post_ids[1], wp_list_pluck( $tag_locs, 'object_id' ) );

	}

	/**
	* issue 581
	*/
	function test_static_map_filter() {
		$filter = function($map_image, $map_data, $args) {
			return str_replace( "color:red", "color:blue", $map_image );
		};

		$this->add_filter( 'geo_mashup_static_map', $filter, 10, 3 );

		list( $post_id ) = $this->generate_rand_located_posts( 1 );
		$static_map = GeoMashup::map( 'map_content=single&static=true&object_id=' . $post_id );
		$this->assertStringContainsString( 'color:blue', $static_map );
	}

	/**
	* issue 612
	*/
	function test_wp_query_single_map() {
		$post_id = $this->factory->post->create( array( 
			'post_content' => '[geo_mashup_map map_content="single"]',
		) );
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );
		
		$test_query = new WP_Query( array( 
			'posts_per_page' => 1,
		) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$this->assertStringContainsString( '<iframe', apply_filters( 'the_content', get_the_content() ) );
		wp_reset_postdata();
	}

	/**
	 * Security: the name shortcode attribute must be escaped in the map iframe,
	 * or a contributor can inject an event handler attribute (XSS).
	 */
	function test_map_name_attribute_is_escaped() {
		$malicious_name = 'x" onload="alert(document.domain)';
		$post_id = $this->factory->post->create( array(
			'post_content' => "[geo_mashup_map load_empty_map=true name='{$malicious_name}']",
		) );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		$this->assertStringContainsString( '<iframe', $content );
		$this->assertStringNotContainsString( 'onload="alert', $content );
		$this->assertStringContainsString( esc_attr( $malicious_name ), $content );
	}

	/**
	 * Security: the shape shortcode attribute reaches the div style attribute
	 * via the same sprintf() as the name attribute, unescaped and
	 * unvalidated, letting a contributor break out and inject an event
	 * handler (XSS).
	 */
	function test_map_shape_attribute_is_escaped() {
		$malicious_shape = '0%" onmouseover="alert(1)';
		$post_id = $this->factory->post->create( array(
			'post_content' => "[geo_mashup_map load_empty_map=true shape='{$malicious_shape}']",
		) );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		$this->assertStringContainsString( '<iframe', $content );
		$this->assertStringNotContainsString( 'onmouseover="alert', $content );
	}

	/**
	 * Security: the zoom shortcode attribute is concatenated unescaped into
	 * the static map image's src attribute, letting a contributor break out
	 * and inject an event handler (XSS). Requires static=true and a located
	 * object so build_map_image() actually runs.
	 */
	function test_static_map_zoom_attribute_is_escaped() {
		$malicious_zoom = '10" onerror="alert(1)';
		$post_id = $this->factory->post->create( array(
			'post_content' => "[geo_mashup_map map_content=\"single\" static=\"true\" zoom='{$malicious_zoom}']",
		) );
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		$this->assertStringContainsString( '<img', $content );
		$this->assertStringNotContainsString( 'onerror="alert', $content );
	}

	/**
	 * Security: the click_to_load_text shortcode attribute is concatenated
	 * unescaped into the static map image's title attribute inside
	 * build_map_image(), which runs before click_to_load_content()'s
	 * esc_html() ever sees the value, letting a contributor break out and
	 * inject an event handler (XSS).
	 */
	function test_static_map_click_to_load_text_is_escaped() {
		$malicious_text = 'x" onmouseover="alert(1)';
		$post_id = $this->factory->post->create( array(
			'post_content' => "[geo_mashup_map map_content=\"single\" static=\"true\" click_to_load=\"true\" click_to_load_text='{$malicious_text}']",
		) );
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		$this->assertStringContainsString( '<img', $content );
		$this->assertStringNotContainsString( 'onmouseover="alert', $content );
	}

	/**
	 * Security: build_map_data() must not let request/shortcode data override
	 * the trusted, server-computed URL properties (ajaxurl, siteurl, url_path,
	 * template_url_path, custom_url_path). Those values reach GeoMashup.opts
	 * in JavaScript and are trusted there.
	 */
	function test_build_map_data_ignores_request_urls() {
		$query = array(
			'map_content' => 'global',
			'load_empty_map' => 'true',
			'ajaxurl' => 'https://evil.example/ajax',
			'siteurl' => 'https://evil.example/',
			'url_path' => 'https://evil.example/plugin',
			'template_url_path' => 'https://evil.example/theme',
			'custom_url_path' => 'https://evil.example/custom',
		);

		$map_data = GeoMashup::build_map_data( $query );

		$this->assertNotWPError( $map_data );
		$this->assertSame( admin_url( 'admin-ajax.php' ), $map_data['ajaxurl'] );
		$this->assertSame( home_url( '/' ), $map_data['siteurl'] );
		$this->assertSame( GEO_MASHUP_URL_PATH, $map_data['url_path'] );
		$this->assertSame( get_stylesheet_directory_uri(), $map_data['template_url_path'] );
		$this->assertNotEquals(
			'https://evil.example/custom',
			isset( $map_data['custom_url_path'] ) ? $map_data['custom_url_path'] : null
		);
	}

	/**
	 * Security hardening: the map data key names the transient a request reads
	 * its map data from, so only keys in the format we generate may be looked
	 * up. Otherwise a visitor picks which stored value is loaded and rendered.
	 */
	function test_get_map_data_rejects_foreign_data_key() {
		require_once GEO_MASHUP_DIR_PATH . '/render-map.php';

		$saved_get = $_GET;
		set_transient( 'gmm_not_a_map_key', array( 'name' => 'foreign-data' ) );

		$_GET = array( 'map_data_key' => '_not_a_map_key' );
		$map_data = GeoMashupRenderMap::get_map_data();

		$_GET = $saved_get;
		delete_transient( 'gmm_not_a_map_key' );

		$this->assertEmpty( $map_data );
	}

	/**
	 * The map data key check must still accept the keys GeoMashup::map()
	 * generates, or every iframe map stops rendering.
	 */
	function test_get_map_data_accepts_generated_data_key() {
		require_once GEO_MASHUP_DIR_PATH . '/render-map.php';

		$saved_get = $_GET;
		// The format generated in GeoMashup::map()
		$key = md5( serialize( array( 'map_content' => 'global' ) ) );
		set_transient( 'gmm' . $key, array( 'name' => 'cached-map' ) );

		$_GET = array( 'map_data_key' => $key );
		$map_data = GeoMashupRenderMap::get_map_data();

		$_GET = $saved_get;
		delete_transient( 'gmm' . $key );

		$this->assertIsArray( $map_data );
		$this->assertSame( 'cached-map', $map_data['name'] );
	}

	/**
	 * Security: build_map_data() must not pass a request/shortcode load_kml
	 * value through as markup. It reaches JavaScript as a map overlay URL, so
	 * a value like '<img src=x onerror=...>' used to run as HTML there.
	 */
	function test_build_map_data_sanitizes_load_kml() {
		$payloads = array(
			'<img src=x onerror=alert(document.domain)>',
			'"><img src=x onerror=alert(1)>',
			'javascript:alert(document.domain)',
			'JaVaScRiPt:alert(1)',
			'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
			// The entity-decoding step must not reintroduce markup
			'&lt;img src=x onerror=alert(1)&gt;',
		);

		foreach ( $payloads as $payload ) {
			$map_data = GeoMashup::build_map_data( array(
				'map_content' => 'global',
				'load_empty_map' => 'true',
				'load_kml' => $payload,
			) );

			$this->assertNotWPError( $map_data );

			// None of these is a usable KML URL, so the option is dropped
			$this->assertArrayNotHasKey( 'load_kml', $map_data, "Payload: $payload" );

			// Belt and braces: nothing resembling the payload reaches the
			// JSON that gets printed into a script tag
			$json = json_encode( $map_data );
			$this->assertStringNotContainsString( 'onerror', $json, "Payload: $payload" );
			$this->assertStringNotContainsString( 'javascript:', $json, "Payload: $payload" );
		}
	}

	/**
	 * The load_kml fix must not break the documented feature. Supplying a KML
	 * URL by request or shortcode is legitimate, including the site-relative
	 * form and the HTML-encoded '&' that shortcode attributes are stored with.
	 */
	function test_build_map_data_allows_real_kml_urls() {
		$urls = array(
			'https://example.com/path/my.kml' => 'https://example.com/path/my.kml',
			'http://example.com/my.kml' => 'http://example.com/my.kml',
			'/wp-content/uploads/2024/01/my.kml' => '/wp-content/uploads/2024/01/my.kml',
			'//example.com/my.kml' => '//example.com/my.kml',
			'https://example.com/kml.php?id=1&amp;v=2' => 'https://example.com/kml.php?id=1&v=2',
			'https://example.com/a%20b.kml' => 'https://example.com/a%20b.kml',
		);

		foreach ( $urls as $url => $expected ) {
			$map_data = GeoMashup::build_map_data( array(
				'map_content' => 'global',
				'load_empty_map' => 'true',
				'load_kml' => $url,
			) );

			$this->assertNotWPError( $map_data );
			$this->assertSame( $expected, $map_data['load_kml'], "URL: $url" );
		}
	}

	/**
	 * Render a post containing a map shortcode, with $query_string standing in
	 * for the request the page was loaded with.
	 *
	 * @param string $post_content
	 * @param string $query_string
	 * @return string The rendered content.
	 */
	private function map_content_for_query_string( $post_content, $query_string ) {
		$saved_query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : null;
		$_SERVER['QUERY_STRING'] = $query_string;

		$post_id = $this->factory->post->create( array( 'post_content' => $post_content ) );
		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		if ( null === $saved_query_string ) {
			unset( $_SERVER['QUERY_STRING'] );
		} else {
			$_SERVER['QUERY_STRING'] = $saved_query_string;
		}

		return $content;
	}

	/**
	 * Security: a global map on a page takes whitelisted parameters from the
	 * query string (see
	 * GeoMashupMapAttributes::add_whitelisted_query_string_parameters()), so
	 * load_kml is attacker controlled there too, with no map_content or
	 * geo_mashup_content parameter needed. An unusable one must not be
	 * reflected into the page or copied into the map iframe URL.
	 */
	function test_query_string_load_kml_is_sanitized() {
		$content = $this->map_content_for_query_string(
			'[geo_mashup_map map_content=global load_empty_map=true]',
			'load_kml=' . rawurlencode( '<img src=x onerror=alert(document.domain)>' )
		);

		$this->assertStringContainsString( '<iframe', $content );
		$this->assertStringNotContainsString( 'onerror', $content );
		$this->assertStringNotContainsString( 'load_kml', $content );
	}

	/**
	 * Taking load_kml from the query string is a feature, so a usable URL must
	 * still reach the map iframe.
	 */
	function test_query_string_load_kml_allows_real_url() {
		$content = $this->map_content_for_query_string(
			'[geo_mashup_map map_content=global load_empty_map=true]',
			'load_kml=' . rawurlencode( 'https://example.com/trails.kml' )
		);

		$this->assertStringContainsString( 'load_kml=https://example.com/trails.kml', $content );
	}

	/**
	 * A single map takes its KML overlay from an attachment of the post, not
	 * from the request. That URL is generated server side, so the load_kml
	 * sanitizing must leave it alone.
	 */
	function test_single_map_uses_kml_attachment() {
		$post_id = $this->factory->post->create();
		$attachment_id = $this->factory->attachment->create_object( 'trail.kml', $post_id, array(
			'post_mime_type' => 'application/vnd.google-earth.kml+xml',
			'post_type' => 'attachment',
		) );
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), $do_lookups = false );

		$map_data = GeoMashup::build_map_data( array(
			'map_content' => 'single',
			'object_name' => 'post',
			'object_id' => $post_id,
		) );

		$this->assertNotWPError( $map_data );
		$this->assertSame( wp_get_attachment_url( $attachment_id ), $map_data['load_kml'] );
	}

	/**
	 * The reserved-key fix in build_map_data() (see
	 * test_build_map_data_ignores_request_urls()) must only strip the
	 * specific URL properties it's meant to. Every other query value is
	 * still legitimate map configuration and must still take effect.
	 */
	function test_build_map_data_allows_other_request_overrides() {
		$query = array(
			'map_content' => 'global',
			'load_empty_map' => 'true',
			'map_api' => 'leaflet', // default is googlev3, so this proves an override
			'name' => 'my-custom-map-name',
			'zoom' => '15',
			'width' => '444',
			'height' => '333',
			'background_color' => 'c0ffee',
		);

		$map_data = GeoMashup::build_map_data( $query );

		$this->assertNotWPError( $map_data );
		$this->assertSame( 'leaflet', $map_data['map_api'] );
		$this->assertSame( 'my-custom-map-name', $map_data['name'] );
		$this->assertSame( '15', $map_data['zoom'] );
		$this->assertSame( '444', $map_data['width'] );
		$this->assertSame( '333', $map_data['height'] );
		$this->assertSame( 'c0ffee', $map_data['background_color'] );
	}

	/**
	 * Security: the name shortcode attribute must be escaped for the
	 * click-to-load onclick handler, or a contributor can break out of the
	 * embedded JS string and inject arbitrary JavaScript (XSS).
	 */
	function test_click_to_load_name_attribute_is_escaped() {
		$malicious_name = "x');alert(document.domain);//";
		$post_id = $this->factory->post->create( array(
			'post_content' => "[geo_mashup_map load_empty_map=true click_to_load=true name=\"{$malicious_name}\"]",
		) );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$content = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		$this->assertStringContainsString( 'onclick=', $content );
		$this->assertStringNotContainsString( $malicious_name, $content );
		$this->assertStringContainsString( esc_attr( esc_js( $malicious_name ) ), $content );
	}

	/**
	* issue 621
	*/
	function test_wp_query_location_info() {
		$post_id = $this->factory->post->create( array( 
			'post_content' => '[geo_mashup_location_info fields="locality_name,admin_code" format="%s, %s"]',
		) );
		$nv_location = $this->get_nv_test_location();
		$nv_location->admin_code = 'NV';
		$nv_location->locality_name = 'Reno';
		GeoMashupDB::set_object_location( 'post', $post_id, $nv_location, false );

		$test_query = new WP_Query( array( 
			'posts_per_page' => 1,
		) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$this->assertStringContainsString( 'Reno, NV', apply_filters( 'the_content', get_the_content() ) );
		wp_reset_postdata();
	}

	/**
	 * issue 629
	 */
	function test_map_link_outside_loop() {
		global $geo_mashup_options;

		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$geo_mashup_options->set_valid_options( array(
			array( 'overall' => array(
				'mashup_page' => $page_id,
			) ),
		) );
		$post_id = $this->factory->post->create();
		// Use 4 decimal places in location to get 4 good total characters to check in link
		$location = $this->rand_location( 4 );
		GeoMashupDB::set_object_location( 'post', $post_id, $location, false );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$this->assertTrue( $test_query->have_posts() );
		$test_query->the_post();
		$this->assertFalse( $test_query->have_posts() );
		$this->assertStringContainsString( 'center_lat=' . substr( $location->lat, 0, 4 ), GeoMashup::show_on_map_link() );
	}

	/**
	 * issue 650
	 */
	function test_map_dimension_postfix() {
		$post_id = $this->factory->post->create();
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );

		$html = GeoMashup::map( 'map_content=global&width=400&height=300px' );
		$this->assertThat( $html, $this->stringContains( 'width: 400px;' ) );
		$this->assertThat( $html, $this->stringContains( 'height: 300px;' ) );

		$html = GeoMashup::map( 'map_content=global&width=40%&height=300' );
		$this->assertThat( $html, $this->stringContains( 'width: 40%;' ) );
		$this->assertThat( $html, $this->stringContains( 'height: 300px;' ) );

		$html = GeoMashup::map( 'map_content=global&width=400px&height=30%' );
		$this->assertThat( $html, $this->stringContains( 'width: 400px;' ) );
		$this->assertThat( $html, $this->stringContains( 'height: 30%;' ) );
	}

	/**
	 * Places mock_geocode_request() knows the coordinates of, keyed by the
	 * geocode query that should find them.
	 *
	 * @return array
	 */
	private function canned_geocode_results() {
		return array(
			"l'isle d'abeau,france" => array(
				'lat' => '45.6186',
				'lon' => '5.2258',
				'display_name' => "L'Isle-d'Abeau, Isère, France",
				'address' => array(
					'city' => "L'Isle-d'Abeau",
					'county' => 'Isère',
					'postcode' => '38080',
					'country_code' => 'fr',
				),
			),
			'paris,france' => array(
				'lat' => '48.8566',
				'lon' => '2.3522',
				'display_name' => 'Paris, Île-de-France, France',
				'address' => array(
					'city' => 'Paris',
					'postcode' => '75000',
					'country_code' => 'fr',
				),
			),
			'paris,texas' => array(
				'lat' => '33.6609',
				'lon' => '-95.5555',
				'display_name' => 'Paris, Lamar County, Texas, United States',
				'address' => array(
					'city' => 'Paris',
					'county' => 'Lamar County',
					'postcode' => '75460',
					'country_code' => 'us',
				),
			),
		);
	}

	/**
	 * Stand in for a geocoding service.
	 *
	 * The geocoders make their requests through WP_Http, which applies
	 * pre_http_request first, so this keeps them off the network entirely -
	 * including the reverse geocoding a location save attempts, which gets an
	 * error here just as it would with no connection.
	 *
	 * @param false|array|WP_Error $preempt
	 * @param array $args Request arguments.
	 * @param string $url
	 * @return array|WP_Error A canned response, or an error for any request
	 *                        this isn't meant to answer.
	 */
	function mock_geocode_request( $preempt, $args, $url ) {
		$this->data->geocode_urls[] = $url;

		if ( false === strpos( $url, 'nominatim.openstreetmap.org/search' ) ) {
			return new WP_Error( 'http_request_failed', 'Unexpected request: ' . $url );
		}

		$url_args = array();
		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $url_args );
		$query = isset( $url_args['q'] ) ? strtolower( $url_args['q'] ) : '';
		// The import builds its query by joining field values with a comma
		$query = trim( preg_replace( '/\s*,\s*/', ',', $query ) );

		$results = array();
		foreach ( $this->canned_geocode_results() as $place => $result ) {
			if ( false !== strpos( $query, $place ) ) {
				$results[] = $result;
				break;
			}
		}

		return array(
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'body' => wp_json_encode( $results ),
		);
	}

	function test_search() {
		require_once GEO_MASHUP_DIR_PATH . '/geo-mashup-search.php';

		// Geocode locally, so the test doesn't depend on a geocoding service
		// being reachable, in agreement, and under its rate limit
		$this->data->geocode_urls = array();
		$this->add_filter( 'pre_http_request', array( $this, 'mock_geocode_request' ), 10, 3 );

		$not_found_post = $this->factory->post->create_and_get();
		GeoMashupDB::set_object_location( 'post', $not_found_post->ID, $this->get_nv_test_location(), false );

		$found_post = $this->factory->post->create_and_get();
		$location = GeoMashupDB::blank_location();
		$location->lat = 45.61806;
		$location->lng = 5.226046;
		GeoMashupDB::set_object_location( 'post', $found_post->ID, $location, false );

		$search_args = array(
			'location_text' => "l'isle d'abeau, France",
			'object_name' => 'post',
			'radius' => 30,
			'units' => 'km',
			'geo_mashup_search_submit' => 'Search',
		);
		$search = new GeoMashupSearch( $search_args );
		$this->assertNotEmpty( $this->data->geocode_urls, 'Search did not geocode the search text.' );
		$this->assertTrue( $search->have_posts(), 'Search did not find any posts.' );
		$this->assertContains( (string) $found_post->ID, $search->get_the_IDs(), 'Search did not find the target post.' );
		$this->assertNotContains( (string) $not_found_post->ID, $search->get_the_IDs(), 'Search found the wrong post.' );

		ob_start();
		$search->load_template();
		$output = ob_get_clean();

		$this->assertThat(
			$output,
			$this->stringContains( htmlentities( $search_args['location_text'] ) ),
			'Default results template does not contain search text.'
		);
		$this->assertThat(
			$output,
			$this->stringContains( $found_post->post_title ),
			'Default results template does not contain found post title.'
		);
	}

	function test_small_search() {
		require_once GEO_MASHUP_DIR_PATH . '/geo-mashup-search.php';

		$found_post = $this->factory->post->create_and_get();
		$location = GeoMashupDB::blank_location();
		$location->lat = 45.61806;
		$location->lng = 5.226046;
		GeoMashupDB::set_object_location( 'post', $found_post->ID, $location, false );

		$search_args = array(
			'near_lat' => 45.6181,
			'near_lng' => 5.226,
			'object_name' => 'post',
			'radius' => 0.5,
			'units' => 'km',
			'geo_mashup_search_submit' => 'Search',
		);
		$search = new GeoMashupSearch( $search_args );
		$this->assertTrue( $search->have_posts(), 'Search did not find any posts.' );
		$this->assertContains( (string) $found_post->ID, $search->get_the_IDs(), 'Search did not find the target post.' );
	}

    /**
     * Issue 777
     */
	function test_search_map_cat() {
		require_once GEO_MASHUP_DIR_PATH . '/geo-mashup-search.php';

		$search_args = array(
			'near_lat' => 45.6181,
			'near_lng' => 5.226,
			'object_name' => 'post',
			'radius' => 0.5,
			'units' => 'km',
			'geo_mashup_search_submit' => 'Search',
            'map_cat' => 'test',
		);

		$query_args_filter = $this->getMockBuilder('filterMock')->setMethods( array( 'check_args' ) )->getMock();
		$query_args_filter->expects( $this->once() )
            ->method( 'check_args' )
            ->with( $this->arrayHasKey( 'map_cat' ) )
            ->willReturn( array() );

		add_filter( 'geo_mashup_search_query_args', array( $query_args_filter, 'check_args' ) );
		$search = new GeoMashupSearch( array() );
		$search->query( $search_args );
        remove_filter( 'geo_mashup_search_query_args', array( $query_args_filter, 'check_args' ) );
	}

	/**
	 * Issue 846
	 */
	public function test_nm_search() {
		require_once GEO_MASHUP_DIR_PATH . '/geo-mashup-search.php';

		$found_post = $this->factory->post->create_and_get();
		$location = GeoMashupDB::blank_location();
		$location->lat = 45.61806;
		$location->lng = 5.226046;
		GeoMashupDB::set_object_location( 'post', $found_post->ID, $location, false );

		$search_args = array(
			'near_lat' => 45.619,
			'near_lng' => 5.225,
			'object_name' => 'post',
			'radius' => 0.5,
			'units' => 'nm',
			'geo_mashup_search_submit' => 'Search',
		);
		$search = new GeoMashupSearch( $search_args );
		$this->assertTrue( $search->have_posts(), 'Search did not find any posts.' );
		$this->assertContains( (string) $found_post->ID, $search->get_the_IDs(), 'Search did not find the target post.' );

		$search->the_post();
		$this->assertEquals(
			$search->the_distance([ 'echo' => false ]),
			'0.08 nm',
			'Did not find distance in nautical miles.'
		);
	}

	/**
	 * Issue 639
	 */
	function test_wp_query() {
		$unlocated_post_ids = $this->factory->post->create_many( 2 );

		$nv_location = GeoMashupDB::blank_location();
		$nv_location->lat = 39.5;
		$nv_location->lng = -119.0;
		$nv_location->country_code = 'US';
		$nv_location->admin_code = 'NV';
		$nv_location->sub_admin_code = 'LUV';
		$nv_location->postal_code = '89403';
		$nv_location->locality_name = 'Fallon';

		$nv_post_ids = $this->factory->post->create_many( 2 );
		GeoMashupDB::set_object_location( 'post', $nv_post_ids[0], $nv_location, false );
		GeoMashupDB::set_object_location( 'post', $nv_post_ids[1], $nv_location, false );

		$ca_location = GeoMashupDB::blank_location();
		$ca_location->lat = 39.32;
		$ca_location->lng = -120.19;
		$ca_location->country_code = 'US';
		$ca_location->admin_code = 'CA';
		$ca_location->postal_code = '96161';
		$ca_location->locality_name = 'Fallon';

		$ca_post_id = $this->factory->post->create();
		GeoMashupDB::set_object_location( 'post', $ca_post_id, $ca_location, false );

		$all_post_query = new WP_Query( array(
			'posts_per_page' => -1,
		) );
		$this->assertEquals( 5, $all_post_query->post_count );

		$nv_query = new WP_Query( array(
			'posts_per_page' => -1,
			'geo_mashup_query' => array(
				'admin_code' => 'NV',
			)
		) );
		$this->assertEquals( 2, $nv_query->post_count );
		$this->assertContains( $nv_post_ids[0], wp_list_pluck( $nv_query->posts, 'ID' ) );
		$this->assertContains( $nv_post_ids[1], wp_list_pluck( $nv_query->posts, 'ID' ) );

		$sub_query = new WP_Query( array(
			'posts_per_page' => -1,
			'geo_mashup_query' => array(
				'sub_admin_code' => 'LUV',
			)
		) );
		$this->assertEquals( 2, $sub_query->post_count );
		$this->assertContains( $nv_post_ids[0], wp_list_pluck( $sub_query->posts, 'ID' ) );
		$this->assertContains( $nv_post_ids[1], wp_list_pluck( $sub_query->posts, 'ID' ) );

		$zip_query = new WP_Query( array(
			'posts_per_page' => -1,
			'geo_mashup_query' => array(
				'postal_code' => '96161',
			),
		) );
		$this->assertEquals( 1, $zip_query->post_count );
		$this->assertEquals( $ca_post_id, $zip_query->posts[0]->ID );

		$radius_query = new WP_Query( array(
			'posts_per_page' => -1,
			'geo_mashup_query' => array(
				'near_lat' => 39,
				'near_lng' => -120,
				'radius_km' => 50,
			),
		) );
		$this->assertEquals( 1, $radius_query->post_count );
		$this->assertEquals( $ca_post_id, $radius_query->posts[0]->ID );
		$radius_query->the_post();
		$this->assertTrue( $GLOBALS['post']->distance_km >= 0, 'Distance field is not present or negative.' );
		$this->assertTrue( $GLOBALS['post']->distance_km < 50, 'Distance field is greater than radius.' );
	}

	function test_gm_location_query() {
		global $wpdb;

		$unlocated_user_ids = $this->factory->user->create_many( 2 );
		$nv_user_id = $this->factory->user->create();
		$nv_location = $this->get_nv_test_location();
		GeoMashupDB::set_object_location( 'user',$nv_user_id, $nv_location, false );

		$location_query = new GM_Location_Query( array(
			'minlat' => $nv_location->lat - 1,
			'maxlat' => $nv_location->lat + 1,
			'minlon' => $nv_location->lng - 1,
			'maxlon' => $nv_location->lng + 1,
		) );
		list( $cols, $join, $where, $groupby ) = $location_query->get_sql( $wpdb->users, 'ID' );
		$this->assertStringNotContainsString( 'distance_km', $cols, 'Got a distance_km column for a non-radius query.' );
		$this->assertEmpty( $groupby, 'Got a groupby value for a non-radius query.' );

		$sql = "SELECT {$wpdb->users}.ID
			FROM {$wpdb->users}{$join}
			WHERE 1=1{$where}
		";

		$results = $wpdb->get_results( $sql );
		$this->assertCount( 1, $results );
		$this->assertEquals( $nv_user_id, $results[0]->ID );
	}

	function test_long_map_url() {
		$post_ids = $this->generate_rand_located_posts( 3 );
		$post_ids = array_merge( $post_ids, range( $post_ids[2], $post_ids[2] + 3000, 3 ) );
		$html = GeoMashup::map( array(
			'map_content' => 'global',
			'object_ids' => implode( ',', $post_ids ),
		) );
		$this->assertStringMatchesFormat( '%soids=.%s', $html, 'Long id list was not compressed.' );
	}

	public function test_user_import_fields() {
		global $geo_mashup_options;

		// Geocode locally, so the test doesn't depend on a geocoding service
		// being reachable, in agreement, and under its rate limit
		$this->data->geocode_urls = array();
		$this->add_filter( 'pre_http_request', array( $this, 'mock_geocode_request' ), 10, 3 );

		$geo_mashup_options->set_valid_options( array( 'overall' => array( 'import_custom_field' => 'city, region' ) ) );
		GeoMashupDB::add_geodata_sync_hooks();

		$user_id = $this->factory->user->create();
		$this->assertEmpty( GeoMashupDB::get_object_location( 'user', $user_id ) );

		// Both fields are needed before the import geocodes anything
		update_user_meta( $user_id, 'city', 'Paris' );
		$this->assertEmpty( GeoMashupDB::get_object_location( 'user', $user_id ) );

		update_user_meta( $user_id, 'region', 'France' );

		$this->assertEmpty( get_user_meta( $user_id, 'geocoding_error', true ) );
		$location = GeoMashupDB::get_object_location( 'user', $user_id );
		$this->assertEquals( 48, (int) $location->lat );
		$this->assertEquals( 2, (int) $location->lng );

		// A second update overwrites the first result with Paris, TX
		update_user_meta( $user_id, 'region', 'Texas' );

		$this->assertEmpty( get_user_meta( $user_id, 'geocoding_error', true ) );
		$location = GeoMashupDB::get_object_location( 'user', $user_id );
		$this->assertEquals( 33, (int) $location->lat );
		$this->assertEquals( -95, (int) $location->lng );
	}

	/**
	 * A field value that can't be geocoded records the error and leaves any
	 * location already found in place.
	 */
	public function test_user_import_fields_geocoding_failure() {
		global $geo_mashup_options;

		$this->data->geocode_urls = array();
		$this->add_filter( 'pre_http_request', array( $this, 'mock_geocode_request' ), 10, 3 );

		$geo_mashup_options->set_valid_options( array( 'overall' => array( 'import_custom_field' => 'city, region' ) ) );
		GeoMashupDB::add_geodata_sync_hooks();

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, 'city', 'Nowhere' );
		update_user_meta( $user_id, 'region', 'Nowhere' );

		$this->assertNotEmpty( $this->data->geocode_urls, 'Import did not geocode the fields.' );
		$this->assertNotEmpty( get_user_meta( $user_id, 'geocoding_error', true ) );
		$this->assertEmpty( GeoMashupDB::get_object_location( 'user', $user_id ) );
	}


	/**
	 * Issue 691
	 */
	function test_map_content_with_object_ids() {
		$post_ids = $this->generate_rand_located_posts( 3 );
		$html = GeoMashup::map( array(
			'object_name' => 'post',
			'object_ids' => implode( ',', $post_ids ),
		) );
		$this->assertStringContainsString( 'map_content=global', $html, 'Expected global map content when object_ids are passed.' );
	}

	/**
	 * Issue 713
	 */
	function test_field_truncation() {
		$location = self::rand_location();
		$location->saved_name = str_repeat( 'a', 101 );
		$location->country_code = 'aaa';
		$location->admin_code = str_repeat( 'a', 21 );
		$location->sub_admin_code = str_repeat( 'a', 81 );

		GeoMashupDB::set_location( $location, false );

		$this->assertEquals( str_repeat( 'a', 100 ), $location->saved_name );
		$this->assertEquals( 'aa', $location->country_code );
		$this->assertEquals( str_repeat( 'a', 20 ), $location->admin_code );
		$this->assertEquals( str_repeat( 'a', 80 ), $location->sub_admin_code );
	}

	/**
	 * issue 718
	 */
	function test_show_on_map_url_parameters() {
		global $geo_mashup_options;

		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$geo_mashup_options->set_valid_options( array( 'overall' => array( 'mashup_page' => $page_id, ) ) );

		$post_id = $this->factory->post->create();

		$location = $this->rand_location( 4 );
		GeoMashupDB::set_object_location( 'post', $post_id, $location, false );

		$test_query = new WP_Query( array( 'p' => $post_id ) );
		$test_query->the_post();

		$test_args = array( 'text' => 'TEST TEXT', 'zoom' => 10 );
		$this->assertStringNotContainsString( 'text=', GeoMashup::show_on_map_link( $test_args ) );
		$this->assertStringContainsString( 'zoom=10', GeoMashup::show_on_map_link( $test_args ) );
	}

	/**
	 * issue 715
	 */
	function test_map_shape() {
		$post_id = $this->factory->post->create();
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );

		$html = GeoMashup::map( 'map_content=global&width=400&height=300px' );
		$this->assertStringNotContainsString( 'padding-bottom', $html );

		$html = GeoMashup::map( 'map_content=global&width=40%&height=300&shape=50%' );
		$this->assertThat( $html, $this->stringContains( 'width: 100%;' ) );
		$this->assertThat( $html, $this->stringContains( 'height: 0;' ) );
		$this->assertThat( $html, $this->stringContains( 'padding-bottom: 50%;' ) );

		$html = GeoMashup::map( 'map_content=global&width=400px&height=30%&shape=50%' );
		$this->assertThat( $html, $this->stringContains( 'width: 100%;' ) );
		$this->assertThat( $html, $this->stringContains( 'height: 0;' ) );
		$this->assertThat( $html, $this->stringContains( 'padding-bottom: 50%;' ) );
	}

	/**
	 * issue 818
	 */
	function test_location_update_with_id_zero() {

		$location = $this->get_nv_test_location();
		$id = GeoMashupDB::set_location( $location, $do_lookups = false );

		$location->id = 0;
		$location->locality_name = rand_str();
		$update_id = GeoMashupDB::set_location( $location, $do_lookups = false );

		$this->assertEquals( $update_id, $id );
		$out = GeoMashupDB::get_location( $id );
		$this->assertEquals( $id, $out->id );
		$this->assertEquals( $location->locality_name, $out->locality_name );
	}

	/**
	 * issue 828
	 */
	function test_querystring_parameter_whitelist() {
		$post_id = $this->factory->post->create();
		GeoMashupDB::set_object_location( 'post', $post_id, $this->rand_location(), false );

		$_SERVER['QUERY_STRING'] = 'limit=10&foo=bar';
		$html = GeoMashup::map( 'map_content=global&width=400&height=300px' );
		$this->assertThat( $html, $this->stringContains( 'limit=10' ) );
		$this->assertThat( $html, $this->logicalNot($this->stringContains('foo=bar') ) );
	}

	/**
	 * issue 829
	 */
	function test_rss_ns_no_duplicate() {
		GeoMashup::rss_ns_buffer();
		$ns = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"
				xmlns:content="http://purl.org/rss/1.0/modules/content/"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:atom="http://www.w3.org/2005/Atom"
				xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
				xmlns:georss="http://www.georss.org/georss" ';

		ob_start();
		echo $ns;
		GeoMashup::rss_ns();
		$ns_output = ob_get_clean();

		$this->assertEquals( $ns_output, $ns );
	}

	function test_sanitize_sort_arg_valid_single_column() {
		$args = array( 'sort' => 'post_title ASC' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( 'post_title ASC', $result['sort'] );
	}

	function test_sanitize_sort_arg_valid_multiple_clauses() {
		$args = array( 'sort' => 'post_status ASC, geo_date DESC' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( 'post_status ASC, geo_date DESC', $result['sort'] );
	}

	function test_sanitize_sort_arg_valid_no_direction() {
		$args = array( 'sort' => 'post_title' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( 'post_title', $result['sort'] );
	}

	function test_sanitize_sort_arg_valid_distance() {
		$args = array( 'sort' => 'distance_km ASC' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( 'distance_km ASC', $result['sort'] );
	}

	function test_sanitize_sort_arg_rejects_sql_injection() {
		$args = array( 'sort' => '1; DROP TABLE wp_posts; --' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEmpty( $result['sort'] );
	}

	function test_sanitize_sort_arg_rejects_unknown_column() {
		$args = array( 'sort' => 'evil_column ASC' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEmpty( $result['sort'] );
	}

	function test_sanitize_sort_arg_rejects_invalid_direction() {
		$args = array( 'sort' => 'post_title SIDEWAYS' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEmpty( $result['sort'] );
	}

	function test_sanitize_sort_arg_rejects_subquery() {
		$args = array( 'sort' => '(SELECT 1 FROM wp_users)' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEmpty( $result['sort'] );
	}

	function test_sanitize_object_id_cast_to_int() {
		$args = array( 'object_id' => '42 OR 1=1' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertSame( 42, $result['object_id'] );
	}

	function test_sanitize_exclude_object_ids_strips_non_numeric() {
		$args = array( 'exclude_object_ids' => '1,2,3 OR 1=1' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( '1,2,311', $result['exclude_object_ids'] );
	}

	function test_sanitize_show_future_sanitized() {
		$args = array( 'show_future' => 'true<script>' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( 'truescript', $result['show_future'] );
	}

	function test_sanitize_map_cat_preserves_negative_ids() {
		$args = array( 'map_cat' => '491,-71,-493' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( '491,-71,-493', $result['map_cat'] );
	}

	function test_sanitize_map_cat_strips_non_numeric() {
		$args = array( 'map_cat' => '491 OR 1=1' );
		$result = GeoMashupDB::sanitize_query_args( $args );
		$this->assertEquals( '49111', $result['map_cat'] );
	}
}
