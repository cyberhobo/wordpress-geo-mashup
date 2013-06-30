/**
 * @fileOverview
 * The default Geo Mashup location editor AJAX interface.
 */

/*global jQuery */
/*global mxn */
/*global GeoMashupLocationEditor: true */ // Misnamed, not a constructor
/*global geo_mashup_location_editor: true, geo_mashup_location_editor_settings */
/*jslint browser: true, white: true, sloppy: true, undef: true */

var GeoMashupLocationEditor, geo_mashup_location_editor;

/**
 * @namespace The public interface for the location editor.
 *
 * The object is created with event properties defined. Other members are
 * added when the editor loads.
 *
 * Events use the Mapstraction mxn.Event interface.
 *
 * @public
 * @since 1.4
 */
geo_mashup_location_editor = {

	/**
	 * The object id being edited
	 * @public
	 * @since 1.4
	 */
	object_id: null,

	/**
	 * The Mapstraction object.
	 * @public
	 * @since 1.4
	 */
	map: null,

	/**
	 * An event fired when the map is created.
	 * @event
	 * @name geo_mashup_location_editor#mapCreated
	 */
	mapCreated: new mxn.Event( 'mapCreated', this ),

	/**
	 * An event fired when the editor has loaded.
	 * @event
	 * @name geo_mashup_location_editor#loaded
	 */
	loaded: new mxn.Event( 'loaded', this ),

	/**
	 * An event fired when a marker is created.
	 * @event
	 * @name geo_mashup_location_editor#markerCreated
	 * @param object Marker filter.
	 */
	markerCreated: new mxn.Event( 'markerCreated', this ),

	/**
	 * An event fired when a marker is selected.
	 * @event
	 * @name geo_mashup_location_editor#markerSelected
	 * @param object Marker filter.
	 */
	markerSelected: new mxn.Event( 'markerSelected', this )

};

