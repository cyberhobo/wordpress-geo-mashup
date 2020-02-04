<?php
/**
 * A typed representation of overall options.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Options;

use GeoMashupOptions;

class Overall {
	/** @var string */
	public $google_server_key;
	/** @var string */
	public $map_api;
	/** @var string */
	public $googlev3_key;
	/** @var string */
	public $mashup_page;
	/** @var array */
	public $located_post_types;
	/** @var bool */
	public $locate_users;
	/** @var bool */
	public $locate_comments;
	/** @var bool */
	public $enable_geo_search;
	/** @var array */
	public $include_taxonomies;
	/** @var bool */
	public $copy_geodata;
	/** @var string */
	public $import_custom_field;
	/** @var bool */
	public $enable_reverse_geocoding;
	/** @var bool */
	public $theme_stylesheet_with_maps;
	/** @var string */
	public $geonames_username;
	/** @var bool */
	public $add_category_links;
	/** @var string */
	public $category_link_separator;
	/** @var string */
	public $category_link_text;
	/** @var int */
	public $category_zoom;

	public static function from_options( GeoMashupOptions $options ) {
		$overall                              = $options->get( 'overall' );
		$instance                             = new self();
		$instance->google_server_key          = $overall['google_server_key'];
		$instance->map_api                    = $overall['map_api'];
		$instance->googlev3_key               = $overall['googlev3_key'];
		$instance->mashup_page                = $overall['mashup_page'];
		$instance->located_post_types         = $overall['located_post_types'];
		$instance->locate_users               = $overall['located_object_name']['user'] === 'true';
		$instance->locate_comments            = $overall['located_object_name']['comment'] === 'true';
		$instance->enable_geo_search          = $overall['enable_geo_search'] === 'true';
		$instance->include_taxonomies         = $overall['include_taxonomies'];
		$instance->copy_geodata               = $overall['copy_geodata'] === 'true';
		$instance->import_custom_field        = $overall['import_custom_field'];
		$instance->enable_reverse_geocoding   = $overall['enable_reverse_geocoding'] === 'true';
		$instance->theme_stylesheet_with_maps = $overall['theme_stylesheet_with_maps'] === 'true';
		$instance->geonames_username          = $overall['geonames_username'];
		$instance->add_category_links         = $overall['add_category_links'] === 'true';
		$instance->category_link_separator    = $overall['category_link_separator'];
		$instance->category_link_text         = $overall['category_link_text'];
		$instance->category_zoom              = (int) $overall['category_zoom'];

		return $instance;
	}
}
