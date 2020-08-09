<?php

namespace GeoMashup\Hooks;

use GeoMashup;
use GeoMashupRenderMap;

class RenderSearchMap extends Base {

	public function render_map() {
		if ( 'search-results-map' === GeoMashupRenderMap::map_property( 'name' ) ) {
			// Custom javascript for optional use in template
			GeoMashup::register_script(
				'geo-mashup-search-results',
				'js/search-results.js',
				[ 'geo-mashup' ],
				GEO_MASHUP_VERSION,
				true
			);
			GeoMashupRenderMap::enqueue_script( 'geo-mashup-search-results' );
		}
	}

	public function add() {
		add_action( 'geo_mashup_render_map', [ $this, 'render_map' ] );
	}

	public function remove() {
		remove_action( 'geo_mashup_render_map', [ $this, 'render_map' ] );
	}
}
