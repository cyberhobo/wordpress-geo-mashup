var jQuery, document;

(function( $ ) {
	$( document ).on( 'widget-added', function( event, widget ) {

		var widget_id = $( '#' + widget[0].id ),
			select_obj_name = widget_id.find( '.GeoMashup-search > p.object-name select' ),
			select_tax = widget_id.find( '.GeoMashup-search > .taxonomy_section select' );

		// Check Include Taxonomies in Geo Mashup Options
		if ( select_tax.length ) {

			var fieldset_tems = widget_id.find( '.GeoMashup-search > .taxonomy_section > fieldset' ),
				termlist = fieldset_tems.find( 'fieldset' ),
				hidden_input = fieldset_tems.find( 'input.hide-if-js' ),
				checkbox_array = [];

			var reset_terms = function() {

				termlist.hide();
				checkbox_array = [];
				hidden_input.val( '' );

				fieldset_tems.find( 'input:checkbox' ).each( function() {
					$( this ).prop( "checked", false );
				} );
			};

			var obj_name_action = function( select, action ) {

				// only if action changed
				if (action == 'change' ) {
											
					reset_terms();
											
					if (select.val() == 'post') {
						select_tax.val( 'category' ).change();
					} else {
						select_tax.val( 'select' ).change();
					}
				}
										
				// always, when it is added widget and action changed
				if ( select.val() == 'user' || select.val() == 'comment' ) {
											
					
					widget_id.find( 'span.taxonomy_section' ).hide();
					reset_terms();
					select_tax.val( 'select' ).change();

				} else {
					fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:selected' ).val() ).show();
					widget_id.find( 'span.taxonomy_section' ).show();
				}
			};

			/**
			 * Star action for widget form
			 */
			
			obj_name_action( select_obj_name, 'add' );
			// hide all terms lists
			termlist.hide();

			if ( hidden_input.val().length !== 0 || select_tax.val() !== 'select' ) {

				checkbox_array = hidden_input.val().split( "," );
				// Show only list of selected option taxonomy
				fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:selected' ).val() ).show();

			} else {

				checkbox_array = [];
				// Show only list of first option taxonomy
				fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:first-child' ).val() ).show();
			}

			/**
			 * Change action for widget form
			 */

			// Action for object_name
			select_obj_name.change( function() {
				obj_name_action( $( this ), 'change');
			} );

			// Action for Taxonomy Select
			select_tax.change( function() {
				// Reset terms
				reset_terms();
				// Show terms list of select taxonomy
				fieldset_tems.find( 'fieldset.' + $( this ).val() ).show();
			} );

			// Action for Terms Checkbox
			fieldset_tems.find( 'input:checkbox' ).change( function() {

				var input = $( this );

				if ( input.is( ':checked' ) ) {
					checkbox_array.push( input.val() );
				} else {
					checkbox_array.splice( $.inArray( input.val(), checkbox_array ), 1 );
				}

				hidden_input.val( checkbox_array.join( ',' ) );
			} );
		}// if select_tax.length
	} );//$(document).on( 'widget-added'...
}( jQuery ));