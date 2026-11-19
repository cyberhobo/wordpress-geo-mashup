<?php
/**
 * Plugin Name: Geo Mashup E2E Fixtures
 * Description: Test-only fixtures for the Playwright end-to-end suite. Loaded as a must-use plugin by wp-env; never shipped.
 *
 * @package GeoMashup
 */

/**
 * Register the hierarchical taxonomy the fixture posts are tagged with.
 */
add_action(
	'init',
	static function () {
		register_taxonomy(
			'test_tax',
			'post',
			array(
				'label'        => 'Test Taxonomy',
				'hierarchical' => true,
				'public'       => true,
			)
		);
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'geo-mashup-e2e seed', 'geo_mashup_e2e_seed' );
}

/**
 * Create the fixture content: located posts, taxonomy terms, plugin options,
 * and one global map page per map provider.
 *
 * Safe to run repeatedly; existing fixture content is deleted first.
 *
 * ## OPTIONS
 *
 * [--googlev3-key=<key>]
 * : Google Maps API key. Empty leaves the saved key unchanged.
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 * @return void
 */
function geo_mashup_e2e_seed( $args = array(), $assoc_args = array() ) {
	global $geo_mashup_options;

	geo_mashup_e2e_clean();

	$terms = array();
	foreach (
		array(
			'term-one'       => array( 'Term One', 0 ),
			'term-one-child' => array( 'Term One Child', 'term-one' ),
			'other-term'     => array( 'Other Term', 0 ),
		) as $slug => list( $name, $parent )
	) {
		$parent_id     = $parent ? $terms[ $parent ] : 0;
		$result        = wp_insert_term( $name, 'test_tax', array( 'slug' => $slug, 'parent' => $parent_id ) );
		$terms[ $slug ] = is_wp_error( $result ) ? $result->get_error_data( 'term_exists' ) : $result['term_id'];
	}

	$locations = array(
		'test_range'         => array( 37.80338, -116.77927, 'tonopah test range' ),
		'cactus_flat'        => array( 37.79133, -116.70951, 'Cactus flat' ),
		'west_intersection'  => array( 37.82009, -116.697121, 'Western intersection' ),
		'south_intersection' => array( 37.793508, -116.671028, 'Southern intersection' ),
		'north_intersection' => array( 37.841784, -116.658325, 'Northern intersection' ),
	);

	// Six posts at five locations: two posts share Cactus flat.
	$posts = array(
		array( 'test_range', 'tonopah test range post', array( 'term-one' ) ),
		array( 'cactus_flat', 'Cactus flat post', array( 'other-term' ) ),
		array( 'west_intersection', 'Western intersection post', array( 'term-one-child' ) ),
		array( 'south_intersection', 'Southern intersection post', array( 'term-one-child', 'other-term' ) ),
		array( 'north_intersection', 'Northern intersection post', array( 'term-one-child' ) ),
		array( 'cactus_flat', 'A second post at Cactus flat', array( 'term-one' ) ),
	);

	foreach ( $posts as $i => list( $location_key, $title, $post_terms ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'post',
				// Distinct dates keep query order deterministic.
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '2020-01-01' ) + $i * DAY_IN_SECONDS ),
				'meta_input'  => array( '_geo_mashup_e2e' => 1 ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id );
		}
		wp_set_object_terms( $post_id, array_map( 'intval', array_intersect_key( $terms, array_flip( $post_terms ) ) ), 'test_tax' );

		list( $lat, $lng, $saved_name ) = $locations[ $location_key ];
		$result = GeoMashupDB::set_object_location(
			'post',
			$post_id,
			array(
				'lat'        => $lat,
				'lng'        => $lng,
				'saved_name' => $saved_name,
			),
			false
		);
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result );
		}
	}

	// set_valid_options() treats missing booleans as unchecked checkboxes, so pass every section.
	$options = array();
	foreach ( array( 'overall', 'single_map', 'global_map', 'context_map' ) as $section ) {
		$options[ $section ] = $geo_mashup_options->get( $section );
	}
	$options['overall']['include_taxonomies']  = array( 'test_tax' );
	$options['overall']['located_post_types']  = array( 'post' );
	if ( ! empty( $assoc_args['googlev3-key'] ) ) {
		$options['overall']['googlev3_key'] = $assoc_args['googlev3-key'];
	}
	$options['global_map']['term_options']     = array(
		'test_tax' => array(
			'color'     => array(
				'term-one'       => 'red',
				'term-one-child' => 'fuchsia',
				'other-term'     => 'olive',
			),
			'line_zoom' => array( 'term-one-child' => '10' ),
		),
	);
	$geo_mashup_options->set_valid_options( $options );
	if ( ! $geo_mashup_options->save() && $geo_mashup_options->validation_errors ) {
		WP_CLI::error( 'Invalid Geo Mashup options: ' . implode( '; ', $geo_mashup_options->validation_errors ) );
	}

	$shortcode_atts = 'map_content="global" zoom="10" cluster_max_zoom="9" marker_select_info_window="false"'
		. ' marker_select_highlight="true" marker_select_center="true" auto_info_open="false" show_future="true"'
		. ' width="400" height="400"';

	foreach ( array( 'googlev3', 'openlayers', 'leaflet' ) as $api ) {
		$page_id = wp_insert_post(
			array(
				'post_title'   => "Global map $api",
				'post_name'    => "e2e-global-map-$api",
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => "[geo_mashup_map map_api=\"$api\" $shortcode_atts]",
				'meta_input'   => array( '_geo_mashup_e2e' => 1 ),
			),
			true
		);
		if ( is_wp_error( $page_id ) ) {
			WP_CLI::error( $page_id );
		}
	}

	WP_CLI::success( 'Geo Mashup e2e fixtures seeded.' );
}

/**
 * Delete content created by a previous seed.
 *
 * @return void
 */
function geo_mashup_e2e_clean() {
	$ids = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_geo_mashup_e2e',
		)
	);
	foreach ( $ids as $id ) {
		GeoMashupDB::delete_object_location( 'post', $id );
		wp_delete_post( $id, true );
	}
	$term_ids = get_terms(
		array(
			'taxonomy'   => 'test_tax',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( is_array( $term_ids ) ) {
		foreach ( $term_ids as $term_id ) {
			wp_delete_term( $term_id, 'test_tax' );
		}
	}
}