/*
* Manage the location editing interface with jQuery.
*
* Variables holding a jQuery object are named with a $.
*
* @uses edit-form.php
*/
jQuery( function( $ ) {
var 
	// Private variables 
	geo_mashup_url_path = geo_mashup_location_editor_settings.geo_mashup_url_path,
	ajax_url = geo_mashup_location_editor_settings.ajax_url,
	object_id = $( '#geo_mashup_object_id' ).val(),
	have_unsaved_changes = false,
	red_icon,
	green_icon,
	selected_marker,
	map, // short for public geo_mashup_location_editor.map
	kml,

	// jQuery elements
	$busy_icon,
	$inline_help_link = $('#geo-mashup-inline-help-link'),
	$add_button = $( '#geo_mashup_add_location' ),
	$update_button = $( '#geo_mashup_update_location' ),
	$delete_button = $( '#geo_mashup_delete_location' ),
	$changed_input = $( '#geo_mashup_changed' ),
	$location_name_input = $( '#geo_mashup_location_name' ),
	$location_input = $( '#geo_mashup_location' ),
	$location_id_input = $( '#geo_mashup_location_id' ),
	$geoname_input = $( '#geo_mashup_geoname' ),
	$address_input = $( '#geo_mashup_address' ),
	$postal_code_input = $( '#geo_mashup_postal_code' ),
	$country_code_input = $( '#geo_mashup_country_code' ),
	$admin_code_input = $( '#geo_mashup_admin_code' ),
	$admin_name_input = $( '#geo_mashup_admin_name' ),
	$sub_admin_code_input = $( '#geo_mashup_sub_admin_code' ),
	$sub_admin_name_input = $( '#geo_mashup_sub_admin_name' ),
	$null_fields_input = $( '#geo_mashup_null_fields' ),
	$kml_url_input = $( '#geo_mashup_kml_url' ),
	$locality_name_input = $( '#geo_mashup_locality_name' ),
	$display = $( '#geo_mashup_display' ),
	$info_display = $display.find( '.geo-mashup-info' ),
	$address_display = $display.find( '.geo-mashup-address' ),
	$coordinate_display = $display.find( '.geo-mashup-coordinates' ),
	$saved_name_ui = $( '#geo_mashup_saved_name_ui' ),
	$date_ui = $( '#geo_mashup_date_ui' ),
	$ajax_message = $( '#geo_mashup_ajax_message' ),

	/**
	 * An object prototype for managing data associated with a location.
	 */
	GeoAddress = function (init_data) {
		this.id = null;
		this.title = '';
		this.geoname = '';
		this.country_code = '';
		this.admin_code = '';
		this.admin_name = '';
		this.sub_admin_code = '';
		this.sub_admin_name = '';
		this.postal_code = '';
		this.locality_name = '';
		this.address = '';

		this.definedValue = function( val, alt ) {
			if ( typeof val === 'undefined' ) {
				return alt;
			} else {
				return val;
			}
		};

		this.subValue = function(obj, keys, default_value) {
			var key;
			if (typeof default_value !== 'string') {
				default_value = '';
			}
			if (typeof obj !== 'object') {
				return default_value;
			}
			if (typeof keys !== 'object') {
				return default_value;
			}
			if (typeof keys.length !== 'number') {
				return default_value;
			}
			key = keys.shift();
			if (typeof obj[key] === 'undefined') {
				return default_value;
			}
			if (keys.length === 0) {
				return obj[key];
			}
			return this.subValue(obj[key], keys, default_value);
		};

		this.set = function (data) {
			var i, types;
			if (typeof data === 'string') {
				if (isNaN(data)) {
					this.title = data;
				} else {
					this.id = data;
				}
			} else if (typeof data === 'number') {
				this.id = data;
			} else if (typeof data === 'object') {
				if (typeof data.location_id === 'string') {
					this.id = data.location_id;
					this.title = data.name;
					this.address = data.address;
				} else if (typeof data.name === 'string') { 
					// GeoNames location
					this.id = '';
					this.title = data.name;
					this.geoname = data.name; 
					this.country_code = this.definedValue( data.countryCode, '' );
					this.admin_code = this.definedValue( data.adminCode1, '' );
					this.admin_name = this.definedValue( data.adminName1, '' );
					this.sub_admin_code = this.definedValue( data.adminCode2, '' );
					this.sub_admin_name = this.definedValue( data.adminName2, '' );
					this.address = this.title + ', ' + this.admin_name + ', ' + this.country_code;
				} else if (typeof data.address === 'string') {
					// Google v2 location
					this.title = data.address;
					this.address = data.address;
					this.country_code = this.subValue(data, ['AddressDetails','Country','CountryNameCode']);
					this.admin_code = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','AdministrativeAreaName']);
					this.sub_admin_name = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','SubAdministrativeArea','SubAdministrativeAreaName']);
					if (this.sub_admin_name) {
						this.locality_name = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','SubAdministrativeArea','Locality','LocalityName']);
						this.postal_code = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','SubAdministrativeArea','Locality','PostalCode','PostalCodeNumber']);
					} else if (this.admin_code) {
						this.locality_name = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','Locality','LocalityName']);
						this.postal_code = this.subValue(data, ['AddressDetails','Country','AdministrativeArea','Locality','PostalCode','PostalCodeNumber']);
					}
					// Difficult to distinguish admin code from a name - but try
					if ( this.admin_code.length > 20 || this.admin_code.indexOf(' ') >= 0 ) {
						this.admin_name = this.admin_code;
						this.admin_code = '';
					}
				} else if (typeof data.types === 'object') {
					// Google v3 location
					this.title = data.formatted_address;
					this.address = data.formatted_address;
					for( i = 0; i < data.address_components.length; i += 1 ) {
						types = data.address_components[i].types.join('|');
						if ( types.indexOf( 'country' ) >= 0 ) {
							this.country_code = data.address_components[i].short_name;
						} else if ( types.indexOf( 'administrative_area_level_1' ) >= 0 ) {
							this.admin_code = data.address_components[i].short_name;
							this.admin_name = data.address_components[i].long_name;
						} else if ( types.indexOf( 'administrative_area_level_2' ) >= 0 ) {
							this.sub_admin_code = data.address_components[i].short_name;
							this.sub_admin_name = data.address_components[i].long_name;
						} else if ( types.indexOf( 'locality' ) >= 0 ) {
							this.locality_name = data.address_components[i].short_name;
						} else if ( types.indexOf( 'postal_code') >= 0 ) {
							this.postal_code = data.address_components[i].short_name;
						}
					}
					// Difficult to distinguish admin code from a name - but try
					if ( this.admin_code.length > 20 || this.admin_code.indexOf(' ') >= 0 ) {
						this.admin_name = this.admin_code;
						this.admin_code = '';
					}
				}
			}
		};

		if (init_data) {
			this.set(init_data);
		}
	},

	/**
	 * An object to manage the saved location select box.
	 */
	saved_selector = (function() {
		var $select = $( '#geo_mashup_select' ),
			select = $select.get( 0 ),
			selected_latlng = null,
			selected_location = null,

			/**
			 * Parse out a new selected location.
			 */
			updateSelection = function() {
				var option, saved_location;

				if  (select.selectedIndex > 0) {
					option = select.options[select.selectedIndex];
					saved_location = option.value.split( '|' );
					if ( saved_location.length > 2 ) {
						selected_location = { 
							location_id: saved_location[0],
							name: option.text,
							address: saved_location[3]
						};
						selected_latlng = new mxn.LatLonPoint( parseFloat( saved_location[1] ), parseFloat( saved_location[2] ) );
						$location_name_input.val( option.text );
					}
				}
			};

		$select.change( function() {
			selected_location = selected_latlng = null;
			updateSelection();
			if ( selected_latlng ) {
				addSelectedMarker( selected_latlng, selected_location );
			}
		} );

		// Return a public interface
		return {
			/**
			 * Get the selected ID, if any.
			 * 
			 * @return string The selected ID, or null if none selected.
			 */
			getSelectedID: function() {
				if ( selected_location ) {
					return selected_location.location_id;
				} else {
					return null;
				}
			 },

			/**
			 * Get the selected latitude and longitude, if any.
			 * 
			 * @return GLatLng The selected latitude and longitude, or null if none selected.
			 */
			getSelectedLatLng: function() {
				 return selected_latlng;
			 },

			/**
			 * Get the selected location, if any.
			 * 
			 * @return string The selected ID, or null if none selected.
			 */
			getSelectedLocation: function() {
				 return selected_location;
			 },

			/**
			 * Reset the state to no location selected.
			 */
			selectNone: function() {
				 select.selectedIndex = 0;
			 },

			/**
			 * Select an item by ID.
			 *
			 * @param string id The location id of the item to select.
			 * @return bool Whether an item was selected.
			 */
			selectByID: function( id ) {
				var i;

				if ( ! select.options || ! select.options.length ) {
					return false;
				}

				for( i = 1; i < select.options.length; i += 1 ) {
					if ( i !== select.selectedIndex && select.options[i].value.indexOf( id + '|') === 0 ) {
						select.selectedIndex = i;
						updateSelection();
						return true;
					}
				}
				this.selectNone();
				return false;
			},

			/**
			 * Select an item by name.
			 *
			 * @param string text The name of the item to select.
			 * @return bool Whether an item was selected.
			 */
			selectByText: function( text ) {
				var i;

				if ( ! select.options || ! select.options.length ) {
					return false;
				}
				if ( typeof text !== 'string' ) {
					text = text.toString();
				}

				for( i = 1; i < select.options.length; i += 1 ) {
					if ( i !== select.selectedIndex && select.options[i].text === text ) {
						select.selectedIndex = i;
						updateSelection();
						return true;
					}
				}
				this.selectNone();
				return false;
			}
		};
	}()),

	/**
	 * Initialize the interface when the document and Maps API
	 * are ready.
	 */
	init = function() {
		var latlng_array, latlng, $container;

		red_icon = {
			image: geo_mashup_url_path + '/images/mm_20_red.png',
			shadow: geo_mashup_url_path + '/images/mm_20_shadow.png',
			iconSize: [12, 20],
			shadowSize: [22, 20],
			iconAnchor: [6, 20],
			infoWindowAnchor: [5, 1]
		};

		green_icon = {
			image: geo_mashup_url_path + '/images/mm_20_green.png',
			shadow: geo_mashup_url_path + '/images/mm_20_shadow.png',
			iconSize: [12, 20],
			shadowSize: [22, 20],
			iconAnchor: [6, 20],
			infoWindowAnchor: [5, 1]
		};

		$container = $( '#geo_mashup_map' ).empty();

		// Create the loading spinner icon and show it
		$busy_icon = $( '<div id="gm-loading-icon" style="-moz-user-select: none; z-index: 100; position: absolute; left: ' +
			( $container.width() / 2 ) + 'px; top: ' + ( $container.height() / 2 ) + 'px;">' +
			'<img style="border: 0px none ; margin: 0px; padding: 0px; width: 16px; height: 16px; -moz-user-select: none;" src="' +
			geo_mashup_url_path + '/images/busy_icon.gif"/></a></div>' );
		$container.append( $busy_icon );

		geo_mashup_location_editor.map = map = new mxn.Mapstraction( $container.get( 0 ), geo_mashup_location_editor_settings.map_api );
		map.setOptions( {enableDragging: true, enableScrollWheelZoom: true} );
		map.addControls( {zoom: 'small', map_type: true} );
		geo_mashup_location_editor.showBusyIcon();
		map.load.addHandler( function() {geo_mashup_location_editor.hideBusyIcon();}, map );
		map.setCenterAndZoom( new mxn.LatLonPoint( 0, 0 ), 2 );
		if ( typeof map.enableGeoMashupExtras === 'function' ) {
			map.enableGeoMashupExtras();
		}
		geo_mashup_location_editor.mapCreated.fire();

		if ( $kml_url_input.val().length > 0 ) {
			geo_mashup_location_editor.loadKml( $kml_url_input.val() );
		}
		if ( $location_input.val().indexOf( ',' ) > 0 ) {
			// There are coordinates in the location input
			latlng_array = $location_input.val().split( ',' );
			if ( latlng_array.length > 1 ) {
				latlng = new mxn.LatLonPoint( parseFloat( latlng_array[0] ), parseFloat( latlng_array[1] ) );
				// Make sure the input coordinates match the parsed ones to start with
				$location_input.val( latlng.toString() );
				addSelectedMarker( latlng, {location_id: $location_id_input.val(), name: $location_name_input.val()} );
			}
		}

		map.click.addHandler( handleClick, map );

		geo_mashup_location_editor.loaded.fire();
	},

	/**
	 * Update form elements with current location information.
	 *
	 * @param GLatLng latlng The current location coordinates.
	 * @param GeoMashupLocation loc The related Geo Mashup location info.
	 */
	setInputs = function (latlng, loc) {
		var latlng_string = latlng.toString();
		if (($location_id_input.val() !== loc.id) || ($location_input.val() !== latlng_string)) {
			if ( saved_selector.getSelectedID()!== loc.id ) {
				saved_selector.selectByID( loc.id );
			}
			$location_id_input.val( loc.id || '' );
			$location_input.val( latlng_string );
			$geoname_input.val( loc.geoname );
			$address_input.val( loc.address );
			$postal_code_input.val( loc.postal_code );
			$country_code_input.val( loc.country_code );
			$admin_code_input.val( loc.admin_code );
			$admin_name_input.val( loc.admin_name );
			$sub_admin_code_input.val( loc.sub_admin_code );
			$sub_admin_name_input.val( loc.sub_admin_name );
			$locality_name_input.val( loc.locality_name );

			// Update the display
			$address_display.text( loc.address );
			$coordinate_display.text( latlng.toString() );
			$info_display.addClass( 'ui-state-highlight' );
			geo_mashup_location_editor.setHaveUnsavedChanges();
		}
	},

	/**
	 * Create a new marker and select it if there is no selected marker.
	 *
	 * @param LatLonPoint latlng The coordinates of the marker.
	 * @param GeoMashupAddress loc The related Geo Mashup location info.
	 */
	createMarker = function(latlng, loc) {
		var marker, filter,
			marker_opts = {
				label: loc.title,
				icon: red_icon.image,
				iconSize: green_icon.iconSize,
				iconShadow: green_icon.iconShadow,
				iconAnchor: green_icon.iconAnchor,
				iconShadowSize: green_icon.shadowSize
			};
		marker = new mxn.Marker( latlng );
		marker.addData( marker_opts );
		marker.geo_mashup_location = loc;
		marker.click.addHandler( function () {selectMarker( marker );} );
		if ( !selected_marker ) {
			marker_opts.icon = green_icon.image;
			marker_opts.draggable = true;
			marker.addData( marker_opts );
			selected_marker = marker;
			map.setCenter( latlng );
			setInputs( latlng, loc );

			filter = {marker: marker};
			geo_mashup_location_editor.markerSelected.fire( filter );
			marker = filter.marker;
		}
		filter = {marker: marker};
		geo_mashup_location_editor.markerCreated.fire( filter );

		map.addMarker( filter.marker );
		if ( filter.marker.dragend ) {
			// Drag handling must be set after the marker is added to a map
			filter.marker.dragend.addHandler( function( name, source, args) {
				// Dragging will create a new location under the hood
				loc.id = '';
				setInputs( args.location, loc );
				map.setCenter( args.location );
			});
		}
		return filter.marker;
	},

	/**
	 * Select a marker as the desired location.
	 *
	 * @param GMarker marker The marker to select.
	 */
	selectMarker = function(marker) {
		if (marker !== selected_marker) {
			createMarker( selected_marker.location, selected_marker.geo_mashup_location );
			map.removeMarker( selected_marker );
			selected_marker = null;

			// The new marker will be selected on creation
			createMarker( marker.location, marker.geo_mashup_location );
			map.removeMarker( marker );
		}
		map.setCenter( marker.location );
	},
	
	/**
	 * Add a marker and select it.
	 * 
	 * @param GLatLng latlng The coordinates for the marker.
	 * @param object selection Optional init data for a GeoAddress.
	 */
	addSelectedMarker = function(latlng, selection) {
		var marker = createMarker(latlng, new GeoAddress(selection));
		selectMarker( marker );
	},

	/**
	 * Handle a click on the map.
	 *
	 * @param LatLon latlon The location of a non-overlay click.
	 */
	handleClick = function( click, map, args ) {
		if ( args ) {
			searchForLocations( args.location.toString() );
		}
	},

	/**
	 * Display the results of a geocode request.
	 *
	 * @param object response The query response data.
	 */
	showAddresses = function( response, status ) {
		var i, latlng, marker, geonames_request_url;

		// Look for provider-specific geocoder responses
		// Only google v3 provides the status argument
		if ( response && typeof status !== 'undefined' && google.maps.GeocoderStatus.OK === status ) {

			// Google v3 results
			for ( i = 0; i < response.length && i<20 && response[i].geometry; i += 1 ) {
				latlng = new mxn.LatLonPoint( 
					response[i].geometry.location.lat(),
					response[i].geometry.location.lng()
				);
				marker = createMarker( latlng, new GeoAddress( response[i] ) );
			}

		} else if ( typeof status === 'undefined' && response && 200 === response.Status.code && response.Placemark && response.Placemark.length > 0 ) {

			// Google v2 results
			for ( i = 0; i < response.Placemark.length && i < 20 && response.Placemark[i]; i += 1) {
				latlng = new mxn.LatLonPoint(
					response.Placemark[i].Point.coordinates[1],
					response.Placemark[i].Point.coordinates[0] 
				);
				marker = createMarker(latlng, new GeoAddress(response.Placemark[i]));
			}

			
		} else {

			// Do a GeoNames search as backup
			geonames_request_url = 'http://api.geonames.org/search?type=json&maxRows=20&style=full&callback=?&username=' +
				geo_mashup_location_editor_settings.geonames_username + '&name=' + encodeURIComponent( $( '#geo_mashup_search' ).val() );
			jQuery.getJSON( geonames_request_url, showGeoNames );
			geo_mashup_location_editor.showBusyIcon();

		}
	},

	/**
	 * Display the results of a Geonames search request.
	 *
	 * @param object data The query response data.
	 */
	showGeoNames = function (data) {
		var i, result_latlng, marker;
		if (data) {
			for (i=0; i<data.geonames.length && i<100 && data.geonames[i]; i += 1) {
				result_latlng = new mxn.LatLonPoint( data.geonames[i].lat, data.geonames[i].lng );
				marker = createMarker( result_latlng, new GeoAddress(data.geonames[i]) );
			}
			geo_mashup_location_editor.hideBusyIcon();
		}
	},

	/**
	 * Use geocoding services to search for a location.
	 * The search map is cleared and loaded with results.
	 *
	 * @param string|LatLonPoint search Name, address, coordinates, etc.
	 */
	searchForLocations = function( search_text ) {
		var geocoder, request, latlng_array, latlng = null, mxn_map_bounds;

		// Clear current locations
		map.removeAllMarkers();
		selected_marker = null;
		$location_input.val( '' );
		geo_mashup_location_editor.setHaveUnsavedChanges();
		saved_selector.selectNone();
		if ( saved_selector.selectByText( search_text ) ) {

			addSelectedMarker( saved_selector.getSelectedLatLng(), saved_selector.getSelectedLocation() );

		} else {

			if (search_text.match(/^[\-\d\.\s]*,[\-\d\.\s]*$/)) {
				// For coorinates, add the selected marker at the exact location
				latlng_array = search_text.split(',');
				latlng = new mxn.LatLonPoint( parseFloat( latlng_array[0] ), parseFloat( latlng_array[1] ) );
				addSelectedMarker(latlng);
			}

			// Geocoding is the place we'll reference individual providers if loaded
			if ( typeof google === 'object' && typeof google.maps === 'object' ) {

				geo_mashup_location_editor.showBusyIcon();
				if ( typeof google.maps.Geocoder === 'function' ) {

					geocoder = new google.maps.Geocoder();
				
					mxn_map_bounds = map.getBounds();
					request = {
						bounds: new google.maps.LatLngBounds( 
							new google.maps.LatLng( mxn_map_bounds.getSouthWest().lat, mxn_map_bounds.getSouthWest().lng ),
							new google.maps.LatLng( mxn_map_bounds.getNorthEast().lat, mxn_map_bounds.getNorthEast().lng )
						)
					};
					if ( latlng ) {
						request.latLng = new google.maps.LatLng( latlng.lat, latlng.lng );
					} else {
						request.address = search_text;
					}
					geocoder.geocode( request, showAddresses );

				} else if ( typeof google.maps.ClientGeocoder === 'function' ) {

					geocoder = new google.maps.ClientGeocoder();
					geo_mashup_location_editor.showBusyIcon();
					mxn_map_bounds = map.getBounds();
					geocoder.setViewport( 
						new google.maps.LatLngBounds( 
							new google.maps.LatLng( mxn_map_bounds.getSouthWest().lat, mxn_map_bounds.getSouthWest().lng ),
							new google.maps.LatLng( mxn_map_bounds.getNorthEast().lat, mxn_map_bounds.getNorthEast().lng )
						)
					);
					geocoder.getLocations( search_text, showAddresses );

				}
			} else {

				// Do a GeoNames search 
				if ( latlng ) {
					geonames_request_url = 'http://api.geonames.org/findNearbyJSON?radius=50&style=full&callback=?&username=' +
							geo_mashup_location_editor_settings.geonames_username + '&lat=' + latlng.lat + '&lng=' + latlng.lng;
				} else {
					geonames_request_url = 'http://api.geonames.org/search?type=json&maxRows=20&style=full&callback=?&username=' +
							geo_mashup_location_editor_settings.geonames_username + '&q=' + encodeURIComponent( $( '#geo_mashup_search' ).val() );
				}
				jQuery.getJSON( geonames_request_url, showGeoNames );
				geo_mashup_location_editor.showBusyIcon();

			}
		} 

	},

	/**
	 * Handle keypresses in the search textbox.
	 *
	 * @param object e Event.
	 * @param string search_text Current contents of the textbox.
	 */
	searchKey = function( e, search_text ) {
		if ((e.keyCode && e.keyCode === 13) || (e.which && e.which === 13)) {
			// Enter key was hit - new search
			searchForLocations( search_text );
			return false;
		} else {
			return true;
		}
	},
	
	/**
	 * Note that changes have been successfully saved.
	 */
	clearHaveUnsavedChanges = function() {
		have_unsaved_changes = false;
		$changed_input.val( '' );
	};

	// End of private function variables

	// Add public members
	geo_mashup_location_editor.object_id = object_id;
	geo_mashup_location_editor.map = map;

	geo_mashup_location_editor.getSelectedLatLngs = function() {
		var latlngs = [];
		if ( selected_marker ) {
			latlngs.push( selected_marker.location );
		}
		return latlngs;
	};

	geo_mashup_location_editor.setHaveUnsavedChanges = function() {
		have_unsaved_changes = true;
		$changed_input.val( 'true' );
		if ( object_id > 0 ) {
			// If there's no object id yet, the AJAX method won't work
			$update_button.show();
		}
		$ajax_message.hide();
	};

	geo_mashup_location_editor.getHaveUnsavedChanges = function() {
		return have_unsaved_changes;
	};

	geo_mashup_location_editor.loadKml = function( kml_url ) {
		var addKmlMarker = function() {
			map.endPan.removeHandler( addKmlMarker, null );
			addSelectedMarker( map.getCenter(), {location_id: $location_id_input.val(), name: $location_name_input.val()} );
		};
		if ( $location_input.val().length === 0 ) {
			// Add a selected marker after the map has been recentered
			map.endPan.addHandler( addKmlMarker, null );
		}
		map.addOverlay( kml_url, true );
	};

	geo_mashup_location_editor.showBusyIcon = function() {
		$busy_icon.show();
	};

	geo_mashup_location_editor.hideBusyIcon = function() {
		$busy_icon.hide();
	};

	// Show js stuff (hidden in the stylesheet) 
	$( '.geo-mashup-js' ).removeClass( 'geo-mashup-js' );
	$ajax_message.hide();
	$( '#geo_mashup_no_js' ).val( '' );

	// Help interface
	$inline_help_link.click( function() {
		$( this ).find( 'span' ).toggleClass( 'ui-icon-triangle-1-s' )
			.toggleClass( 'ui-icon-triangle-1-n' );
		$('#geo-mashup-inline-help').slideToggle();
		return false;
	} );

	$('#geo-mashup-inline-help').hide().click( function() {
		return $inline_help_link.click();
	} );

	// Geo date interface
	if ( typeof $.datepicker === 'object' ) {

		// We've managed to load the datepicker script
		$('#geo_mashup_date').datepicker( { 
			dateFormat: 'M d, yy', 
			changeYear: true,
			onSelect: function( newDate, picker) {
				geo_mashup_location_editor.setHaveUnsavedChanges();
				$date_ui.addClass( 'ui-state-highlight' );
			} 
		} );

	} else {

		// Datepicker is not available
		$('#geo_mashup_date').change( function() {
			geo_mashup_location_editor.setHaveUnsavedChanges();
			$date_ui.addClass( 'ui-state-highlight' );
		} );
	}
	$('#geo_mashup_hour').change( function() {
		geo_mashup_location_editor.setHaveUnsavedChanges();
		$date_ui.addClass( 'ui-state-highlight' );
	} );
	$('#geo_mashup_minute').change( function() {
		geo_mashup_location_editor.setHaveUnsavedChanges();
		$date_ui.addClass( 'ui-state-highlight' );
	} );

	// Saved name interface
	$location_name_input.keypress( function() {
		if ( ! geo_mashup_location_editor.getHaveUnsavedChanges() ) { 
			geo_mashup_location_editor.setHaveUnsavedChanges();
			saved_selector.selectNone();
			$saved_name_ui.addClass( 'ui-state-highlight' );
		}
	} );

	// Search interface
	$('#geo_mashup_search')
		.focus( function() { 
			var $container = $( '#geo_mashup_map' );
			this.select(); 
			map.resizeTo( $container.width(), $container.height() );
		} )
		.keypress( function(e) {
			return searchKey( e, this.value );
		} );

	// Load the map
	init();

	// Ajax error messages
	$ajax_message.ajaxError( function( event, request, settings ) {
		// Try to show only errors from Geo Mashup requests
		if ( settings && settings.data && settings.data.toString().indexOf( 'geo_mashup' ) >= 0 ) {
			$ajax_message.text( request.statusText + ': ' + request.responseText ).show();
		}
	} );

	// Update buttons
	$( '#geo_mashup_submit' ).appendTo( '#geo_mashup_ajax_buttons' );
	$delete_button.click( function() {
		var post_data;

		// Make sure no coordinates are submitted
		$location_input.val( '' );
		post_data = $( '#geo_mashup_location_editor input' ).serialize() +
			'&geo_mashup_delete_location=true&action=geo_mashup_edit';
		$ajax_message.hide();
		$.post( ajax_url, post_data, function( data ) {
			$ajax_message.html( data.status.message ).fadeIn( 'slow' );
			if ( 200 === data.status.code ) {
				clearHaveUnsavedChanges();
				$display.find( '.ui-state-highlight' ).removeClass( 'ui-state-highlight' );
				map.removeAllMarkers();
				selected_marker = null;
				saved_selector.selectNone();
				$address_display.text( '' );
				$coordinate_display.text( '' );
				$location_name_input.val( '' );
				$delete_button.hide();
				$update_button.hide();
			}
		}, 'json' );

		return false;
	} );

	$update_button.click( function() {
		var post_data = $( '#geo_mashup_location_editor input' ).serialize() +
				'&geo_mashup_update_location=true&action=geo_mashup_edit',
			copy_geodata = ( geo_mashup_location_editor_settings.copy_geodata === 'true' ),
			$lat_custom_key = $( '#postcustom input[value="geo_latitude"]');

		if ( '' === $location_name_input.val() && $saved_name_ui.hasClass( 'ui-state-highlight' ) ) {
			// The saved name has been cleared
			post_data += '&geo_mashup_null_fields=saved_name';
		}
		$ajax_message.hide();

		$.post( ajax_url, post_data, function( data ) {
			$ajax_message.html( data.status.message ).fadeIn( 'slow' );
			if ( 200 === data.status.code ) {
				if ( copy_geodata && $lat_custom_key.length > 0 ) {
					// Custom fields were updated in the DB but not the current view
					// A reload (not an update) will load the new values
					window.location.reload();
				}
				clearHaveUnsavedChanges();
				$display.find( '.ui-state-highlight' ).removeClass( 'ui-state-highlight' );
				$update_button.hide();
			}
		}, 'json' );
		return false;
	} );

	$add_button.hide();
	if ( ! have_unsaved_changes ) {
		$update_button.hide();
	}

	if ( ! object_id ) {
		$delete_button.hide();
	}

} );


/**
* @deprecated GeoMashupLocationEditor The camel case name is against convention, since it is not
* a constructor, and is deprecated. Use geo_mashup_location_editor.
*/
GeoMashupLocationEditor = geo_mashup_location_editor;
