/**
 * Google API v2 implementation for Geo Mashup maps.
 * @fileOverview
 */

/*global GeoMashup */
/*global customizeGeoMashup, customizeGeoMashupMap, customGeoMashupColorIcon, customGeoMashupCategoryIcon */
/*global customGeoMashupSinglePostIcon, customGeoMashupMultiplePostImage */
/*global jQuery, google, G_DEFAULT_ICON, mxn, ClusterMarker, MarkerClusterer */
/*jslint browser:true, white: true, vars: true, sloppy: true, evil: true */

GeoMashup.createTermLine = function ( term_data ) {

	term_data.line = new google.maps.Polyline( term_data.points, term_data.color);

	google.maps.Event.addListener( term_data.line, 'click', function () {
		GeoMashup.map.zoomIn();
	} );

	this.doAction( 'termLine', term_data.line );
	this.doAction( 'categoryLine', GeoMashup.opts, term_data.line );

	this.map.addOverlay( term_data.line );

	if ( this.map.getZoom() > term_data.max_line_zoom ) {
		term_data.line.hide();
	}

};

GeoMashup.openMarkerInfoWindow = function( marker, content_node, window_opts ) {
	var latlng = marker.getLatLng();
	this.doAction( 'markerInfoWindowOptions', this.opts, this.locations[latlng], window_opts );
	marker.openInfoWindow( content_node, window_opts );
};

GeoMashup.loadMaxContent = function( marker, regular_node, info_window_max_url, cache ) {
	var info_window_max_request = new google.maps.XmlHttp.create();
	var request_options = {url: info_window_max_url};
	/** 
	 * A marker's maximized info window content is being requested.
	 * @name GeoMashup#markerInfoWindowMaxRequest
	 * @event
	 * @param {Marker} marker
	 * @param {AjaxRequestOptions} request_options 
	 */
	this.doAction( 'markerInfoWindowMaxRequest', marker, request_options );
	info_window_max_request.open( 'GET', request_options.url, true );
	info_window_max_request.onreadystatechange = function() {
		var max_node, max_options, filter;
		if (info_window_max_request.readyState === 4 && info_window_max_request.status === 200 ) {
			filter = {content: info_window_max_request.responseText};
			/**
			 * A marker's maximized info window content is being loaded.
			 * @name GeoMashup#markerInfoWindowMaxLoad
			 * @event
			 * @param {Marker} marker
			 * @param {ContentFilter} filter 
			 */
			GeoMashup.doAction( 'markerInfoWindowMaxLoad', marker, filter );
			max_node = document.createElement( 'div' );
			max_node.innerHTML = filter.content;
			GeoMashup.parentizeLinks( max_node );
			cache.info_window_options = {maxContent: max_node};
			GeoMashup.openMarkerInfoWindow( marker, regular_node, cache.info_window_options );
		} // end max readState === 4
	}; // end max onreadystatechange function
	info_window_max_request.send( null );
};

GeoMashup.openInfoWindow = function( marker ) {
	var object_ids, cache_key, cache, i, request_options, info_window_request, object_element, point = marker.getPoint();

	this.map.closeInfoWindow();
		
	object_ids = this.getOnObjectIDs( this.getMarkerObjects( marker ) );
	cache_key = 'info-window-' + object_ids.join(',');
	cache = this.locationCache( point, cache_key );
	if ( cache.info_node ) {
		marker.openInfoWindow( cache.info_node, cache.info_window_options );
	} else {
		marker.openInfoWindowHtml('<div align="center"><img src="' +
			this.opts.url_path + 
			'/images/busy_icon.gif" alt="Loading..." /></div>');
		request_options = {
			url: this.geo_query_url + '&object_name=' + this.opts.object_name +
				'&object_ids=' + object_ids.join( ',' )
		};
		this.doAction( 'markerInfoWindowRequest', marker, request_options );
		info_window_request = new google.maps.XmlHttp.create();
		info_window_request.open('GET', request_options.url, true);
		info_window_request.onreadystatechange = function() {
			var node, info_window_max_request, filter;

			if (info_window_request.readyState === 4 && info_window_request.status === 200) {
				filter = {content: info_window_request.responseText};
				GeoMashup.doAction( 'markerInfoWindowLoad', marker, filter );
				node = document.createElement('div');
				node.innerHTML = filter.content;
				GeoMashup.parentizeLinks( node );
				cache.info_node = node;
				if ( 'post' === GeoMashup.opts.object_name ) {
					GeoMashup.loadMaxContent( marker, node, request_options.url + '&template=info-window-max', cache );
				} else {
					cache.info_window_options = {};
					GeoMashup.openMarkerInfoWindow( marker, node, cache.info_window_options );
					GeoMashup.doAction( 'loadedInfoWindow' );
				}
			} // end readystate === 4
		}; // end onreadystatechange function
		info_window_request.send(null);
	} // end object not loaded yet 
};

