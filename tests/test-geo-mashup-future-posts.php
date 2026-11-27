<?php

/**
 * Security: the public geo_mashup_query action must not reveal scheduled
 * (future) posts unless the site has made them public via the global map
 * "Show Future Posts" setting, or the current user can read them.
 */
class GeoMashup_Future_Posts_Unit_Tests extends GeoMashupTestCase {

	private $saved_show_future;
	private $saved_get;
	private $saved_request;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'GeoMashupQuery' ) ) {
			// geo-query.php dispatches a request when loaded, so give it a harmless one
			$saved_get = $_GET;
			$saved_request = $_REQUEST;
			$_GET = $_REQUEST = array( 'output' => 'json', 'object_name' => 'user' );
			// It also sends headers, which PHPUnit output has made impossible
			set_error_handler( '__return_true', E_WARNING );
			ob_start();
			require_once dirname( __FILE__ ) . '/../geo-query.php';
			ob_end_clean();
			restore_error_handler();
			$_GET = $saved_get;
			$_REQUEST = $saved_request;
		}
	}

	protected function setUp(): void {
		global $geo_mashup_options;
		parent::setUp();
		$this->saved_show_future = $geo_mashup_options->get( 'global_map', 'show_future' );
		$this->saved_get = $_GET;
		$this->saved_request = $_REQUEST;
	}

	protected function tearDown(): void {
		$this->set_global_show_future( $this->saved_show_future );
		$_GET = $this->saved_get;
		$_REQUEST = $this->saved_request;
		parent::tearDown();
	}

	private function set_global_show_future( $value ) {
		global $geo_mashup_options;
		$geo_mashup_options->set_valid_options( array( 'global_map' => array( 'show_future' => $value ) ) );
		$this->assertEquals( $value, $geo_mashup_options->get( 'global_map', 'show_future' ) );
	}

	private function create_post( $status ) {
		// Not authored by the current user, who could then read it
		$author_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$args = array( 'post_status' => $status, 'post_author' => $author_id );
		if ( 'future' === $status ) {
			$args['post_date'] = gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS );
		}
		$post_id = self::factory()->post->create( $args );
		$this->assertEquals( $status, get_post_status( $post_id ) );
		return $post_id;
	}

	private function create_located_post( $status ) {
		$post_id = $this->create_post( $status );
		GeoMashupDB::set_object_location( 'post', $post_id, $this->get_nv_test_location(), false );
		return $post_id;
	}

	/**
	 * Run the info window query, returning the IDs of the posts it would render.
	 *
	 * The rendered HTML isn't checked, since load_template() includes a
	 * template only once per process.
	 */
	private function object_html_post_ids( $object_ids ) {
		global $wp_query;
		$_GET = array( 'object_ids' => implode( ',', $object_ids ) );
		ob_start();
		GeoMashupQuery::generate_object_html();
		ob_end_clean();
		$post_ids = array_map( 'intval', wp_list_pluck( $wp_query->posts, 'ID' ) );
		wp_reset_query();
		sort( $post_ids );
		return $post_ids;
	}

	private function public_location_ids( $query_args ) {
		$data = json_decode( GeoMashup::get_public_locations_json( $query_args ), true );
		return wp_list_pluck( $data['objects'], 'object_id' );
	}

	function test_future_post_html_hidden_from_visitors() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( 0 );
		$post_id = $this->create_post( 'future' );

		$this->assertEquals( array(), $this->object_html_post_ids( array( $post_id ) ) );
	}

	function test_hidden_future_ids_do_not_return_all_posts() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( 0 );
		$this->create_post( 'publish' );
		$future_id = $this->create_post( 'future' );

		$this->assertEquals( array(), $this->object_html_post_ids( array( $future_id ) ) );
	}

	function test_published_post_html_shown_alongside_hidden_future() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( 0 );
		$published_id = $this->create_post( 'publish' );
		$future_id = $this->create_post( 'future' );

		$this->assertEquals( array( $published_id ), $this->object_html_post_ids( array( $published_id, $future_id ) ) );
	}

	function test_future_post_html_shown_when_site_shows_future() {
		$this->set_global_show_future( 'true' );
		wp_set_current_user( 0 );
		$post_id = $this->create_post( 'future' );

		$this->assertEquals( array( $post_id ), $this->object_html_post_ids( array( $post_id ) ) );
	}

	function test_future_post_html_shown_to_editor() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post_id = $this->create_post( 'future' );

		$this->assertEquals( array( $post_id ), $this->object_html_post_ids( array( $post_id ) ) );
	}

	function test_future_post_html_hidden_from_subscriber() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$post_id = $this->create_post( 'future' );

		$this->assertEquals( array(), $this->object_html_post_ids( array( $post_id ) ) );
	}

	function test_draft_and_private_posts_not_visible() {
		$this->set_global_show_future( 'true' );
		wp_set_current_user( 0 );

		$this->assertFalse( GeoMashup::current_user_can_see_queried_post( $this->create_post( 'draft' ) ) );
		$this->assertFalse( GeoMashup::current_user_can_see_queried_post( $this->create_post( 'private' ) ) );
	}

	function test_location_json_ignores_requested_future_for_visitors() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( 0 );
		$published_id = $this->create_located_post( 'publish' );
		$future_id = $this->create_located_post( 'future' );

		$this->assertEquals( array( $published_id ), $this->public_location_ids( array( 'show_future' => 'true' ) ) );
		$this->assertEquals( array(), $this->public_location_ids( array( 'show_future' => 'only' ) ) );
	}

	function test_location_json_future_for_editor() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$future_id = $this->create_located_post( 'future' );

		$this->assertEquals( array( $future_id ), $this->public_location_ids( array( 'show_future' => 'only' ) ) );
	}

	function test_location_json_future_hidden_from_subscriber() {
		$this->set_global_show_future( 'false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->create_located_post( 'future' );

		$this->assertEquals( array(), $this->public_location_ids( array( 'show_future' => 'only' ) ) );
	}

	function test_location_json_future_when_site_shows_future() {
		$this->set_global_show_future( 'true' );
		wp_set_current_user( 0 );
		$future_id = $this->create_located_post( 'future' );

		$this->assertEquals( array( $future_id ), $this->public_location_ids( array( 'show_future' => 'only' ) ) );
	}
}
