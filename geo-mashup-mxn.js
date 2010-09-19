/**
 * Mapstraction implementation for Geo Mashup maps.
 *
 * @package GeoMashup
 * @subpackage Client
 */

/*global GeoMashup */
/*global customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon */
/*glboal customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*global mxn */

GeoMashup.loadFullPost = function( point ) {
	var i, url, post_request, object_ids;

	this.getShowPostElement().innerHTML = '<div align="center"><img src="' +
		this.opts.url_path + '/images/busy_icon.gif" alt="Loading..." /></div>';
	object_ids = [];
	for(i=0; i<this.locations[point].objects.length; i += 1) {
		object_ids.push( this.locations[point].objects[i].object_id );
	}
	url = this.geo_query_url + '&object_name=' + this.opts.object_name +
		'&object_ids=' + object_ids.join( ',' ) + '&template=full-post';
	jQuery( this.getShowPostElement() ).load( url );
};

GeoMashup.createCategoryLine = function ( category ) {
	// Polylines are close, but the openlayers implementation at least cannot hide or remove a polyline
	/*
	category.line = new mxn.Polyline(category.points);
	this.doAction( 'categoryLine', this.opts, category.line );
	this.map.addPolylineWithData( category.line, { color: category.color, width: 5, opacity: 0.5 } );
	if (this.map.getZoom() > category.max_line_zoom) {
		try {
			category.line.hide();
		} catch( e ) {
			// Not implemented?
			this.map.removePolyline( category.line );
		}
	}
	*/
};

GeoMashup.openInfoWindow = function( marker ) {
	var object_ids = [], i, object_element, point = marker.location;

	if ( this.locations[point].loaded ) {
		marker.openBubble();
	} else {
		marker.setInfoBubble( '<div align="center"><img src="' + this.opts.url_path + 
			'/images/busy_icon.gif" alt="Loading..." /></div>' );
		marker.openBubble();
		// Collect object ids
		for( i = 0; i < this.locations[point].objects.length; i += 1 ) {
			object_ids.push( this.locations[point].objects[i].object_id );
		}
		// Do an AJAX query to get content for these objects
		jQuery.get( 
			this.geo_query_url, 
			{ object_name: this.opts.object_name, object_ids: object_ids.join(',') },
			function( content ) {
				marker.closeBubble();
				marker.setInfoBubble( GeoMashup.parentizeLinksMarkup( content ) );
				marker.openBubble();
			}
		); 
	}
};

GeoMashup.addGlowMarker = function( marker ) {
	var point = marker.location, 
		glow_options = {
			clickable : false,
			icon : this.opts.url_path + '/images/mm_20_glow.png',
			iconSize : [ 22, 30 ],
			iconAnchor : [ 11, 27 ] 
		};

	if ( this.glow_marker ) {
		this.glow_marker.hide();
		this.map.removeMarker( this.glow_marker );
		this.glow_marker = null;
	} 
	this.doAction( 'glowMarkerIcon', this.opts, glow_options );
	this.glow_marker = new mxn.Marker( point );
	this.glow_marker.addData( glow_options );
	this.map.addMarker( this.glow_marker );
};

GeoMashup.removeGlowMarker = function( marker ) {
};

GeoMashup.hideAttachments = function() {
	var i, j, obj;

	/* No removeOverlay (yet)
	for ( i = 0; i < this.open_attachments.length; i += 1 ) {
		this.map.removeOverlay( this.open_attachments[i] );
	} 
	this.open_attachments = [];
	*/
};

GeoMashup.showMarkerAttachments = function( marker ) {
	var i, j, obj, point = marker.location;

	this.hideAttachments();
	for ( i = 0; i < this.locations[point].objects.length; i += 1 ) {
		obj = this.locations[point].objects[i];
		if ( obj.attachment_urls && obj.attachment_urls.length > 0 ) {
			// There are attachments to load
			for ( j = 0; j < obj.attachment_urls.length; j += 1 ) {
				this.open_attachments.push( obj.attachment_urls[j] );
				this.map.addOverlay( obj.attachment_urls[j] );
			}
		}
	}
};

