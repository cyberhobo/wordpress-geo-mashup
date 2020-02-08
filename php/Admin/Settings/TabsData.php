<?php
/**
 * Data required to render tabs
 */

namespace GeoMashup\Admin\Settings;

class TabsData {
	/** @var string */
	public $selected_tab;
	/** @var bool */
	public $include_tests;

	public static function from_submission( $submission ) {
		$instance                = new self();
		$instance->selected_tab  = isset($submission['geo_mashup_selected_tab']) ? $submission['geo_mashup_selected_tab'] : '0';
		$instance->include_tests = defined( 'WP_DEBUG' ) && WP_DEBUG;

		return $instance;
	}
}
