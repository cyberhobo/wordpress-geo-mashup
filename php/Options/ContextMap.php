<?php
/**
 * A typed representation of context map options.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Options;

use GeoMashupOptions;

class ContextMap extends SingleMap {
	/** @var bool */
	public $marker_select_info_window;
	/** @var bool */
	public $marker_select_highlight;
	/** @var bool */
	public $marker_select_center;
	/** @var bool */
	public $marker_select_attachments;
	/** @var string */
	public $marker_default_color;

	public static function from_options( GeoMashupOptions $options ) {
		$context  = $options->get( 'context_map' );
		$instance = new self();
		$instance->from_options_array( $context );

		return $instance;
	}

	protected function from_options_array( $context ) {
		parent::from_options_array( $context );
		$this->marker_select_info_window = $context['marker_select_info_window'] === 'true';
		$this->marker_select_highlight   = $context['marker_select_highlight'] === 'true';
		$this->marker_select_center      = $context['marker_select_center'] === 'true';
		$this->marker_select_attachments = $context['marker_select_attachments'] === 'true';
		$this->marker_default_color      = $context['marker_default_color'];
	}
}
