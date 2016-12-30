<?php

include_once "sitepress-mock.php" ;
define( 'ICL_LANGUAGE_CODE', 'fo' );
include_once GEO_MASHUP_DIR_PATH . '/wpml.php';

class GeoMashupWPML_Unit_Tests extends GeoMashupTestCase {

	public function test_loaded() {
		$this->assertTrue( class_exists( 'GeoMashupWPML' ), 'Expected the WPML integration class to be loaded' );
	}

	public function test_get_language_code_filter() {
		$this->assertEquals( 'fo', GeoMashupWPML::get_language_code( 'en' ), 'Expected the WPML language code.' );
	}

	public function test_get_language_code_filter_override() {
		$_GET['lang'] = 'xx';
		$this->assertEquals( 'xx', GeoMashupWPML::get_language_code( 'en' ), 'Expected the querystring language code.' );
	}

	/**
	* issue 607
	*/
	public function test_auto_post_join() {

		GeoMashupWPML::load();

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

		$GLOBALS['wpml_query_filter'] = new SitePressQueryFilterMock( $posts[1]->post_title );

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

}
