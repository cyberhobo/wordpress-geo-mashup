/**
 * Google v3 MarkerClusterer plus implementation
 * @fileOverview
 */

/**global GeoMashup, MarkerClusterer, Modernizr */

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
			zoomOnClick: true,
			styles: []
		},
		base_style = {
			fontFamily: 'Ubuntu Condensed,Trebuchet MS,Verdana,sans-serif',
			textColor: 'white'
		},
		sizes = [ 45, 52, 63, 75, 90 ];

	function icon_url( size ) {
		var url = GeoMashup.opts.url_path;
		if ( Modernizr.backgroundsize ) {
			url += '/images/cluster.png';
		} else {
			url += '/images/cluster-' + size + '.png';
		}
		return url;
	}

	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
		proprietary_markers.push( marker.proprietary_marker );
	} );

	this.forEach( sizes, function( i, size ) {
		options.styles[i] = this.clone( base_style );
		options.styles[i].width = size;
		options.styles[i].height = size;
		options.styles[i].url = icon_url( size );
	} );
	/**
	 * <a href="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/docs/reference.html#MarkerClustererOptions">MarkerClustererPlus options</a>
	 * are being set for a Google v3 map.
	 *
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