GeoMashup.addObjectIcon = function( obj ) {
	if (typeof customGeoMashupCategoryIcon === 'function') {
		obj.icon = customGeoMashupCategoryIcon(this.opts, obj.categories);
	} 
	if (!obj.icon) {
		if (obj.categories.length > 1) {
			obj.icon = this.clone(this.multiple_category_icon);
		} else if (obj.categories.length === 1) {
			obj.icon = this.clone(this.categories[obj.categories[0]].icon);
		} else {
			obj.icon = this.colorIcon( 'red' );
		} 
		this.doAction( 'objectIcon', this.opts, obj );
	}
};

GeoMashup.createMarker = function(point,obj) {
	var marker, marker_opts;

	if ( !obj.icon ) {
		this.addObjectIcon( obj );
	}
	marker_opts = {
		label: obj.title, 
		icon: obj.icon.image,
		iconSize: obj.icon.iconSize,
		iconShadow: obj.icon.iconShadow,
		iconAnchor: obj.icon.iconAnchor,
		iconShadowSize: obj.icon.shadowSize,
		visible: true
	};
	this.doAction( 'objectMarkerOptions', this.opts, marker_opts, obj );
	marker = new mxn.Marker( point );
	marker.addData( marker_opts );

	marker.click.addHandler( function() {
		GeoMashup.selectMarker( marker, point );
	} ); 

	this.doAction( 'marker', this.opts, marker );

	return marker;
};

GeoMashup.clickObjectMarker = function( object_id, try_count ) {
	var obj = this.objects[object_id];
	if (typeof try_count === 'undefined') {
		try_count = 1;
	}
	if ( obj && obj.marker && try_count < 4 ) {
		// openlayers/mxn seems to have trouble displaying an infobubble right away
		if ( try_count < 2 ) {
			try_count += 1;
			setTimeout(function () { GeoMashup.clickObjectMarker(object_id, try_count); }, 1000);
		} else {
			obj.marker.click.fire();
		}
	}
};

GeoMashup.colorIcon = function( color_name ) {
	var icon = this.clone( this.base_color_icon );
	icon.image = this.opts.url_path + '/images/mm_20_' + color_name + '.png';
	return icon;
};

GeoMashup.hideMarker = function( marker ) {
	marker.hide();
};

GeoMashup.showMarker = function( marker ) {
	marker.show();
};

GeoMashup.hideLine = function( line ) {
	try {
		line.hide();
	} catch( e ) {
		this.map.removePolyline( line );
	}
};

GeoMashup.showLine = function( line ) {
	try {
		line.show();
	} catch( e ) {
		this.map.addPolyline( line );
	}
};

GeoMashup.newLatLng = function( lat, lng ) {
	return new mxn.LatLonPoint( lat, lng );
};

GeoMashup.extendLocationBounds = function( latlng ) {
	this.location_bounds.extend( latlng );
};

GeoMashup.addMarkers = function( markers ) {
	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
	} );
};

GeoMashup.makeMarkerMultiple = function( marker ) {
	var plus_image;
	if (typeof customGeoMashupMultiplePostImage === 'function') {
		plus_image = customGeoMashupMultiplePostImage(this.opts, marker);
	}
	if (!plus_image) {
		plus_image = this.opts.url_path + '/images/mm_20_plus.png';
	}
	marker.setIcon( plus_image );
	this.doAction( 'multiObjectMarker', this.opts, marker );
	this.doAction( 'multiObjectIcon', this.opts, plus_image );
};

GeoMashup.autoZoom = function() {
	this.map.autoCenterAndZoom();
};

GeoMashup.isMarkerVisible = function( marker ) {
	var map_bounds = this.map.getBounds();
	if ( ! map_bounds ) {
		// No bounds available yet, assume all markers are visible
		return true;
	}
	return ( marker.getAttribute( 'visible' ) && map_bounds && map_bounds.contains( marker.location ) ); 
};

GeoMashup.centerMarker = function( marker, zoom ) {
	if ( typeof zoom === 'number' ) {
		this.map.setCenterAndZoom( marker.location, zoom );
	} else {
		this.map.setCenter( marker.location, {}, true );
	}
};

