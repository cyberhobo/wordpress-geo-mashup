/*
Geo Mashup - Adds a Google Maps mashup of geocoded blog posts.
Copyright (c) 2005-2010 Dylan Kuhn

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

/**
 * @fileOverview 
 * The base Geo Mashup code that is independent of mapping API.
 */

/*global jQuery, GeoMashup: true */
// These globals are retained for backward custom javascript compatibility
/*global customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon */
/*global customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*jslint browser: true, white: true, sloppy: true */

var GeoMashup, customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon, 
customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage;

/** 
 * @name GeoMashupObject
 * @class This type represents an object Geo Mashup can place on the map.
 * It has no constructor, but is instantiated as an object literal.
 * Custom properties can be added, but some are present by default.
 * 
 * @property {String} object_name The type of object: post, user, comment, etc.
 * @property {String} object_id A unique identifier for the object
 * @property {String} title
 * @property {Number} lat Latitude
 * @property {Number} lng Longitude
 * @property {String} author_name The name of the object author
 * @property {Array} categories Deprecated, use terms. The object's category IDs if any.
 * @property {Object} terms The object's terms by taxonomy, e.g. { "tax1": [ "2", "5" ], "tax2": [ "1" ] }
 * @property {GeoMashupIcon} icon The default icon to use for the object
 */
 
/**
 * @name GeoMashupIcon
 * @class This type represents an icon that can be used for a map marker.
 * It has no constructor, but is instantiated as an object literal.
 * @property {String} image URL of the icon image
 * @property {String} iconShadow URL of the icon shadow image
 * @property {Array} iconSize Pixel width and height of the icon
 * @property {Array} shadowSize Pixel width and height of the icon shadow 
 * @property {Array} iconAnchor Pixel offset from top left: [ right, down ]
 * @property {Array} infoWindowAnchor Pixel offset from top left: [ right, down ]
 */

/** 
 * @name GeoMashupOptions
 * @class This type represents options used for a specific Geo Mashup map. 
 * It has no constructor, but is instantiated as an object literal.
 * Properties reflect the <a href="http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Map">map tag parameters</a>.
 */

/**
 * @name VisibilityFilter
 * @class This type represents objects used to filter object visibility.
 * It has no constructor, but is instantiated as an object literal.
 *
 * @name ContentFilter#visible
 * @property {Boolean} visible Whether the object is currently visible
 */

/**
 * @namespace Used more as a singleton than a namespace for data and methods for a single Geo Mashup map.
 *
 * <p>Violates the convention that capitalized objects are designed to be used with the 
 * 'new' keyword - an artifact of the age of the project. :o</p>
 * 
 * <p><strong>Note: Events are Actions</strong></p>
 *
 * <p>Actions available for use with GeoMashup.addAction() are documented as events.
 * See the <a href="http://code.google.com/p/wordpress-geo-mashup/wiki/Documentation#Custom_JavaScript">custom javascript documentation</a>
 * for an example.
 * </p>
 */
