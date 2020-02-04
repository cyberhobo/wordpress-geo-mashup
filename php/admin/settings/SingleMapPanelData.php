<?php
/**
 * Data required to render the single map settings tab panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

require_once dirname( dirname( __DIR__ ) ) . '/Options/SingleMap.php';
require_once __DIR__ . '/BaseData.php';

use GeoMashup\Options\SingleMap;

class SingleMapPanelData extends BaseData {
	/** @var SingleMap */
	public $options;

	public function __construct( SingleMap $single_map = null ) {
		global $geo_mashup_options;
		BaseData::__construct();
		$this->options = $single_map === null ? SingleMap::from_options( $geo_mashup_options ) : $single_map;
	}
}
