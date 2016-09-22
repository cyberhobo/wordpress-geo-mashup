<?php

class GeoMashupOptions_Unit_Tests extends WP_UnitTestCase {

	function test_loaded() {
		global $geo_mashup_options;
		$this->assertTrue( class_exists( 'GeoMashupOptions' ) );
		$this->assertInstanceOf( 'GeoMashupOptions', $geo_mashup_options );
		$this->assertArrayHasKey( 'overall', $geo_mashup_options->default_options );
	}

	function test_validation() {
		global $geo_mashup_options;
		$valid_options = array( 
			'overall' => array( 
				'map_api' => 'openlayers',
				'add_category_links' => true,
			),
			'global_map' => array(
				'width' => '50%', 
				'cluster_lib' => 'markerclusterer',
			),
		);

		$geo_mashup_options->set_valid_options( $valid_options );

		$this->assertEmpty( $geo_mashup_options->validation_errors );
		$this->assertEquals( $valid_options['overall']['map_api'], $geo_mashup_options->get( 'overall', 'map_api' ) );
		$this->assertEquals( $valid_options['overall']['add_category_links'], $geo_mashup_options->get( 'overall', 'add_category_links' ) );
		$this->assertEquals( $valid_options['global_map']['width'], $geo_mashup_options->get( 'global_map', 'width' ) );
		$this->assertEquals( $valid_options['global_map']['cluster_lib'], $geo_mashup_options->get( 'global_map', 'cluster_lib' ) );

		$invalid_options = array( 
			'overall' => array( 
				'map_api' => 'bubblegum',
				'add_category_links' => 'maybe',
			),
			'global_map' => array(
				'width' => 'big', 
				'cluster_lib' => 'magic',
			),
		);
		
		$geo_mashup_options->set_valid_options( $invalid_options );

		$this->assertNotCount( 0, $geo_mashup_options->validation_errors );
		$this->assertNotEquals( $invalid_options['overall']['map_api'], $geo_mashup_options->get( 'overall', 'map_api' ) );
		$this->assertNotEquals( $invalid_options['overall']['add_category_links'], $geo_mashup_options->get( 'overall', 'add_category_links' ) );
		$this->assertNotEquals( $invalid_options['global_map']['width'], $geo_mashup_options->get( 'global_map', 'width' ) );
		$this->assertNotEquals( $invalid_options['global_map']['cluster_lib'], $geo_mashup_options->get( 'global_map', 'cluster_lib' ) );
	}

	function test_get_set_save() {
		global $geo_mashup_options;
		
		$rand_str = rand_str();
		$geo_mashup_options->set_valid_options( array( 
			'overall' => array( 
				'import_custom_field' => $rand_str
			)
		) );

		$this->assertEquals( $rand_str, $geo_mashup_options->get( 'overall', 'import_custom_field' ) );

		$this->assertTrue( $geo_mashup_options->save() );

		$fresh_instance = new GeoMashupOptions();

		$this->assertEquals( $rand_str, $fresh_instance->get( 'overall', 'import_custom_field' ) );
	}

	function test_google_api_conversion() {
		global $geo_mashup_options;

		$options = $geo_mashup_options->default_options;
		$options['overall']['map_api'] = 'google';
		$options['overall']['google_key'] = 'TEST';
		update_option( 'geo_mashup_options', $options );

		$test_options = new GeoMashupOptions();

		$this->assertEquals( 'googlev3', $test_options->get( 'overall', 'map_api' ) );
		$this->assertEquals( 'TEST', $test_options->get( 'overall', 'googlev3_key' ) );

	}
}
