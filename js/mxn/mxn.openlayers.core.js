mxn.register('openlayers', {	

	Mapstraction: {

		init: function(element, api){
			var me = this;
			
			if (typeof ol.Map === 'undefined') {
				throw new Error(api + ' map script not imported');
			}

			this.controls = {
				pan: null,
				zoom: null,
				overview: null,
				scale: null,
				map_type: null,
				full_screen: null
			};

			var map = new ol.Map({
				target: element.id,
				view: new ol.View({
					center: ol.proj.fromLonLat([0, 0]),
					zoom: 2
				})
			});

			var popupElement = document.createElement('div');
			popupElement.setAttribute('class', 'ol-popup');
			element.appendChild(popupElement);

			var popupOverlay = new ol.Overlay({
				id: 'popup',
				element: popupElement,
				positioning: 'bottom-center',
				stopEvent: false,
				autoPan: {}
			});

			map.addOverlay(popupOverlay);

			// initialize layers map (this was previously in mxn.core.js)
			this.layers = {};

			// create OSM layer
			this.layers.osm = new ol.layer.Tile({ source: new ol.source.OSM() });

			// deal with click
			map.on('click', function(evt){
				var point = new mxn.LatLonPoint();
				point.fromProprietary(api, evt.coordinate);
				me.click.fire({'location': point });
			});

			// deal with zoom change
			map.getView().on('change:resolution', function(){
				me.changeZoom.fire();
			});
		
			// deal with map movement
			map.on('moveend', function(evt){
				me.moveendHandler(me);
				me.endPan.fire();
			});

			this.layers.osm.once('postrender', function () {
				me.load.fire();
			});

			map.addLayer(this.layers.osm);
			this.tileLayers.push(["https://a.tile.openstreetmap.org/", this.layers.osm, true]);
			this.maps[api] = map;
			this.loaded[api] = true;

			this.boundsToExtent = function(bounds) {
				var swMxn = bounds.getSouthWest();
				var neMxn = bounds.getNorthEast();

				if(sw.lon > ne.lon) {
					sw.lon -= 360;
				}

				var swMerc = ol.proj.fromLonLat([swMxn.lon, swMxn.lat]);
				var neMerc = ol.proj.fromLonLat([neMxn.lon, neMxn.lat]);
				return [swMerc[0], swMerc[1], neMerc[0], neMerc[1]]
			};
		},

		applyOptions: function(){
			var map = this.maps[this.api];

			if ( this.options.enableScrollWheelZoom ) {
				map.addInteraction(new ol.interaction.MouseWheelZoom())
			} else {
				map.removeInteraction(new ol.interaction.MouseWheelZoom())
			}
		},

		resizeTo: function(width, height){	
			this.currentElement.style.width = width;
			this.currentElement.style.height = height;
			this.maps[this.api].updateSize();
		},

		addControls: function( args ) {
			/* args = { 
			 *     pan:      true,
			 *     zoom:     'large' || 'small',
			 *     overview: true,
			 *     scale:    true,
			 *     map_type: true,
			 *     full_screen_control: true,
			 * }
			 */

			var map = this.maps[this.api];

			if ('zoom' in args) {
				if (args.zoom === 'large') {
					this.controls.zoom = this.addLargeControls()
				} else if (args.zoom === 'small') {
					this.controls.zoom = this.addSmallControls()
				}
			} else {
				if (this.controls.zoom !== null) {
					map.removeControl(this.controls.zoom)
					this.controls.zoom = null
				}
			}

			if ('overview' in args && args.overview) {
				if (this.controls.overview === null) {
					this.controls.overview = new ol.control.OverviewMap()
					map.addControl(this.controls.overview)
				}
			} else {
				if (this.controls.overview !== null) {
					map.removeControl(this.controls.overview)
					this.controls.overview = null
				}
			}

			if ('full_screen_control' in args && args.full_screen_control) {
				if (this.controls.full_screen === null) {
					this.controls.full_screen = new ol.control.FullScreen()
					map.addControl(this.controls.full_screen)
				}
			} else {
				if (this.controls.full_screen !== null) {
					map.removeControl(this.controls.full_screen)
					this.controls.full_screen = null
				}
			}

			if ('map_type' in args && args.map_type) {
				this.controls.map_type = this.addMapTypeControls()
			} else {
				if (this.controls.map_type !== null) {
					map.removeControl(this.controls.map_type)
					this.controls.map_type = null
				}
			}

			if ('scale' in args && args.scale) {
				if (this.controls.scale === null) {
					this.controls.scale = new ol.Control.ScaleLine()
					map.addControl(this.controls.scale)
				}
			} else {
				if (this.controls.scale !== null) {
					map.removeControl(this.controls.scale)
					this.controls.scale = null
				}
			}
		},

		addSmallControls: function() {
			var map = this.maps[this.api];

			if (this.controls.zoom !== null) {
				map.removeControl(this.controls.zoom);
			}

			this.controls.zoom = new ol.control.Zoom();
			map.addControl(this.controls.zoom);
			return this.controls.zoom;
		},

		addLargeControls: function() {
			var map = this.maps[this.api];
			if (this.controls.zoom !== null) {
				map.removeControl(this.controls.zoom);
			}

			this.controls.zoom = new ol.control.ZoomSlider();
			map.addControl(this.controls.zoom);
			return this.controls.zoom;
		},

		addMapTypeControls: function() {
			// Only Open Street Map road map is implemented, so you can't change the Map Type
		},

		setCenterAndZoom: function(point, zoom) { 
			var view = this.maps[this.api].getView();
			var pt = point.toProprietary(this.api);
			view.setCenter(pt);
			view.setZoom(zoom);
		},

		addMarker: function(marker, old) {
			var map = this.maps[this.api];
			var pin = marker.toProprietary(this.api);

			if (!this.layers.markers) {
				this.layers.markers = new ol.layer.Vector({
          source: new ol.source.Vector(),
          zIndex: 10
				});
				map.addLayer(this.layers.markers);

				map.on('click', function(e) {
					var feature = map.forEachFeatureAtPixel(e.pixel, function(feature) { return feature; });
					if (feature) {
						feature.mapstraction_marker.click.fire();
					}
				});
			}

			if (marker.draggable) {
				// TODO: add drag handling
			}
			this.layers.markers.getSource().addFeature(pin);
			marker.layer = this.layers.markers;
			return pin;
		},

		removeMarker: function(marker) {
			var pin = marker.proprietary_marker;
			if (this.layers.markers.getSource().hasFeature(pin)) {
				this.layers.markers.getSource().removeFeature(pin);
			}
		},

		declutterMarkers: function(opts) {
			throw new Error('Mapstraction.declutterMarkers is not currently supported by provider ' + this.api);
		},

		addPolyline: function(polyline, old) {
			var map = this.maps[this.api];
			var pl = polyline.toProprietary(this.api);
			if (!this.layers.polylines) {
				this.layers.polylines = new ol.layer.Vector({
					source: new ol.source.Vector(),
				});
				map.addLayer(this.layers.polylines);
			}
			this.layers.polylines.getSource().addFeature(pl);
			return pl;
		},

		removePolyline: function(polyline) {
			var pl = polyline.proprietary_polyline;
			this.layers.polylines.getSource().removeFeature(pl);
		},
		
		removeAllPolylines: function() {
			for (var i = 0, length = this.polylines.length; i < length; i++) {
				this.layers.polylines.getSource().removeFeature(this.polylines[i].proprietary_polyline);
			}
		},

		getCenter: function() {
			var map = this.maps[this.api];
			var pt = map.getView().getCenter();
			var mxnPt = new mxn.LatLonPoint();
			mxnPt.fromProprietary(this.api, pt);
			return mxnPt;
		},

		setCenter: function(point, options) {
			var map = this.maps[this.api];
			var pt = point.toProprietary(this.api);
			map.getView().setCenter(pt);
		},

		setZoom: function(zoom) {
			var map = this.maps[this.api];
			map.getView().setZoom(zoom);
		},

		getZoom: function() {
			var map = this.maps[this.api];
			return map.getView().getZoom();
		},

		getZoomLevelForBoundingBox: function(bbox) {
			var map = this.maps[this.api];

			var obounds = this.boundsToExtent(bbox)

			return map.getView().getZoomForResolution(map.getResolutionForExtent(obounds));
		},

		setMapType: function(type) {
			// Only Open Street Map road map is implemented, so you can't change the Map Type
		},

		getMapType: function() {
			// Only Open Street Map road map is implemented, so you can't change the Map Type
			return mxn.Mapstraction.ROAD;
		},

		getBounds: function () {
			var map = this.maps[this.api];
			var extent = map.getView().calculateExtent();
			var sw = ol.proj.toLonLat([extent[0], extent[1]]);
			var ne = ol.proj.toLonLat([extent[2], extent[3]]);
			return new mxn.BoundingBox(sw[1], sw[0], ne[1], ne[0]);
		},

		setBounds: function(bounds) {
			var map = this.maps[this.api];

			var extent = this.boundsToExtent(bounds);
			
			map.getView().fit(extent, map.getSize());
		},

		addImageOverlay: function(id, src, opacity, west, south, east, north, oContext) {
			var map = this.maps[this.api];
			var extent = new ol.Extent();
			extent.extend(new mxn.LatLonPoint(south,west).toProprietary(this.api));
			extent.extend(new mxn.LatLonPoint(north,east).toProprietary(this.api));
			var overlay = new ol.Layer.Image(
				id, 
				src,
				extent,
				[oContext.imgElm.width, oContext.imgElm.height],
				{'isBaseLayer': false, 'alwaysInRange': true}
			);
			map.addLayer(overlay);
			this.setImageOpacity(overlay.div.id, opacity);
		},

		setImagePosition: function(id, oContext) {
			throw new Error('Mapstraction.setImagePosition is not currently supported by provider ' + this.api);
		},

		addOverlay: function(url, autoCenterAndZoom) {
			var map = this.maps[this.api];
			var layer = new ol.layer.Vector({
				source: new ol.source.Vector({
					url: url,
					format: new ol.format.KML()
				})
			});
			if (autoCenterAndZoom) {
				throw new Error('Overlay autoCenterAndZoom is not currently supported by provider ' + this.api);
			}
			map.addLayer(layer);
		},

		addTileLayer: function(tile_url, opacity, label, attribution, min_zoom, max_zoom, map_type, subdomains) {
			var map = this.maps[this.api];
			var new_tile_url = tile_url.replace(/\{Z\}/gi,'${z}').replace(/\{X\}/gi,'${x}').replace(/\{Y\}/gi,'${y}');
			
			if (typeof subdomains !== 'undefined') {
				//make a new array of each subdomain.
				var domain = [];
				for(var i = 0; i < subdomains.length; i++)
				{
					domain.push(mxn.util.getSubdomainTileURL(new_tile_url, subdomains[i]));
				}
			}	

			var layer = new ol.layer.Tile({
				source: new ol.source.XYZ({
					attributions: attribution,
					url: domain || new_tile_url,
					minZoom: min_zoom || 0,
					maxZoom: max_zoom || 20
				})
			});

			if(!opacity) {
				layer.setOpacity(opacity);
			}
			
			map.addLayer(layer);
			this.tileLayers.push( [tile_url, layer, true] );
		},

		toggleTileLayer: function(tile_url) {
			var map = this.maps[this.api];
			for (var f=this.tileLayers.length-1; f>=0; f--) {
				if(this.tileLayers[f][0] === tile_url) {
					this.tileLayers[f][2] = !this.tileLayers[f][2];
					this.tileLayers[f][1].setVisible(this.tileLayers[f][2]);
				}
			}	   
		},

		getPixelRatio: function() {
			throw new Error('Mapstraction.getPixelRatio is not currently supported by provider ' + this.api);
		},

		mousePosition: function(element) {
			var map = this.maps[this.api];
			var control = new ol.control.MousePosition({
				coordinateFormat: ol.coordinate.createStringXY(4),
				projection: map.getProjection(),
				target: document.getElementById(element)
			});
			map.getControls().extend([control]);
		}
	},

	LatLonPoint: {

		toProprietary: function() {
			return ol.proj.fromLonLat([this.lon, this.lat]);
		},

		fromProprietary: function(coord) {
			var lonLat = ol.proj.toLonLat(coord);
			this.lon = lonLat[0];
			this.lng = this.lon;
			this.lat = lonLat[1];
		}

	},

	Marker: {

		toProprietary: function() {
			var size, anchor, src, style, marker, position;
			if (!!this.iconSize) {
				size = this.iconSize;
			}
			else {
				size = [21, 25];
			}

			if (!!this.iconAnchor) {
				anchor = this.iconAnchor;
			}
			else {
				anchor = [ size[0] / 2, size[1] ];
			}

			if (!!this.iconUrl) {
			  src = this.iconUrl;
			}
			else {
			  src = 'https://openlayers.org/dev/img/marker-gold.png';
			}

			this.icon = new ol.style.Icon({
				anchor: anchor,
				anchorXUnits: 'pixels',
				anchorYUnits: 'pixels',
				size: size,
				src: src
			});

			this.style = new ol.style.Style({
				image: this.icon
			});

			position = this.location.toProprietary('openlayers');

			marker = new ol.Feature({
				geometry: new ol.geom.Point(position)
			});

			marker.setStyle(this.style);

			return marker;
		},

		openBubble: function() {
			var overlay = this.map.getOverlayById('popup')
      overlay.setOffset([0, -this.iconSize[1]]);
			overlay.getElement().innerHTML = this.infoBubble;
			overlay.setPosition(this.location.toProprietary('openlayers'));

			this.openInfoBubble.fire( { 'marker': this } );
		},

		closeBubble: function() {
			var overlay = this.map.getOverlayById('popup')
			overlay.setPosition(undefined);

			this.closeInfoBubble.fire( { 'marker': this } );
		},

		hide: function() {
			this.mapstraction.layers.markers.getSource().removeFeature(this.proprietary_marker);
		},

		show: function() {
			this.mapstraction.layers.markers.getSource().addFeature(this.proprietary_marker);
		},

		update: function() {
			throw new Error('Marker.update is not currently supported by provider ' + this.api);
		}
	},

	Polyline: {

		toProprietary: function() {
			var coords = [];
			var geometry;
			var hexOpacity = this.opacity ? Math.floor(this.opacity * 255).toString(16) : '';
			this.style = new ol.style.Style({
			  stroke: new ol.style.Stroke({
					color: this.color + hexOpacity,
					width: this.width
				})
			});

			for (var i = 0, length = this.points.length ; i< length; i++){
				var point = this.points[i].toProprietary('openlayers');
				coords.push(point);
			}

			if (this.closed) {
				if (!(this.points[0].equals(this.points[this.points.length - 1]))) {
					coords.push(coords[0]);
				}
			}

			else if (this.points[0].equals(this.points[this.points.length - 1])) {
				this.closed = true;
			}

			if (this.closed) {
				// a closed polygon
				geometry = new ol.geom.LinearRing(coords);
			} else {
				// a line
				geometry = new ol.geom.LineString(coords);
			}

			this.proprietary_polyline = new ol.Feature({
				geometry: geometry,
				name: 'polylines'
			});

			this.proprietary_polyline.setStyle(this.style);

			return this.proprietary_polyline;
		},

		show: function() {
			this.map.layers.polylines.removeFeature(this.proprietary_polyline);
		},

		hide: function() {
			this.map.layers.polylines.addFeature(this.proprietary_polyline);
		}
	}

});
