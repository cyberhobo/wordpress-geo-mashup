/*
Geo Mashup - Adds a Google Maps mashup of blog posts geocoded with the Geo plugin. 
Copyright (c) 2005 Dylan Kuhn

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

GeoMashup.posts = new Array();
GeoMashup.locations = new Array();
GeoMashup.loading = false;
GeoMashup.firstLoad = true;

GeoMashup.log = function(message,color) {
	if (this.showLog) {
		GLog.write(message,color);
	}
}

GeoMashup.getCookie = function(NameOfCookie) { 
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
}

GeoMashup.setCookie = function(NameOfCookie, value) { 
	document.cookie = NameOfCookie + "=" + escape(value); 
}

GeoMashup.delCookie = function(NameOfCookie) { 
	if (this.getCookie(NameOfCookie)) {
		document.cookie = NameOfCookie + "=" +
			"; expires=Thu, 01-Jan-70 00:00:01 GMT";
	}
}

GeoMashup.getTagContent = function (parent, tag, default_value) {
	if (!default_value) {
		default_value = '';
	}
	var children = parent.getElementsByTagName(tag);
	if (children.length > 0 && children[0].firstChild) {
		return children[0].firstChild.nodeValue;
	} else {
		return default_value;
	}
}

GeoMashup.renderRss = function (rss_doc) {
	var items = rss_doc.getElementsByTagName('item');
	if (items.length == 0) return false;
	this.log('Render ' + items.length + ' items from RSS');
	var html = ['<div class="locationinfo">'];
		
	for (var i=0; i<items.length; i++) {
		var link = this.getTagContent(items[i],'link');
		var url = link;
		var onclick = 'GeoMashup.setBackCookies()';
		if (this.showPostHere) {
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
			if (this.showPostHere) { this.showPost(link); }
		}
	} 
	html.push('<\/div>');
	return html.join('');
}

GeoMashup.showPost = function (url) {
	this.log('Show post: ' + url);
	if (this.showing_url == url) {
		return false;
	}
	var geoPost = document.getElementById('geoPost');
	if (!geoPost) {
		this.showPostHere = false;
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
	}
	request.send(null);
}

GeoMashup.createMarker = function(point) {
	this.log('Create a marker at ' + point);
	var marker;
	if (this.marker_icon) {
		marker = new GMarker(point, this.marker_icon);
	} else {
		marker = new GMarker(point);
	}

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
			GeoMashup.log('Load all posts at ' + point);
			for(var i=0; i<GeoMashup.locations[point].posts.length; i++) {
				var post_id = GeoMashup.locations[point].posts[i];
				if (!GeoMashup.locations[point].loaded[post_id]) {
					GeoMashup.log('The XML for post ' + post_id + ' has not been loaded, request it');
					var url = GeoMashup.linkDir + '/geo-query.php?post_id=' + post_id;
					// Use a synchronous request to simplify multiple posts at a location
					request.open('GET', url, false);
					try {
						request.send(null);
						if (!GeoMashup.locations[point].xmlDoc) {
							GeoMashup.log('This is the only post here, use the XML as it is');
							GeoMashup.locations[point].xmlDoc = request.responseXML;
						} else {
							GeoMashup.log('There are multiple posts here, append this one to the others');
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
			marker.openInfoWindowHtml(html);
		} 
	}); // end marker infowindowopen

	GEvent.addListener(marker, 'infowindowclose', function() {
		GeoMashup.log('Closed the infowindow');
		var geoPost = document.getElementById('geoPost');
		if (geoPost && geoPost.firstChild) {
			geoPost.removeChild(geoPost.firstChild);
			GeoMashup.showing_url = '';
		}
	});

	return marker;
}

GeoMashup.checkDependencies = function () {
	this.log('Check browser compatibility');
	if (typeof(GMap) == "undefined" || !GBrowserIsCompatible()) {
		this.container.innerHTML = '<p class="errormessage">' +
			'Sorry, the Google Maps script failed to load. Have you entered your ' +
			'<a href="http://maps.google.com/apis/maps/signup.html">API key<\/a> ' +
			'in the <a href="' + this.linkDir + 
			'../../../wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">' +
			'Geo Mashup Options<\/a>?';
		throw "The Google Maps javascript didn't load.";
	}
}

GeoMashup.clickCenterMarker = function() {
	this.log('If there is a marker at the center, click it');
	var center = this.map.getCenter();
	if (this.locations[center]) {
		GEvent.trigger(this.locations[center].marker,"click");
	}
}

GeoMashup.loadMap = function() {
	this.container = document.getElementById("geoMashup");
	this.showing_url = '';
	this.checkDependencies();
	this.map = new GMap2(this.container);
	if (document.all&&window.attachEvent) { // IE-Win
			 window.attachEvent("onunload", GUnload);
	} else if (window.addEventListener) { // Others
			 window.addEventListener("unload", GUnload, false);
	}
	GEvent.addListener(this.map, "moveend", function() {
		GeoMashup.log('Moved the map, query for new visible locations');
		var request = GXmlHttp.create();
		var bounds = GeoMashup.map.getBounds();
		var url = GeoMashup.linkDir + '/geo-query.php?minlat=' +
			bounds.getSouthWest().lat() + '&minlon=' + bounds.getSouthWest().lng() + '&maxlat=' +
			bounds.getNorthEast().lat() + '&maxlon=' + bounds.getNorthEast().lng();
		if (GeoMashup.cat) {
			url += '&cat=' + GeoMashup.cat;
		}
		request.open("GET", url, true);
		request.onreadystatechange = function() {
			GeoMashup.log('Request state ' + request.readyState);
			if (request.readyState == 4) {
				var xmlDoc = request.responseXML;
				var markers = xmlDoc.getElementsByTagName("marker");
				for (var i = 0; i < markers.length; i++) {
					// Make a marker for each new post location
					var post_id = markers[i].getAttribute("post_id");
					if (!GeoMashup.posts[post_id]) {
						// This post has not yet been loaded
						var point = new GLatLng(
							parseFloat(markers[i].getAttribute("lat")),
							parseFloat(markers[i].getAttribute("lon")));
						if (!GeoMashup.locations[point]) {
							// There are no other posts yet at this point, create a marker
							GeoMashup.locations[point] = new Object();
							GeoMashup.locations[point].posts = new Array();
							GeoMashup.locations[point].posts.push(post_id);
							GeoMashup.locations[point].loaded = new Array();
							GeoMashup.posts[post_id] = true;
							var marker = GeoMashup.createMarker(point);
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
					if (GeoMashup.autoOpenInfoWindow) {
						GeoMashup.clickCenterMarker();
					}
				}
			} // end readystate == 4
		} // end onreadystatechange function
		request.send(null);
	});

	if (!this.loadLat && !this.loadLon) {
		this.log('Look for load settings in cookies');
		this.loadLat = this.getCookie("loadLat");
		this.loadLon = this.getCookie("loadLon");
	}
	if (typeof(this.loadZoom) == 'undefined') {
		var cookieZoom = parseInt(this.getCookie("loadZoom"));
		if (cookieZoom) {
			this.log('Zoom level ' + cookieZoom + ' from cookie');
			this.loadZoom = cookieZoom;
		} else if (typeof(this.defaultZoom) != 'undefined') {
			this.log('Zoom level ' + this.defaultZoom + ' from default');
			this.loadZoom = this.defaultZoom;
		} else {
			this.log('Zoom level 5, last resort');
			this.loadZoom = 5;
		}
	}
	// Use default map type if appropriate
	if (!this.loadType) {
		var cookieTypeNum = parseInt(this.getCookie("loadType"));
		if (cookieTypeNum) {
			this.log('Load type ' + cookieTypeNum + ' from cookie');
			this.loadType = this.map.getMapTypes()[cookieTypeNum];
		} else if (this.defaultMapType) {
			this.log('Load type ' + this.defaultMapType + ' from default');
			this.loadType = this.defaultMapType;
		} else {
			this.log('Load normal type, last resort');
			this.loadType = G_NORMAL_MAP;
		}
	} 

	if (this.loadLat && this.loadLon && typeof(this.loadZoom) != 'undefined') {
		this.log('Center map based on load settings');
		this.map.setCenter(new GLatLng(this.loadLat, this.loadLon), this.loadZoom, this.loadType);
	} else {
		this.log('Query the most recent geo-tagged post and center there');
		var request = GXmlHttp.create();
		var url = this.linkDir + '/geo-query.php';
		if (this.cat) {
			url += '?cat='+this.cat;
		}
		request.open("GET", url, false);
		request.send(null);
		var xmlDoc = request.responseXML;
		var markers = xmlDoc.getElementsByTagName("marker");
		if (markers.length>0) {
			var point = new GLatLng(
				parseFloat(markers[0].getAttribute("lat")),
				parseFloat(markers[0].getAttribute("lon")));
			this.map.setCenter(point,this.loadZoom,this.loadType);
		} else {
			this.log('No posts available - center at 0,0');
			this.map.setCenter(new GLatLng(0,0),this.loadZoom,this.loadType);
		}
	}

	this.log('Add controls');
	if (this.mapControl == 'GSmallZoomControl') {
		this.map.addControl(new GSmallZoomControl());
	} else if (this.mapControl == 'GSmallMapControl') {
		this.map.addControl(new GSmallMapControl());
	} else if (this.mapControl == 'GLargeMapControl') {
		this.map.addControl(new GLargeMapControl());
	}
	
	if (this.addMapTypeControl) {
		this.map.addControl(new GMapTypeControl());
	}

	if (this.addOverviewControl) {
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

	if (this.customizeMap) {
		this.log('The customizeMap user function exists, call it');
		this.customizeMap();
	}

} // end loadMap();
	
GeoMashup.setBackCookies = function() {
	this.log('Set cookies to remember map position');
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

