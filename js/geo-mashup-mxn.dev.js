/**
 * Mapstraction implementation for Geo Mashup maps.
 * @fileOverview
 */

/**
 * @name AjaxRequestOptions
 * @class This type represents options used for an AJAX request.
 * It has no constructor, but is instantiated as an object literal.
 *
 * @property {String} url The AJAX request URL.
 */

/**
 * @name ContentFilter
 * @class This type represents objects used to filter content.
 * It has no constructor, but is instantiated as an object literal.
 * 
 * @name ContentFilter#content
 * @property {String} content HTML content to filter.
 */

/*global GeoMashup */
/*global customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon */
/*global customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*global jQuery, mxn */
/*jslint browser: true, white: true, sloppy: true */

GeoMashup.loadFullPost = function( point ) {
	var i, request, cache, objects, object_ids;

	objects = this.getObjectsAtLocation( point );
	object_ids = this.getOnObjectIDs( objects );
	cache = this.locationCache( point, 'full-post-' + object_ids.join(',') );
	if ( cache.html ) {

		this.getShowPostElement().innerHTML = cache.html;

	} else {

		this.getShowPostElement().innerHTML = '<div align="center"><img src="' +
			this.opts.url_path + '/images/busy_icon.gif" alt="Loading..." /></div>';
		request = {
			url: this.geo_query_url + '&object_name=' + this.opts.object_name +
				'&object_ids=' + object_ids.join( ',' ) + '&template=full-post'
		};
		/**
		 * Requesting full post content.
		 * @name GeoMashup#fullPostRequest
		 * @event
		 * @param {Array} objects Objects included in the request
		 * @param {AjaxRequestOptions} options
		 */
		this.doAction( 'fullPostRequest', objects, request );
		jQuery.get( request.url, function( content ) {
			var filter = {content: content};
			/**
			 * Loading full post content.
			 * @name GeoMashup#fullPostLoad
			 * @event
			 * @param {Array} objects Objects included in the request
			 * @param {ContentFilter} filter
			 */
			GeoMashup.doAction( 'fullPostLoad', objects, filter );
			cache.html = filter.content;
			jQuery( GeoMashup.getShowPostElement() ).html( filter.content );
			/**
			 * The full post display has changed.
			 * @name GeoMashup#fullPostChanged
			 * @event
			 */
			GeoMashup.doAction( 'fullPostChanged' );
		} );
	}
};

GeoMashup.createTermLine = function ( term_data ) {

	// Polylines are close, but the openlayers implementation at least cannot hide or remove a polyline
	var options = {color: term_data.color, width: 5, opacity: 0.5};

	term_data.line = new mxn.Polyline(term_data.points);
	/**
	 * A term line was created.
	 * @name GeoMashup#termLine
	 * @event
	 * @param {Polyline} line
	 */
	this.doAction( 'termLine', term_data.line );
	/**
	 * A category line was created.
	 * @name GeoMashup#categoryLine
	 * @event
	 * @deprecated Use GeoMashup#termLine
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Polyline} line
	 */
	this.doAction( 'categoryLine', this.opts, term_data.line );

	/**
	 * A term line will be added with the given options.
	 * @name GeoMashup#termLineOptions
	 * @event
	 * @deprecated Use GeoMashup#termLineOptions
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Object} options Modifiable <a href="http://mapstraction.github.com/mxn/build/latest/docs/symbols/mxn.Polyline.html#addData">Mapstraction</a>
	 *   or <a href="http://code.google.com/apis/maps/documentation/javascript/v2/reference.html#GPolylineOptions">Google</a> Polyline options
	 */
	this.doAction( 'termLineOptions', this.opts, options );

	this.map.addPolylineWithData( term_data.line, options );

	if (this.map.getZoom() > term_data.max_line_zoom) {
		try {
			term_data.line.hide();
		} catch( e ) {
			// Not implemented?
			this.map.removePolyline( term_data.line );
		}
	}
};