GeoMashup = {
	/**
	 * Access to the options for this map.
	 * Properties reflect the <a href="http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Map">map tag parameters</a>.
	 * @property {GeoMashupOptions}
	 */
	opts: {},
	actions : {},
	objects : {},
	object_count : 0,
	locations : {},
	open_attachments : [],
	errors : [],
	color_names : ['red','lime','blue','orange','yellow','aqua','green','silver','maroon','olive','navy','purple','gray','teal','fuchsia','white','black'],
	colors : {
		'red':'#ff071e',
		'lime':'#62d962',
		'blue':'#9595ff',
		'orange':'#fe8f00',
		'yellow':'#f2f261',
		'aqua':'#8eeff0',
		'green':'#459234',
		'silver':'#c2c2c2',
		'maroon':'#ae1a40',
		'olive':'#9f9b46',
		'navy':'#30389d',
		'purple':'#a54086',
		'gray':'#9b9b9b',
		'teal':'#13957b',
		'fuchsia':'#e550e5',
		'white':'#ffffff',
		'black':'#000000'
	},
	firstLoad : true,

	clone : function( obj ) {
		var ClonedObject = function(){};
		ClonedObject.prototype = obj;
		return new ClonedObject();
	},

	forEach : function( obj, callback ) {
		var key;
		for( key in obj ) {
			if ( obj.hasOwnProperty( key ) && typeof obj[key] !== 'function' ) {
				callback.apply( this, [key, obj[key]] );
			}
		}
	},

	locationCache : function( latlng, key ) {
		if ( !this.locations.hasOwnProperty( latlng ) ) {
			return false;
		}
		if ( !this.locations[latlng].cache ) {
			this.locations[latlng].cache = {};
		}
		if ( !this.locations[latlng].cache.hasOwnProperty( key ) ) {
			this.locations[latlng].cache[key] = {};
		}
		return this.locations[latlng].cache[key];
	},

	/**
	 * Add an action callback to extend Geo Mashup functionality.
	 * 
	 * Essentially an event interface. Might make sense to convert to 
	 * Mapstraction events in the future.
	 *
	 * @param {String} name The name of the action (event).
	 * @param {Function} callback The function to call when the action occurs
	 */
	addAction : function ( name, callback ) {
		if ( typeof callback !== 'function' ) {
			return false;
		}
		if ( typeof this.actions[name] !== 'object' ) {
			this.actions[name] = [callback];
		} else {
			this.actions[name].push( callback );
		}
		return true;
	},

	/**
	 * Fire all callbacks for an action.
    * 
	 * Essentially an event interface. Might make sense to convert to 
	 * Mapstraction events in the future.
	 *
	 * @param {String} name The name of the action (event).
	 */
	doAction : function ( name ) {
		var args, i;

		if ( typeof this.actions[name] !== 'object' ) {
			return false;
		}
		args = Array.prototype.slice.apply( arguments, [1] );
		for ( i = 0; i < this.actions[name].length; i += 1 ) {
			if ( typeof this.actions[name][i] === 'function' ) {
				this.actions[name][i].apply( null, args );
			}
		}
		return true;
	},

	parentScrollToGeoPost : function () {
		var geo_post;
		if ( this.have_parent_access ) {
			geo_post = parent.document.getElementById('gm-post');
			if (geo_post) {
				parent.focus();
				parent.scrollTo(geo_post.offsetLeft, geo_post.offsetTop);
			}
		}
		return false;
	},

	/**
	 * Get the DOM element where the full post content should be displayed, if any.
	 * @returns {DOMElement} The element, or undefined if none.
	 */
	getShowPostElement : function() {
		if ( this.have_parent_access && !this.show_post_element && this.opts.name) {
			this.show_post_element = parent.document.getElementById(this.opts.name + '-post');
		}
		if ( this.have_parent_access && !this.show_post_element) {
			this.show_post_element = parent.document.getElementById('gm-post');
		}
		return this.show_post_element;
	},

	/**
	 * Change the target of links in HTML markup to target the parent frame.
	 * @param {String} markup
	 * @returns {String} Modified markup
	 */
	parentizeLinksMarkup : function( markup ) {
		var container = document.createElement( 'div' );
		container.innerHTML = markup;
		this.parentizeLinks( container );
		return container.innerHTML;
	},

	/**
	 * Change the target of links in a DOM element to target the parent frame.
	 * @param {DOMElement} node The element to change
	 */
	parentizeLinks : function( node ) {
		var i, links = node.getElementsByTagName('a');
		if ( parent ) {
			for (i=0; i<links.length; i += 1) {
				if ( links[i].target.length === 0 || links[i].target === '_self' ) {
					links[i].target = "_parent";
				}
			}
		}
	},

	/**
	 * Display a spinner icon for the map.
	 */
	showLoadingIcon : function() {
		if ( ! this.spinner_div.parentNode ) {
			this.container.appendChild( this.spinner_div );
		}
	},

	/**
	 * Hide the spinner icon for the map.
	 */
	hideLoadingIcon : function() {
		if ( this.spinner_div.parentNode ) {
			this.spinner_div.parentNode.removeChild( this.spinner_div );
		}
	},

	/**
	 * Get the objects at a specified location.
	 * @param {LatLonPoint} point The query location
	 * @returns {Array} The mapped objects at the query location
	 */
	getObjectsAtLocation : function( point ) {
		return this.locations[point].objects;
	},

	/**
	 * Get the objects at the location of a specified marker.
	 * @param {Marker} marker 
	 * @returns {Array} The mapped objects at the marker location
	 */
	getMarkerObjects : function( marker ) {
		return this.getObjectsAtLocation( this.getMarkerLatLng( marker ) );
	},

	/**
	 * Get the location coordinates for a marker.
	 * @param {Marker} marker 
	 * @returns {LatLonPoint} The marker coordinates
	 */
	getMarkerLatLng : function( marker ) {
	// Provider override
	},

	/**
	 * Obscure an existing marker with the highlighted "glow" marker.
	 * @param {Marker} marker The existing marker
	 */
	addGlowMarker : function( marker ) {
	// Provider override
	},

	/**
	 * Open the info bubble for a marker.
	 * @param {Marker} marker
	 */
	openInfoWindow : function( marker ) {
	// Provider override
	},

	/**
	 * Close the info bubble for a marker.
	 * @param {Marker} marker
	 */
	closeInfoWindow : function( marker ) {
	// provider override
	},

	/**
	 * Remove the highlighted "glow" marker from the map if it exists.
	 */
	removeGlowMarker : function() {
	// Provider override
	},

	/**
	 * Hide any visible attachment layers on the map.
	 */
	hideAttachments : function() {
	// Provider override
	},

	/**
	 * Show any attachment layers associated with the objects represented
	 * by a marker, loading the layer if necessary.
	 * @param {Marker} marker
	 */
	showMarkerAttachments : function( marker ) {
	// Provider override
	},

	/** 
	 * Load full content for the objects/posts at a location into the 
	 * full post display element.
	 * @param {LatLonPoint} point
	 */
	loadFullPost : function( point ) {
	// jQuery or provider override
	},

	/**
	 * Select a marker.
	 * @param {Marker} marker
	 */
	selectMarker : function( marker ) {
		var point = this.getMarkerLatLng( marker );

		this.selected_marker = marker;
		if ( this.opts.marker_select_info_window ) {
			this.openInfoWindow( marker );
		}
		if ( this.opts.marker_select_attachments ) {
			this.showMarkerAttachments( marker );
		}
		if ( this.opts.marker_select_highlight ) {
			this.addGlowMarker( marker );
		}
		if ( this.opts.marker_select_center ) {
			this.centerMarker( marker );
		}
		if ('full-post' !== this.opts.template && this.getShowPostElement()) {
			this.loadFullPost( point );
		}
		/**
		 * A marker was selected.
		 * @name GeoMashup#selectedMarker
		 * @event
		 * @param {GeoMashupOptions} properties Geo Mashup configuration data
		 * @param {Marker} marker The selected marker
		 * @param {Map} map The map containing the marker
		 */
		this.doAction( 'selectedMarker', this.opts, this.selected_marker, this.map );
	},

	/**
	 * Center and optionally zoom to a marker.
	 * @param {Marker} marker 
	 * @param {Number} zoom Optional zoom level
	 */
	centerMarker : function ( marker, zoom ) {
	// provider override
	},

	/**
	 * De-select the currently selected marker if there is one.
	 */
	deselectMarker : function() {
		var i, post_element = GeoMashup.getShowPostElement();
		if ( post_element ) {
			post_element.innerHTML = '';
		}
		if ( this.glow_marker ) {
			this.removeGlowMarker();
		}
		if ( this.selected_marker ) {
			this.closeInfoWindow( this.selected_marker );
		}
		this.hideAttachments();
		this.selected_marker = null;
	},

	addObjectIcon : function( obj ) {
	// provider override
	},

	createMarker : function( point, obj ) {
		var marker;
		// provider override
		return marker;
	},

	checkDependencies : function () {
	// provider override
	},

	/**
	 * Simulate a user click on the marker that represents a specified object.
	 * @param {String} object_id The ID of the object.
	 * @param {Number} try_count Optional number of times to try (in case the object 
	 *   is still being loaded).
	 */
	clickObjectMarker : function(object_id, try_count) {
	// provider override
	},

	/**
	 * Backward compatibility for clickObjectMarker().
	 * @deprecated
	 */
	clickMarker : function( object_id, try_count ) {
		this.clickObjectMarker( object_id, try_count );
	},

	/**
	 * Get the name of a category, if loaded.
	 * @param {String} category_id
	 * @return {String} The ID or null if not available.
	 */
	getCategoryName : function (category_id) {
		if ( !this.opts.term_properties.hasOwnProperty( 'category' ) ) {
			return null;
		}
		return this.opts.term_properties.category[category_id];
	},

	/**
	 * Hide a marker.
	 * @param {Marker} marker
	 */
	hideMarker : function( marker ) {
	// Provider override
	},

	/**
	 * Show a marker.
	 * @param {Marker} marker
	 */
	showMarker : function( marker ) {
	// Provider override
	},

	/**
	 * Hide a line.
	 * @param {Polyline} line
	 */
	hideLine : function( line ) {
	// Provider override
	},

	/**
	 * Show a line.
	 * @param {Polyline} line
	 */
	showLine : function( line ) {
	// Provider override
	},

	/**
	 * Create a new geo coordinate object.
	 * @param {Number} lat Latitude
	 * @param {Number} lng Longitude
	 * @returns {LatLonPoint} Coordinates
	 */
	newLatLng : function( lat, lng ) {
		var latlng;
		// Provider override
		return latlng;
	},

	extendLocationBounds : function( ) {
	// Provider override
	},

	addMarkers : function( ) {
	// Provider override
	},

	makeMarkerMultiple : function( marker ) {
	// Provider override
	},

	setMarkerImage : function( marker, image_url ) {
	// Provider override
	},

	/**
	 * Zoom the map to loaded content.
	 */
	autoZoom : function( ) {
	// Provider override
	},

	/**
	 * If clustering is active, refresh clusters.
	 */
	recluster : function( ) {
	// Provider override
	},

	/**
	 * Show or hide markers according to current visibility criteria.
	 */
	updateMarkerVisibilities : function( ) {
		this.forEach( this.locations, function( point, loc ) {
			GeoMashup.updateMarkerVisibility( loc.marker, point );
		} );
		this.updateVisibleList();
	},

	updateMarkerVisibility : function( marker ) {
		if ( this.isMarkerOn( marker ) ) {
			this.showMarker( marker );
		} else {
			this.hideMarker( marker );
		}
	},

	isMarkerOn : function( marker ) {
		var i, objects, visible_object_indices = [], filter = {
			visible: false
		};

		objects = this.getMarkerObjects( marker );
		for ( i = 0; i < objects.length; i += 1 ) {
			if ( this.isObjectOn( objects[i] ) ) {
				filter.visible = true;
				visible_object_indices.push( i );
			}
		}

		// Adjust marker icon based on current visible contents
		if ( filter.visible ) {

			if ( objects.length > 1 ) {

				if ( visible_object_indices.length === 1 ) {
					GeoMashup.setMarkerImage( marker, objects[visible_object_indices[0]].icon.image );
				} else {
					GeoMashup.makeMarkerMultiple( marker );
				}

			} else if ( objects[0].combined_term_count > 1 ) {

				if ( objects[0].visible_term_count === 1 ) {

					jQuery.each( objects[0].visible_terms, function( taxonomy, term_ids ) {

						if ( term_ids.length === 1 ) {
							GeoMashup.setMarkerImage( marker, GeoMashup.term_manager.getTermData( taxonomy, term_ids[0], 'icon' ).image );
						}

					} );

				} else {
					GeoMashup.setMarkerImage( marker, objects[0].icon.image );
				}
			}
		}
		/**
		 * Visibility is being tested for a marker.
		 * @name GeoMashup#markerVisibilityOptions
		 * @event
		 * @param {GeoMashupOptions} properties Geo Mashup configuration data
		 * @param {VisibilityFilter} filter Test and set filter.visible
		 * @param {Marker} marker The marker being tested
		 * @param {Map} map The map for context
		 */
		this.doAction( 'markerVisibilityOptions', this.opts, filter, marker, this.map );

		return filter.visible;
	},

	isObjectOn : function( obj ) {
		var filter = {
			visible: false
		};

		obj.visible_terms = {};
		obj.visible_term_count = 0;

		if ( !GeoMashup.term_manager || 0 === obj.combined_term_count ) {

			// Objects without terms are visible by default
			filter.visible = true;

		} else {

			// Check term visibility
			jQuery.each( obj.terms, function( taxonomy, term_ids ) {
				
				obj.visible_terms[taxonomy] = [];

				jQuery.each( term_ids, function( i, term_id ) {
					
					if ( GeoMashup.term_manager.getTermData( taxonomy, term_id, 'visible' ) ) {
						obj.visible_terms[taxonomy].push( term_id );
						obj.visible_term_count += 1;
						filter.visible = true;
					}

				});

			});

		}

		/**
		 * Visibility is being tested for an object.
		 * @name GeoMashup#objectVisibilityOptions
		 * @event
		 * @param {GeoMashupOptions} properties Geo Mashup configuration data
		 * @param {VisibilityFilter} filter Test and set filter.visible
		 * @param {Object} object The object being tested
		 * @param {Map} map The map for context
		 */
		this.doAction( 'objectVisibilityOptions', this.opts, filter, obj, this.map );

		return filter.visible;
	},

	/**
	 * Extract the IDs of objects that are "on" (not filtered by a control).
	 * @since 1.4.2
	 * @param {Array} objects The objects to check
	 * @returns {Array} The IDs of the "on" objects
	 */
	getOnObjectIDs : function( objects ) {
		var i, object_ids = [];
		for( i = 0; i < objects.length; i += 1 ) {
			if ( this.isObjectOn( objects[i] ) ) {
				object_ids.push( objects[i].object_id );
			}
		}
		return object_ids;
	},

	/**
	 * Add objects to the map.
	 * @param {Object} response_data Data returned by a geo query.
	 * @param {Boolean} add_term_info Whether to build and show term
	 *   data for these objects, for legend or other term controls.
	 */
	addObjects : function(response_data, add_term_info) {
		var i, k, object_id, point, taxonomy, term_ids, term_id, marker, plus_image,
			added_markers = [];

		if ( add_term_info && this.term_manager ) {
			this.term_manager.reset();
		}

		for (i = 0; i < response_data.length; i+=1) {
			// Make a marker for each new object location
			object_id = response_data[i].object_id;
			point = this.newLatLng(
				parseFloat(response_data[i].lat),
				parseFloat(response_data[i].lng)
			);

			// Back compat for categories API
			response_data[i].categories = [];

			response_data[i].combined_term_count = 0;
			if ( this.term_manager ) {
				// Add terms
				for( taxonomy in response_data[i].terms ) {
					if ( response_data[i].terms.hasOwnProperty( taxonomy ) && typeof taxonomy !== 'function' ) {

						term_ids = response_data[i].terms[taxonomy];
						for (k = 0; k < term_ids.length; k+=1) {
							GeoMashup.term_manager.extendTerm( point, taxonomy, term_ids[k], response_data[i] );
						}

						response_data[i].combined_term_count += term_ids.length;

						if ( 'category' === taxonomy ) {
							response_data[i].categories = term_ids;
						}
					}
				}
			}
			
			if (this.opts.max_posts && this.object_count >= this.opts.max_posts) {
				break;
			}

			if (!this.objects[object_id]) {
				// This object has not yet been loaded
				this.objects[object_id] = response_data[i];
				this.object_count += 1;
				if (!this.locations[point]) {
					// There are no other objects yet at this point, create a marker
					this.extendLocationBounds( point );
					this.locations[point] = {
						objects : [ response_data[i] ], 
						loaded_content: {}
					};
					marker = this.createMarker(point, response_data[i]);
					this.objects[object_id].marker = marker;
					this.locations[point].marker = marker;
					added_markers.push( marker );
				} else {
					// There is already a marker at this point, add the new object to it
					this.locations[point].objects.push( response_data[i] );
					marker = this.locations[point].marker;
					this.makeMarkerMultiple( marker );
					this.objects[object_id].marker = marker;
					this.addObjectIcon( this.objects[object_id] );
				}
			}
		} // end for each marker

		// Openlayers at least only gets clicks on the top layer, so add markers last
		this.addMarkers( added_markers );

		if ( this.term_manager ) {
			this.term_manager.populateTermElements();
		}

		if (this.firstLoad) {
			this.firstLoad = false;
			if ( this.opts.auto_info_open && this.object_count > 0 ) {
				if ( !this.opts.open_object_id ) {
					if ( this.opts.context_object_id && this.objects[ this.opts.context_object_id ] ) {
						this.opts.open_object_id = this.opts.context_object_id;
					} else {
						this.opts.open_object_id = response_data[0].object_id;
					}
				}
				this.clickObjectMarker(this.opts.open_object_id);
			}
			if ( this.opts.zoom === 'auto' ) {
				this.autoZoom();
			} else {
				if ( this.opts.context_object_id && this.objects[ this.opts.context_object_id ] ) {
					this.centerMarker( this.objects[ this.opts.context_object_id ].marker, parseInt( this.opts.zoom, 10 ) );
				}
				this.updateVisibleList();
			}
		}
	},

	requestObjects : function(use_bounds) {
	// provider override (maybe jQuery?)
	},

	/**
	 * Hide all markers.
	 */
	hideMarkers : function() {
		var point;

		for (point in this.locations) {
			if ( this.locations.hasOwnProperty( point ) && this.locations[point].marker ) {
				this.hideMarker( this.locations[point].marker );
			}
		}
		this.recluster();
		this.updateVisibleList();
	},

	/**
	 * Show all unfiltered markers.
	 */
	showMarkers : function() {

		jQuery.each( this.locations, function( point, location ) {
			if ( GeoMashup.isMarkerOn( location.marker ) ) {
				GeoMashup.showMarker( location.marker );
			}
		});
		this.recluster();
		this.updateVisibleList();

	},

	adjustZoom : function() {
		var old_level, new_level;
		new_level = this.map.getZoom();
		if ( typeof this.last_zoom_level === 'undefined' ) {
			this.last_zoom_level = new_level;
		}
		old_level = this.last_zoom_level;

		if ( this.term_manager ) {
			this.term_manager.updateLineZoom( old_level, new_level );
		}

		if ( this.clusterer && 'google' === this.opts.map_api ) {
			if ( old_level <= this.opts.cluster_max_zoom && 
				new_level > this.opts.cluster_max_zoom ) {
				this.clusterer.clusteringEnabled = false;
				this.clusterer.refresh( true );
			} else if ( old_level > this.opts.cluster_max_zoom &&
				new_level <= this.opts.cluster_max_zoom ) {
				this.clusterer.clusteringEnabled = true;
				this.clusterer.refresh( true );
			}
		}
		this.last_zoom_level = new_level;
	},

	objectLinkHtml : function(object_id) {
		return ['<a href="#',
		this.opts.name,
		'" onclick="frames[\'',
		this.opts.name,
		'\'].GeoMashup.clickObjectMarker(',
		object_id,
		');">',
		this.objects[object_id].title,
		'</a>'].join('');
	},

	/**
	 * Whether a marker is currently visible on the map.
	 * @param {Marker} marker
	 * @return {Boolean} False if the marker is hidden or outside the current viewport.
	 */
	isMarkerVisible : function( marker ) {
		// Provider override
		return false;
	},

	/**
	 * Recompile the list of objects currently visible on the map.
	 */
	updateVisibleList : function() {
		var list_element, header_element, list_html, list_count = 0;

		if (this.have_parent_access && this.opts.name) {
			header_element = parent.document.getElementById(this.opts.name + "-visible-list-header");
			list_element = parent.document.getElementById(this.opts.name + "-visible-list");
		}
		if (header_element) {
			header_element.style.display = 'block';
		}
		if (list_element) {
			list_html = ['<ul class="gm-visible-list">'];
			this.forEach( this.objects, function (object_id, obj) {
				if ( this.isObjectOn( obj ) && this.isMarkerVisible( obj.marker ) ) {
					list_html.push('<li><img src="');
					list_html.push(obj.icon.image);
					list_html.push('" alt="');
					list_html.push(obj.title);
					list_html.push('" />');
					list_html.push(this.objectLinkHtml(object_id));
					list_html.push('</li>');
					list_count += 1;
				}
			});
			list_html.push('</ul>');
			list_element.innerHTML = list_html.join('');
			/** 
			 * The visible posts list was updated.
			 * @name GeoMashup#updatedVisibleList
			 * @event
			 * @param {GeoMashupOptions} properties Geo Mashup configuration data
			 * @param {Number} list_count The number of items in the list
			 */
			this.doAction( 'updatedVisibleList', this.opts, list_count );
		}
	},

	adjustViewport : function() {
		this.updateVisibleList();
	},

	createMap : function(container, opts) {
	// Provider override
	}
};