GeoMashup.addGlowMarker = function( marker ) {
	var glow_icon;

	if ( this.glow_marker ) {
		this.map.removeOverlay( this.glow_marker );
		this.glow_marker.setLatLng( marker.getLatLng() );
	} else {
		glow_icon = new google.maps.Icon( {
			image : this.opts.url_path + '/images/mm_20_glow.png',
			iconSize : new google.maps.Size( 22, 30 ),
			iconAnchor : new google.maps.Point( 11, 27 ) 
		} );
		this.doAction( 'glowMarkerIcon', this.opts, glow_icon );
		this.glow_marker = new google.maps.Marker( marker.getLatLng(), {
			clickable : false,
			icon : glow_icon
		} );
	}
	this.map.addOverlay( this.glow_marker );
};

GeoMashup.removeGlowMarker = function() {
	this.map.removeOverlay( this.glow_marker );
};

GeoMashup.hideAttachments = function() {
	var i, j, obj;

	for ( i = 0; i < this.open_attachments.length; i += 1 ) {
		this.map.removeOverlay( this.open_attachments[i] );
	} 
	this.open_attachments = [];
};

GeoMashup.showMarkerAttachments = function( marker ) {
	var objects;

	this.hideAttachments();
	objects = this.getObjectsAtLocation( marker.getLatLng() );
	this.forEach( objects, function( i, obj ) {
		var ajax_params = {action: 'geo_mashup_kml_attachments'};
		if ( obj.attachments ) {
			// Attachment overlays are available
			this.forEach( obj.attachments, function( j, attachment ) {
				this.open_attachments.push( attachment );
				this.map.addOverlay( attachment );
			} );
		} else {
			// Look for attachments
			obj.attachments = [];
			ajax_params.post_ids = obj.object_id;
			jQuery.getJSON( this.opts.ajaxurl + '?callback=?', ajax_params, function( data ) {
				GeoMashup.forEach( data, function( j, url ) {
					var geoxml = new google.maps.GeoXml( url );
					obj.attachments.push( geoxml );
					this.open_attachments.push( geoxml );
					this.map.addOverlay( geoxml );
				} );
			} );
		}
	} );
};

GeoMashup.loadFullPost = function( point ) {
	var i, url, cache, post_request, objects, object_ids, request_options;

	objects = this.getObjectsAtLocation( point );
	object_ids = this.getOnObjectIDs( objects );
	cache = this.locationCache( point, 'full-post-' + object_ids.join(',') );
	if ( cache.post_html ) {
		this.getShowPostElement().innerHTML = cache.post_html;
	} else {

		this.getShowPostElement().innerHTML = '<div align="center"><img src="' +
			this.opts.url_path + '/images/busy_icon.gif" alt="Loading..." /></div>';
		request_options = {
			url: this.geo_query_url + '&object_name=' + this.opts.object_name +
				'&object_ids=' + object_ids.join( ',' ) + '&template=full-post'
		};
		this.doAction( 'fullPostRequest', objects, request_options );
		post_request = new google.maps.XmlHttp.create();
		post_request.open('GET', request_options.url, true);
		post_request.onreadystatechange = function() {
			var filter;
			if (post_request.readyState === 4 && post_request.status === 200) {
				filter = {content: post_request.responseText};
				GeoMashup.doAction( 'fullPostLoad', objects, filter );
				GeoMashup.getShowPostElement().innerHTML = filter.content;
				GeoMashup.doAction( 'fullPostChanged' );
				cache.post_html = filter.content;
			} // end readystate === 4
		}; // end onreadystatechange function
		post_request.send(null);
	}
};

GeoMashup.addObjectIcon = function( obj ) {

	// Back compat
	if ( typeof customGeoMashupCategoryIcon === 'function' && obj.terms && obj.terms.hasOwnProperty( 'category' ) ) {
		obj.icon = customGeoMashupCategoryIcon( GeoMashup.opts, obj.terms.category );
	} 

	if (!obj.icon) {

		
		jQuery.each( obj.terms, function( taxonomy, terms ) {
			var single_icon;

			if ( terms.length > 1 ) {

				obj.icon = new google.maps.Icon( GeoMashup.multiple_term_icon );
				return false; // continue

			} else if ( terms.length === 1 ) {

				single_icon = GeoMashup.term_manager.getTermData( taxonomy, terms[0], 'icon' );

				if ( obj.icon && obj.icon.image !== single_icon.image ) {

					// We have two different icons in different taxonomies
					obj.icon = new google.maps.Icon( GeoMashup.multiple_term_icon );
					return false;

				} else {

					obj.icon = GeoMashup.clone( single_icon );

				}

			} 

		} );

		if ( !obj.icon ) {
			obj.icon = GeoMashup.colorIcon( 'red' );
		}

		this.doAction( 'objectIcon', this.opts, obj );

	}
};