GeoMashup.openInfoWindow = function( marker ) {
	var request, cache, object_ids, i, object_element, point = marker.location;

	if ( this.open_window_marker && !this.opts.multiple_info_windows ) {
		this.open_window_marker.closeBubble();
	}
	object_ids = this.getOnObjectIDs( this.getObjectsAtLocation( point ) );
	cache = this.locationCache( point, 'info-window-' + object_ids.join(',') );
	if ( cache.html ) {
		marker.setInfoBubble( cache.html );
		marker.openBubble();
	} else {
		marker.setInfoBubble( '<div align="center"><img src="' + this.opts.url_path + 
			'/images/busy_icon.gif" alt="Loading..." /></div>' );
		marker.openBubble();
		this.open_window_marker = marker;
		// Collect object ids
		// Do an AJAX query to get content for these objects
		request = {
			url: this.geo_query_url + '&object_name=' + this.opts.object_name +
				'&object_ids=' + object_ids.join( ',' ) 
		};
		/** 
		 * A marker's info window content is being requested.
		 * @name GeoMashup#markerInfoWindowRequest
		 * @event
		 * @param {Marker} marker
		 * @param {AjaxRequestOptions} request Modifiable property: url
		 */
		this.doAction( 'markerInfoWindowRequest', marker, request );
		jQuery.get( 
			request.url,
			function( content ) {
				var filter = {content: content};
				marker.closeBubble();
				/**
				 * A marker info window content is being loaded.
				 * @name GeoMashup#markerInfoWindowLoad
				 * @event
				 * @param {Marker} marker
				 * @param {ContentFilter} filter Modifiable property: content
				 */
				GeoMashup.doAction( 'markerInfoWindowLoad', marker, filter );
				cache.html = GeoMashup.parentizeLinksMarkup( filter.content );
				marker.setInfoBubble( cache.html );
				marker.openBubble();
			}
		); 
	}
};

GeoMashup.closeInfoWindow = function( marker ) {
	marker.closeBubble();
};

GeoMashup.addGlowMarker = function( marker ) {
	var point = marker.location, 
		glow_options = {
			clickable : true,
			icon : this.opts.url_path + '/images/mm_20_glow.png',
			iconSize : [ 22, 30 ],
			iconAnchor : [ 11, 27 ] 
		};

	if ( this.glow_marker ) {
		this.removeGlowMarker();
	} 
	/** 
	 * A highlight "glow" marker is being created.
	 * @name GeoMashup#glowMarkerIcon
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Object} glow_options Modifiable <a href="http://mapstraction.github.com/mxn/build/latest/docs/symbols/mxn.Marker.html#addData">Mapstraction</a> 
	 *   or <a href="http://code.google.com/apis/maps/documentation/javascript/v2/reference.html#GMarkerOptions">Google</a> marker options
	 */
	this.doAction( 'glowMarkerIcon', this.opts, glow_options );
	this.glow_marker = new mxn.Marker( point );
	this.glow_marker.addData( glow_options );
	this.glow_marker.click.addHandler( function() {
		GeoMashup.deselectMarker();
	} );
	this.map.addMarker( this.glow_marker );
};

GeoMashup.removeGlowMarker = function() {
	if ( this.glow_marker ) {
		this.glow_marker.hide();
		this.map.removeMarker( this.glow_marker );
		this.glow_marker = null;
	}
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
	var object_ids, uncached_ids = [];

	this.hideAttachments(); // check support
	object_ids = this.getOnObjectIDs( this.getMarkerObjects( marker ) );
	jQuery.each( object_ids, function( i, id ) {
		var cached_attachments = GeoMashup.locationCache( marker.location, 'attachments-' + id );
		if ( cached_attachments.urls ) {
			jQuery.each( cached_attachments.urls, function( j, url ) {
				GeoMashup.open_attachments.push( url );
				GeoMashup.map.addOverlay( url );
			} );
		} else {
			uncached_ids.push( id );
		}
	} );
	// Request any uncached attachments
	jQuery.each( uncached_ids, function( i, id ) {
		var ajax_params = {action: 'geo_mashup_kml_attachments'};
		ajax_params.post_ids = id;
		jQuery.getJSON( GeoMashup.opts.ajaxurl + '?callback=?', ajax_params, function( data ) {
			var cached_attachments = GeoMashup.locationCache( marker.location, 'attachments-' + id );
			if ( !cached_attachments.urls ) {
				cached_attachments.urls = [];
			}
			jQuery.each( data, function( j, url ) {
				cached_attachments.urls.push( url );
				GeoMashup.open_attachments.push( url );
				GeoMashup.map.addOverlay( url );
			} );
		} );
	} );
};

