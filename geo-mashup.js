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

var customizeGeoMashup, customGeoMashupColorIcon, customGeoMashupCategoryIcon, customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage;

var GeoMashup = {
	posts : {},
	post_count : 0,
	locations : {},
	categories : {},
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
		  window.attachEvent("onunload", GUnload);
		} else if (window.addEventListener) { // Others
			window.addEventListener("load", function () { GeoMashup.createMap(container, opts); }, false);
			window.addEventListener("unload", GUnload, false);
		}
	},

	getCookie : function(NameOfCookie) { 
		if (document.cookie.length > 0) { 
			var begin = document.cookie.indexOf(NameOfCookie+"=");
			if (begin != -1) { 
				begin += NameOfCookie.length+1;
				var end = document.cookie.indexOf(";", begin);
				if (end == -1) end = document.cookie.length;
				return unescape(document.cookie.substring(begin, end)); 
			}
		}
		return null;
	},

	setCookie : function(NameOfCookie, value) { 
		document.cookie = NameOfCookie + "=" + escape(value); 
	},

	delCookie : function(NameOfCookie) { 
		if (this.getCookie(NameOfCookie)) {
			document.cookie = NameOfCookie + "=" +
				"; expires=Thu, 01-Jan-70 00:00:01 GMT";
		}
	},

	loadSettings : function (obj, settings_str) {
		if (!settings_str) {
			return false;
		}
		if (settings_str.charAt(0) == '?') {
			settings_str = settings_str.substr(1);
		}
		var pairs = settings_str.split('&');
		for (var i=0; i<pairs.length; i++) {
			var keyvalue = pairs[i].split('=');
			obj[keyvalue[0]] = decodeURIComponent(keyvalue[1]);
			if (obj[keyvalue[0]] == 'false') obj[keyvalue[0]] = false;
		}
		return true;
	},

	settingsToString : function (obj) {
		var str = [];
		var sep = '';
		for (key in obj) {
			str.push(sep);
			str.push(key);
			str.push('=');
			str.push(encodeURIComponent(obj[key]));
			sep = '&';
		}
		return str.join('');
	},

	getTagContent : function (container, tag, default_value) {
		if (!default_value) {
			default_value = '';
		}
		var children = container.getElementsByTagName(tag);
		if (children.length > 0 && children[0].firstChild) {
			return children[0].firstChild.nodeValue;
		} else {
			return default_value;
		}
	},

	parentScrollToGeoPost : function () {
		var geo_post = parent.document.getElementById('geoPost');
		if (geo_post) {
			parent.focus();
			parent.scrollTo(geo_post.offsetLeft, geo_post.offsetTop);
		}
		return false;
	},

	renderRss : function (rss_doc) {
		// Built excerpt HTML
		var items = rss_doc.getElementsByTagName('item');
		if (items.length == 0) return false;
		var html = ['<div class="locationinfo">'];

		for (var i=0; i<items.length; i++) {
			var link = this.getTagContent(items[i],'link');
			var url = link;
			var onclick = 'this.target=\'_parent\'; GeoMashup.saveBackSettings()';
			if (this.opts.show_post) {
				onclick = 'return GeoMashup.parentScrollToGeoPost()';
				url = '#';
			}
			var title = this.getTagContent(items[i],'title','-');
			var pubDate = this.getTagContent(items[i],'pubDate','-').substr(0,16);
			var tags = [];
			var post_categories = items[i].getElementsByTagName('category');
			for (var j=0; j<post_categories.length; j++) {
				tags.push(post_categories[j].firstChild.nodeValue);
			}
			html = html.concat(['<h2><a href="', url, '" onclick="', onclick, '">',
				title,'<\/a><\/h2><p class="meta"><span class="blogdate">',pubDate,'<\/span>, ',
				tags.join(' '),
				'<\/p>']);
			if (items.length == 1) {
				var desc = this.getTagContent(items[i],'description').replace('[...]','');
				desc = desc.replace(/(<a[^>])target="[^"]*"/gi,'$1'); // remove existing link targets
				desc = desc.replace(/<a[^>]*/gi,'$& target="_parent"'); // make link target '_parent'
				html = html.concat(['<div class="storycontent">',desc,
					'<a href="',url,'" onclick="',onclick,'">[...]<\/a><\/div>']);
				if (this.opts.show_post) { this.showPost(link); }
			}
		} 
		html.push('<\/div>');
		return html.join('');
	},

	buildCategoryHeirarchy : function(category) {
		if (category) {
			var children = new Object();
			var child_count = 0;
			for (child_category in this.opts.category_opts) {
				if (this.opts.category_opts[child_category].parent_id && 
						this.opts.category_opts[child_category].parent_id == this.opts.category_opts[category].id) {
						children[child_category] = this.buildCategoryHeirarchy(child_category);
						child_count++;
					}
			}
			return (child_count > 0) ? children : null;
		} else {
			this.category_heirarchy = new Object();
			for (category in this.opts.category_opts) {
				if (this.categories[category] && !this.opts.category_opts[category].parent_id) {
					this.category_heirarchy[category] = this.buildCategoryHeirarchy(category);
				}
			}
		}
	},

	hideCategoryHeirarchy : function(category) {
		this.hideCategory(category);
		for (child_cat in this.category_heirarchy[category]) {
			this.hideCategoryHeirarchy(child_cat);
		}
  },

	showCategoryHeirarchy : function(category) {
		this.showCategory(category);
		for (child_cat in this.category_heirarchy[category]) {
			this.showCategoryHeirarchy(child_cat);
		}
  },

	categoryTabSelect : function(select_category) {
		var tab_div = parent.document.getElementById(window.name + 'TabIndex');
		if (!tab_div) return false;
		var tab_list_element = tab_div.childNodes[0];
		for (var i=0; i<tab_list_element.childNodes.length; i++) {
			var tab_element = tab_list_element.childNodes[i];
			if (tab_element.childNodes[0].href.match(this.categoryIndexId(select_category))) {
				tab_element.className = 'gm-tab-active';
			} else {
				tab_element.className = 'gm-tab-inactive';
			}
		}
		for (category in this.category_heirarchy) {
			var index_div = parent.document.getElementById(this.categoryIndexId(category));
			if (index_div) {
				if (category == select_category) {
					index_div.className = '';
					this.showCategoryHeirarchy(category);
				} else {
					index_div.className = 'gm-hidden';
					this.hideCategoryHeirarchy(category);
				}
			}
		}
	},

	categoryIndexId : function(category) {
		return 'geo_mashup_cat_index_' + this.opts.category_opts[category].id;
	},

	categoryTabIndexHtml : function() {
		var html_array = [];
		html_array.push('<div id="');
		html_array.push(window.name);
		html_array.push('TabIndex"><ul class="geo_mashup_tab_index">');
		for (category in this.category_heirarchy) {
			html_array = html_array.concat([
				'<li><a href="#',
				this.categoryIndexId(category),
				'" onclick="frames[\'',
				window.name,
				'\'].GeoMashup.categoryTabSelect(\'',
				category,
				'\'); return false;"><img src="',
				this.categories[category].icon.image,
				'" /><span>',
				category,
				'</span></a></li>']);
		} 
		html_array.push('</ul>');
		for (category in this.category_heirarchy) {
			html_array.push(this.categoryIndexHtml(category, this.category_heirarchy[category]));
		}
		return html_array.join('');
	},

	categoryIndexHtml : function(category, children) {
		var html_array = [];
		html_array.push('<div id="geo_mashup_cat_index_');
		html_array.push(this.opts.category_opts[category].id);
		html_array.push('"><ul class="geo_mashup_index_posts">');
		for (var i=0; i<this.categories[category].posts.length; ++i) {
			html_array.push('<li>');
			html_array.push(this.postLinkHtml(this.categories[category].posts[i]));
			html_array.push('</li>');
		}
		html_array.push('</ul><ul class="geo_mashup_sub_cat_index">');
		for (child_cat in children) {
			html_array.push('<li><img src="');
			html_array.push(this.categories[child_cat].icon.image);
			html_array.push('" /><span class="geo_mashup_sub_cat_title">');
			html_array.push(child_cat);
			html_array.push(this.categoryIndexHtml(child_cat, children[child_cat]));
			html_array.push('</li>');
		}
		html_array.push('</ul></div>');
		return html_array.join('');
	},

	showCategoryInfo : function() {
		var legend_element = null;
		var index_element = null;
		var interactive = false;
		if (window.name) {
			legend_element = parent.document.getElementById(window.name + "Legend");
			index_element = parent.document.getElementById(window.name + "TabbedIndex");
			interactive = true;
		}
		if (!legend_element) {
			legend_element = parent.document.getElementById("geoMashupCategoryLegend");
		}
		if (!index_element) {
			index_element = parent.document.getElementById("geoMashupTabbedIndex");
		}
		var legend_html = ['<table class="geo_mashup_legend">'];
		for (category in this.categories) {
			this.categories[category].line = new GPolyline(this.categories[category].points, 
				this.categories[category].color);
			this.map.addOverlay(this.categories[category].line);
			if (this.map.getZoom() > this.categories[category].max_line_zoom) {
				this.categories[category].line.hide();
			}
			if (legend_element) {
				var label;
				if (window.name && interactive) {
					var id = 'category_' + this.opts.category_opts[category].id + '_checkbox';
					label = [
						'<label for="',
						id,
						'"><input type="checkbox" name="category_checkbox" id="',
						id,
						'" onclick="if (this.checked) { frames[\'',
						window.name,
						'\'].GeoMashup.showCategory(\'',
						category,
						'\'); } else { frames[\'',
						window.name,
						'\'].GeoMashup.hideCategory(\'',
						category,
						'\'); }" checked="true" />',
						category,
						'</label>'].join('');
				} else {
					label = category;
				}
				legend_html = legend_html.concat(['<tr><td><img src="',
					this.categories[category].icon.image,
					'" alt="',
					category,
					'"></td><td>',
					label,
					'</td></tr>']);
			}
		}
		legend_html.push('</table>');
		if (legend_element) legend_element.innerHTML = legend_html.join('');
		if (index_element) {
			this.buildCategoryHeirarchy();
			index_element.innerHTML = this.categoryTabIndexHtml();
			for (category in this.category_heirarchy) {
				this.categoryTabSelect(category);
				break;
			}
		}
	}, 

	showPost : function (url) {
		if (this.showing_url == url) {
			return false;
		}
		var geoPost = parent.document.getElementById('geoPost');
		if (!geoPost) {
			this.opts.show_post = false;
			return false;
		}
		this.showing_url = url;
		var request = new GXmlHttp.create();
		geoPost.innerHTML = '';
		request.open('GET',url,true);
		request.onreadystatechange = function() {
			if (request.readyState == 4) {
				if (request.status == 200) {
					var node = parent.document.createElement('div');
					node.innerHTML = request.responseText;
					var divs = node.getElementsByTagName('div');
					for (var i=0; i<divs.length; i++) {
						if (divs[i].className=='post') { 
							geoPost.appendChild(divs[i]);
							break;
						}
					}
				} else {
					geoPost.innerHTML = 'Request for '+url+' failed: '+request.status;
				}
			}
		};
		request.send(null);
	},

	createMarker : function(point,post) {
		var marker_opts = {title:post.title};
		if (typeof(customGeoMashupCategoryIcon) == 'function') {
			marker_opts.icon = customGeoMashupCategoryIcon(this.opts, post.categories);
		} 
		if (!marker_opts.icon) {
			if (post.categories.length > 1) {
				marker_opts.icon = new GIcon(this.multiple_category_icon);
			} else {
				marker_opts.icon = new GIcon(this.categories[post.categories[0]].icon);
			}
		}
		var marker = new GMarker(point,marker_opts);

		// Show this markers index in the info window when it is clicked
		GEvent.addListener(marker, "click", function() {
			var request = new GXmlHttp.create();
			for(var i=0; i<GeoMashup.locations[point].posts.length; i++) {
				var post_id = GeoMashup.locations[point].posts[i];
				if (!GeoMashup.locations[point].loaded[post_id]) {
					var url = GeoMashup.opts.url_path + '/geo-query.php?post_id=' + post_id;
					// Use a synchronous request to simplify multiple posts at a location
					request.open('GET', url, false);
					try {
						request.send(null);
						if (!GeoMashup.locations[point].xmlDoc) {
							GeoMashup.locations[point].xmlDoc = request.responseXML;
						} else {
							var newItem = request.responseXML.getElementsByTagName('item')[0];
							var channel = GeoMashup.locations[point].xmlDoc.getElementsByTagName('channel')[0];
							if (GeoMashup.locations[point].xmlDoc.importNode) {
								// Standards browsers
								var importedNode = GeoMashup.locations[point].xmlDoc.importNode(newItem, true);

								// Safari bug - if the title element got lost create a new one and append it
								if(newItem.getElementsByTagName("title")[0] && !importedNode.getElementsByTagName("title")[0]){                              
										var titleElement = newItem.ownerDocument.createElement("title");
										var titleData = newItem.ownerDocument.createTextNode(newItem.getElementsByTagName("title")[0].firstChild.data);
										titleElement.appendChild(titleData);
										importedNode.appendChild(titleElement);
								}

								channel.appendChild(importedNode);
							} else {
								// break the rules for IE
								channel.appendChild(newItem);
							}
						} 
						GeoMashup.locations[point].loaded[post_id] = true;
					} catch (e) {
						GeoMashup.errors.push('Request for ' + url + ' failed: ' + e);
					}
				} // end if not loaded
			} // end location posts loop
			var info_window_opts = {};
			var html = GeoMashup.renderRss(GeoMashup.locations[point].xmlDoc);
			GeoMashup.map.closeInfoWindow();
			if (GeoMashup.opts.info_window_width) info_window_opts.maxWidth = GeoMashup.opts.info_window_width;
			if (GeoMashup.opts.info_window_height) info_window_opts.maxHeight = GeoMashup.opts.info_window_height;
			marker.openInfoWindowHtml(html,info_window_opts);
		}); // end marker infowindowopen

		GEvent.addListener(marker, 'infowindowclose', function() {
			var geoPost = parent.document.getElementById('geoPost');
			if (geoPost && geoPost.firstChild) {
				geoPost.removeChild(geoPost.firstChild);
				GeoMashup.showing_url = '';
			}
		});

		return marker;
	},

	checkDependencies : function () {
		if (typeof(GMap) == "undefined" || !GBrowserIsCompatible()) {
			this.container.innerHTML = '<p class="errormessage">' +
				'Sorry, the Google Maps script failed to load. Have you entered your ' +
				'<a href="http://maps.google.com/apis/maps/signup.html">API key<\/a> ' +
				'in the Geo Mashup Options?';
			throw "The Google Maps javascript didn't load.";
		}
	},

	clickMarker : function(post_id, try_count) {
		if (typeof(try_count) == 'undefined') {
			try_count = 1;
		}
		if (this.posts[post_id] && try_count < 4) {
			if (GeoMashup.posts[post_id].marker.isHidden()) {
				try_count++;
				setTimeout(function () { GeoMashup.clickMarker(post_id, try_count); }, 300);
			} else {
				GEvent.trigger(GeoMashup.posts[post_id].marker,"click"); 
			}
		}
	},

	extendCategory : function(point, category, post_id) {
		if (!this.categories[category]) {
			var icon, color, color_name;
			if (this.opts.category_opts[category].color_name) {
				color_name = this.opts.category_opts[category].color_name;
			} else {
				color_name = this.color_names[this.category_count%this.colors_names.length];
			}
			color = this.colors[color_name];
			if (!icon && typeof(customGeoMashupCategoryIcon) == 'function') {
				icon = customGeoMashupCategoryIcon(this.opts, [category]);
			}
			if (!icon && typeof(customGeoMashupColorIcon) == 'function') {
				icon = customGeoMashupColorIcon(this.opts, color_name);
			}
			if (!icon) {
				icon = new GIcon(this.base_color_icon);
				icon.image = this.opts.url_path + '/images/mm_20_' + color_name + '.png';
			}
			var max_line_zoom = 0;
			if (this.opts.category_opts[category].max_line_zoom) {
				max_line_zoom = this.opts.category_opts[category].max_line_zoom;
			}
			this.categories[category] = {
				icon : icon,
				points : [point],
				posts : [post_id],
				color : color,
				visible : true,
				max_line_zoom : max_line_zoom
			};
			this.category_count++;
		} else {
			this.categories[category].points.push(point);
			this.categories[category].posts.push(post_id);
		}
	},

	hideCategory : function(category) {
		this.map.closeInfoWindow();
		if (this.categories[category].line) {
			this.categories[category].line.hide();
		}
		for (var i=0; i<this.categories[category].points.length; i++) {
			var point = this.categories[category].points[i];
			this.locations[point].marker.hide();
		}
		this.categories[category].visible = false;
		this.updateVisibleList();
	},

	showCategory : function(category) {
		if (this.categories[category].line && this.map.getZoom() <= this.categories[category].max_line_zoom) {
			this.categories[category].line.show();
		}
		for (var i=0; i<this.categories[category].points.length; i++) {
			var point = this.categories[category].points[i];
			this.locations[point].marker.show();
		}
		this.categories[category].visible = true;
		this.updateVisibleList();
	},

	addPosts : function(response_data, add_category_info) {
		if (add_category_info) {
			for (category in this.categories) {
				this.categories[category].points.length = 0;
				if (this.categories[category].line) {
					this.categories[category].line.hide();
				}
			}
		}
		for (var i = 0; i < response_data.length; i++) {
			// Make a marker for each new post location
			var post_id = response_data[i].post_id;
			var point = new GLatLng(
				parseFloat(response_data[i].lat),
				parseFloat(response_data[i].lng));
			// Update categories
			for (var j = 0; j < response_data[i].categories.length; j++) {
				var category = response_data[i].categories[j];
				this.extendCategory(point, category, post_id);
			}
			if (this.opts.max_posts && this.post_count >= this.opts.max_posts) break;
			if (!this.posts[post_id]) {
				// This post has not yet been loaded
				this.post_count++;
				if (!this.locations[point]) {
					// There are no other posts yet at this point, create a marker
					this.locations[point] = new Object();
					this.locations[point].posts = new Array();
					this.locations[point].posts.push(post_id);
					this.locations[point].loaded = new Array();
					this.posts[post_id] = new Object();
					var marker = this.createMarker(point, response_data[i]);
					this.posts[post_id].marker = marker;
					this.locations[point].marker = marker;
					this.map.addOverlay(marker);
				} else {
					// There is already a marker at this point, add the new post to it
					this.locations[point].posts.push(post_id);
					var plus_image;
					var marker = this.locations[point].marker;
					if (typeof(customGeoMashupMultiplePostImage) == 'function') {
						plus_image = customGeoMashupMultiplePostImage(this.opts, marker.getIcon().image);
					}
					if (!plus_image) {
						plus_image = this.opts.url_path + '/images/mm_20_plus.png';
					}
					marker.setImage(plus_image);
					this.posts[post_id] = new Object();
					this.posts[post_id].marker = this.locations[point].marker;
				}
				this.posts[post_id].title = response_data[i].title;
			}
		} // end for each marker
		// Add category lines
		if (add_category_info) this.showCategoryInfo();

		if (this.firstLoad) {
			this.firstLoad = false;
			if (this.opts.auto_info_open && this.opts.open_post_id) {
				this.clickMarker(this.opts.open_post_id);
			}
		}
	},

	requestPosts : function(use_bounds) {
		if (this.opts.max_posts && this.post_count >= this.opts.max_posts) return;
		var request = GXmlHttp.create();
		var url = this.opts.url_path + '/geo-query.php?i=1';
		if (use_bounds) {
			var map_bounds = this.map.getBounds();
			var map_span = map_bounds.toSpan();
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
			if (request.readyState == 4 && request.status == 200) {
				var posts = eval(request.responseText);
				GeoMashup.addPosts(posts,!use_bounds);
			} // end readystate == 4
		}; // end onreadystatechange function
		request.send(null);
	},

	hideMarkers : function() {
		for (point in this.locations) {
			this.locations[point].marker.hide();
		}
		this.updateVisibleList();
	},

	showMarkers : function() {
		for (category in this.categories) {
			if (this.categories[category].visible) {
				for (var i=0; i<this.categories[category].points.length; i++) {
					var point = this.categories[category].points[i];
					this.locations[point].marker.show();
				}
			}
		}
	},

	adjustZoom : function(old_level, new_level) {
		if (old_level >= this.opts.min_marker_zoom && new_level < this.opts.min_marker_zoom) {
			this.hideMarkers();
		} else if (old_level < this.opts.min_marker_zoom && new_level >= this.opts.min_marker_zoom) {
			this.showMarkers();
		}
		for (category in this.categories) {
			if (old_level <= this.categories[category].max_line_zoom &&
			  new_level > this.categories[category].max_line_zoom) {
				this.categories[category].line.hide();
			} else if (this.categories[category].visible &&
				old_level > this.categories[category].max_line_zoom &&
			  new_level <= this.categories[category].max_line_zoom) {
				this.categories[category].line.show();
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
		var list_element = null;
		var header_element = null;
		if (window.name) {
			header_element = parent.document.getElementById(window.name + "VisibleListHeader");
			list_element = parent.document.getElementById(window.name + "VisibleList");
		}
		if (header_element) {
			header_element.style.display = 'block';
		}
		if (list_element) {
			var list_html = ['<ul class="geo_mashup_visible_list">'];
			for (post_id in this.posts) {
				var map_bounds = this.map.getBounds();
				var marker = this.posts[post_id].marker;
				if (!marker.isHidden() && map_bounds.containsLatLng(marker.getLatLng())) {
					list_html.push('<li><img src="');
					list_html.push(marker.getIcon().image);
					list_html.push('" alt="');
					list_html.push(this.posts[post_id].title);
					list_html.push('" />');
					list_html.push(this.postLinkHtml(post_id));
					list_html.push('</li>');
				}
			}
			list_html.push('</ul>');
			list_element.innerHTML = list_html.join('');
		}
	},

	adjustViewport : function() {
		this.updateVisibleList();
	},

	createMap : function(container, opts) {
		this.container = container;
		this.showing_url = '';
		this.checkDependencies();
		this.base_color_icon = new GIcon();
		this.base_color_icon.image = opts.url_path + '/images/mm_20_black.png';
		this.base_color_icon.shadow = opts.url_path + '/images/mm_20_shadow.png';
		this.base_color_icon.iconSize = new GSize(12, 20);
		this.base_color_icon.shadowSize = new GSize(22, 20);
		this.base_color_icon.iconAnchor = new GPoint(6, 20);
		this.base_color_icon.infoWindowAnchor = new GPoint(5, 1);
		this.multiple_category_icon = new GIcon(this.base_color_icon);
		this.multiple_category_icon.image = opts.url_path + '/images/mm_20_mixed.png';
		this.map = new GMap2(this.container);
		if (window.location.search == this.getCookie('back_search'))
		{
			this.loadSettings(opts, this.getCookie('back_settings'));
		}
		this.opts = opts;

		if (typeof(opts.zoom) == 'string') {
			opts.zoom = parseInt(opts.zoom);
		} else if (typeof(opts.zoom) == 'undefined') {
			opts.zoom = 5;
		}

		if (typeof(opts.map_type) == 'string') {
			var typeNum = parseInt(opts.map_type);

			if (isNaN(typeNum)) {
				opts.map_type = eval(opts.map_type);
			} else {
				opts.map_type = this.map.getMapTypes()[typeNum];
			}
		} else if (typeof(opts.map_type) == 'undefined') {
			opts.map_type = G_NORMAL_MAP;
		}

		if (opts.load_kml)
		{
			this.kml = new GGeoXml(opts.load_kml);
			this.map.addOverlay(this.kml);
		}

		if (opts.center_lat && opts.center_lng) {
			// Use the center form options
			this.map.setCenter(new GLatLng(opts.center_lat, opts.center_lng), opts.zoom, opts.map_type);
		} else if (this.kml) {
			this.map.setCenter(this.kml.getDefaultCenter, opts.zoom, opts.map_type);
		} else if (opts.post_data && opts.post_data.posts[0]) {
			var center_latlng = new GLatLng(opts.post_data.posts[0].lat, opts.post_data.posts[0].lng);
			this.map.setCenter(center_latlng, opts.zoom, opts.map_type);
			if (this.opts.auto_info_open && !this.opts.open_post_id) {
				this.opts.open_post_id = opts.post_data.posts[0].post_id;
			}
		} else {
			// Center on the most recent located post
			var request = GXmlHttp.create();
			var url = this.opts.url_path + '/geo-query.php?limit=1';
			if (opts.map_cat) {
				url += '&cat='+opts.map_cat;
			}
			request.open("GET", url, false);
			request.send(null);
			var posts = eval(request.responseText);
			if (posts.length>0) {
				var point = new GLatLng(posts[0].lat,posts[0].lng);
				this.map.setCenter(point,opts.zoom,opts.map_type);
				if (this.opts.auto_info_open) {
					this.opts.open_post_id = posts[0].post_id;
				}
			} else {
				this.map.setCenter(new GLatLng(0,0),opts.zoom,opts.map_type);
			}
		}

		GEvent.bind(this.map, "zoomend", this, this.adjustZoom);
		GEvent.bind(this.map, "moveend", this, this.adjustViewport);

		if (opts.map_content == 'single')
		{
			if (opts.center_lat && opts.center_lng && !this.kml)
			{
				var marker_opts = new Object();
				if (typeof(customGeoMashupSinglePostIcon) == 'function') {
					marker_opts.icon = customGeoMashupSinglePostIcon(this.opts);
				}
				this.map.addOverlay(new GMarker(new GLatLng(this.opts.center_lat,this.opts.center_lng), marker_opts));
			}
		}
		else if (opts.post_data)
		{
			this.addPosts(opts.post_data.posts,true);
		}
		else
		{
			// Request posts near visible range first
			this.requestPosts(true);

			// Request all posts
			this.requestPosts(false);
		}

		if (opts.map_control == 'GSmallZoomControl') {
			this.map.addControl(new GSmallZoomControl());
		} else if (opts.map_control == 'GSmallMapControl') {
			this.map.addControl(new GSmallMapControl());
		} else if (opts.map_control == 'GLargeMapControl') {
			this.map.addControl(new GLargeMapControl());
		}

		this.map.addMapType(G_PHYSICAL_MAP);
		if (opts.add_map_type_control) {
			this.map.addControl(new GMapTypeControl());
		}

		if (opts.add_overview_control) {
			this.map.addControl(new GOverviewMapControl());
			var ov = document.getElementById('geoMashup_overview');
			if (ov) {
				ov.style.position = 'absolute';
				this.container.appendChild(ov);
			}
		}

		if (this.addCategoryControl) {
			this.map.addControl(new GeoMashupCategoryControl());
		}

		if (typeof(customizeGeoMashupMap) == 'function') {
			customizeGeoMashupMap(this.opts, this.map);
		}

		if (typeof(customizeGeoMashup) == 'function') {
			customizeGeoMashup(this);
		}

	},

	saveBackSettings : function() {
		var center = this.map.getCenter();
		var mapTypeNum = 0;
		for(var ix=0; ix<this.map.getMapTypes().length; ix++){
			if(this.map.getMapTypes()[ix]==this.map.getCurrentMapType())
				mapTypeNum=ix;
		}
		var back_settings = {
			'map_type':mapTypeNum,
			'lat':center.lat(),
			'lng':center.lng(),
			'zoom':this.map.getZoom()
		};
		this.setCookie('back_settings',this.settingsToString(back_settings));
		this.setCookie('back_search',window.location.search);
		return true;
	}

};