GeoMashup.createMarker = function( point, obj ) {
	var marker, 
		// Apersand entities have been added for validity, but look bad in titles
		marker_opts = {title: obj.title.replace( '&amp;', '&' )};

	if ( !obj.icon ) {
		this.addObjectIcon( obj );
	}
	marker_opts.icon = this.clone( obj.icon );
	this.doAction( 'objectMarkerOptions', this.opts, marker_opts, obj );
	marker = new google.maps.Marker(point,marker_opts);

	google.maps.Event.addListener(marker, 'click', function() {
		GeoMashup.selectMarker( marker );
	}); 

	google.maps.Event.addListener( marker, 'remove', function() {
		if ( GeoMashup.selected_marker && marker === GeoMashup.selected_marker ) {
			GeoMashup.deselectMarker();
		}
	} );

	google.maps.Event.addListener( marker, 'visibilitychanged', function( is_visible ) {
		if ( GeoMashup.selected_marker && marker === GeoMashup.selected_marker && !is_visible ) {
			GeoMashup.deselectMarker();
		}
	} );

	this.doAction( 'marker', this.opts, marker );

	return marker;
};

GeoMashup.checkDependencies = function () {
	if (typeof google.maps.Map === 'undefined' || !google.maps.BrowserIsCompatible()) {
		this.container.innerHTML = '<p class="errormessage">' +
			'Sorry, the Google Maps script failed to load. Have you entered your ' +
			'<a href="http://maps.google.com/apis/maps/signup.html">API key<\/a> ' +
			'in the Geo Mashup Options?';
		throw "The Google Maps javascript didn't load.";
	}
};

GeoMashup.clickObjectMarker = function(object_id, try_count) {
	if (typeof try_count === 'undefined') {
		try_count = 1;
	}
	if (this.objects[object_id] && try_count < 4) {
		if (GeoMashup.objects[object_id].marker.isHidden()) {
			try_count += 1;
			setTimeout(function () {GeoMashup.clickObjectMarker(object_id, try_count);}, 300);
		} else {
			google.maps.Event.trigger(GeoMashup.objects[object_id].marker,"click"); 
		}
	}
};

GeoMashup.colorIcon = function( color_name ) {
	var icon = new google.maps.Icon(this.base_color_icon);
	icon.image = this.opts.url_path + '/images/mm_20_' + color_name + '.png';
	return icon;
};

GeoMashup.getMarkerLatLng = function( marker ) {
	return marker.getLatLng();
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
	line.hide();
};

GeoMashup.showLine = function( line ) {
	line.show();
};

GeoMashup.newLatLng = function( lat, lng ) {
	return new google.maps.LatLng( lat, lng );
};

GeoMashup.extendLocationBounds = function( latlng ) {
	this.location_bounds.extend( latlng );
};

GeoMashup.addMarkers = function( markers ) {
	// No clustering, or ClusterMarker need the markers added to the map
	this.forEach( markers, function( i, marker ) {
		this.map.addOverlay( marker );
	} );
	if ( this.clusterer && markers.length > 0 ) {
		this.clusterer.addMarkers( markers );
		this.recluster();
	}
};

GeoMashup.makeMarkerMultiple = function( marker ) {
	var plus_image;
	if (typeof customGeoMashupMultiplePostImage === 'function') {
		plus_image = customGeoMashupMultiplePostImage(this.opts, marker.getIcon().image);
	}
	if (!plus_image) {
		plus_image = this.opts.url_path + '/images/mm_20_plus.png';
	}
	if ( marker.getIcon().image !== plus_image ) {
		// User testing gave best results when both methods of
		// changing the marker image are used in this order
		marker.setImage( plus_image );
		marker.getIcon().image = plus_image;
	}
	this.doAction( 'multiObjectMarker', this.opts, marker );
	this.doAction( 'multiObjectIcon', this.opts, marker.getIcon() );
};

GeoMashup.setMarkerImage = function( marker, image_url ) {
	if ( marker.getIcon().image !== image_url ) {
		marker.setImage( image_url );
		marker.getIcon().image = image_url;
	}
};