GeoMashup.createMap = function(container, opts) {
	var i, type_num, center_latlng, map_opts, map_types, request, url, objects, point, marker_opts, 
		clusterer_opts, single_marker, ov, credit_div, initial_zoom = 1, controls = {};

	this.container = container;
	this.base_color_icon = {};
	this.base_color_icon.image = opts.url_path + '/images/mm_20_black.png';
	this.base_color_icon.shadow = opts.url_path + '/images/mm_20_shadow.png';
	this.base_color_icon.iconSize = [12, 20];
	this.base_color_icon.shadowSize =  [22, 20];
	this.base_color_icon.iconAnchor = [6, 20];
	this.base_color_icon.infoWindowAnchor = [5, 1];
	this.multiple_category_icon = this.clone( this.base_color_icon );
	this.multiple_category_icon.image = opts.url_path + '/images/mm_20_mixed.png';

	// Falsify options to make tests simpler
	this.forEach( opts, function( key, value ) {
		if ( 'false' === value || 'FALSE' === value ) {
			opts[key] = false;
		}
	} );

	// For now, the map name is always the iframe name
	opts.name = window.name;

	// For now, siteurl is the home url
	opts.home_url = opts.siteurl;

	map_types = {
		'G_NORMAL_MAP' : mxn.Mapstraction.ROAD,
		'G_SATELLITE_MAP' : mxn.Mapstraction.SATELLITE,
		'G_HYBRID_MAP' : mxn.Mapstraction.HYBRID,
		'G_PHYSICAL_MAP' : mxn.Mapstraction.PHYSICAL
	};

	if (typeof opts.map_type === 'string') {
		if ( map_types[opts.map_type] ) {
			opts.map_type = map_types[opts.map_type] ;
		} else {
			type_num = parseInt(opts.map_type, 10);
			if ( isNaN(type_num) || type_num > 2 ) {
				opts.map_type = map_types['G_NORMAL_MAP'];
			} else {
				opts.map_type = type_num;
			}
		}
	} else if (typeof opts.map_type === 'undefined') {
		opts.map_type = map_types['G_NORMAL_MAP'];
	}
	/* 
	map_opts = {
		backgroundColor : '#' + opts.background_color,
		mapTypes : [ opts.map_type ],
		googleBarOptions : { 
			adsOptions : { client : ( opts.adsense_code ) ? opts.adsense_code : 'pub-5088093001880917' }	
		}
	};
	this.doAction( 'mapOptions', opts, map_opts );
	*/
	this.map = new mxn.Mapstraction( this.container, opts.map_api );
	this.map.setCenterAndZoom(new mxn.LatLonPoint(0,0), 0);

	this.doAction( 'newMap', opts, this.map );

	// Create the loading spinner icon and show it
	this.spinner_div = document.createElement( 'div' );
	this.spinner_div.innerHTML = '<div id="gm-loading-icon" style="-moz-user-select: none; z-index: 100; position: absolute; left: ' +
		( jQuery(this.container).width() / 2 ) + 'px; top: ' + ( jQuery(this.container).height() / 2 ) + 'px;">' +
		'<img style="border: 0px none ; margin: 0px; padding: 0px; width: 16px; height: 16px; -moz-user-select: none;" src="' +
		opts.url_path + '/images/busy_icon.gif"/></a></div>';
	this.showLoadingIcon();
	this.map.load.addHandler( function() { GeoMashup.hideLoadingIcon(); } );

	if (!opts.object_name) {
		opts.object_name = 'post';
	}
	this.opts = opts;
	this.geo_query_url = opts.siteurl + '?geo_mashup_content=geo-query&_wpnonce=' + opts.nonce;


	// TODO: Try to deleselect markers with clicks? Need to make sure we don't get other object's clicks.
	this.map.changeZoom.addHandler( function( old_zoom, new_zoom ) { GeoMashup.adjustZoom( old_zoom, new_zoom ); } );
	this.map.endPan.addHandler( function() { GeoMashup.adjustViewport(); } );

	// No clustering available

	if ( opts.zoom !== 'auto' && typeof opts.zoom === 'string' ) {
		initial_zoom = parseInt(opts.zoom, 10);
	} else {
		initial_zoom = opts.zoom;
	}

	if (opts.load_kml) {
		try {
			if ( initial_zoom === 'auto' ) {
				this.map.addOverlay( this.kml, true );
			} else {
				this.map.addOverlay( this.kml );
			}
		} catch (e) {
			// Probably not implemented
		}
	}

	this.buildCategoryHierarchy();

	try {
		this.map.setMapType( opts.map_type );
	} catch (e) {
		// Probably not implemented
	}
	if ( initial_zoom === 'auto' ) {
		// Wait to center and zoom after loading
	} else if (opts.center_lat && opts.center_lng) {
		// Use the center from options
		this.map.setCenterAndZoom(new mxn.LatLonPoint( parseFloat( opts.center_lat ), parseFloat( opts.center_lng ) ), initial_zoom );
	} else if (opts.object_data && opts.object_data.objects[0]) {
		center_latlng = new mxn.LatLonPoint( parseFloat( opts.object_data.objects[0].lat ), parseFloat( opts.object_data.objects[0].lng ) );
		this.map.setCenterAndZoom( center_latlng, initial_zoom );
	} else {
		// TODO: change to jquery xmlhttp
		// Center on the most recent located object
		/*
		request = google.maps.XmlHttp.create();
		url = this.geo_query_url + '&limit=1';
		if (opts.map_cat) {
			url += '&cat='+opts.map_cat;
		}
		request.open("GET", url, false);
		request.send(null);
		objects = window['eval']( '(' + request.responseText + ')' );
		if (objects.length>0) {
			point = new google.maps.LatLng(objects[0].lat,objects[0].lng);
			this.map.setCenter(point,initial_zoom,opts.map_type);
		} else {
			this.map.setCenter(new google.maps.LatLng(0,0),initial_zoom,opts.map_type);
		}
		*/
	}

	this.location_bounds = new mxn.BoundingBox( new mxn.LatLonPoint( 0, 0 ), new mxn.LatLonPoint( 0, 0 ) );

	if (opts.map_content === 'single')
	{
		if (opts.center_lat && opts.center_lng && !this.kml)
		{
			marker_opts = {};
			if (typeof customGeoMashupSinglePostIcon === 'function') {
				marker_opts.icon = customGeoMashupSinglePostIcon(this.opts);
			}
			if ( !marker_opts.icon ) {
				marker_opts.icon = this.clone( this.base_color_icon );
			}
			this.doAction( 'singleMarkerOptions', this.opts, marker_opts );
			single_marker = new mxn.Marker(
				new mxn.LatLonPoint( parseFloat( this.opts.center_lat ), parseFloat( this.opts.center_lng ) )
			);
			this.map.addOverlay( single_marker );
			this.doAction( 'singleMarker', this.opts, single_marker );
		}
	} else if (opts.object_data) {
		this.addObjects(opts.object_data.objects,true);
	} else {
		// Request objects near visible range first
		this.requestObjects(true);

		// Request all objects
		this.requestObjects(false);
	}

	if ('GSmallZoomControl' === opts.map_control || 'GSmallZoomControl3D' === opts.map_control) {
		controls.zoom = 'small';
	} else if ('GSmallMapControl' === opts.map_control) {
		controls.zoom = 'small';
		controls.pan = true;
	} else if ('GLargeMapControl' === opts.map_control || 'GLargeMapControl3D' === opts.map_control) {
		controls.zoom = 'large';
		controls.pan = true;
	}

	if (opts.add_map_type_control ) {
		controls.map_type = true;
	}

	if (opts.add_overview_control) {
		controls.overview = true;
	}
	this.map.addControls( controls );

	if (typeof customizeGeoMashupMap === 'function') {
		customizeGeoMashupMap(this.opts, this.map);
	}
	if (typeof customizeGeoMashup === 'function') {
		customizeGeoMashup(this);
	}
	this.doAction( 'loadedMap', this.opts, this.map );

};
