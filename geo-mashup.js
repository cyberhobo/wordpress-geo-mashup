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

var customizeGeoMashup;

var GeoMashup = {
	posts : [],
	locations : [],
	loading : false,
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

	getTagContent : function (parent, tag, default_value) {
		if (!default_value) {
			default_value = '';
		}
		var children = parent.getElementsByTagName(tag);
		if (children.length > 0 && children[0].firstChild) {
			return children[0].firstChild.nodeValue;
		} else {
			return default_value;
		}
	},

	renderRss : function (rss_doc) {
		var items = rss_doc.getElementsByTagName('item');
		if (items.length == 0) return false;
		var html = ['<div class="locationinfo">'];
			
		for (var i=0; i<items.length; i++) {
			var link = this.getTagContent(items[i],'link');
			var url = link;
			var onclick = 'GeoMashup.setBackCookies()';
			if (this.opts.showPostHere) {
				onclick = 'GeoMashup.showPost(\''+url+'\')';
				url = '#geoPost';
			}
			var title = this.getTagContent(items[i],'title','-');
			var pubDate = this.getTagContent(items[i],'pubDate','-').substr(0,16);
			var tags = [];
			var categories = items[i].getElementsByTagName('category');
			for (var j=0; j<categories.length; j++) {
				tags.push(categories[j].firstChild.nodeValue);
			}
			html = html.concat(['<h2><a href="', url, '" onclick="', onclick, '">',
				title,'<\/a><\/h2><p class="meta"><span class="blogdate">',pubDate,'<\/span>, ',
				tags.join(' '),
				'<\/p>']);
			if (items.length == 1) {
				var desc = this.getTagContent(items[i],'description').replace('[...]','');
				html = html.concat(['<p class="storycontent">',desc,
					'<a href="',url,'" onclick="',onclick,'">[...]<\/a><\/p>']);
				if (this.opts.showPostHere) { this.showPost(link); }
			}
		} 
		html.push('<\/div>');
		return html.join('');
	},

	showPost : function (url) {
		if (this.showing_url == url) {
			return false;
		}
		var geoPost = document.getElementById('geoPost');
		if (!geoPost) {
			this.opts.showPostHere = false;
			return false;
		}
		this.showing_url = url;
		var request = new GXmlHttp.create();
		geoPost.innerHTML = '';
		request.open('GET',url,true);
		request.onreadystatechange = function() {
			if (request.readyState == 4) {
				if (request.status == 200) {
					var node = document.createElement('div');
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

	createMarker : function(point,title) {
		var marker_opts = {title:title};
		if (this.marker_icon) {
			marker_opts.icon = this.marker_icon;
		}
		var marker = new GMarker(point,marker_opts);

		// Show this markers index in the info window when it is clicked
		GEvent.addListener(marker, "click", function() {
			// The loading window is late to show up - may replace it 
			// with a message div 
			marker.openInfoWindowHtml('Loading...');
			GeoMashup.loading = true;
		});

		GEvent.addListener(marker, "infowindowopen", function() {
			if (GeoMashup.loading) {
				var request = new GXmlHttp.create();
				for(var i=0; i<GeoMashup.locations[point].posts.length; i++) {
					var post_id = GeoMashup.locations[point].posts[i];
					if (!GeoMashup.locations[point].loaded[post_id]) {
						var url = GeoMashup.opts.linkDir + '/geo-query.php?post_id=' + post_id;
						// Use a synchronous request to simplify multiple posts at a location
						request.open('GET', url, false);
						try {
							request.send(null);
							if (!GeoMashup.locations[point].xmlDoc) {
								GeoMashup.locations[point].xmlDoc = request.responseXML;
							} else {
								var newItem = request.responseXML.getElementsByTagName('item')[0];
								var channel = GeoMashup.locations[point].xmlDoc.getElementsByTagName('channel')[0];
								channel.appendChild(newItem);
							} 
							GeoMashup.locations[point].loaded[post_id] = true;
						} catch (e) {
							alert('Request for ' + url + ' failed: ' + e);
						}
					} // end if not loaded
				} // end location posts loop
				GeoMashup.loading = false;
				var html = GeoMashup.renderRss(GeoMashup.locations[point].xmlDoc);
				GeoMashup.map.closeInfoWindow();
				marker.openInfoWindowHtml(html);
			} 
		}); // end marker infowindowopen

		GEvent.addListener(marker, 'infowindowclose', function() {
			var geoPost = document.getElementById('geoPost');
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
				'in the <a href="' + this.opts.linkDir + 
				'../../../wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">' +
				'Geo Mashup Options<\/a>?';
			throw "The Google Maps javascript didn't load.";
		}
	},

	clickCenterMarker : function() {
		var center = this.map.getCenter();
		if (this.locations[center]) {
			GEvent.trigger(this.locations[center].marker,"click");
		}
	},

	createMap : function(container, opts) {
		this.container = container;
		this.opts = opts;
		this.showing_url = '';
		this.checkDependencies();
		this.map = new GMap2(this.container);
		GEvent.addListener(this.map, "moveend", function() {
			var request = GXmlHttp.create();
			var bounds = GeoMashup.map.getBounds();
			var url = GeoMashup.opts.linkDir + '/geo-query.php?minlat=' +
				bounds.getSouthWest().lat() + '&minlon=' + bounds.getSouthWest().lng() + '&maxlat=' +
				bounds.getNorthEast().lat() + '&maxlon=' + bounds.getNorthEast().lng();
			if (GeoMashup.opts.cat) {
				url += '&cat=' + GeoMashup.opts.cat;
			}
			request.open("GET", url, true);
			request.onreadystatechange = function() {
				if (request.readyState == 4 && request.status == 200) {
					var response_data = eval(request.responseText);
					for (var i = 0; i < response_data.length; i++) {
						// Make a marker for each new post location
						var post_id = response_data[i].post_id;
						if (!GeoMashup.posts[post_id]) {
							// This post has not yet been loaded
							var point = new GLatLng(
								response_data[i].lat,
								response_data[i].lng);
							if (!GeoMashup.locations[point]) {
								// There are no other posts yet at this point, create a marker
								GeoMashup.locations[point] = new Object();
								GeoMashup.locations[point].posts = new Array();
								GeoMashup.locations[point].posts.push(post_id);
								GeoMashup.locations[point].loaded = new Array();
								GeoMashup.posts[post_id] = true;
								var marker = GeoMashup.createMarker(point,response_data[i].title);
								GeoMashup.locations[point].marker = marker;
								GeoMashup.map.addOverlay(marker);
							} else {
								// There is already a marker at this point, add the new post to it
								GeoMashup.locations[point].posts.push(post_id);
								GeoMashup.posts[post_id] = true;
							}
						}
					} // end for each marker
					if (GeoMashup.firstLoad) {
						GeoMashup.firstLoad = false;
						if (GeoMashup.opts.autoOpenInfoWindow) {
							GeoMashup.clickCenterMarker();
						}
					}
				} // end readystate == 4
			}; // end onreadystatechange function
			request.send(null);
		});

		if (opts.loadLat && opts.loadLon) {
			this.loadLat = opts.loadLat;
			this.loadLon = opts.loadLon;
		} else {
			this.loadLat = this.getCookie("loadLat");
			this.loadLon = this.getCookie("loadLon");
		}
		if (opts.loadZoom)
		{
			this.loadZoom = opts.loadZoom;
		} else {
			var cookieZoom = parseInt(this.getCookie("loadZoom"));
			if (cookieZoom) {
				this.loadZoom = cookieZoom;
			} else if (typeof(opts.defaultZoom) != 'undefined') {
				this.loadZoom = opts.defaultZoom;
			} else {
				this.loadZoom = 5;
			}
		}
		// Use default map type if appropriate
		if (!this.loadType) {
			var cookieTypeNum = parseInt(this.getCookie("loadType"));
			if (cookieTypeNum) {
				this.loadType = this.map.getMapTypes()[cookieTypeNum];
			} else if (opts.defaultMapType) {
				this.loadType = opts.defaultMapType;
			} else {
				this.loadType = G_NORMAL_MAP;
			}
		} 

		if (this.loadLat && this.loadLon && typeof(this.loadZoom) != 'undefined' && !opts.cat) {
			this.map.setCenter(new GLatLng(this.loadLat, this.loadLon), this.loadZoom, this.loadType);
		} else {
			var request = GXmlHttp.create();
			var url = this.opts.linkDir + '/geo-query.php';
			if (opts.cat) {
				url += '?cat='+opts.cat;
			}
			request.open("GET", url, false);
			request.send(null);
			var posts = eval(request.responseText);
			if (posts.length>0) {
				var point = new GLatLng(posts[0].lat,posts[0].lng);
				this.map.setCenter(point,this.loadZoom,this.loadType);
			} else {
				this.map.setCenter(new GLatLng(0,0),this.loadZoom,this.loadType);
			}
		}

		if (opts.mapControl == 'GSmallZoomControl') {
			this.map.addControl(new GSmallZoomControl());
		} else if (opts.mapControl == 'GSmallMapControl') {
			this.map.addControl(new GSmallMapControl());
		} else if (opts.mapControl == 'GLargeMapControl') {
			this.map.addControl(new GLargeMapControl());
		}
		
		if (opts.addMapTypeControl) {
			this.map.addControl(new GMapTypeControl());
		}

		if (opts.addOverviewControl) {
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

		if (customizeGeoMashup != undefined) {
			customizeGeoMashup(this);
		}

	},
		
	setBackCookies : function() {
		var center = this.map.getCenter();
		var mapTypeNum = 0;
		for(var ix=0; ix<this.map.getMapTypes().length; ix++){
			if(this.map.getMapTypes()[ix]==this.map.getCurrentMapType())
				mapTypeNum=ix;
		}
		this.setCookie("loadType",mapTypeNum);
		this.setCookie("loadLat",center.lat());
		this.setCookie("loadLon",center.lng());
		this.setCookie("loadZoom",this.map.getZoom());
		return true;
	}

};

