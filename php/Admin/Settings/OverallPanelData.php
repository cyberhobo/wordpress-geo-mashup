<?php
/**
 * Data required to render the overall settings tab panel.
 *
 * @package GeoMashup
 * @since 1.12.0
 */

namespace GeoMashup\Admin\Settings;

use GeoMashup\Options\Overall;

class OverallPanelData extends BaseData {
	/** @var Overall */
	public $options;
	/** @var array */
	public $map_apis;

	public function __construct(Overall $overall = null) {
		global $geo_mashup_options;

		BaseData::__construct();
		$this->options = $overall === null ? Overall::from_options($geo_mashup_options) : $overall;
		$this->map_apis = [
			'googlev3'   => __( 'Google v3', 'GeoMashup' ),
			'openlayers' => __( 'OpenLayers', 'GeoMashup' ),
			'leaflet'    => __( 'Leaflet', 'GeoMashup' ),
		];
	}
}
