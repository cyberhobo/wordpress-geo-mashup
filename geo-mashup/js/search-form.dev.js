/*global jQuery */

// If jQuery is in use, enhance the search form(s) a bit
if ( typeof jQuery !== 'undefined' ) {
	jQuery( function( $ ) {
		var cleared = false;
		$( '.geo-mashup-search-input' ).focus( function() {
			if ( !cleared ) {
				$( this ).val('');
				cleared = true;
			}
		} );
	} );
}
