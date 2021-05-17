<?php
/**
 * Data used in multiple places in the settings.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class BaseData {
	/** @var array */
	public $zoom_options;
	/** @var array */
	public $map_controls;
	/** @var array */
	public $map_types;
	/** @var array */
	public $color_names;

	public function __construct() {
		$this->map_controls = [
			'GSmallZoomControl' => __( 'Small Zoom', 'GeoMashup' ),
			'GSmallMapControl'  => __( 'Small Pan/Zoom', 'GeoMashup' ),
			'GLargeMapControl'  => __( 'Large Pan/Zoom', 'GeoMashup' ),
		];
		$this->map_types    = [
			'googlev3' => [
				'G_NORMAL_MAP'    => __( 'Roadmap', 'GeoMashup' ),
				'G_SATELLITE_MAP' => __( 'Satellite', 'GeoMashup' ),
				'G_HYBRID_MAP'    => __( 'Hybrid', 'GeoMashup' ),
				'G_PHYSICAL_MAP'  => __( 'Terrain', 'GeoMashup' ),
			],
			'leaflet'  => [
				'G_NORMAL_MAP'    => __( 'Roadmap', 'GeoMashup' ),
				'G_SATELLITE_MAP' => __( 'Satellite', 'GeoMashup' ),
			]
		];
		$this->color_names  = [
			'aqua'    => '#00ffff',
			'black'   => '#000000',
			'blue'    => '#0000ff',
			'fuchsia' => '#ff00ff',
			'gray'    => '#808080',
			'green'   => '#008000',
			'lime'    => '#00ff00',
			'maroon'  => '#800000',
			'navy'    => '#000080',
			'olive'   => '#808000',
			'orange'  => '#ffa500',
			'purple'  => '#800080',
			'red'     => '#ff0000',
			'silver'  => '#c0c0c0',
			'teal'    => '#008080',
			'white'   => '#ffffff',
			'yellow'  => '#ffff00'
		];
		$this->zoom_options = [ 'auto' => __( 'auto', 'GeoMashup' ) ];
		foreach ( range( 1, GEO_MASHUP_MAX_ZOOM ) as $i ) {
			$this->zoom_options[ $i ] = $i;
		}
	}
}
