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
			zoomOnClick: true,
			styles: [
				{
					fontFamily: 'Ubuntu Condensed,Trebuchet MS,Verdana,sans-serif',
					textColor: 'white',
					height: 45,
					width: 45,
					url: this.opts.url_path + '/images/cluster.png'
				}

			]
		};
	this.forEach( markers, function( i, marker ) {
		this.map.addMarker( marker );
		proprietary_markers.push( marker.proprietary_marker );
	} );

	options.styles.push( this.clone( options.styles[0]) );
	options.styles[1].width = 52;
	options.styles[1].height = 52;
	options.styles[1].textSize = 12;
	options.styles.push( this.clone( options.styles[0]) );
	options.styles[2].width = 63;
	options.styles[2].height = 63;
	options.styles[2].textSize = 13;
	options.styles.push( this.clone( options.styles[0]) );
	options.styles[3].width = 75;
	options.styles[3].height = 75;
	options.styles[3].textSize = 14;
	options.styles.push( this.clone( options.styles[0]) );
	options.styles[4].width = 90;
	options.styles[4].height = 90;
	options.styles[4].textSize = 15;
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

