<?php

/**
 * Manage integration with Snazzy Maps.
 * @since 1.10.0
 */
class GeoMashupSnazzyMaps {

	/**
	 * Load Snazzy Maps integrations.
	 *
	 * @since 1.10.0
	 */
	public static function load() {
		add_action( 'geo_mashup_render_map', array( __CLASS__, 'enqueue_snazzy_script' ) );
	}

	/**
	 * Enqueue the Snazzy Maps script in the Geo Mashup frame.
	 *
	 * @since 1.10.0
	 */
	public static function enqueue_snazzy_script() {
		/** @noinspection ClassConstantCanBeUsedInspection */
		if ( class_exists( '\SnazzyMaps\SnazzyMaps_Main' ) ) {
			// Enqueue function as of 1.1.5
			\SnazzyMaps\SnazzyMaps_Main::snazzy_enqueue_script();
		} else if ( function_exists( 'snazzy_enqueue_script' ) ) {
			// Enqueue function as of 1.1.3
			snazzy_enqueue_script();
		} else if ( function_exists( 'enqueue_script' ) ) {
			// Enqueue function prior to 1.1.3
			enqueue_script();
		}
		GeoMashupRenderMap::enqueue_script( 'snazzymaps-js' );
	}

}
