mxn.addProxyMethods( mxn.Mapstraction, [ 'enableGeoMashupExtras', 'setMapTypes' ], false );

mxn.register( 'googlev3', {
	Mapstraction: {
		enableGeoMashupExtras: function() {
			var me = this;
			me.markerAdded.addHandler( function( name, source, args ) {
				if ( args.marker.draggable ) {
					// add marker dragend event
					args.marker.dragend = new mxn.Event( 'dragend', args.marker );
					google.maps.event.addListener( args.marker.proprietary_marker, 'dragend', function( mouse ) {
						args.marker.dragend.fire( {
							location: new mxn.LatLonPoint( mouse.latLng.lat(), mouse.latLng.lng() )
						} );
					});
				}
			});

			// Fire endPan when the center changes (add a better event to mxn?)
			// Lets us detect recentering after a KML layer is loaded
			google.maps.event.addListener( me.maps['googlev3'], 'center_changed', function() {
				me.endPan.fire()
			} );
		},

		setMapTypes: function( types ) {
			var map = this.maps[this.api];
			var mapTypeIds = [];
			var i;
			if ( typeof types !== 'object' ) {
				types = [ types ];
			}
			for ( i = 0; i < types.length; i += 1 ) {
				switch( types[i] ) {
					case mxn.Mapstraction.ROAD:
						mapTypeIds.push(google.maps.MapTypeId.ROADMAP);
						break;
					case mxn.Mapstraction.SATELLITE:
						mapTypeIds.push(google.maps.MapTypeId.SATELLITE);
						break;
					case mxn.Mapstraction.HYBRID:
						mapTypeIds.push(google.maps.MapTypeId.HYBRID);
						break;
					case mxn.Mapstraction.PHYSICAL:
						mapTypeIds.push(google.maps.MapTypeId.TERRAIN);
						break;
				}	
			}
			map.setOptions( {
				mapTypeControlOptions: {
					mapTypeIds: mapTypeIds
				}
			} );
		}
	},

	/**
	 * Override info bubbles to use a single google info window object.
	 *
	 * This prevents multiple info windows from popping up when markerclusterer
	 * adds and removes markers from the map.
	 *
	 * Maybe mapstraction would also benefit a from single-bubble option that does this?
	 */
	Marker: {
		openBubble: function() {
			var marker = this;
			if (!this.map.hasOwnProperty('geo_mashup_infowindow') || this.map.geo_mashup_infowindow === null) {
				this.map.geo_mashup_infowindow = new google.maps.InfoWindow();
				google.maps.event.addListener(this.map.geo_mashup_infowindow, 'closeclick', function(closedWindow) {
					marker.closeBubble();
				});
				if ( !this.proprietary_marker.map ) {
					// Marker is clustered and needs to have the info window repositioned
					google.maps.event.addListenerOnce( this.map.geo_mashup_infowindow, 'domready', function() {
						marker.map.geo_mashup_infowindow.close();
						marker.map.geo_mashup_infowindow.setPosition( marker.proprietary_marker.getPosition() );
						marker.map.geo_mashup_infowindow.open( marker.map );
					});
				}
			}
			this.openInfoBubble.fire( { 'marker': this } );
			this.map.geo_mashup_infowindow.setContent( this.infoBubble );
			this.map.geo_mashup_infowindow.open( this.map, this.proprietary_marker );
		},

		closeBubble: function() {
			if (this.map.hasOwnProperty('geo_mashup_infowindow') && this.map.geo_mashup_infowindow !== null) {
				this.map.geo_mashup_infowindow.close();
				this.closeInfoBubble.fire( { 'marker': this } );
			}
		}

	}
});
