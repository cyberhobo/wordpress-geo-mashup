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

/*global GeoMashup: true */
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
 * @property {Array} categories The IDs of the categories this object belongs to
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
	actions : {},
	objects : {},
	object_count : 0,
	locations : {},
	categories : {}, // only categories on the map here
	category_count : 0,
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

	buildCategoryHierarchy : function(category_id) {
		var children, child_count, cat_id, child_id;
		if (category_id) {
			category_id = category_id.toString();
			children = {};
			child_count = 0;
			for (child_id in this.opts.category_opts) {
				if (this.opts.category_opts.hasOwnProperty( child_id ) &&
					this.opts.category_opts[child_id].parent_id &&
					this.opts.category_opts[child_id].parent_id === category_id) {
						children[child_id] = this.buildCategoryHierarchy(child_id);
						child_count += 1;
				}
			}
			return (child_count > 0) ? children : null;
		} else {
			this.category_hierarchy = {};
			for (cat_id in this.opts.category_opts) {
				if ( this.opts.category_opts.hasOwnProperty( cat_id ) &&
					!this.opts.category_opts[cat_id].parent_id) {
					this.category_hierarchy[cat_id] = this.buildCategoryHierarchy(cat_id);
				}
			}
		}
	},

	/**
	 * Determine whether a category ID is an ancestor of another.
	 * 
	 * Works on the loadedMap action and after, when the category hierarchy has been
	 * determined.
	 * 
	 * @param {String} ancestor_id The category ID of the potential ancestor
	 * @param {String} child_id The category ID of the potential child
	 */
	isCategoryAncestor : function(ancestor_id, child_id) {
		ancestor_id = ancestor_id.toString();
		child_id = child_id.toString();
		if (this.opts.category_opts[child_id].parent_id) {
			if (this.opts.category_opts[child_id].parent_id === ancestor_id) {
				return true;
			} else {
				return this.isCategoryAncestor(ancestor_id, this.opts.category_opts[child_id].parent_id);
			}
		} else {
			return false;
		}
	},

	hasLocatedChildren : function(category_id, hierarchy) {
		var child_id;

		if (this.categories[category_id]) {
			return true;
		}
		for (child_id in hierarchy) {
			if ( hierarchy.hasOwnProperty( child_id ) && this.hasLocatedChildren(child_id, hierarchy[child_id]) ) {
				return true;
			}
		}
		return false;
	},

	searchCategoryHierarchy : function(search_id, hierarchy) {
		var child_search, category_id;

		if (!hierarchy) {
			hierarchy = this.category_hierarchy;
		}
		// Use a regular loop, so it can return a value for this function
		for( category_id in hierarchy ) {
			if ( hierarchy.hasOwnProperty( category_id ) && typeof hierarchy[category_id] !== 'function' ) {
				if (category_id === search_id) {
					return hierarchy[category_id];
				}else if (hierarchy[category_id]) {
					child_search = this.searchCategoryHierarchy(search_id, hierarchy[category_id]);
					if (child_search) {
						return child_search;
					}
				}
			}
		}
		return null;
	},

	/**
	 * Hide a category and all its child categories.
	 * @param {String} category_id The ID of the category to hide
	 */
	hideCategoryHierarchy : function(category_id) {
		var child_id;

		this.hideCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.hideCategoryHierarchy(child_id);
		});
  },

	/**
	 * Show a category and all its child categories.
	 * @param {String} category_id The ID of the category to show
	 */
	showCategoryHierarchy : function(category_id) {
		var child_id;

		this.showCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.showCategoryHierarchy(child_id);
		});
  },

	/**
	 * Select a tab of the tabbed category index control.
	 * @param {String} select_category_id The ID of the category tab to select
	 */
	categoryTabSelect : function(select_category_id) {
		var i, tab_div, tab_list_element, tab_element, id_match, category_id, index_div;

		if ( !this.have_parent_access ) {
			return false;
		}
		tab_div = parent.document.getElementById(this.opts.name + '-tab-index');
		if (!tab_div) {
			return false;
		}
		tab_list_element = tab_div.childNodes[0];
		for (i=0; i<tab_list_element.childNodes.length; i += 1) {
			tab_element = tab_list_element.childNodes[i];
			id_match = tab_element.childNodes[0].href.match(/\d+$/);
			category_id = id_match && id_match[0];
			if (category_id === select_category_id) {
				tab_element.className = 'gm-tab-active gm-tab-active-' + select_category_id;
			}else {
				tab_element.className = 'gm-tab-inactive gm-tab-inactive-' + category_id;
			}
		}
		this.forEach( this.tab_hierarchy, function (category_id) {
			index_div = parent.document.getElementById(this.categoryIndexId(category_id));
			if (index_div) {
				if (category_id === select_category_id) {
					index_div.className = 'gm-tabs-panel';
				} else {
					index_div.className = 'gm-tabs-panel gm-hidden';
					if (!this.opts.show_inactive_tab_markers) {
						this.hideCategoryHierarchy(category_id);
					}
				}
			}
		});
		if (!this.opts.show_inactive_tab_markers) {
			// Done last so none of the markers get re-hidden
			this.showCategoryHierarchy(select_category_id);
		}
	},

	/**
	 * Get the DOM ID of the element containing a category index in the 
	 * tabbed category index control.
	 * @param {String} category_id The category ID
	 */
	categoryIndexId : function(category_id) {
		return 'gm-cat-index-' + category_id;
	},

	categoryTabIndexHtml : function(hierarchy) {
		var html_array = [], category_id;

		html_array.push('<div id="');
		html_array.push(this.opts.name);
		html_array.push('-tab-index"><ul class="gm-tabs-nav">');
		for (category_id in hierarchy) {
			if ( hierarchy.hasOwnProperty( category_id ) && this.hasLocatedChildren(category_id, hierarchy[category_id]) ) {
				html_array = html_array.concat([
					'<li class="gm-tab-inactive gm-tab-inactive-',
					category_id,
					'"><a href="#',
					this.categoryIndexId(category_id),
					'" onclick="frames[\'',
					this.opts.name,
					'\'].GeoMashup.categoryTabSelect(\'',
					category_id,
					'\'); return false;">']);
				if (this.categories[category_id]) {
					html_array.push('<img src="');
					html_array.push(this.categories[category_id].icon.image);
					html_array.push('" />');
				}
				html_array.push('<span>');
				html_array.push(this.opts.category_opts[category_id].name);
				html_array.push('</span></a></li>');
			}
		} 
		html_array.push('</ul></div>');
		this.forEach( hierarchy, function (category_id) {
			html_array.push(this.categoryIndexHtml(category_id, hierarchy[category_id]));
		});
		return html_array.join('');
	},

	categoryIndexHtml : function(category_id, children, top_level) {
		var i, a_name, b_name, html_array = [], group_count, ul_open_tag, child_id;
		if ( typeof top_level === 'undefined' ) {
			top_level = true;
		}
		html_array.push('<div id="');
		html_array.push(this.categoryIndexId(category_id));
		html_array.push('" class="gm-tabs-panel');
		if ( top_level ) {
			html_array.push(' gm-hidden');
		}
		html_array.push('"><ul class="gm-index-posts">');
		if (this.categories[category_id]) {
			this.categories[category_id].posts.sort(function (a, b) {
				a_name = GeoMashup.objects[a].title;
				b_name = GeoMashup.objects[b].title;
				if (a_name === b_name) {
					return 0;
				} else {
					return a_name < b_name ? -1 : 1;
				}
			});
			for (i=0; i<this.categories[category_id].posts.length; i += 1) {
				html_array.push('<li>');
				html_array.push(this.objectLinkHtml(this.categories[category_id].posts[i]));
				html_array.push('</li>');
			}
		}
		html_array.push('</ul>');
		group_count = 0;
		ul_open_tag = '<ul class="gm-sub-cat-index">';
		html_array.push(ul_open_tag);
		this.forEach( children, function (child_id) {
			html_array.push('<li>');
			if (this.categories[child_id]) {
				html_array.push('<img src="');
				html_array.push(this.categories[child_id].icon.image);
				html_array.push('" />');
				html_array.push('<span class="gm-sub-cat-title">');
				html_array.push(this.opts.category_opts[child_id].name);
				html_array.push('</span>');
			}
			html_array.push(this.categoryIndexHtml(child_id, children[child_id], false));
			html_array.push('</li>');
			group_count += 1;
			if (this.opts.tab_index_group_size && group_count%this.opts.tab_index_group_size === 0) {
				html_array.push('</ul>');
				html_array.push(ul_open_tag);
			}
		});
		html_array.push('</ul></div>');
		return html_array.join('');
	},

	createCategoryLine : function( category ) {
		// Provider override
	},

	showCategoryInfo : function() {
		var legend_element = null,
			legend_html,
			category_id,
			label,
			list_tag,
			row_tag,
			term_tag,
			definition_tag,
			id;
		if (this.opts.name && this.have_parent_access ) {
			legend_element = parent.document.getElementById(this.opts.name + "-legend");
		}
		if (!legend_element && this.have_parent_access ) {
			legend_element = parent.document.getElementById("gm-cat-legend");
		}
		if (this.opts.legend_format && 'dl' === this.opts.legend_format) {
			list_tag = 'dl';
			row_tag = '';
			term_tag = 'dt';
			definition_tag = 'dd';
		} else {
			list_tag = 'table';
			row_tag = 'tr';
			term_tag = 'td';
			definition_tag = 'td';
		}

		legend_html = ['<', list_tag, ' class="gm-legend">'];
		this.forEach(this.categories, function (category_id, category) {
			this.createCategoryLine( category );
			if (legend_element) {
				// Default is interactive
				if (typeof this.opts.interactive_legend === 'undefined') {
					this.opts.interactive_legend = true;
				}
				if (this.opts.name && this.opts.interactive_legend) {
					id = 'gm-cat-checkbox-' + category_id;
					label = [
						'<label for="',
						id,
						'"><input type="checkbox" name="category_checkbox" id="',
						id,
						'" onclick="if (this.checked) { frames[\'',
						this.opts.name,
						'\'].GeoMashup.showCategory(\'',
						category_id,
						'\'); } else { frames[\'',
						this.opts.name,
						'\'].GeoMashup.hideCategory(\'',
						category_id,
						'\'); }" checked="true" />',
						this.opts.category_opts[category_id].name,
						'</label>'].join('');
				} else {
					label = this.opts.category_opts[category_id].name;
				}
				if (row_tag) {
					legend_html.push('<' + row_tag + '>');
				}
				legend_html = legend_html.concat([
					'<',
					term_tag,
					'><img src="',
					category.icon.image,
					'" alt="',
					category_id,
					'"></',
					term_tag,
					'><',
					definition_tag,
					'>',
					label,
					'</',
					definition_tag,
					'>']);
				if (row_tag) {
					legend_html.push('</' + row_tag + '>');
				}
			}
		}); // end forEach this.categories
		legend_html.push('</' + list_tag + '>');
		if (legend_element) {
			legend_element.innerHTML = legend_html.join('');
		}
	},

	initializeTabbedIndex : function() {
		var category_id,
			index_element;
		if ( !this.have_parent_access ) {
			return false;
		}
		index_element = parent.document.getElementById(this.opts.name + "-tabbed-index");
		if (!index_element) {
			index_element = parent.document.getElementById("gm-tabbed-index");
		}
		if (index_element) {
			if (this.opts.start_tab_category_id) {
				this.tab_hierarchy = this.searchCategoryHierarchy(this.opts.start_tab_category_id);
			} else {
				this.tab_hierarchy = this.category_hierarchy;
			}
			index_element.innerHTML = this.categoryTabIndexHtml(this.tab_hierarchy);
			if (!this.opts.disable_tab_auto_select) {
				// Select the first tab
				for (category_id in this.tab_hierarchy) {
					if (this.tab_hierarchy.hasOwnProperty(category_id) && typeof category_id !== 'function') {
						if (this.hasLocatedChildren(category_id, this.tab_hierarchy[category_id])) {
							this.categoryTabSelect(category_id);
							break;
						}
					}
				}
			}
		}
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
	 * Get the name of a category.
	 * @param {String} category_id
	 */
	getCategoryName : function (category_id) {
		return this.category_opts[category_id].name;
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

	extendCategory : function(point, category_id, post_id) {
		var icon, color, color_name, max_line_zoom;

		if (!this.categories[category_id]) {
			if (this.opts.category_opts[category_id].color_name) {
				color_name = this.opts.category_opts[category_id].color_name;
			} else {
				color_name = this.color_names[this.category_count%this.color_names.length];
			}
			color = this.colors[color_name];
			// Custom callbacks
			if (!icon && typeof customGeoMashupCategoryIcon === 'function') {
				icon = customGeoMashupCategoryIcon(this.opts, [category_id]);
			}
			if (!icon && typeof customGeoMashupColorIcon === 'function') {
				icon = customGeoMashupColorIcon(this.opts, color_name);
			}
			if (!icon) {
				icon = this.colorIcon( color_name );
			}
			/**
			 * A category icon is being assigned.
			 * @name GeoMashup#categoryIcon
			 * @event
			 * @param {GeoMashupOptions} properties Geo Mashup configuration data
			 * @param {GeoMashupIcon} icon
			 * @param {String} category_id
			 */
			this.doAction( 'categoryIcon', this.opts, icon, category_id );
			/**
			 * A category icon is being assigned by color.
			 * @name GeoMashup#colorIcon
			 * @event
			 * @param {GeoMashupOptions} properties Geo Mashup configuration data
			 * @param {GeoMashupIcon} icon
			 * @param {String} color_name
			 */
			this.doAction( 'colorIcon', this.opts, icon, color_name );

			max_line_zoom = -1;
			if (this.opts.category_opts[category_id].max_line_zoom) {
				max_line_zoom = this.opts.category_opts[category_id].max_line_zoom;
			}
			this.categories[category_id] = {
				icon : icon,
				points : [point],
				posts : [post_id],
				color : color,
				visible : true,
				max_line_zoom : max_line_zoom
			};
			this.category_count += 1;
		} else {
			this.categories[category_id].points.push(point);
			this.categories[category_id].posts.push(post_id);
		}
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

	updateMarkerVisibility : function( marker, point ) {
		if ( this.isMarkerOn( marker ) ) {
			this.showMarker( marker );
		} else {
			this.hideMarker( marker );
		}
	},

	isMarkerOn : function( marker ) {
		var i, objects, visible_object_indices = [], filter = {visible: false};

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
					this.setMarkerImage( marker, objects[visible_object_indices[0]].icon.image );
				} else {
					this.makeMarkerMultiple( marker );
				}

			} else if ( objects[0].categories.length > 1 ) {

				if ( objects[0].visible_categories.length === 1 ) {
					this.setMarkerImage( marker, this.categories[objects[0].visible_categories[0]].icon.image );
				} else {
					this.setMarkerImage( marker, objects[0].icon.image );
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
		var i, check_cat_id, filter = {visible: false};

		obj.visible_categories = [];
		if ( 0 === obj.categories.length ) {

			// Objects without categories are visible by default
			filter.visible = true;

		} else {

			// Check category visibility
			for ( i = 0; i < obj.categories.length; i += 1 ) {
				check_cat_id = obj.categories[i];
				if ( this.categories[check_cat_id] && this.categories[check_cat_id].visible ) {
					obj.visible_categories.push( check_cat_id );
					filter.visible = true;
				}
			}

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
	 * Hide markers and line for a category.
	 * @param {String} category_id
	 */
	hideCategory : function(category_id) {
		var i, loc;

		if (!this.categories[category_id]) {
			return false;
		}
		if ( this.map.closeInfoWindow ) {
			this.map.closeInfoWindow();
		}
		if (this.categories[category_id].line) {
			this.hideLine( this.categories[category_id].line );
		}
		// A somewhat involved check for other visible categories at this location
		this.categories[category_id].visible = false;
		for (i=0; i<this.categories[category_id].points.length; i+=1) {
			loc = this.locations[ this.categories[category_id].points[i] ];
			this.updateMarkerVisibility( loc.marker, this.categories[category_id].points[i] );
		}
		this.recluster();
		this.updateVisibleList();
	},

	/**
	 * Show markers for a category. Also show line if consistent with configuration.
	 * @param {String} category_id
	 */
	showCategory : function(category_id) {
		var i, point;

		if (!this.categories[category_id]) {
			return false;
		}
		if (this.categories[category_id].line && this.map.getZoom() <= this.categories[category_id].max_line_zoom) {
			this.showLine( this.categories[category_id].line );
		}
		this.categories[category_id].visible = true;
		for (i=0; i<this.categories[category_id].points.length; i+=1) {
			point = this.categories[category_id].points[i];
			this.updateMarkerVisibility( this.locations[point].marker, point );
		}
		this.recluster();
		this.updateVisibleList();
	},

	/**
	 * Add objects to the map.
	 * @param {Object} response_data Data returned by a geo query.
	 * @param {Boolean} add_category_info Whether to build and show category
	 *   data for these objects, for legend or other category controls.
	 */
	addObjects : function(response_data, add_category_info) {
		var i, j, object_id, point, category_id, marker, plus_image,
			added_markers = [];

		if (add_category_info) {
			this.forEach( this.categories, function (category_id, category) {
				category.points.length = 0;
				if (category.line) {
					this.hideLine( category.line );
				}
			});
		}
		for (i = 0; i < response_data.length; i+=1) {
			// Make a marker for each new object location
			object_id = response_data[i].object_id;
			point = this.newLatLng(
				parseFloat(response_data[i].lat),
				parseFloat(response_data[i].lng)
			);
			// Update categories
			for (j = 0; j < response_data[i].categories.length; j+=1) {
				category_id = response_data[i].categories[j];
				this.extendCategory(point, category_id, object_id);
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
					this.locations[point] = {objects : [ response_data[i] ], loaded_content: {}};
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

		// Add category lines
		if (add_category_info) {
			this.showCategoryInfo();
		}

		// Openlayers at least only gets clicks on the top layer, so add markers last
		this.addMarkers( added_markers );

		// Tabbed index may hide markers
		this.initializeTabbedIndex();

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
	 * Show all markers.
	 */
	showMarkers : function() {
		var i, category_id, point;

		for (category_id in this.categories) {
			if ( this.categories.hasOwnProperty( category_id ) && this.categories[category_id].visible ) {
				for ( i = 0; i < this.categories[category_id].points.length; i += 1 ) {
					point = this.categories[category_id].points[i];
					this.showMarker( this.locations[point].marker );
				}
			}
		}
	},

	adjustZoom : function() {
		var category_id, old_level, new_level;
		new_level = this.map.getZoom();
		if ( typeof this.last_zoom_level === 'undefined' ) {
			this.last_zoom_level = new_level;
		}
		old_level = this.last_zoom_level;

		for (category_id in this.categories) {
			if ( this.categories.hasOwnProperty( category_id ) ) {
				if (old_level <= this.categories[category_id].max_line_zoom &&
				  new_level > this.categories[category_id].max_line_zoom) {
					this.hideLine( this.categories[category_id].line );
				} else if (this.categories[category_id].visible &&
					old_level > this.categories[category_id].max_line_zoom &&
				  new_level <= this.categories[category_id].max_line_zoom) {
					this.showLine( this.categories[category_id].line );
				}
			}
		}

		if ( this.clusterer && 'markercluster' === this.opts.cluster_lib ) {
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
	 * @param {Boolean} False if the marker is hidden or outside the current viewport.
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
