<?php
/**
 * A typed representation of global map options.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Options;

use GeoMashupOptions;

class GlobalMap extends SingleMap {
	/** @var array */
	public $term_options;
	/** @var bool */
	public $show_post;
	/** @var string */
	public $show_future;
	/** @var bool */
	public $marker_select_info_window;
	/** @var bool */
	public $marker_select_highlight;
	/** @var bool */
	public $marker_select_center;
	/** @var bool */
	public $marker_select_attachments;
	/** @var string */
	public $max_posts;
	/** @var bool */
	public $auto_info_open;
	/** @var int */
	public $cluster_max_zoom;
	/** @var string */
	public $cluster_lib;
	/** @var string */
	public $thumbnail_size;
	/** @var string */
	public $marker_default_color;

	public static function from_options( GeoMashupOptions $options ) {
		$global   = $options->get( 'global_map' );
		$instance = new self();
		$instance->from_options_array( $global );

		return $instance;
	}

	protected function from_options_array( $global ) {
		parent::from_options_array( $global );
		$this->term_options              = $global['term_options'];
		$this->show_post                 = $global['show_post'] === 'true';
		$this->show_future               = $global['show_future'];
		$this->marker_select_info_window = $global['marker_select_info_window'] === 'true';
		$this->marker_select_highlight   = $global['marker_select_highlight'] === 'true';
		$this->marker_select_center      = $global['marker_select_center'] === 'true';
		$this->marker_select_attachments = $global['marker_select_attachments'] === 'true';
		$this->max_posts                 = $global['max_posts'];
		$this->auto_info_open            = $global['auto_info_open'] === 'true';
		$this->cluster_max_zoom          = (int) $global['cluster_max_zoom'];
		$this->cluster_lib               = $global['cluster_lib'];
		$this->thumbnail_size            = $global['thumbnail_size'];
		$this->marker_default_color      = $global['marker_default_color'];
	}
}
