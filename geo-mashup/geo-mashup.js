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

function GeoMashup() {}

GeoMashup.posts = new Array();
GeoMashup.locations = new Array();
GeoMashup.loading = false;
GeoMashup.firstLoad = true;

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

GeoMashup.renderRss = function (rss_doc) {
	var items = rss_doc.getElementsByTagName('item');
	if (items.length == 0) return false;
	var html = ['<div class="locationinfo">'];
		
	for (var i=0; i<items.length; i++) {
		var link = items[i].getElementsByTagName('link')[0].firstChild.nodeValue;
		var url = link;
		var onclick = 'GeoMashup.setBackCookies()';
		if (this.showPostHere) {
			onclick = 'GeoMashup.showPost(\''+url+'\')';
			url = '#geoPost';
		}
		html = html.concat(['<h2><a href="', url, '" onclick="', onclick, '">',
			items[i].getElementsByTagName('title')[0].firstChild.nodeValue,
			'</a></h2><p class="meta"><span class="blogdate">',
			items[i].getElementsByTagName('pubDate')[0].firstChild.nodeValue.substr(0,16),
			'</span>, ',
			items[i].getElementsByTagName('category')[0].firstChild.nodeValue,
			'</p>']);
		if (items.length == 1) {
			html = html.concat(['<p class="storycontent">',
				items[i].getElementsByTagName('description')[0].firstChild.nodeValue.replace('[...]',''),
				'<a href="',url,'" onclick="',onclick,'">[...]</a></p>']);
			if (this.showPostHere) { this.showPost(link); }
		}
	} 
	html.push('</div>');
	return html.join('');
}

GeoMashup.showPost = function (url) {
	if (this.showing_url == url) {
		return false;
	}
	this.showing_url = url;
	var request = new GXmlHttp.create();
	var geoPost = document.getElementById('geoPost');
	if (geoPost.firstChild) {
		geoPost.removeChild(geoPost.firstChild);
	}
	request.open('GET',url,true);
	request.onreadystatechange = function() {
		if (request.readyState == 4 && request.status == 200) {
			var node = document.createElement('div');
			node.innerHTML = request.responseText;
			var divs = node.getElementsByTagName('div');
			for (var i=0; i<divs.length; i++) {
				if (divs[i].className=='post') { 
					geoPost.appendChild(divs[i]);
					break;
				}
			}
		}
	}
	request.send(null);
}

// Create a marker whose info window displays the given number
GeoMashup.createMarker = function(point) {
	var marker = new GMarker(point);

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
			// Load all posts at the marker location
			for(var i=0; i<GeoMashup.locations[point].posts.length; i++) {
				var post_id = GeoMashup.locations[point].posts[i];
				if (!GeoMashup.locations[point].loaded[post_id]) {
					// The post RSS XML has not been loaded yet, request it
					var url = GeoMashup.rssUri + '?p=' + post_id;
					// Use a synchronous request to simplify multiple posts at a location
					request.open('GET', url, false);
					request.send(null);
					var xmlDoc = request.responseXML;
					if (!GeoMashup.locations[point].xmlDoc) {
						// This is the only post, use the XML as it is
						GeoMashup.locations[point].xmlDoc = xmlDoc;
					} else {
						// There are multiple posts here, append this one to the others
						var newItem = xmlDoc.getElementsByTagName("item")[0];
						var channel = GeoMashup.locations[point].xmlDoc.getElementsByTagName("channel")[0];
						if (channel && newItem) {
							channel.appendChild(newItem);
						}
					} 
					GeoMashup.locations[point].loaded[post_id] = true;
				} // end if not loaded
			} // end location posts loop
			GeoMashup.loading = false;
			var html = GeoMashup.renderRss(GeoMashup.locations[point].xmlDoc);
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
}

GeoMashup.checkDependencies = function () {
	if (typeof(GMap) == "undefined" || !GBrowserIsCompatible()) {
		this.container.innerHTML = '<p class="errormessage">' +
			'Sorry, the Google Maps script failed to load. Have you entered your ' +
			'<a href="http://maps.google.com/apis/maps/signup.html">API key</a> ' +
			'in the <a href="' + this.linkDir + 
			'../../../wp-admin/options-general.php?page=geo-mashup/geo-mashup.php">' +
			'Geo Mashup Options</a>?';
		throw "The Google Maps javascript didn't load.";
	}
}

GeoMashup.clickCenterMarker = function() {
  // If there's a marker at the center, click it
	var center = this.map.getCenter();
	if (this.locations[center]) {
		GEvent.trigger(this.locations[center].marker,"click");
	}
}

GeoMashup.loadMap = function() {
	this.container = document.getElementById("geoMashup");
	this.checkDependencies();
	this.map = new GMap2(this.container);
	GEvent.addListener(this.map, "moveend", function() {
		// Download markers from the blog and load it on the map. The format we
		// expect is:
		// <markers>
		//	<marker post_id="1" lat="37.441" lon="-122.141"/>
		//	<marker post_id="2" lat="37.322" lon="-121.213"/>
		// </markers>
		var request = GXmlHttp.create();
		var bounds = GeoMashup.map.getBounds();
		var url = GeoMashup.linkDir + '/geo-query.php?minlat=' +
			bounds.getSouthWest().lat() + '&minlon=' + bounds.getSouthWest().lng() + '&maxlat=' +
			bounds.getNorthEast().lat() + '&maxlon=' + bounds.getNorthEast().lng();
		request.open("GET", url, true);
		request.onreadystatechange = function() {
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
					GeoMashup.clickCenterMarker();
				}
			} // end readystate == 4
		} // end onreadystatechange function
		request.send(null);
	});

	this.loadType = G_NORMAL_MAP;
	if (!this.loadLat && !this.loadLon) {
		// look for load settings in cookies
		this.loadLat = this.getCookie("loadLat");
		this.loadLon = this.getCookie("loadLon");
		this.loadZoom = parseInt(this.getCookie("loadZoom"));
		var mapTypeNum = parseInt(this.getCookie("loadType"));
		this.loadType = this.map.getMapTypes()[mapTypeNum];
	}
	// Use default zoom level if appropriate
	if (!this.loadZoom && this.defaultZoom) {
		this.loadZoom = this.defaultZoom;
	} else {
		this.loadZoom = 5;
	}
	// Use default map type if appropriate
	if (!this.loadType && this.defaultMapType) {
		this.loadType = this.defaultMapType;
	} 

	if (this.loadLat && this.loadLon && this.loadZoom) {
		// Center on the last clicked marker
		this.map.setCenter(new GLatLng(this.loadLat, this.loadLon), this.loadZoom, this.loadType);
	} else {
		// Center the map on the most recent geo-tagged post
		var request = GXmlHttp.create();
		var url = this.linkDir + '/geo-query.php';
		request.open("GET", url, false);
		request.send(null);
		var xmlDoc = request.responseXML;
		var markers = xmlDoc.getElementsByTagName("marker");
		if (markers.length>0) {
			var point = new GLatLng(
				parseFloat(markers[0].getAttribute("lat")),
				parseFloat(markers[0].getAttribute("lon")));
			this.map.setCenter(point,this.loadZoom,this.loadType);
		}
	}

	// Add controls
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

} // end loadMap();
	
GeoMashup.setBackCookies = function() {
	// so when a post link is clicked you can go back to the spot
	// you left on the map
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

