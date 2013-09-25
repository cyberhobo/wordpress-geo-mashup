<?php

class GMIntList_Unit_Tests extends WP_UnitTestCase {

	function setUp() {
		require_once( GEO_MASHUP_DIR_PATH . '/gm-int-list.php' );
	}

	function test_loaded() {
		$this->assertTrue( class_exists( 'GM_Int_List' ) );
	}

	function test_empty_list() {
		$list = new GM_Int_List( '' );
		$this->assertEmpty( $list->compressed(), 'Compressed list not empty.' );
		$this->assertEmpty( $list->expanded(), 'Expanded list not empty.' );
	}

	function provide_lists() {
		return array(
			array( '0' ),
			array( '1' ),
			array( '2147483648' ),
			array( '0,1' ),
			array( '3,2,1,0' ),
			array( '8374,23849,23489,13984,12394,8,2342,952,184,28934723,2' ),
		);
	}

	/**
	 * @param string $list_string
	 * @dataProvider provide_lists
	 */
	function test_single_list( $list_string ) {
		$list = new GM_Int_List( $list_string );
		$this->assertEquals( $list_string, $list->expanded(), 'Expanded list is different from source.' );
		$this->assertNotEmpty( $list->compressed(), 'Compressed list is empty.' );
		$reverse_list = new GM_Int_List( $list->compressed() );
		$this->assertEquals( $list_string, $reverse_list->expanded(), 'Reverse expanded list is different from original.' );
		$this->assertEquals( $list->compressed(), $reverse_list->compressed(), 'Compressed list is different from source.' );
	}

}
