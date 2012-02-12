mxn.addProxyMethods( mxn.Mapstraction, [ 'enableGeoMashupExtras', 'setMapTypes' ]);

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
	}
});