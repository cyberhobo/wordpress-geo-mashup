<?php

/**
 * Manage Geo Mashup uninstall.
 */
class GeoMashupUninstaller {
	/**
	 * Drop Geo Mashup database tables.
	 *
	 * @since 1.3
	 */
	public function geo_mashup_uninstall_db() {
		global $wpdb;
		$tables = array( 'geo_mashup_administrative_names', 'geo_mashup_location_relationships', 'geo_mashup_locations' );
		foreach ( $tables as $table ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $table );
		}
	}

	/**
	 * Delete Geo Mashup saved options.
	 *
	 * @since 1.3
	 */
	public function geo_mashup_uninstall_options() {
		delete_option( 'geo_mashup_temp_kml_url' );
		delete_option( 'geo_mashup_db_version' );
		delete_option( 'geo_mashup_activation_log' );
		delete_option( 'geo_mashup_options' );
		delete_option( 'geo_locations' );
		// Leave the google_api_key option
		// Still belongs to this site, and may be used by other plugins
	}
}
