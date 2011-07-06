mxn.addProxyMethods( mxn.Mapstraction, [ 'enableGeoMashupExtras' ]);

mxn.register( 'google', {
	Mapstraction: {
		enableGeoMashupExtras: function() {
			var me = this;
			me.markerAdded.addHandler( function( name, source, args ) {
				if ( args.marker.draggable ) {
					// add marker dragend event
					args.marker.dragend = new mxn.Event( 'dragend', args.marker );
					google.maps.Event.addListener( args.marker.proprietary_marker, 'dragend', function( latlng ) {
						args.marker.dragend.fire( { location: new mxn.LatLonPoint( latlng.lat(), latlng.lng() ) } );
					});
				}
			});
		}
	}
});