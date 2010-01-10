<?php
/** 
 * Geo Mashup uninstall procedure.
 *
 * @package GeoMashup
 */

// Make sure this is a legitimate uninstall request
if( ! defined( 'ABSPATH') or ! defined('WP_UNINSTALL_PLUGIN') or ! current_user_can( 'delete_plugins' ) )
	exit();

/**
 * Drop Geo Mashup database tables.
 * 
 * @since 1.3
 * @access public
 */
function geo_mashup_uninstall_db() {
	global $wpdb;
	$tables = array( 'geo_mashup_administrative_names', 'geo_mashup_location_relationships', 'geo_mashup_locations' );
	foreach( $tables as $table ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $table );
	}
}

/**
 * Delete Geo Mashup saved options.
 * 
 * @since 1.3
 * @access public
 */
function geo_mashup_uninstall_options() {
	delete_option( 'geo_mashup_temp_kml_url' );
	delete_option( 'geo_mashup_db_version' );
	delete_option( 'geo_mashup_activation_log' );
	delete_option( 'geo_mashup_options' );
	delete_option( 'geo_locations' );
	// Leave the google_api_key option 
	// Still belongs to this site, and may be used by other plugins
}

// I'm afraid to do this - started a trac ticket http://core.trac.wordpress.org/ticket/11850
// geo_mashup_uninstall_db();
geo_mashup_uninstall_options();
?>
