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


/*global GeoMashup */
/*global customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon */
/*global customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*global google */

var GeoMashup, customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon, 
	customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage;

/**
 * The base Geo Mashup object is built with code that is independent of mapping API.
 *
 * Used as an extendible namespace object.
 *
 * Violates the convention that capitalized objects are designed to be used with the 
 * 'new' keyword - early coder ignorance.
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
		'red':'#ff0000',
		'lime':'#00ff00',
		'blue':'#0000ff',
		'orange':'#ffa500',
		'yellow':'#ffff00',
		'aqua':'#00ffff',
		'green':'#008000',
		'silver':'#c0c0c0',
		'maroon':'#800000',
		'olive':'#808000',
		'navy':'#000080',
		'purple':'#800080',
		'gray':'#808080',
		'teal':'#008080',
		'fuchsia':'#ff00ff',
		'white':'#ffffff',
		'black':'#000000'},
	firstLoad : true,

	clone : function( obj ) {
		var ClonedObject = function(){};
		ClonedObject.prototype = obj;
		return new ClonedObject;
	},

	registerMap : function(container, opts) {
		if (document.all&&window.attachEvent) { // IE-Win
			window.attachEvent("onload", function () { GeoMashup.createMap(container, opts); });
		  window.attachEvent("onunload", google.maps.Unload);
		} else if (window.addEventListener) { // Others
			window.addEventListener("load", function () { GeoMashup.createMap(container, opts); }, false);
			window.addEventListener("unload", google.maps.Unload, false);
		}
	},

	forEach : function( obj, callback ) {
		var key;
		for( key in obj ) {
			if ( obj.hasOwnProperty( key ) && typeof obj[key] !== 'function' ) {
				callback.apply( this, [key, obj[key]] );
			}
		}
	},

	getCookie : function(NameOfCookie) { 
		var begin, end;

		if (document.cookie.length > 0) { 
			begin = document.cookie.indexOf(NameOfCookie+"=");
			if (begin !== -1) { 
				begin += NameOfCookie.length+1;
				end = document.cookie.indexOf(";", begin);
				if (end === -1) {
					end = document.cookie.length;
				}
				return decodeURIComponent(document.cookie.substring(begin, end)); 
			}
		}
		return null;
	},

	setCookie : function(NameOfCookie, value) { 
		document.cookie = NameOfCookie + "=" + encodeURIComponent(value); 
	},

	delCookie : function(NameOfCookie) { 
		if (this.getCookie(NameOfCookie)) {
			document.cookie = NameOfCookie + "=" +
				"; expires=Thu, 01-Jan-70 00:00:01 GMT";
		}
	},

	loadSettings : function (obj, settings_str) {
		var i, pairs, keyvalue;

		if (!settings_str) {
			return false;
		}
		if (settings_str.charAt(0) === '?') {
			settings_str = settings_str.substr(1);
		}
		pairs = settings_str.split('&');
		for (i=0; i<pairs.length; i += 1) {
			keyvalue = pairs[i].split('=');
			obj[keyvalue[0]] = decodeURIComponent(keyvalue[1]);
			if (obj[keyvalue[0]] === 'false') {
				obj[keyvalue[0]] = false;
			}
		}
		return true;
	},

	settingsToString : function (obj) {
		var str = [], sep = '', key;
		this.forEach( obj, function (key, value) {
			str.push(sep);
			str.push(key);
			str.push('=');
			str.push(encodeURIComponent( value ));
			sep = '&';
		});
		return str.join('');
	},

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

	getTagContent : function (container, tag, default_value) {
		var children;

		if (!default_value) {
			default_value = '';
		}
		children = container.getElementsByTagName(tag);
		if (children.length > 0 && children[0].firstChild) {
			return children[0].firstChild.nodeValue;
		} else {
			return default_value;
		}
	},

	parentScrollToGeoPost : function () {
		var geo_post = parent.document.getElementById('gm-post');
		if (geo_post) {
			parent.focus();
			parent.scrollTo(geo_post.offsetLeft, geo_post.offsetTop);
		}
		return false;
	},

	buildCategoryHierarchy : function(category_id) {
		var children, child_count, cat_id, child_id;
		if (category_id) {
			children = {};
			child_count = 0;
			for (child_id in this.opts.category_opts) {
				if (this.opts.category_opts[child_id].parent_id && 
					this.opts.category_opts[child_id].parent_id == category_id) {
						children[child_id] = this.buildCategoryHierarchy(child_id);
						child_count += 1;
				}
			}
			return (child_count > 0) ? children : null;
		} else {
			this.category_hierarchy = {};
			for (cat_id in this.opts.category_opts) {
				if (!this.opts.category_opts[cat_id].parent_id) {
					this.category_hierarchy[cat_id] = this.buildCategoryHierarchy(cat_id);
				}
			}
		}
	},

	isCategoryAncestor : function(ancestor_id, child_id) {
		if (this.opts.category_opts[child_id].parent_id) {
			if (this.opts.category_opts[child_id].parent_id == ancestor_id) {
				return true;
			} else {
				return this.isCategoryAncestor(ancestor_id, this.opts.category_opts[child_id].parent_id);
			}
		} else {
			return false;
		}
	},

	setCenterUpToMaxZoom : function( latlng, zoom, callback ) {
		var map_type = this.map.getCurrentMapType();
		if ( map_type == google.maps.SATELLITE_MAP || map_type == google.maps.HYBRID_MAP ) {
			map_type.getMaxZoomAtLatLng( latlng, function( response ) {
				if ( response && response['status'] === google.maps.GEO_SUCCESS ) {
					if ( response['zoom'] < zoom ) {
						zoom = response['zoom'];
					}
				}
				GeoMashup.map.setCenter( latlng, zoom );
				if ( typeof callback === 'function' ) {
					callback( zoom );
				}
			}, zoom );
		} else {
			// Current map type doesn't have getMaxZoomAtLatLng
			if ( map_type.getMaximumResolution() < zoom ) {
				zoom = map_type.getMaximumResolution();
			}	
			this.map.setCenter( latlng, zoom );
			if ( typeof callback === 'function' ) {
				callback( zoom );
			}
		}
	},

	hasLocatedChildren : function(category_id, hierarchy) {
		var child_id;

		if (this.categories[category_id]) {
			return true;
		}
		for (child_id in hierarchy) {
			if (this.hasLocatedChildren(child_id, hierarchy[child_id])) {
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
				} else if (hierarchy[category_id]) {
					child_search = this.searchCategoryHierarchy(search_id, hierarchy[category_id]);
					if (child_search) {
						return child_search;
					}
				}
			}
		}
		return null;
	},

	hideCategoryHierarchy : function(category_id) {
		var child_id;

		this.hideCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.hideCategoryHierarchy(child_id);
		});
  },

	showCategoryHierarchy : function(category_id) {
		var child_id;

		this.showCategory(category_id);
		this.forEach( this.tab_hierarchy[category_id], function (child_id) {
			this.showCategoryHierarchy(child_id);
		});
  },

	categoryTabSelect : function(select_category_id) {
		var i, tab_div, tab_list_element, tab_element, id_match, category_id, index_div;

		tab_div = parent.document.getElementById(window.name + '-tab-index');
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
			} else {
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

	categoryIndexId : function(category_id) {
		return 'gm-cat-index-' + category_id;
	},

	categoryTabIndexHtml : function(hierarchy) {
		var html_array = [], category_id;

		html_array.push('<div id="');
		html_array.push(window.name);
		html_array.push('-tab-index"><ul class="gm-tabs-nav">');
		for (category_id in hierarchy) {
			if (this.hasLocatedChildren(category_id, hierarchy[category_id])) {
				html_array = html_array.concat([
					'<li class="gm-tab-inactive gm-tab-inactive-',
					category_id,
					'"><a href="#',
					this.categoryIndexId(category_id),
					'" onclick="frames[\'',
					window.name,
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
			index_element = null,
			legend_html,
			category_id,
			label,
			list_tag,
			row_tag,
			term_tag,
			definition_tag,
			id;
		if (window.name) {
			legend_element = parent.document.getElementById(window.name + "-legend");
			index_element = parent.document.getElementById(window.name + "-tabbed-index");
		}
		if (!legend_element) {
			legend_element = parent.document.getElementById("gm-cat-legend");
		}
		if (!index_element) {
			index_element = parent.document.getElementById("gm-tabbed-index");
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
				if (window.name && this.opts.interactive_legend) {
					id = 'gm-cat-checkbox-' + category_id;
					label = [
						'<label for="',
						id,
						'"><input type="checkbox" name="category_checkbox" id="',
						id,
						'" onclick="if (this.checked) { frames[\'',
						window.name,
						'\'].GeoMashup.showCategory(\'',
						category_id,
						'\'); } else { frames[\'',
						window.name,
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
						this.categoryTabSelect(category_id);
						break;
					}
				}
			}
		}
	}, 

	getShowPostElement : function() {
	  if (!this.show_post_element && window.name) {
			this.show_post_element = parent.document.getElementById(window.name + '-post');
		}
	  if (!this.show_post_element) {
			this.show_post_element = parent.document.getElementById('gm-post');
		}
		return this.show_post_element;
	},

	parentizeLinksMarkup : function( markup ) {
		var container = document.createElement( 'div' );
		container.innerHTML = markup;
		this.parentizeLinks( container );
		return container.innerHTML;
	},

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

	showLoadingIcon : function() {
		if ( ! this.spinner_div.parentNode ) {
			this.container.appendChild( this.spinner_div );
		}
	},

	hideLoadingIcon : function() {
		if ( this.spinner_div.parentNode ) {
			this.spinner_div.parentNode.removeChild( this.spinner_div );
		}
	},

	getObjectsAtLocation : function( point ) {
		return this.locations[point].objects;
	},

	addGlowMarker : function( marker, point ) {
		// Provider override
	},	openInfoWindow : function( marker ) {
		// Provider override
	},

	addGlowMarker : function( marker, point ) {
		// Provider override
	},

	removeGlowMarker : function( marker, point ) {
		// Provider override
	},

	hideAttachments : function() {
		// Provider override
	},

	showMarkerAttachments : function( marker, point ) {
		// Provider override
	},

  loadFullPost : function( point ) {
		// jQuery or provider override
	},

	selectMarker : function( marker, point ) {
		this.selected_marker = marker;
		if ( this.opts.marker_select_info_window ) {
			this.openInfoWindow( marker );
		}
		if ( this.opts.marker_select_attachments ) {
			this.showMarkerAttachments( marker, point );
		}
		if ( this.opts.marker_select_highlight ) {
			this.addGlowMarker( marker, point );
		}
		if ( this.opts.marker_select_center ) {
			this.centerMarker( marker );
		}
		if ('full-post' !== this.opts.template && this.getShowPostElement()) {
			if ( this.locations[point].post_html ) {
				this.getShowPostElement().innerHTML = this.locations[point].post_html;
			} else {
				this.loadFullPost( point );
			}
		}
		this.doAction( 'selectedMarker', this.opts, this.selected_marker, this.map );
	},

	centerMarker : function ( marker ) {
		// provider override
	},

	deselectMarker : function() {
		var i, post_element = GeoMashup.getShowPostElement();
		if ( post_element ) {
			post_element.innerHTML = '';
		}
		if ( this.glow_marker ) {
			this.removeGlowMarker();
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

	clickObjectMarker : function(object_id, try_count) {
		// provider override
	},

	clickMarker : function( object_id, try_count ) {
		this.clickObjectMarker( object_id, try_count );
	},

	getCategoryName : function (category_id) {
		return this.category_opts[category_id].name;
	},

  hideMarker : function( marker ) {
		// Provider override
	},

  showMarker : function( marker ) {
		// Provider override
	},

  hideLine : function( line ) {
		// Provider override
	},

  showLine : function( line ) {
		// Provider override
	},

  newLatLng : function( lat, lng ) {
		var latlng;
		// Provider override
		return latlng;
	},

  extendLocationBounds : function( ) {
		// Provider override
	},

  addMarker : function( ) {
		// Provider override
	},

  makeMarkerMultiple : function( ) {
		// Provider override
	},

  autoZoom : function( ) {
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
			this.doAction( 'categoryIcon', this.opts, icon, category_id );
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

	recluster : function( ) {
		if (this.clusterer) { 
			this.clusterer.refresh();
		}
	},

	updateMarkerVisibilities : function( ) {
		this.forEach( this.locations, function( point, loc ) {
			GeoMashup.updateMarkerVisibility( loc.marker, point );
		} );
		this.updateVisibleList();
	},

	updateMarkerVisibility : function( marker, point ) {
		var i, j, loc, obj, check_cat_id, options = { visible: false };

		loc = this.locations[ point ];
		for ( i=0; i<loc.objects.length; i+=1 ) {
			obj = loc.objects[i];
			for ( j=0; j<obj.categories.length; j+=1 ) {
				check_cat_id = obj.categories[j];
				if ( this.categories[check_cat_id] && this.categories[check_cat_id].visible ) {
					options.visible = true;
				}
			}
			this.doAction( 'objectVisibilityOptions', this.opts, options, obj, this.map );
		}
		this.doAction( 'markerVisibilityOptions', this.opts, options, loc.marker, this.map );

		if ( options.visible ) {
			this.showMarker( marker );
		} else {
			this.hideMarker( marker );
		}
	},

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
				if ( !this.opts.open_object_id ) {
					this.opts.open_object_id = object_id;
				}
				if (!this.locations[point]) {
					// There are no other objects yet at this point, create a marker
					this.extendLocationBounds( point );
					this.locations[point] = { objects : [ response_data[i] ] };
					this.locations[point].loaded = false;
					marker = this.createMarker(point, response_data[i]);
					this.objects[object_id].marker = marker;
					this.locations[point].marker = marker;
					if ( this.clusterer ) {
						added_markers.push( marker );
					} 
					this.addMarker( marker );
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

		if ( this.clusterer && added_markers.length > 0 ) {
			this.clusterer.addMarkers( added_markers );
			this.clusterer.refresh();
		}

		// Add category lines
		if (add_category_info) {
			this.showCategoryInfo();
		}

		if (this.firstLoad) {
			this.firstLoad = false;
			if ( this.opts.auto_info_open && this.object_count > 0 ) {
				this.clickObjectMarker(this.opts.open_object_id);
			}
			if ( this.opts.zoom === 'auto' ) {
				this.setCenterUpToMaxZoom( 
					this.location_bounds.getCenter(), 
					this.map.getBoundsZoomLevel( this.location_bounds ),
					function() { GeoMashup.updateVisibleList(); } 
				);
			}
			this.updateVisibleList();
		}
	},

	requestObjects : function(use_bounds) {
		// provider override (maybe jQuery?)
	},

	hideMarkers : function() {
		var point;

		for (point in this.locations) {
			if ( this.locations[point].marker ) {
				this.hideMarker( this.locations[point].marker );
			}
		}
		this.recluster();
		this.updateVisibleList();
	},

	showMarkers : function() {
		var i, category_id, point;

		for (category_id in this.categories) {
			if (this.categories[category_id].visible) {
				for (i=0; i<this.categories[category_id].points.length; i++) {
					point = this.categories[category_id].points[i];
					this.showMarker( this.locations[point].marker );
				}
			}
		}
	},

	adjustZoom : function(old_level, new_level) {
		var category_id;

		for (category_id in this.categories) {
			if (old_level <= this.categories[category_id].max_line_zoom &&
			  new_level > this.categories[category_id].max_line_zoom) {
				this.hideLine( this.categories[category_id].line );
			} else if (this.categories[category_id].visible &&
				old_level > this.categories[category_id].max_line_zoom &&
			  new_level <= this.categories[category_id].max_line_zoom) {
				this.showLine( this.categories[category_id].line );
			}
		}

		if ( this.clusterer ) {
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
	},

	objectLinkHtml : function(object_id) {
		return ['<a href="#',
			window.name,
			'" onclick="frames[\'',
			window.name,
			'\'].GeoMashup.clickObjectMarker(',
			object_id,
			');">',
			this.objects[object_id].title,
			'</a>'].join('');
	},

	isMarkerVisible : function( marker ) {
		// Provider override
		return false;
	},

	updateVisibleList : function() {
		var list_element, header_element, list_html;

		if (window.name) {
			header_element = parent.document.getElementById(window.name + "-visible-list-header");
			list_element = parent.document.getElementById(window.name + "-visible-list");
		}
		if (header_element) {
			header_element.style.display = 'block';
		}
		if (list_element) {
			list_html = ['<ul class="gm-visible-list">'];
			this.forEach( this.objects, function (object_id, obj) {
				if ( this.isMarkerVisible( obj.marker ) ) {
					list_html.push('<li><img src="');
					list_html.push(obj.icon.image);
					list_html.push('" alt="');
					list_html.push(obj.title);
					list_html.push('" />');
					list_html.push(this.objectLinkHtml(object_id));
					list_html.push('</li>');
				}
			});
			list_html.push('</ul>');
			list_element.innerHTML = list_html.join('');
		}
	},

	adjustViewport : function() {
		this.updateVisibleList();
	},

	createMap : function(container, opts) {
		// Provider override
	}
};
