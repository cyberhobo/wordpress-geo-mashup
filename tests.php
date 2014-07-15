<?php
/**
 * Data and dependencies for automated functional tests.
 * @package GeoMashup
 */
$width = 400;
$height = 400;
$name = 'test-map';
$global_locations = array(
	"test_range" => array(
		"lat" => "37.80338",
		"lng" => "-116.77927",
		"title" => "tonopah test range",
	),
	"cactus_flat" => array(
		"lat" => "37.79133",
		"lng" => "-116.70951",
		"title" => "Cactus flat",
	),
	"west_intersection" => array(
		"lat" => "37.82009",
		"lng" => "-116.697121",
		"title" => "Western intersection",
	),
	"south_intersection" => array(
		"lat" => "37.793508",
		"lng" => "-116.671028",
		"title" => "Southern intersection",
	),
	"north_intersection" => array(
		"lat" => "37.841784",
		"lng" => "-116.658325",
		"title" => "Northern intersection",
	)
);
$global_data = array(
	"name" => $name,
	"width" => $width,
	"height" => $height,
	"map_type" => "G_NORMAL_MAP",
	"zoom" => "10",
	"background_color" => "c0c0c0",
	"map_control" => "GSmallZoomControl",
	"add_map_type_control" => array(
		"G_NORMAL_MAP",
		"G_SATELLITE_MAP",
		"G_PHYSICAL_MAP",
	),
	"add_overview_control" => "true",
	"add_google_bar" => "false",
	"enable_scroll_wheel_zoom" => "true",
	"show_post" => "false",
	"show_future" => "true",
	"marker_select_info_window" => "false",
	"marker_select_highlight" => "true",
	"marker_select_center" => "true",
	"marker_select_attachments" => "false",
	"max_posts" => "",
	"auto_info_open" => "false",
	"cluster_max_zoom" => "9",
	"include_taxonomies" => array( "test_tax" ),
	"map_content" => "global",
	"ajaxurl" => admin_url( 'admin-ajax.php' ),
	"siteurl" => home_url( '/' ),
	"url_path" => GEO_MASHUP_URL_PATH,
	"template_url_path" => get_stylesheet_directory_uri(),
	"custom_url_path" => "",
	"context_object_id" => 0,
	"object_data" => array(
		"objects" => array(
			array(
				"object_name" => "post",
				"object_id" => "1",
				"title" => $global_locations['test_range']['title'] . ' post',
				"lat" => $global_locations['test_range']['lat'],
				"lng" => $global_locations['test_range']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "1" ),
				),
			),
			array(
				"object_name" => "post",
				"object_id" => "2",
				"title" => $global_locations['cactus_flat']['title'] . ' post',
				"lat" => $global_locations['cactus_flat']['lat'],
				"lng" => $global_locations['cactus_flat']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "3" ),
				),
			),
			array(
				"object_name" => "post",
				"object_id" => "3",
				"title" => $global_locations['west_intersection']['title'] . ' post',
				"lat" => $global_locations['west_intersection']['lat'],
				"lng" => $global_locations['west_intersection']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "2" ),
				),
			),
			array(
				"object_name" => "post",
				"object_id" => "4",
				"title" => $global_locations['south_intersection']['title'] . ' post',
				"lat" => $global_locations['south_intersection']['lat'],
				"lng" => $global_locations['south_intersection']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "2", "3" ),
				),
			),
			array(
				"object_name" => "post",
				"object_id" => "5",
				"title" => $global_locations['north_intersection']['title'] . ' post',
				"lat" => $global_locations['north_intersection']['lat'],
				"lng" => $global_locations['north_intersection']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "2" ),
				),
			),
			array(
				"object_name" => "post",
				"object_id" => "6",
				"title" => 'A second post at ' . $global_locations['cactus_flat']['title'],
				"lat" => $global_locations['cactus_flat']['lat'],
				"lng" => $global_locations['cactus_flat']['lng'],
				"author_name" => "admin",
				"terms" => array(
					"test_tax" => array( "1" ),
				),
			),
		),
	),
	"check_all_label" => "Check/Uncheck All",
	"term_properties" => array(
		"test_tax" => array(
			"label" => "Test Taxonomy",
			"terms" => array(
				"1" => array(
					"name" => "Term One",
					"parent_id" => "",
					"color" => "red",
				),
				"2" => array(
					"name" => "Term One Child",
					"parent_id" => "1",
					"color" => "fuchsia",
					"line_zoom" => "10",
				),
				"3" => array(
					"name" => "Other Term",
					"parent_id" => "",
					"color" => "olive",
				),
			),
		),
	),
);

$test_apis = array( 'googlev3', 'openlayers', 'leaflet' );

$global_urls = array();

foreach( $test_apis as $test_api ) {
	$api_data = $global_data;
	$api_data['map_api'] = $test_api;
	$data_key = $test_api . '-global-test';
	set_transient( 'gmm' . $data_key, $api_data );
	$global_urls[$test_api] = htmlspecialchars_decode( GeoMashup::build_home_url( array(
		'geo_mashup_content' => 'render-map',
		'map_data_key' => $data_key,
	) ) );
}

$location_count = count( $global_locations );
wp_localize_script(
	'geo-mashup-tests',
	'gm_test_data',
	compact( 'test_apis', 'global_urls', 'location_count', 'width', 'height', 'name' )
);

