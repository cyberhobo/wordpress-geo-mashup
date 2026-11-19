<?php
/**
 * Data required to render tabs
 */

namespace GeoMashup\Admin\Settings;

class TabsData {
	/** @var string */
	public $selected_tab;

	public static function from_submission( $submission ) {
		$instance = new self();
		$instance->selected_tab = isset( $submission['geo_mashup_selected_tab'] ) ? intval( $submission['geo_mashup_selected_tab'] ) : 0;

		return $instance;
	}
}
