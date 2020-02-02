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

	public function __construct() {
		$this->map_controls = [
			'GSmallZoomControl'   => __( 'Small Zoom', 'GeoMashup' ),
			'GSmallZoomControl3D' => __( 'Small Zoom 3D', 'GeoMashup' ),
			'GSmallMapControl'    => __( 'Small Pan/Zoom', 'GeoMashup' ),
			'GLargeMapControl'    => __( 'Large Pan/Zoom', 'GeoMashup' ),
			'GLargeMapControl3D'  => __( 'Large Pan/Zoom 3D', 'GeoMashup' )
		];
		$this->map_types = [
			'G_NORMAL_MAP'       => __( 'Roadmap', 'GeoMashup' ),
			'G_SATELLITE_MAP'    => __( 'Satellite', 'GeoMashup' ),
			'G_HYBRID_MAP'       => __( 'Hybrid', 'GeoMashup' ),
			'G_PHYSICAL_MAP'     => __( 'Terrain', 'GeoMashup' ),
			'G_SATELLITE_3D_MAP' => __( 'Earth Plugin', 'GeoMashup' )
		];
		$this->zoom_options = [ 'auto' => __( 'auto', 'GeoMashup' ) ];
		foreach ( range( 1, GEO_MASHUP_MAX_ZOOM ) as $i ) {
			$this->zoom_options[ $i ] = $i;
		}
	}
}
