/*
Geo Mashup - Adds a Google Maps mashup of geocoded blog posts.
Copyright (c) 2005-2007 Dylan Kuhn

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
/*glboal customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*global google */

var GeoMashup, customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon, 
	customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage;

google.load( 'maps', '2' );

GeoMashup = {
	posts : {},
	post_count : 0,
	locations : {},
	categories : {}, // only categories on the map here
	category_count : 0,
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
					this.opts.category_opts[child_id].parent_id === category_id) {
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
		this.forEach( hierarchy, function (category_id) {
			if (category_id === search_id) {
				return hierarchy[category_id];
			} else if (hierarchy[category_id]) {
				child_search = this.searchCategoryHierarchy(search_id, hierarchy[category_id]);
				if (child_search) {
					return child_search;
				}
			}
		});
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

	categoryIndexHtml : function(category_id, children) {
		var i, a_name, b_name, html_array = [], group_count, ul_open_tag, child_id;
		html_array.push('<div id="');
		html_array.push(this.categoryIndexId(category_id));
		html_array.push('" class="gm-tabs-panel gm-hidden"><ul class="gm-index-posts">');
		if (this.categories[category_id]) {
			this.categories[category_id].posts.sort(function (a, b) {
				a_name = GeoMashup.posts[a].title;
				b_name = GeoMashup.posts[b].title;
				if (a_name === b_name) {
					return 0;
				} else {
					return a_name < b_name ? -1 : 1;
				}
			});
			for (i=0; i<this.categories[category_id].posts.length; i += 1) {
				html_array.push('<li>');
				html_array.push(this.postLinkHtml(this.categories[category_id].posts[i]));
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
			}
			html_array.push('<span class="gm-sub-cat-title">');
			html_array.push(this.opts.category_opts[child_id].name);
			html_array.push(this.categoryIndexHtml(child_id, children[child_id]));
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
			category.line = new google.maps.Polyline(category.points, category.color);
			google.maps.Event.addListener( category.line, 'click', function () {
				GeoMashup.map.zoomIn();
			} );
			this.map.addOverlay(category.line);
			if (this.map.getZoom() > category.max_line_zoom) {
				category.line.hide();
			}
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

	createMarker : function(point,post) {
		var marker, marker_opts = {title:post.title};

		if (typeof customGeoMashupCategoryIcon === 'function') {
			marker_opts.icon = customGeoMashupCategoryIcon(this.opts, post.categories);
		} 
		if (!marker_opts.icon) {
			if (post.categories.length > 1) {
				marker_opts.icon = new google.maps.Icon(this.multiple_category_icon);
			} else {
				marker_opts.icon = new google.maps.Icon(this.categories[post.categories[0]].icon);
			}
		}
		marker = new google.maps.Marker(point,marker_opts);

		// Show this markers index in the info window when it is clicked
		google.maps.Event.addListener(marker, "click", function() {
			var post_ids, i, url, info_window_request, post_element, post_request;

			GeoMashup.map.closeInfoWindow();
			if (GeoMashup.locations[point].loaded) {
				marker.openInfoWindow(GeoMashup.locations[point].info_node);
				if (GeoMashup.opts.show_post && GeoMashup.getShowPostElement()) {
					GeoMashup.getShowPostElement().innerHTML = GeoMashup.locations[point].post_html;
				}
			} else {
				marker.openInfoWindowHtml('<div align="center"><img src="' +
					GeoMashup.opts.url_path + 
					'/images/busy_icon.gif" alt="Loading..." /></div>');
				post_ids = '';
				for(i=0; i<GeoMashup.locations[point].posts.length; i += 1) {
					if (i>0) {
						post_ids += ',';
					}
					post_ids += GeoMashup.locations[point].posts[i];
				}
				url = GeoMashup.opts.url_path + '/geo-query.php?post_ids=' + post_ids;
				info_window_request = new google.maps.XmlHttp.create();
				info_window_request.open('GET', url, true);
				info_window_request.onreadystatechange = function() {
					var node, links, i;

					if (info_window_request.readyState === 4 && info_window_request.status === 200) {
						node = document.createElement('div');
						node.innerHTML = info_window_request.responseText;
						links = node.getElementsByTagName('a');
						if (parent) {
							for (i=0; i<links.length; i += 1) {
								links[i].target = "_parent";
							}
						}
						GeoMashup.locations[point].info_node = node;
						GeoMashup.locations[point].loaded = true;
						marker.openInfoWindow(node);
						if (GeoMashup.opts.show_post && 'full-post' !== GeoMashup.opts.template && GeoMashup.getShowPostElement()) {
							url += '&template=full-post';
							post_request = new google.maps.XmlHttp.create();
							post_request.open('GET', url, true);
							post_request.onreadystatechange = function() {
								if (post_request.readyState === 4 && post_request.status === 200) {
									GeoMashup.getShowPostElement().innerHTML = post_request.responseText;
									GeoMashup.locations[point].post_html = post_request.responseText;
								} // end readystate === 4
							}; // end onreadystatechange function
							post_request.send(null);
						}
					} // end readystate === 4
				}; // end onreadystatechange function
				info_window_request.send(null);
			} // end post not loaded yet 
		}); // end marker click

		google.maps.Event.addListener(marker, 'infowindowclose', function() {
			var post_element = GeoMashup.getShowPostElement();
			if (post_element) {
				post_element.innerHTML = '';
			}
		});

		return marker;
	},

	checkDependencies : function () {
		if (typeof google.maps.Map === 'undefined' || !google.maps.BrowserIsCompatible()) {
			this.container.innerHTML = '<p class="errormessage">' +
				'Sorry, the Google Maps script failed to load. Have you entered your ' +
				'<a href="http://maps.google.com/apis/maps/signup.html">API key<\/a> ' +
				'in the Geo Mashup Options?';
			throw "The Google Maps javascript didn't load.";
		}
	},

	clickMarker : function(post_id, try_count) {
		if (typeof try_count === 'undefined') {
			try_count = 1;
		}
		if (this.posts[post_id] && try_count < 4) {
			if (GeoMashup.posts[post_id].marker.isHidden()) {
				try_count += 1;
				setTimeout(function () { GeoMashup.clickMarker(post_id, try_count); }, 300);
			} else {
				google.maps.Event.trigger(GeoMashup.posts[post_id].marker,"click"); 
			}
		}
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
			if (!icon && typeof customGeoMashupCategoryIcon === 'function') {
				icon = customGeoMashupCategoryIcon(this.opts, [category_id]);
			}
			if (!icon && typeof customGeoMashupColorIcon === 'function') {
				icon = customGeoMashupColorIcon(this.opts, color_name);
			}
			if (!icon) {
				icon = new google.maps.Icon(this.base_color_icon);
				icon.image = this.opts.url_path + '/images/mm_20_' + color_name + '.png';
			}
			max_line_zoom = 0;
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

	hideCategory : function(category_id) {
		var i, point;

		if (!this.categories[category_id]) {
			return false;
		}
		this.map.closeInfoWindow();
		if (this.categories[category_id].line) {
			this.categories[category_id].line.hide();
		}
		for (i=0; i<this.categories[category_id].points.length; i+=1) {
			point = this.categories[category_id].points[i];
			this.locations[point].marker.hide();
		}
		this.categories[category_id].visible = false;
		if (this.clusterer) { 
			this.clusterer.refresh();
		}
		this.updateVisibleList();
	},

	showCategory : function(category_id) {
		var i, point;

		if (!this.categories[category_id]) {
			return false;
		}
		if (this.categories[category_id].line && this.map.getZoom() <= this.categories[category_id].max_line_zoom) {
			this.categories[category_id].line.show();
		}
		for (i=0; i<this.categories[category_id].points.length; i+=1) {
			point = this.categories[category_id].points[i];
			this.locations[point].marker.show();
		}
		this.categories[category_id].visible = true;
		if (this.clusterer) { 
			this.clusterer.refresh();
		}
		this.updateVisibleList();
	},

	addPosts : function(response_data, add_category_info) {
		var i, j, post_id, point, category_id, marker, plus_image,
			added_markers = [];

		if (add_category_info) {
			this.forEach( this.categories, function (category_id, category) {
				category.points.length = 0;
				if (category.line) {
					category.line.hide();
				}
			});
		}
		for (i = 0; i < response_data.length; i+=1) {
			// Make a marker for each new post location
			post_id = response_data[i].post_id;
			point = new google.maps.LatLng(
				parseFloat(response_data[i].lat),
				parseFloat(response_data[i].lng));
			// Update categories
			for (j = 0; j < response_data[i].categories.length; j+=1) {
				category_id = response_data[i].categories[j];
				this.extendCategory(point, category_id, post_id);
			}
			if (this.opts.max_posts && this.post_count >= this.opts.max_posts) {
				break;
			}
			if (!this.posts[post_id]) {
				// This post has not yet been loaded
				this.post_count += 1;
				if (!this.locations[point]) {
					// There are no other posts yet at this point, create a marker
					this.locations[point] = { posts : [ post_id ] };
					this.locations[point].loaded = false;
					this.posts[post_id] = {};
					marker = this.createMarker(point, response_data[i]);
					this.posts[post_id].marker = marker;
					this.locations[point].marker = marker;
					if ( this.clusterer ) {
						added_markers.push( marker );
					} 
					this.map.addOverlay(marker);
				} else {
					// There is already a marker at this point, add the new post to it
					this.locations[point].posts.push(post_id);
					marker = this.locations[point].marker;
					if (typeof customGeoMashupMultiplePostImage === 'function') {
						plus_image = customGeoMashupMultiplePostImage(this.opts, marker.getIcon().image);
					}
					if (!plus_image) {
						plus_image = this.opts.url_path + '/images/mm_20_plus.png';
					}
					// marker.setImage doesn't survive clustering
					marker.getIcon().image = plus_image;
					this.posts[post_id] = { marker : this.locations[point].marker };
				}
				this.posts[post_id].title = response_data[i].title;
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
			if (this.opts.auto_info_open && this.opts.open_post_id) {
				this.clickMarker(this.opts.open_post_id);
			}
		}
	},

	requestPosts : function(use_bounds) {
		var request, url, map_bounds, map_span;
		if (this.opts.max_posts && this.post_count >= this.opts.max_posts) {
			return;
		}
		request = google.maps.XmlHttp.create();
		url = this.opts.url_path + '/geo-query.php?i=1';
		if (use_bounds) {
			map_bounds = this.map.getBounds();
			map_span = map_bounds.toSpan();
			url += '&minlat=' + (map_bounds.getSouthWest().lat() - map_span.lat()) + 
				'&minlon=' + (map_bounds.getSouthWest().lng() - map_span.lng()) + 
				'&maxlat=' + (map_bounds.getSouthWest().lat() + 3*map_span.lat()) + 
				'&maxlon=' + (map_bounds.getSouthWest().lng() + 3*map_span.lat());
		}
		if (this.opts.map_cat) {
			url += '&cat=' + GeoMashup.opts.map_cat;
		}
		if (this.opts.max_posts) {
			url += '&limit=' + GeoMashup.opts.max_posts;
		}
		request.open("GET", url, true);
		request.onreadystatechange = function() {
			var posts;

			if (request.readyState === 4 && request.status === 200) {
				posts = window['eval']( '(' + request.responseText + ')' );
				GeoMashup.addPosts(posts,!use_bounds);
			} // end readystate === 4
		}; // end onreadystatechange function
		request.send(null);
	},

	hideMarkers : function() {
		var point;

		for (point in this.locations) {
			if ( this.locations[point].marker ) {
				this.locations[point].marker.hide();
			}
		}
		if (this.clusterer) { 
			this.clusterer.refresh();
		}
		this.updateVisibleList();
	},

	showMarkers : function() {
		var i, category_id, point;

		for (category_id in this.categories) {
			if (this.categories[category_id].visible) {
				for (i=0; i<this.categories[category_id].points.length; i++) {
					point = this.categories[category_id].points[i];
					this.locations[point].marker.show();
				}
			}
		}
	},

	adjustZoom : function(old_level, new_level) {
		var category_id;

		for (category_id in this.categories) {
			if (old_level <= this.categories[category_id].max_line_zoom &&
			  new_level > this.categories[category_id].max_line_zoom) {
				this.categories[category_id].line.hide();
			} else if (this.categories[category_id].visible &&
				old_level > this.categories[category_id].max_line_zoom &&
			  new_level <= this.categories[category_id].max_line_zoom) {
				this.categories[category_id].line.show();
			}
		}
	},

	postLinkHtml : function(post_id) {
		return ['<a href="#',
			window.name,
			'" onclick="frames[\'',
			window.name,
			'\'].GeoMashup.clickMarker(',
			post_id,
			');">',
			this.posts[post_id].title,
			'</a>'].join('');
	},

	updateVisibleList : function() {
		var list_element, header_element, list_html, map_bounds, marker;

		if (window.name) {
			header_element = parent.document.getElementById(window.name + "-visible-list-header");
			list_element = parent.document.getElementById(window.name + "-visible-list");
		}
		if (header_element) {
			header_element.style.display = 'block';
		}
		if (list_element) {
			list_html = ['<ul class="gm-visible-list">'];
			this.forEach( this.posts, function (post_id, post) {
				map_bounds = this.map.getBounds();
				marker = post.marker;
				if (!marker.isHidden() && map_bounds.containsLatLng(marker.getLatLng())) {
					list_html.push('<li><img src="');
					list_html.push(marker.getIcon().image);
					list_html.push('" alt="');
					list_html.push(post.title);
					list_html.push('" />');
					list_html.push(this.postLinkHtml(post_id));
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
		var type_num, center_latlng, map_types, request, url, posts, point, marker_opts, ov, credit_div;

		this.container = container;
		this.checkDependencies();
		this.base_color_icon = new google.maps.Icon();
		this.base_color_icon.image = opts.url_path + '/images/mm_20_black.png';
		this.base_color_icon.shadow = opts.url_path + '/images/mm_20_shadow.png';
		this.base_color_icon.iconSize = new google.maps.Size(12, 20);
		this.base_color_icon.shadowSize = new google.maps.Size(22, 20);
		this.base_color_icon.iconAnchor = new google.maps.Point(6, 20);
		this.base_color_icon.infoWindowAnchor = new google.maps.Point(5, 1);
		this.multiple_category_icon = new google.maps.Icon(this.base_color_icon);
		this.multiple_category_icon.image = opts.url_path + '/images/mm_20_mixed.png';
		this.map = new google.maps.Map2(this.container,{backgroundColor : '#' + opts.background_color});
		this.map.setCenter(new google.maps.LatLng(0,0), 1);
		this.map.addMapType(google.maps.PHYSICAL_MAP);

		if (window.location.search === this.getCookie('back_search'))
		{
			this.loadSettings(opts, this.getCookie('back_settings'));
		}
		this.opts = opts;

		if (opts.cluster_max_zoom) {
			this.clusterer = new ClusterMarker( this.map, { 
				'fitMapMaxZoom' : opts.cluster_max_zoom,
				'clusterMarkerTitle' : '%count'	
			} );
		}

		if (typeof opts.zoom === 'string') {
			opts.zoom = parseInt(opts.zoom, 10);
		} else if (typeof opts.zoom === 'undefined') {
			opts.zoom = 5;
		}

		map_types = {
			'G_NORMAL_MAP' : google.maps.NORMAL_MAP,
			'G_SATELLITE_MAP' : google.maps.SATELLITE_MAP,
			'G_HYBRID_MAP' : google.maps.HYBRID_MAP,
			'G_PHYSICAL_MAP' : google.maps.PHYSICAL_MAP,
			'G_SATELLITE_3D_MAP' : google.maps.SATELLITE_3D_MAP
		};
		if (typeof opts.map_type === 'string') {
			if ( map_types[opts.map_type] ) {
				opts.map_type = map_types[opts.map_type] ;
			} else {
				type_num = parseInt(opts.map_type, 10);
				if (isNaN(type_num)) {
					opts.map_type = google.maps.NORMAL_MAP;
				} else {
					opts.map_type = this.map.getMapTypes()[type_num];
				}
			}
		} else if (typeof opts.map_type === 'undefined') {
			opts.map_type = google.maps.NORMAL_MAP;
		}

		if (opts.load_kml) {
			this.kml = new google.maps.GeoXml(opts.load_kml);
			this.map.addOverlay(this.kml);
		}

		this.buildCategoryHierarchy();

		if (opts.center_lat && opts.center_lng) {
			// Use the center form options
			this.map.setCenter(new google.maps.LatLng(opts.center_lat, opts.center_lng), opts.zoom, opts.map_type);
		} else if (this.kml) {
			this.map.setCenter(this.kml.getDefaultCenter, opts.zoom, opts.map_type);
		} else if (opts.post_data && opts.post_data.posts[0]) {
			center_latlng = new google.maps.LatLng(opts.post_data.posts[0].lat, opts.post_data.posts[0].lng);
			this.map.setCenter(center_latlng, opts.zoom, opts.map_type);
			if (this.opts.auto_info_open && !this.opts.open_post_id) {
				this.opts.open_post_id = opts.post_data.posts[0].post_id;
			}
		} else {
			// Center on the most recent located post
			request = google.maps.XmlHttp.create();
			url = this.opts.url_path + '/geo-query.php?limit=1';
			if (opts.map_cat) {
				url += '&cat='+opts.map_cat;
			}
			request.open("GET", url, false);
			request.send(null);
			posts = window['eval']( '(' + request.responseText + ')' );
			if (posts.length>0) {
				point = new google.maps.LatLng(posts[0].lat,posts[0].lng);
				this.map.setCenter(point,opts.zoom,opts.map_type);
				if (this.opts.auto_info_open) {
					this.opts.open_post_id = posts[0].post_id;
				}
			} else {
				this.map.setCenter(new google.maps.LatLng(0,0),opts.zoom,opts.map_type);
			}
		}

		google.maps.Event.bind(this.map, "zoomend", this, this.adjustZoom);
		google.maps.Event.bind(this.map, "moveend", this, this.adjustViewport);

		if (opts.map_content === 'single')
		{
			if (opts.center_lat && opts.center_lng && !this.kml)
			{
				marker_opts = {};
				if (typeof customGeoMashupSinglePostIcon === 'function') {
					marker_opts.icon = customGeoMashupSinglePostIcon(this.opts);
				}
				this.map.addOverlay(
					new google.maps.Marker(
						new google.maps.LatLng(this.opts.center_lat,this.opts.center_lng), marker_opts));
			}
		} else if (opts.post_data) {
			this.addPosts(opts.post_data.posts,true);
		} else {
			// Request posts near visible range first
			this.requestPosts(true);

			// Request all posts
			this.requestPosts(false);
		}

		if ('GSmallZoomControl' === opts.map_control) {
			this.map.addControl(new google.maps.SmallZoomControl());
		} else if ('GSmallZoomControl3D' === opts.map_control) {
			this.map.addControl(new google.maps.SmallZoomControl3D());
		} else if ('GSmallMapControl' === opts.map_control) {
			this.map.addControl(new google.maps.SmallMapControl());
		} else if ('GLargeMapControl' === opts.map_control) {
			this.map.addControl(new google.maps.LargeMapControl());
		} else if ('GLargeMapControl3D' === opts.map_control) {
			this.map.addControl(new google.maps.LargeMapControl3D());
		}

		if (opts.add_map_type_control) {
			this.map.addControl(new google.maps.MapTypeControl());
		}

		if (opts.add_overview_control) {
			this.map.addControl(new google.maps.OverviewMapControl());
			ov = document.getElementById('gm-overview');
			if (ov) {
				ov.style.position = 'absolute';
				this.container.appendChild(ov);
			}
		}

		credit_div = document.createElement( 'div' );
		credit_div.innerHTML = [
			'<div class="gmnoprint" style="-moz-user-select: none; z-index: 0; position: absolute; left: 2px; bottom: 38px;">',
			'<a title="Geo Mashup" href="http://code.google.com/p/wordpress-geo-mashup" target="_blank">',
			'<img style="border: 0px none ; margin: 0px; padding: 0px; width: 60px; height: 39px; -moz-user-select: none; cursor: pointer;" src="',
			this.opts.url_path,
			'/images/gm-credit.png"/></a></div>'].join( '' );
		this.container.appendChild( credit_div );
		
		if (typeof customizeGeoMashupMap === 'function') {
			customizeGeoMashupMap(this.opts, this.map);
		}

		if (typeof customizeGeoMashup === 'function') {
			customizeGeoMashup(this);
		}

	},

	saveBackSettings : function() {
		var ix, center, map_type_num, back_settings;

		center = this.map.getCenter();
		map_type_num = 0;
		for(ix=0; ix<this.map.getMapTypes().length; ix+=1) {
			if (this.map.getMapTypes()[ix] === this.map.getCurrentMapType()) {
				map_type_num = ix;
			}
		}
		back_settings = {
			'map_type':map_type_num,
			'lat':center.lat(),
			'lng':center.lng(),
			'zoom':this.map.getZoom()
		};
		this.setCookie('back_settings',this.settingsToString(back_settings));
		this.setCookie('back_search',window.location.search);
		return true;
	}

};
