<?php
/**
 * Data required to render the tests settings tab.
 *
 * @package GeoMashup
 */
namespace GeoMashup\Admin\Settings;

class TestsPanelData {
	/** @var bool */
	public $include_tests;
	/** @var bool */
	public $run_tests;

	public static function from_submission( $submission ) {
		$instance = new self();
		$instance->include_tests = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$instance->run_tests = isset($submission['geo_mashup_run_tests']);

		return $instance;
	}
}
