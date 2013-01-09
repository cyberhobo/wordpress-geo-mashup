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
}
