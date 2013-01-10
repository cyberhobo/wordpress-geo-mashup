<?php

class GeoMashup_Unit_Tests extends WP_UnitTestCase {

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
}
