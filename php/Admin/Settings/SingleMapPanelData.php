<?php
/**
 * Data required to render the single map settings tab panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

use GeoMashup\Options\Overall;
use GeoMashup\Options\SingleMap;

class SingleMapPanelData extends BaseData {
	/** @var SingleMap */
	public $options;
	/** @var Overall */
	public $overall;

	public function __construct( SingleMap $single_map = null, Overall $overall = null) {
		global $geo_mashup_options;
		BaseData::__construct();
		$this->options = $single_map === null ? SingleMap::from_options( $geo_mashup_options ) : $single_map;
		$this->overall = $overall === null ? Overall::from_options($geo_mashup_options) : $overall;
	}
}