GeoMashup.addObjectIcon = function( obj ) {

	// Back compat
	if ( typeof customGeoMashupCategoryIcon === 'function' && obj.terms && obj.terms.hasOwnProperty( 'category' ) ) {
		obj.icon = customGeoMashupCategoryIcon( GeoMashup.opts, obj.terms.category );
	} 

	if ( !obj.icon ) {

		jQuery.each( obj.terms, function( taxonomy, terms ) {
			var single_icon;

			if ( terms.length > 1) {

				obj.icon = GeoMashup.clone( GeoMashup.multiple_term_icon );
				return false;

			} else if ( terms.length === 1 ) {

				single_icon = GeoMashup.term_manager.getTermData( taxonomy, terms[0], 'icon' );

				if ( obj.icon && obj.icon.image !== single_icon.image ) {

					// We have two different icons in different taxonomies
					obj.icon = GeoMashup.clone( GeoMashup.multiple_term_icon );
					return false;

				} else {

					obj.icon = GeoMashup.clone( single_icon );

				}

			} 
			
		} );

		if ( !obj.icon ) {
			obj.icon = GeoMashup.colorIcon( 'red' );
		}

		/**
		 * An icon is being assigned to an object.
		 * @name GeoMashup#objectIcon
		 * @event
		 * @param {GeoMashupOptions} properties Geo Mashup configuration data
		 * @param {GeoMashupObject} object Object whose icon property was set.
		 */
		this.doAction( 'objectIcon', GeoMashup.opts, obj );
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
	/**
	 * A marker is being created for an object. Use this to change marker 
	 * options, but if you just want to assign an icon to an object, use the 
	 * objectIcon action.
	 * 
	 * @name GeoMashup#objectMarkerOptions
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Object} glow_options Modifiable <a href="http://mapstraction.github.com/mxn/build/latest/docs/symbols/mxn.Marker.html#addData">Mapstraction</a> 
	 *   or <a href="http://code.google.com/apis/maps/documentation/javascript/v2/reference.html#GMarkerOptions">Google</a> marker options
	 * @param {GeoMashupObject} object
	 */
	this.doAction( 'objectMarkerOptions', this.opts, marker_opts, obj );
	marker = new mxn.Marker( point );
	marker.addData( marker_opts );

	marker.click.addHandler( function() {
		// Toggle marker selection
		if ( marker === GeoMashup.selected_marker ) {
			GeoMashup.deselectMarker();
		} else {
			GeoMashup.selectMarker( marker );
		}
	} ); 

	/**
	 * A marker was created.
	 * @name GeoMashup#marker
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Marker} marker
	 */
	this.doAction( 'marker', this.opts, marker );

	return marker;
};

GeoMashup.clickObjectMarker = function( object_id, try_count ) {
	var obj = this.objects[object_id];

	if ( !GeoMashup.isObjectOn( obj ) ) {
		return false;
	}

	if (typeof try_count === 'undefined') {
		try_count = 1;
	}
	if ( obj && obj.marker && try_count < 4 ) {
		// openlayers/mxn seems to have trouble displaying an infobubble right away
		if ( try_count < 2 ) {
			try_count += 1;
			setTimeout(function () {GeoMashup.clickObjectMarker(object_id, try_count);}, 1000);
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

GeoMashup.getMarkerLatLng = function( marker ) {
	return marker.location;
};

GeoMashup.hideMarker = function( marker ) {
	if ( marker === this.selected_marker ) {
		this.deselectMarker();
	}
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
	if ( this.location_bounds ) {
		this.location_bounds.extend( latlng );
	} else {
		this.location_bounds = new mxn.BoundingBox( latlng, latlng );
	}
};

GeoMashup.addMarkers = function( markers ) {
	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
	} );
};

GeoMashup.makeMarkerMultiple = function( marker ) {
	var plus_image, original_image;
	if (typeof customGeoMashupMultiplePostImage === 'function') {
		plus_image = customGeoMashupMultiplePostImage(this.opts, marker);
	}
	if (!plus_image) {
		plus_image = this.opts.url_path + '/images/mm_20_plus.png';
	}
	original_image = marker.iconUrl;
	marker.setIcon( plus_image );
	/** 
	 * A marker representing multiple objects was created.
	 * @name GeoMashup#multiObjectMarker
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Marker} marker
	 */
	this.doAction( 'multiObjectMarker', this.opts, marker );
	/** 
	 * A marker representing multiple objects was created with this icon.
	 * @name GeoMashup#multiObjectIcon
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {String} plus_image Icon URL
	 */
	this.doAction( 'multiObjectIcon', this.opts, plus_image );
	if ( marker.onmap && marker.iconUrl !== original_image ) {
		this.map.removeMarker( marker );
		this.map.addMarker( marker );
	}
};

GeoMashup.setMarkerImage = function( marker, image_url ) {
	if ( marker.iconUrl !== image_url ) {
		marker.setIcon( image_url );
		if ( marker.onmap ) {
			this.map.removeMarker( marker );
			this.map.addMarker( marker );
		}
	}
};

GeoMashup.autoZoom = function() {
    var map = this.map;
    var limitZoom = function() {
        var max_zoom = parseInt( GeoMashup.opts.auto_zoom_max, 10 );

        if ( map.getZoom() > max_zoom ) {
            map.setZoom( max_zoom );
        }
        map.changeZoom.removeHandler( limitZoom );
    };
    if ( typeof this.opts.auto_zoom_max !== 'undefined' ) {
        this.map.changeZoom.addHandler( limitZoom );
    }
    this.map.autoCenterAndZoom();
};

GeoMashup.isMarkerVisible = function( marker ) {
	var map_bounds;
	try {
		map_bounds = this.map.getBounds();
	} catch( e ) {
		// No bounds available yet, no markers are visible
		return false;
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
		clusterer_opts, single_marker, ov, credit_div, initial_zoom = 1, controls = {}, filter = {};

	this.container = container;
	this.base_color_icon = {};
	this.base_color_icon.image = opts.url_path + '/images/mm_20_black.png';
	this.base_color_icon.iconShadow = opts.url_path + '/images/mm_20_shadow.png';
	this.base_color_icon.iconSize = [12, 20];
	this.base_color_icon.shadowSize =  [22, 20];
	this.base_color_icon.iconAnchor = [6, 20];
	this.base_color_icon.infoWindowAnchor = [5, 1];
	this.multiple_term_icon = this.clone( this.base_color_icon );
	this.multiple_term_icon.image = opts.url_path + '/images/mm_20_mixed.png';

	// Falsify options to make tests simpler
	this.forEach( opts, function( key, value ) {
		if ( 'false' === value || 'FALSE' === value ) {
			opts[key] = false;
		}
	} );

	// See if we have access to a parent frame
	this.have_parent_access = false;
	try {
		if ( typeof parent === 'object' ) {
			// Try access, throws an exception if prohibited
			parent.document.getElementById( 'bogus-test' );
			// Access worked
			this.have_parent_access = true;
		}
	} catch ( parent_exception ) { }

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
				opts.map_type = map_types.G_NORMAL_MAP;
			} else {
				opts.map_type = type_num;
			}
		}
	} else if (typeof opts.map_type === 'undefined') {
		opts.map_type = map_types.G_NORMAL_MAP;
	}
	this.map = new mxn.Mapstraction( this.container, opts.map_api );
	map_opts = {enableDragging: true};
	map_opts.enableScrollWheelZoom = ( opts.enable_scroll_wheel_zoom ? true : false );
	
	if ( typeof this.map.enableGeoMashupExtras === 'function' ) {
		this.map.enableGeoMashupExtras();
	}
	/**
	 * The map options are being set.
	 * @name GeoMashup#mapOptions
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Object} map_opts Modifiable <a href="http://mapstraction.github.com/mxn/build/latest/docs/symbols/mxn.Mapstraction.html#options">Mapstraction</a> 
	 *   or <a href="http://code.google.com/apis/maps/documentation/javascript/v2/reference.html#GMapOptions">Google</a> map options
	 */
	this.doAction( 'mapOptions', opts, map_opts );
	this.map.setOptions( map_opts );
	this.map.setCenterAndZoom(new mxn.LatLonPoint(0,0), 0);

	/**
	 * The map was created.
	 * @name GeoMashup#newMap
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Map} map
	 */
	this.doAction( 'newMap', opts, this.map );

	// Create the loading spinner icon and show it
	this.spinner_div = document.createElement( 'div' );
	this.spinner_div.innerHTML = '<div id="gm-loading-icon" style="-moz-user-select: none; z-index: 100; position: absolute; left: ' +
		( jQuery(this.container).width() / 2 ) + 'px; top: ' + ( jQuery(this.container).height() / 2 ) + 'px;">' +
		'<img style="border: 0px none ; margin: 0px; padding: 0px; width: 16px; height: 16px; -moz-user-select: none;" src="' +
		opts.url_path + '/images/busy_icon.gif"/></a></div>';
	this.showLoadingIcon();
	this.map.load.addHandler( function() {GeoMashup.hideLoadingIcon();} );

	if (!opts.object_name) {
		opts.object_name = 'post';
	}
	this.opts = opts;
	filter.url = opts.siteurl +
		( opts.siteurl.indexOf( '?' ) > 0 ? '&' : '?' ) +
		'geo_mashup_content=geo-query&map_name=' + encodeURIComponent( opts.name );
	if ( opts.lang && filter.url.indexOf( 'lang=' ) === -1 ) {
		filter.url += '&lang=' + encodeURIComponent( opts.lang );
	}

	/**
	 * The base URL used for geo queries is being set.
	 * @name GeoMashup#geoQueryUrl
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Object} filter Mofiable property: url
	 */
	this.doAction( 'geoQueryUrl', this.opts, filter );
	this.geo_query_url = filter.url;

	// TODO: Try to deleselect markers with clicks? Need to make sure we don't get other object's clicks.
	this.map.changeZoom.addHandler( function( old_zoom, new_zoom ) {
		GeoMashup.adjustZoom( old_zoom, new_zoom );
		GeoMashup.adjustViewport();
	} );
	this.map.endPan.addHandler( function() {GeoMashup.adjustViewport();} );

	// No clustering available

	if ( opts.zoom !== 'auto' && typeof opts.zoom === 'string' ) {
		initial_zoom = parseInt(opts.zoom, 10);
	}else {
		initial_zoom = opts.zoom;
	}

	if (opts.load_kml) {
		try {
            // Some servers (Google) don't like HTML entities in URLs
            opts.load_kml = jQuery( '<div/>').html( opts.load_kml ).text();
			if ( initial_zoom === 'auto' ) {
				this.map.addOverlay( opts.load_kml, true );
			} else {
				this.map.addOverlay( opts.load_kml );
			}
		} catch (e) {
			// Probably not implemented
		}
	}

	if ( this.term_manager ) {
		this.term_manager.load();
	}

	try {
		this.map.setMapType( opts.map_type );
	} catch ( map_type_ex) {
		// Probably not implemented
	}
	if ( initial_zoom !== 'auto' ) {
		if (opts.center_lat && opts.center_lng) {
			// Use the center from options
			this.map.setCenterAndZoom(new mxn.LatLonPoint( parseFloat( opts.center_lat ), parseFloat( opts.center_lng ) ), initial_zoom );
		} else if (opts.object_data && opts.object_data.objects[0]) {
			center_latlng = new mxn.LatLonPoint( parseFloat( opts.object_data.objects[0].lat ), parseFloat( opts.object_data.objects[0].lng ) );
			this.map.setCenterAndZoom( center_latlng, initial_zoom );
		} else {
			// Center on the most recent located object
			url = this.geo_query_url + '&limit=1';
			if (opts.map_cat) {
				url += '&map_cat='+opts.map_cat;
			}
			jQuery.getJSON( url, function( objects ) {
				if (objects.length>0) {
					center_latlng = new mxn.LatLonPoint( parseFloat( objects[0].lat ), parseFloat( objects[0].lng ) );
					this.map.setCenterAndZoom( center_latlng, initial_zoom );
				}
			} );
		}
	}

	this.location_bounds = null;

	if (opts.map_content === 'single')
	{
		if (opts.object_data && opts.object_data.objects.length && !opts.load_kml)
		{
			marker_opts = {visible: true};
			if (typeof customGeoMashupSinglePostIcon === 'function') {
				marker_opts = customGeoMashupSinglePostIcon(this.opts);
			}
			if ( !marker_opts.image ) {
				marker_opts = this.colorIcon( 'red' );
				marker_opts.icon = marker_opts.image;
			}
			/**
			 * A single map marker is being created with these options
			 * @name GeoMashup#singleMarkerOptions
			 * @event
			 * @param {GeoMashupOptions} properties Geo Mashup configuration data
			 * @param {Object} marker_opts Mofifiable Mapstraction or Google marker options
			 */
			this.doAction( 'singleMarkerOptions', this.opts, marker_opts );
			single_marker = new mxn.Marker(
				new mxn.LatLonPoint( parseFloat( opts.object_data.objects[0].lat ), parseFloat( opts.object_data.objects[0].lng ) )
			);
			this.map.addMarkerWithData( single_marker, marker_opts );
			/**
			 * A single map marker was added to the map.
			 * @name GeoMashup#singleMarker
			 * @event
			 * @param {GeoMashupOptions} properties Geo Mashup configuration data
			 * @param {Marker} single_marker
			 */
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

	if (opts.add_map_type_control && typeof this.map.setMapTypes === 'function' ) {
		if ( typeof opts.add_map_type_control === 'string' ) {
			opts.add_map_type_control = opts.add_map_type_control.split(/\s*,\s*/);
			if ( typeof map_types[opts.add_map_type_control[0]] === 'undefined' ) {
				// Convert the old boolean value to a default array
				opts.add_map_type_control = [ 'G_NORMAL_MAP', 'G_SATELLITE_MAP', 'G_PHYSICAL_MAP' ];
			}
		}
		// Convert to mapstraction types
		opts.mxn_map_type_control = [];
		for ( i = 0; i < opts.add_map_type_control.length; i += 1 ) {
			opts.mxn_map_type_control.push( map_types[ opts.add_map_type_control[i] ] );
		}
		this.map.setMapTypes( opts.mxn_map_type_control );
	}

	this.map.load.addHandler( function() {GeoMashup.updateVisibleList();} );
	if (typeof customizeGeoMashupMap === 'function') {
		customizeGeoMashupMap(this.opts, this.map);
	}
	if (typeof customizeGeoMashup === 'function') {
		customizeGeoMashup(this);
	}
	/**
	 * The map has loaded.
	 * @name GeoMashup#loadedMap
	 * @event
	 * @param {GeoMashupOptions} properties Geo Mashup configuration data
	 * @param {Map} map
	 */
	this.doAction( 'loadedMap', this.opts, this.map );

};
