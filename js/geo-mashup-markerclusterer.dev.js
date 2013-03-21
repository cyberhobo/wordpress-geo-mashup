/**
 * Google v3 MarkerClusterer plus implementation
 * @fileOverview
 */

/**global GeoMashup, MarkerClusterer */

/**
 * Override addMarkers to kick off clustering.
 * @param markers
 */
GeoMashup.addMarkers = function( markers ) {
	var proprietary_markers = [];
	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
		proprietary_markers.push( marker.proprietary_marker );
	} );
	this.clusterer = new MarkerClusterer( this.map.getMap(), proprietary_markers, {
		ignoreHidden: true,
		minimumClusterSize: 4,
		maxZoom: parseInt( this.opts.cluster_max_zoom ),
		zoomOnClick: true
	} );
};

GeoMashup.recluster = function() {
	this.clusterer.repaint();
}

