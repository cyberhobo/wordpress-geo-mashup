/*
 * This file is deprecated - please use geo-mashup-location-editor.js instead.
 *
 * This file is part of the Geo Mashup WordPress plugin, please see geo-mashup.php for
 * license information.
 *
 * @package GeoMashup
 * @subpackage Client
 * @deprecated
 */

/*global jQuery */

jQuery( function( $ ) {
	var credit_img_src = $( '#geo_mashup_preload_image' ).attr( 'src' ),
		editor_script_url;

	if ( credit_img_src ) {
		editor_script_url = credit_img_src.replace( 'images/gm-credit.png', 'geo-mashup-location-editor.js' );
		$.getScript( editor_script_url );
	}
} );
