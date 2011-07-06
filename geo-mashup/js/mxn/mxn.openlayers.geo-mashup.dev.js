mxn.addProxyMethods( mxn.Mapstraction, [ 'enableGeoMashupExtras' ]);

mxn.register( 'openlayers', {
	Mapstraction: {
		enableGeoMashupExtras: function() {
			var me = this, map = me.maps[me.api];

			map.events.register( 'addlayer', map, function( event ) {
				var new_layer_index;
				if ( 'markers' != event.layer && me.layers.markers ) {
					new_layer_index = map.getLayerIndex( event.layer ); 
					if ( new_layer_index > map.getLayerIndex( me.layers.markers ) ) {
						// Move the markers layer back to the top
						map.setLayerIndex( me.layers.markers, new_layer_index );
					} 
				}
			} );
		}
	}
});