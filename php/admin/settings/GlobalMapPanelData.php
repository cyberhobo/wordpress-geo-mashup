<?php
/**
 * Data required to render the global map settings panel.
 *
 * @package GeoMashup
 */
namespace GeoMashup\Admin\Settings;

require_once dirname(dirname(__DIR__)) . '/Options/GlobalMap.php';
require_once __DIR__ . '/BaseData.php';

use GeoMashup\Options\GlobalMap;
use GeoMashup\Options\Overall;

class GlobalMapPanelData extends BaseData {
	/** @var GlobalMap */
	public $options;
	/** @var Overall */
	public $overall;
	/** @var array */
	public $cluster_zoom_options;
	/** @var array */
	public $future_options;
	/** @var array */
	public $color_names;
	/** @var array */
	public $thumbnail_sizes;

	public function __construct(GlobalMap $global_map = null, Overall $overall = null) {
		global $geo_mashup_options;
		BaseData::__construct();
		$this->options = $global_map === null ? GlobalMap::from_options($geo_mashup_options) : $global_map;
		$this->overall = $overall === null ? Overall::from_options($geo_mashup_options) : $overall;

		$this->cluster_zoom_options = [ '' => __( '0 (Clustering Off)', 'GeoMashup' ) ];
		foreach ( range( 1, GEO_MASHUP_MAX_ZOOM ) as $i ) {
			$this->cluster_zoom_options[ $i ] = $i;
		}

		$this->future_options = [
			'true'  => __( 'Yes', 'GeoMashup' ),
			'false' => __( 'No', 'GeoMashup' ),
			'only'  => __( 'Only', 'GeoMashup' )
		];

		$this->color_names = [
			'aqua'    => '#00ffff',
			'black'   => '#000000',
			'blue'    => '#0000ff',
			'fuchsia' => '#ff00ff',
			'gray'    => '#808080',
			'green'   => '#008000',
			'lime'    => '#00ff00',
			'maroon'  => '#800000',
			'navy'    => '#000080',
			'olive'   => '#808000',
			'orange'  => '#ffa500',
			'purple'  => '#800080',
			'red'     => '#ff0000',
			'silver'  => '#c0c0c0',
			'teal'    => '#008080',
			'white'   => '#ffffff',
			'yellow'  => '#ffff00'
		];

		$this->thumbnail_sizes = get_intermediate_image_sizes();
	}
}
