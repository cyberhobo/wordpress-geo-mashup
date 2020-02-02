<?php
/**
 * Data required to render the context map settings panel.
 *
 * @package GeoMashup
 */
namespace GeoMashup\Admin\Settings;

require_once dirname(dirname(__DIR__)) . '/Options/ContextMap.php';
require_once __DIR__ . '/BaseData.php';

use GeoMashup\Options\ContextMap;

class ContextMapPanelData extends BaseData {
	/** @var ContextMap */
	public $options;

	public function __construct(ContextMap $context = null) {
		global $geo_mashup_options;

		BaseData::__construct();
		$this->options = $context === null ? ContextMap::from_options($geo_mashup_options) : $context;
	}
}
