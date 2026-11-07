<?php

/**
 * Security: the geo_mashup_edit ajax action must only change locations of
 * objects the current user is allowed to edit. The edit form nonce is generic,
 * so any user who can open a location editor holds a valid one.
 */
class GeoMashup_Ajax_Unit_Tests extends WP_Ajax_UnitTestCase {

	public function set_up() {
		parent::set_up();
		// Saves posted here include every geocodable field, so no lookups should be made.
		add_filter( 'pre_http_request', array( $this, 'block_http_request' ) );
	}

	public function tear_down() {
		global $wpdb;
		remove_filter( 'pre_http_request', array( $this, 'block_http_request' ) );
		parent::tear_down();
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_location_relationships" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}geo_mashup_locations" );
	}

	public function block_http_request() {
		return new WP_Error( 'http_blocked', 'No HTTP requests in tests.' );
	}

	private function set_original_location( $object_name, $object_id ) {
		$location = GeoMashupDB::blank_location();
		$location->lat = 40;
		$location->lng = -119;
		$this->assertNotWPError( GeoMashupDB::set_object_location( $object_name, $object_id, $location, false ) );
	}

	/**
	 * Post an edit as the editor form's javascript does, returning the status.
	 */
	private function post_edit( $ui_manager, $object_id, $edit = 'update' ) {
		$_POST = array(
			'geo_mashup_nonce' => wp_create_nonce( 'geo-mashup-edit' ),
			'geo_mashup_ui_manager' => $ui_manager,
			'geo_mashup_object_id' => $object_id,
			'geo_mashup_' . $edit . '_location' => 'true',
		);
		if ( 'update' === $edit ) {
			$_POST += array(
				'geo_mashup_location' => '10,20',
				'geo_mashup_address' => 'Attacker Address',
				'geo_mashup_postal_code' => '00000',
				'geo_mashup_country_code' => 'US',
				'geo_mashup_admin_code' => 'NV',
				'geo_mashup_locality_name' => 'Nowhere',
			);
		}

		$this->_last_response = '';
		try {
			$this->_handleAjax( 'geo_mashup_edit' );
		} catch ( WPAjaxDieContinueException $e ) {
			// The expected way out
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertIsArray( $response, 'Unexpected response: ' . $this->_last_response );
		return $response['status'];
	}

	private function assert_original_location( $object_name, $object_id ) {
		$location = GeoMashupDB::get_object_location( $object_name, $object_id );
		$this->assertNotEmpty( $location, 'The location was deleted.' );
		$this->assertEquals( 40, $location->lat );
		$this->assertEquals( -119, $location->lng );
	}

	function test_contributor_cannot_update_others_post_location() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $admin_id ) );
		$this->set_original_location( 'post', $post_id );

		$this->_setRole( 'contributor' );
		$status = $this->post_edit( 'GeoMashupPostUIManager', $post_id );

		$this->assertSame( 403, $status['code'] );
		$this->assert_original_location( 'post', $post_id );
	}

	function test_contributor_cannot_delete_others_post_location() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $admin_id ) );
		$this->set_original_location( 'post', $post_id );

		$this->_setRole( 'contributor' );
		$status = $this->post_edit( 'GeoMashupPostUIManager', $post_id, 'delete' );

		$this->assertSame( 403, $status['code'] );
		$this->assert_original_location( 'post', $post_id );
	}

	function test_contributor_can_update_own_post_location() {
		$this->_setRole( 'contributor' );
		$post_id = self::factory()->post->create( array(
			'post_author' => get_current_user_id(),
			'post_status' => 'draft',
		) );

		$status = $this->post_edit( 'GeoMashupPostUIManager', $post_id );

		$this->assertSame( 200, $status['code'] );
		$this->assertSame( $post_id, $status['object_id'] );
		$location = GeoMashupDB::get_object_location( 'post', $post_id );
		$this->assertNotEmpty( $location );
		$this->assertEquals( 10, $location->lat );
		$this->assertEquals( 20, $location->lng );
	}

	function test_subscriber_cannot_update_other_user_location() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->set_original_location( 'user', $admin_id );

		$this->_setRole( 'subscriber' );
		$status = $this->post_edit( 'GeoMashupUserUIManager', $admin_id );

		$this->assertSame( 403, $status['code'] );
		$this->assert_original_location( 'user', $admin_id );
	}

	function test_subscriber_can_update_own_user_location() {
		$this->_setRole( 'subscriber' );
		$user_id = get_current_user_id();

		$status = $this->post_edit( 'GeoMashupUserUIManager', $user_id );

		$this->assertSame( 200, $status['code'] );
		$location = GeoMashupDB::get_object_location( 'user', $user_id );
		$this->assertNotEmpty( $location );
		$this->assertEquals( 10, $location->lat );
	}

	function test_unknown_ui_manager_is_rejected() {
		$this->_setRole( 'administrator' );
		$post_id = self::factory()->post->create();
		$this->set_original_location( 'post', $post_id );

		foreach ( array( 'GeoMashupUIManager', 'GeoMashupCommentUIManager', 'ArrayObject' ) as $manager ) {
			$status = $this->post_edit( $manager, $post_id, 'delete' );
			$this->assertSame( 400, $status['code'], "Manager: $manager" );
		}
		$this->assert_original_location( 'post', $post_id );
	}

	function test_object_id_is_cast_to_integer() {
		$this->_setRole( 'administrator' );
		$post_id = self::factory()->post->create();

		$status = $this->post_edit( 'GeoMashupPostUIManager', $post_id . '<img src=x onerror=alert(1)>' );

		$this->assertSame( $post_id, $status['object_id'] );
		$this->assertStringNotContainsString( '<img', $this->_last_response );
	}

	function test_get_instance_requires_ui_manager_class() {
		$this->assertNull( GeoMashupUIManager::get_instance( 'ArrayObject' ) );
		$this->assertInstanceOf( 'GeoMashupPostUIManager', GeoMashupUIManager::get_instance( 'GeoMashupPostUIManager' ) );
	}

	function test_edit_action_not_available_logged_out() {
		$this->assertFalse( has_action( 'wp_ajax_nopriv_geo_mashup_edit' ) );
	}
}
