<?php
/**
 * A typed representation of single map options.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Options;

use GeoMashupOptions;

class SingleMap {
	/** @var string */
	public $width;
	/** @var string */
	public $height;
	/** @var string */
	public $map_control;
	/** @var string */
	public $map_type;
	/** @var int */
	public $zoom;
	/** @var string */
	public $background_color;
	/** @var bool */
	public $add_overview_control;
	/** @var bool */
	public $add_full_screen_control;
	/** @var array */
	public $add_map_type_control;
	/** @var bool */
	public $enable_scroll_wheel_zoom;
	/** @var bool */
	public $click_to_load;
	/** @var string */
	public $click_to_load_text;
	/** @var string */
	public $marker_default_color;

	public static function from_options( GeoMashupOptions $options ) {
		$single   = $options->get( 'single_map' );
		$instance = new self();
		$instance->from_options_array( $single );

		return $instance;
	}

	protected function from_options_array( $single ) {
		$this->width                    = $single['width'];
		$this->height                   = $single['height'];
		$this->map_control              = $single['map_control'];
		$this->map_type                 = $single['map_type'];
		$this->zoom                     = $single['zoom'];
		$this->background_color         = $single['background_color'];
		$this->add_overview_control     = $single['add_overview_control'] === 'true';
		$this->add_full_screen_control  = $single['add_full_screen_control'] === 'true';
		$this->add_map_type_control     = $single['add_map_type_control'];
		$this->enable_scroll_wheel_zoom = $single['enable_scroll_wheel_zoom'] === 'true';
		$this->click_to_load            = $single['click_to_load'] === 'true';
		$this->click_to_load_text       = $single['click_to_load_text'];
		$this->marker_default_color     = $single['marker_default_color'];
	}

}

