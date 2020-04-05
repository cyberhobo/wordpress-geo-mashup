<?php
/**
 * Data required to render the global map settings panel.
 *
 * @package GeoMashup
 */
namespace GeoMashup\Admin\Settings;

use GeoMashup\Options\GlobalMap;
use GeoMashup\Options\Overall;

class GlobalMapPanelData extends BaseData {
	/** @var GlobalMap */
	public $options;
	/** @var Overall */
	public $overall;
	/** @var array */
	public $cluster_zoom_options;
	/** @var array */
	public $future_options;
	/** @var array */
	public $thumbnail_sizes;

	public function __construct(GlobalMap $global_map = null, Overall $overall = null) {
		global $geo_mashup_options;
		BaseData::__construct();
		$this->options = $global_map === null ? GlobalMap::from_options($geo_mashup_options) : $global_map;
		$this->overall = $overall === null ? Overall::from_options($geo_mashup_options) : $overall;

		$this->cluster_zoom_options = [ '' => __( '0 (Clustering Off)', 'GeoMashup' ) ];
		foreach ( range( 1, GEO_MASHUP_MAX_ZOOM ) as $i ) {
			$this->cluster_zoom_options[ $i ] = $i;
		}

		$this->future_options = [
			'true'  => __( 'Yes', 'GeoMashup' ),
			'false' => __( 'No', 'GeoMashup' ),
			'only'  => __( 'Only', 'GeoMashup' )
		];

		$this->thumbnail_sizes = get_intermediate_image_sizes();
	}
}