GeoMashup.setCenterUpToMaxZoom = function( latlng, zoom, callback ) {
	var map_type = this.map.getCurrentMapType();
	if ( map_type === google.maps.SATELLITE_MAP || map_type === google.maps.HYBRID_MAP ) {
		map_type.getMaxZoomAtLatLng( latlng, function( response ) {
			if ( response && response.status === google.maps.GEO_SUCCESS ) {
				if ( response.zoom < zoom ) {
					zoom = response.zoom;
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
};

GeoMashup.autoZoom = function() {
	var zoom = this.map.getBoundsZoomLevel( this.location_bounds );
	var max_zoom = parseInt( this.opts.auto_zoom_max, 10 );
	if ( zoom > max_zoom ) {
		zoom = max_zoom;
	}
	this.setCenterUpToMaxZoom( 
		this.location_bounds.getCenter(), 
		zoom,
		function() {GeoMashup.updateVisibleList();} 
	);
};

GeoMashup.centerMarker = function( marker, zoom ) {
	if ( typeof zoom === 'number' ) {
		this.map.setCenter( marker.getLatLng(), zoom );
	} else {
		this.map.setCenter( marker.getLatLng() );
	}
};

GeoMashup.requestObjects = function( use_bounds ) {
	var request, url, map_bounds, map_span;
	if (this.opts.max_posts && this.object_count >= this.opts.max_posts) {
		return;
	}
	request = google.maps.XmlHttp.create();
	url = this.geo_query_url;
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
		var objects;

		if (request.readyState === 4 && request.status === 200) {
			objects = window['eval']( '(' + request.responseText + ')' );
			GeoMashup.addObjects(objects,!use_bounds);
		} // end readystate === 4
	}; // end onreadystatechange function
	request.send(null);
};

GeoMashup.isMarkerVisible = function( marker ) {
	var map_bounds = this.map.getBounds();
	return ( ! marker.isHidden() && map_bounds.containsLatLng( marker.getLatLng() ) );
};

GeoMashup.recluster = function( ) {
	this.clusterer.refresh();
};

GeoMashup.createMap = function(container, opts) {
	var i, type_num, center_latlng, map_opts, map_types, request, url, objects, point, marker_opts, 
		clusterer_opts, google_bar_opts, single_marker, ov, credit_div, initial_zoom = 1, filter = {};

	this.container = container;
	this.checkDependencies();
	this.base_color_icon = new google.maps.Icon();
	this.base_color_icon.image = opts.url_path + '/images/mm_20_black.png';
	this.base_color_icon.shadow = opts.url_path + '/images/mm_20_shadow.png';
	this.base_color_icon.iconSize = new google.maps.Size(12, 20);
	this.base_color_icon.shadowSize = new google.maps.Size(22, 20);
	this.base_color_icon.iconAnchor = new google.maps.Point(6, 20);
	this.base_color_icon.infoWindowAnchor = new google.maps.Point(5, 1);
	this.multiple_term_icon = new google.maps.Icon( this.base_color_icon );
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
	map_opts = {
		mapTypes : [ opts.map_type ],
		googleBarOptions : { 
			adsOptions : {client : opts.adsense_code || 'pub-5088093001880917'}
		}
	};
	if ( opts.background_color ) {
		map_opts.backgroundColor = opts.background_color;
	}
	this.doAction( 'mapOptions', opts, map_opts );
	this.map = new google.maps.Map2( this.container, map_opts );
	this.map.setCenter(new google.maps.LatLng(0,0), 0);

	this.doAction( 'newMap', opts, this.map );

	// Create the loading spinner icon and show it
	this.spinner_div = document.createElement( 'div' );
	this.spinner_div.innerHTML = '<div id="gm-loading-icon" style="-moz-user-select: none; z-index: 100; position: absolute; left: ' +
		( this.map.getSize().width / 2 ) + 'px; top: ' + ( this.map.getSize().height / 2 ) + 'px;">' +
		'<img style="border: 0px none ; margin: 0px; padding: 0px; width: 16px; height: 16px; -moz-user-select: none;" src="' +
		opts.url_path + '/images/busy_icon.gif"/></a></div>';
	this.showLoadingIcon();
	google.maps.Event.bind( this.map, 'tilesloaded', this, this.hideLoadingIcon );

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
	this.doAction( 'geoQueryUrl', this.opts, filter );
	this.geo_query_url = filter.url;

	google.maps.Event.bind(this.map, "zoomend", this, this.adjustZoom);
	google.maps.Event.bind(this.map, "moveend", this, this.adjustViewport);

	if (opts.cluster_max_zoom) {
		clusterer_opts = {
			'iconOptions' : {},
			'fitMapMaxZoom' : opts.cluster_max_zoom,
			'clusterMarkerTitle' : '%count',
			'intersectPadding' : 3
		};
		/**
		 * Clusterer options are being set.
		 * @name GeoMashup#clusterOptions
		 * @event
		 * @param {GeoMashupOptions} properties Geo Mashup configuration data
		 * @param {Object} clusterer_opts Modifiable clusterer options for
		 *   <a href="http://googlemapsapi.martinpearman.co.uk/readarticle.php?article_id=4">ClusterMarker</a>.
		 */
		this.doAction( 'clusterOptions', this.opts, clusterer_opts );
		this.clusterer = new ClusterMarker( this.map, clusterer_opts );
	}

	if ( opts.zoom !== 'auto' && typeof opts.zoom === 'string' ) {
		initial_zoom = parseInt(opts.zoom, 10);
	} else {
		initial_zoom = opts.zoom;
	}

	if (opts.load_kml) {
		this.kml = new google.maps.GeoXml(opts.load_kml);
		this.map.addOverlay(this.kml);
		if ( initial_zoom === 'auto' ) {
			this.kml.gotoDefaultViewport( this.map );
		}
	}

	if ( this.term_manager ) {
		this.term_manager.load();
	}

	if ( initial_zoom !== 'auto' ) {
		if (opts.center_lat && opts.center_lng) {
			// Use the center from options
			this.map.setCenter(new google.maps.LatLng(opts.center_lat, opts.center_lng), initial_zoom, opts.map_type);
		} else if (this.kml) {
			google.maps.Event.addListener( this.kml, 'load', function() {
				GeoMashup.map.setCenter( GeoMashup.kml.getDefaultCenter(), initial_zoom, opts.map_type );
			} );
		} else if (opts.object_data && opts.object_data.objects[0]) {
			center_latlng = new google.maps.LatLng(opts.object_data.objects[0].lat, opts.object_data.objects[0].lng);
			this.map.setCenter(center_latlng, initial_zoom, opts.map_type);
		} else {
			// Center on the most recent located object
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
		}
	}

	this.location_bounds = new google.maps.LatLngBounds();

	if (opts.map_content === 'single')
	{
		if (opts.object_data && opts.object_data.objects.length && !this.kml)
		{
			marker_opts = {};
			if (typeof customGeoMashupSinglePostIcon === 'function') {
				marker_opts.icon = customGeoMashupSinglePostIcon(this.opts);
			}
			if ( !marker_opts.icon ) {
				marker_opts.icon = G_DEFAULT_ICON;
			}
			this.doAction( 'singleMarkerOptions', this.opts, marker_opts );
			single_marker = new google.maps.Marker(
				new google.maps.LatLng( opts.object_data.objects[0].lat, opts.object_data.objects[0].lng ), marker_opts );
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

	if (opts.add_map_type_control ) {
		if ( typeof opts.add_map_type_control === 'string' ) {
			opts.add_map_type_control = opts.add_map_type_control.split(/\s*,\s*/);
			if ( typeof map_types[opts.add_map_type_control[0]] === 'undefined' ) {
				// Convert the old boolean value to a default array
				opts.add_map_type_control = [ 'G_NORMAL_MAP', 'G_SATELLITE_MAP', 'G_PHYSICAL_MAP' ];
			}
		}
		for ( i = 0; i < opts.add_map_type_control.length; i += 1 ) {
			this.map.addMapType( map_types[opts.add_map_type_control[i]] );
		}
		this.map.addControl(new google.maps.MapTypeControl());
	}

	if (opts.add_overview_control) {
		this.overview_control = new google.maps.OverviewMapControl();
		this.overview_control.setMapType( opts.map_type );
		this.doAction( 'overviewControl', this.opts, this.overview_control );
		this.map.addControl( this.overview_control );
		ov = document.getElementById('gm-overview');
		if (ov) {
			ov.style.position = 'absolute';
			this.container.appendChild(ov);
		}
	}

	if ( opts.add_google_bar ) {
		this.map.enableGoogleBar();
	}

	if ( opts.enable_scroll_wheel_zoom ) {
		this.map.enableScrollWheelZoom();
	}

	google.maps.Event.addListener( this.map, 'click', function( overlay ) {
		if ( GeoMashup.selected_marker && ( ! overlay ) ) {
			GeoMashup.deselectMarker();
		}
	} );

	if (typeof customizeGeoMashupMap === 'function') {
		customizeGeoMashupMap(this.opts, this.map);
	}
	if (typeof customizeGeoMashup === 'function') {
		customizeGeoMashup(this);
	}
	this.doAction( 'loadedMap', this.opts, this.map );

};
