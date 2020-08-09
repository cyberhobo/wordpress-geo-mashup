<?php
/**
 * Data required to render the context map settings panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

use GeoMashup\Options\ContextMap;
use GeoMashup\Options\Overall;

class ContextMapPanelData extends BaseData {
	/** @var ContextMap */
	public $options;
	/** @var Overall */
	public $overall;

	public function __construct( ContextMap $context = null, Overall $overall = null) {
		global $geo_mashup_options;

		BaseData::__construct();
		$this->options = $context === null ? ContextMap::from_options( $geo_mashup_options ) : $context;
		$this->overall = $overall === null ? Overall::from_options( $geo_mashup_options ) : $overall;
	}
}
