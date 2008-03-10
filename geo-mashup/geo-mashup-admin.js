/*
Geo Mashup Admin 
Copyright (c) 2006-2007 Dylan Kuhn

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

var GeoMashupAdmin = {
	
	registerMap : function(container, opts) {
		if (document.all&&window.attachEvent) { // IE-Win
			window.attachEvent("onload", function () { GeoMashupAdmin.createMap(container, opts); });
		  window.attachEvent("onunload", GUnload);
		} else if (window.addEventListener) { // Others
			window.addEventListener("load", function () { GeoMashupAdmin.createMap(container, opts); }, false);
			window.addEventListener("unload", GUnload, false);
		}
	},

	createMap : function(container, opts) {
		this.opts = opts;
		this.red_icon = new GIcon();
		this.red_icon.image = opts.link_url + '/images/mm_20_red.png';
		this.red_icon.shadow = opts.link_url + '/images/mm_20_shadow.png';
		this.red_icon.iconSize = new GSize(12, 20);
		this.red_icon.shadowSize = new GSize(22, 20);
		this.red_icon.iconAnchor = new GPoint(6, 20);
		this.red_icon.infoWindowAnchor = new GPoint(5, 1);
		this.green_icon = new GIcon(this.red_icon);
		this.green_icon.image = opts.link_url + '/images/mm_20_green.png';

		this.name_textbox = document.getElementById("geo_mashup_location_name");
		this.search_textbox = document.getElementById("geo_mashup_search");
		this.saved_select = document.getElementById("geo_mashup_select");
		this.location_input = document.getElementById("geo_mashup_location");

		for each (saved_location in this.opts.saved_locations)
		{
			var selected = (this.opts.post_location_name == saved_location.name);
			this.saved_select.add(new Option(saved_location.name.replace(/\\/g,''),saved_location.name,false,selected),null);
		}

		this.map = new GMap2(container,{draggableCursor:'pointer'});
		this.map.setCenter(new GLatLng(0,0),1);
		this.map.addControl(new GLargeMapControl());
		this.map.addControl(new GMapTypeControl());
		this.map.enableContinuousZoom();

		if (opts.kml_url) {
			this.loadKml(opts.kml_url);
		}
		if (opts.post_lat && opts.post_lng) {
			var latlng = new GLatLng(opts.post_lat, opts.post_lng);
			this.addSelectedMarker(latlng,opts.post_location_name);
		}

		GEvent.bind(this.map,'click',this,this.onclick);
	},
  
	onKmlLoad : function() {
		if (!(this.opts.post_lat && this.opts.post_lng)) {
			var latlng = this.kml.getDefaultCenter();
			this.addSelectedMarker(latlng, this.opts.post_location_name);
			this.search_textbox.value = latlng.lat() + ',' + latlng.lng();
		}
	},

	loadKml : function(kml_url) {
		this.kml = new GGeoXml(kml_url, function () { GeoMashupAdmin.onKmlLoad(); });
		this.map.addOverlay(this.kml);
	},

	onclick : function(overlay, latlng) {
		if (overlay) {
			this.selectMarker(overlay);
		} else if (latlng) {
			this.addSelectedMarker(latlng);
			this.search_textbox.value = latlng.lat() + ',' + latlng.lng();
		}
	},
  
	onSelectChange : function(select) {
		if  (select.selectedIndex > 0) {
			var option = select.options[select.selectedIndex];
			var saved_location = this.opts.saved_locations[option.value];
			if (saved_location) {
				var latlng = new GLatLng(saved_location.lat, saved_location.lng);
				this.addSelectedMarker(latlng, saved_location.name);
			}
		}
  },

	setBusy : function(is_busy) {
		if (is_busy) {
			this.opts.status_icon.src = this.opts.link_url + '/images/busy_icon.gif';
		} else {
			this.opts.status_icon.src = this.opts.link_url + '/images/idle_icon.gif';
		}
	},

	addSelectedMarker : function(latlng, name) {
		var marker = this.createMarker(latlng, name);
		this.map.addOverlay(marker);
		this.selectMarker(marker);

		if (!name || name.length == 0) {
			var geonames_request_url = 'http://ws.geonames.org/findNearbyPlaceNameJSON?callback=GeoMashupAdmin.suggestName&lat=' +
				latlng.lat() + '&lng=' + latlng.lng();
			var jsonRequest = new JSONscriptRequest(geonames_request_url);
			this.setBusy(true);
			jsonRequest.buildScriptTag();
			jsonRequest.addScriptTag();
			this.geoNamesRequest = jsonRequest;
		}
	},

	selectMarker : function(marker) {
		if (marker != this.selected_marker) {
			var deselected_marker = this.createMarker(this.selected_marker.getPoint(),this.selected_marker.location_name);
			this.map.removeOverlay(this.selected_marker);
			this.map.addOverlay(deselected_marker);
			this.selected_marker = null;

			var selected_marker = this.createMarker(marker.getPoint(),marker.location_name);
			this.map.removeOverlay(marker);
			this.map.addOverlay(selected_marker);
			this.map.setCenter(selected_marker.getPoint());
		} else {
			this.map.setCenter(marker.getPoint());
		}
	},

	searchKey : function(e, search_text) {
		if ((e.keyCode && e.keyCode == 13) || (e.which && e.which == 13))
		{
			// Enter key was hit - new search
			this.map.clearOverlays();
			this.selected_marker = null;
			this.location_input.value = '';
			if (search_text.match(/^[-\d\.\s]*,[-\d\.\s]*$/)) {
				// Coordinates
				var latlng_array = search_text.split(',');
				var latlng = new GLatLng(latlng_array[0],latlng_array[1]);
				this.addSelectedMarker(latlng);
			} else if (search_text.match(/\d/) || search_text.match(',')) {
				// Address
				var geocoder = new GClientGeocoder();
				this.setBusy(true);
				geocoder.getLocations(search_text, function (response) { GeoMashupAdmin.showAddresses(response); });
			} else {
				// Name
				var saved_locations_key = search_text.replace("'","\\'");
				if (this.opts.saved_locations[saved_locations_key]) {
					// Saved location
					var latlng = new GLatLng(this.opts.saved_locations[saved_locations_key].lat,this.opts.saved_locations[saved_locations_key].lng);
					this.addSelectedMarker(latlng,search_text);
				} else {
					// Location name search
					var geonames_request_url = 'http://ws.geonames.org/search?type=json&callback=GeoMashupAdmin.showGeoNames&name=' + 
						encodeURIComponent(search_text);
					var jsonRequest = new JSONscriptRequest(geonames_request_url);
					this.setBusy(true);
					jsonRequest.buildScriptTag();
					jsonRequest.addScriptTag();
					this.geoNamesRequest = jsonRequest;
				}
			}
			return false;
		}
		else
		{
			return true;
		}
	},

	createMarker : function(latlng, name) {
		var marker_opts = {title:name};
		if (!this.selected_marker) {
			marker_opts.icon = this.green_icon;
			marker_opts.draggable = true;
		} else {
			marker_opts.icon = this.red_icon;
		}
		var marker = new GMarker(latlng,marker_opts);
		marker.location_name = name;
		if (!this.selected_marker) {
			this.selected_marker = marker;
			this.map.setCenter(latlng);
			this.location_input.value = latlng.lat() + ',' + latlng.lng();
			GEvent.addListener(marker,'dragend',function () { 
				GeoMashupAdmin.location_input.value = marker.getPoint().lat() + ',' + marker.getPoint().lng();
				GeoMashupAdmin.map.setCenter(marker.getPoint());
			});
		}
		return marker;
	},

	showGeoNames : function(data) {
		if (data)
		{
			for (var i=0; i<data.totalResultsCount && i<100; ++i) {
				var result_latlng = new GLatLng(data.geonames[i].lat, data.geonames[i].lng);
				var marker = this.createMarker(result_latlng,data.geonames[i].name);
				this.map.addOverlay(marker);
			}
			this.geoNamesRequest.removeScriptTag();
			this.setBusy(false);
		}
	},

	showAddresses : function(response) {
		if (!response || response.Status.code != 200) {
			alert('No locations found for that address');
		} else {
			for (var i=0; i<response.Placemark.length && i<100; ++i) {
				var latlng = new GLatLng(response.Placemark[i].Point.coordinates[1],
																 response.Placemark[i].Point.coordinates[0]);
				var marker = this.createMarker(latlng, response.Placemark[i].address);
				this.map.addOverlay(marker);
			}
			this.setBusy(false);
		}
	},

	suggestName : function(data) {
		if (data)
		{
			var temp_marker = this.selected_marker;
			this.selected_marker = null;
			var replace_marker = this.createMarker(temp_marker.getPoint(), data.geonames[0].name);
			this.map.removeOverlay(temp_marker);
			this.map.addOverlay(replace_marker);
		}
		this.geoNamesRequest.removeScriptTag();
		this.setBusy(false);
	}

};

