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
	var proprietary_markers = [],
		options = {
			ignoreHidden: true,
			minimumClusterSize: 4,
			maxZoom: parseInt( this.opts.cluster_max_zoom ),
			zoomOnClick: true
		};
	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
		proprietary_markers.push( marker.proprietary_marker );
	} );
	/**
	 * The MarkerClustererPlus object is being created. Use this to change the options.
	 * @name GeoMashup#markerClustererOptions
	 * @event
	 * @param {Object} options Modifiable <a href="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/docs/reference.html#MarkerClustererOptions">MarkerClustererOptions</a>.
	 */
	this.doAction( 'markerClustererOptions', options );
	this.clusterer = new MarkerClusterer( this.map.getMap(), proprietary_markers, options );
};

GeoMashup.recluster = function() {
	this.clusterer.repaint();
};

