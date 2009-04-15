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

/*global jQuery */
/*global google */

/*global GeoMashupLocation, GeoMashupAdmin */
var GeoMashupLocation, GeoMashupAdmin;

google.load( 'maps', '2' );

jQuery(document).ready( function () {
	jQuery('#geo-mashup-inline-help-link').click( function () {
		jQuery('#geo-mashup-inline-help').slideToggle('fast').click( function () {
			jQuery( this ).slideToggle('fast');
		} );
	} );
} );

GeoMashupLocation = function (init_data) {
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
			} else if (typeof data.name === 'string') { 
				this.id = '';
				this.title = data.name;
				this.geoname = data.name; 
				this.country_code = data.countryCode;
				this.admin_code = data.adminCode1;
				this.admin_name = data.adminName1;
				this.sub_admin_code = data.adminCode2;
				this.sub_admin_name = data.adminName2;
			} else if (typeof data.address === 'string') {
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
			}
		}
	};

	if (init_data) {
		this.set(init_data);
	}
};


GeoMashupAdmin = {
	
	registerMap : function(container, opts) {
		if (document.all&&window.attachEvent) { // IE-Win
			window.attachEvent("onload", function () { GeoMashupAdmin.createMap(container, opts); });
		  window.attachEvent("onunload", google.maps.Unload);
		} else if (window.addEventListener) { // Others
			window.addEventListener("load", function () { GeoMashupAdmin.createMap(container, opts); }, false);
			window.addEventListener("unload", google.maps.Unload, false);
		}
	},

	createMap : function(container, opts) {
		var location_name, selected, latlng;

		this.opts = opts;
		this.red_icon = new google.maps.Icon();
		this.red_icon.image = opts.link_url + '/images/mm_20_red.png';
		this.red_icon.shadow = opts.link_url + '/images/mm_20_shadow.png';
		this.red_icon.iconSize = new google.maps.Size(12, 20);
		this.red_icon.shadowSize = new google.maps.Size(22, 20);
		this.red_icon.iconAnchor = new google.maps.Point(6, 20);
		this.red_icon.infoWindowAnchor = new google.maps.Point(5, 1);
		this.green_icon = new google.maps.Icon(this.red_icon);
		this.green_icon.image = opts.link_url + '/images/mm_20_green.png';

		this.name_textbox = document.getElementById("geo_mashup_location_name");
		this.search_textbox = document.getElementById("geo_mashup_search");
		this.saved_select = document.getElementById("geo_mashup_select");
		this.location_input = document.getElementById("geo_mashup_location");
		this.location_id_input = document.getElementById("geo_mashup_location_id");
		this.geoname_input = document.getElementById("geo_mashup_geoname");
		this.address_input = document.getElementById("geo_mashup_address");
		this.postal_code_input = document.getElementById("geo_mashup_postal_code");
		this.country_code_input = document.getElementById("geo_mashup_country_code");
		this.admin_code_input = document.getElementById("geo_mashup_admin_code");
		this.admin_name_input = document.getElementById("geo_mashup_admin_name");
		this.sub_admin_code_input = document.getElementById("geo_mashup_sub_admin_code");
		this.sub_admin_name_input = document.getElementById("geo_mashup_sub_admin_name");
		this.locality_name_input = document.getElementById("geo_mashup_locality_name");
		this.changed_input = document.getElementById("geo_mashup_changed");

		for (location_name in this.opts.saved_locations)
		{
			if (typeof location_name === 'string')
			{
				selected = (this.opts.post_location_name === location_name);
				this.saved_select.options[this.saved_select.options.length] = new Option(location_name.replace(/\\/g,''),location_name,false,selected);
			}
		}

		this.map = new google.maps.Map2(container,{draggableCursor:'pointer'});
		this.map.setCenter(new google.maps.LatLng(0,0),1);
		this.map.addControl(new google.maps.LargeMapControl());
		this.map.addControl(new google.maps.MapTypeControl());
		this.map.enableContinuousZoom();

		if (opts.kml_url) {
			this.loadKml(opts.kml_url);
		}
		if (opts.post_lat && opts.post_lng) {
			latlng = new google.maps.LatLng(opts.post_lat, opts.post_lng);
			this.addSelectedMarker(latlng, { location_id : this.location_id_input.value, name : opts.post_location_name });
		}

		google.maps.Event.bind(this.map,'click',this,this.onclick);

	},
  
	onKmlLoad : function() {
		if (!(this.opts.post_lat && this.opts.post_lng)) {
			var latlng = this.kml.getDefaultCenter();
			this.addSelectedMarker(latlng, this.opts.post_location_name);
			this.search_textbox.value = latlng.lat() + ',' + latlng.lng();
		}
	},

	loadKml : function(kml_url) {
		this.kml = new google.maps.GeoXml(kml_url);
		google.maps.Event.bind(this.kml, 'load', this, this.onKmlLoad);
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
		var option, saved_location, latlng;

		if  (select.selectedIndex > 0) {
			option = select.options[select.selectedIndex];
			saved_location = this.opts.saved_locations[option.value];
			if (saved_location) {
				latlng = new google.maps.LatLng(saved_location.lat, saved_location.lng);
				this.addSelectedMarker(latlng, saved_location);
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

	addSelectedMarker : function(latlng, selection) {
		var marker = this.createMarker(latlng, new GeoMashupLocation(selection));
		this.map.addOverlay(marker);
		this.selectMarker(marker);
	},

	selectMarker : function(marker) {
		var selected_marker, deselected_marker;
		if (marker !== this.selected_marker) {
			deselected_marker = this.createMarker(this.selected_marker.getPoint(),this.selected_marker.geo_mashup_location);
			this.map.removeOverlay(this.selected_marker);
			this.map.addOverlay(deselected_marker);
			this.selected_marker = null;

			selected_marker = this.createMarker(marker.getPoint(),marker.geo_mashup_location);
			this.map.removeOverlay(marker);
			this.map.addOverlay(selected_marker);
			this.map.setCenter(selected_marker.getPoint());
		} else {
			this.map.setCenter(marker.getPoint());
		}
	},

	showGeoNames : function (data) {
		var i, result_latlng, marker;
		if (data) {
			for (i=0; i<data.totalResultsCount && i<100 && data.geonames[i]; i += 1) {
				result_latlng = new google.maps.LatLng(data.geonames[i].lat, data.geonames[i].lng);
				marker = this.createMarker(result_latlng, new GeoMashupLocation(data.geonames[i]));
				this.map.addOverlay(marker);
			}
			this.setBusy(false);
		}
	},

	searchKey : function(e, search_text) {
		var latlng, latlng_array, geocoder, saved_locations_key, saved_loc,
			geonames_request_url;
		if ((e.keyCode && e.keyCode === 13) || (e.which && e.which === 13))
		{
			// Enter key was hit - new search
			this.map.clearOverlays();
			this.selected_marker = null;
			this.location_input.value = '';
			this.changed_input.value = 'true';
			this.saved_select.selectedIndex = 0;
			if (search_text.match(/^[\-\d\.\s]*,[\-\d\.\s]*$/)) {
				// Coordinates
				latlng_array = search_text.split(',');
				latlng = new google.maps.LatLng(latlng_array[0],latlng_array[1]);
				this.addSelectedMarker(latlng);
			} else if (search_text.match(/\d/) || search_text.match(',')) {
				// Address
				geocoder = new google.maps.ClientGeocoder();
				this.setBusy(true);
				geocoder.getLocations(search_text, function (response) { GeoMashupAdmin.showAddresses(response); });
			} else {
				// Name
				saved_locations_key = search_text.replace("'","\\'");
				if (this.opts.saved_locations[saved_locations_key]) {
					// Saved location
					saved_loc = this.opts.saved_locations[saved_locations_key];
					latlng = new google.maps.LatLng(saved_loc.lat, saved_loc.lng);
					this.addSelectedMarker(latlng, saved_loc);
				} else if (search_text.length > 0) {
					// Location name search
					geonames_request_url = 'http://ws.geonames.org/search?type=json&maxRows=20&style=full&callback=?&name=' + 
						encodeURIComponent(search_text);
					jQuery.getJSON( geonames_request_url, function(data) { GeoMashupAdmin.showGeoNames( data ); } );
					this.setBusy(true);
				}
			}
			return false;
		}
		else
		{
			return true;
		}
	},

	setInputs : function (latlng, loc) {
		var latlng_string = latlng.lat() + ',' + latlng.lng();
		if ((this.location_id_input.value !== loc.id) || (this.location_input.value !== latlng_string)) {
			this.location_id_input.value = loc.id ? loc.id : '';
			this.location_input.value = latlng_string;
			this.geoname_input.value = loc.geoname;
			this.address_input.value = loc.address;
			this.postal_code_input.value = loc.postal_code;
			this.country_code_input.value = loc.country_code;
			this.admin_code_input.value = loc.admin_code;
			this.admin_name_input.value = loc.admin_name;
			this.sub_admin_code_input.value = loc.sub_admin_code;
			this.sub_admin_name_input.value = loc.sub_admin_name;
			this.locality_name_input.value = loc.locality_name;
			this.changed_input.value = 'true';
		}
	},

	createMarker : function(latlng, loc) {
		var marker, marker_opts = {title:loc.title};
		if (!this.selected_marker) {
			marker_opts.icon = this.green_icon;
			marker_opts.draggable = true;
		} else {
			marker_opts.icon = this.red_icon;
		}
		marker = new google.maps.Marker(latlng,marker_opts);
		marker.geo_mashup_location = loc;
		if (!this.selected_marker) {
			this.selected_marker = marker;
			this.map.setCenter(latlng);
			this.setInputs(latlng, loc);

			google.maps.Event.addListener(marker,'dragend',function () { 
				GeoMashupAdmin.setInputs(marker.getPoint(), new GeoMashupLocation());
				GeoMashupAdmin.map.setCenter(marker.getPoint());
			});
		}
		return marker;
	},

	showAddresses : function(response) {
		var i, latlng, marker;
		if (!response || response.Status.code !== 200) {
			alert('No locations found for that address');
		} else {
			for (i=0; i<response.Placemark.length && i<20 && response.Placemark[i]; i += 1) {
				latlng = new google.maps.LatLng(
					response.Placemark[i].Point.coordinates[1],
					response.Placemark[i].Point.coordinates[0]);
				marker = this.createMarker(latlng, new GeoMashupLocation(response.Placemark[i]));
				this.map.addOverlay(marker);
			}
			this.setBusy(false);
		}
	}
};

