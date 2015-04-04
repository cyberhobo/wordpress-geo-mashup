JavaScript allows marker customization and other fine control over map behavior.



# Installation #

You'll install and activate the [Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip). This plugin will never be upgraded, and so provides a safe place for your edited files so they aren't overwritten by upgrades.

If you need icon images, check out the [Google Maps Icons](http://code.google.com/p/google-maps-icons/) project.

# JavaScript and map providers #

Until version 1.4, Geo Mashup used only v2 of the Google Maps API for maps, and custom javascript would be written only for that API. Version 1.4 still supports that API, but uses the Mapstraction library to enable more map providers, and now custom javascript can be written for Mapstraction or even tailored to specific map providers.

So if you have Geo Mashup javascript customizations for Google v2, they may not work with a new map provider. Calls to the [Google Maps API v2](http://code.google.com/apis/maps/documentation/javascript/v2/reference.html) need to be replaced with calls to the [mapstraction API](http://mapstraction.com/mxn/build/latest/docs/) or the API of the provider you choose. The key changes are:

  * **properties.map\_api** identifies the map API in use, currently 'google', 'googlev3', or 'openlayers'.
  * **map** the map object is an instance of mxn.Mapstraction for 'googlev3' and 'openlayers'. Other objects like markers are instances of the corresponding mapstraction object.

To help keep custom javascript small and specific to a particular provider API, Geo Mashup loads the custom javascript file according to this file name scheme:

`custom-<provider>.js`

Where `<provider>` is currently google, googev3, or openlayers. If that doesn't exist, but the provider is not google, this file is used for general Mapstraction customizations:

`custom-mxn.js`

And if that is not present or appropriate, the original is loaded:

`custom.js`

# Back end editor customization #

You may also add custom javascript for the back end location editor in the file:

`location-editor.js`

# Usage #

The API is based on an event interface that invokes callback functions for named actions. As an example we'll use the 'loadedMap' action to add a Flickr layer of squirrel pictures to the map.

If you're targeting the old Google v2 map provider, this code could go in a file named `custom-google.js`:

```

// An Example Google Maps API v2 customization

GeoMashup.addAction( 'loadedMap', function ( properties, map ) {
	var kml;

	// Load some KML only into global maps - for instance pictures of squirrels

	if (properties.map_content == 'global') {

		// Make the Google Maps API v2 calls to add a Flickr KML layer
		kml = new google.maps.GeoXml("http://api.flickr.com/services/feeds/geo/?g=52241987644@N01&lang=en-us&format=kml");
		map.addOverlay(kml);

	}

} );

```

Any other map provider can be targeted in a `custom-mxn.js` file, but it will only work if the provider supports the features used:

```

// An Example Mapstraction customization

GeoMashup.addAction( 'loadedMap', function ( properties, map ) {

	// Load some KML only into global maps - for instance pictures of squirrels

	if (properties.map_content == 'global') {

		// Make the Mapstraction call to add a Flickr KML layer
		// This will only work with a provider that supports it (googlev3 does, openlayers doesn't)
		map.addOverlay( 'http://api.flickr.com/services/feeds/geo/?g=52241987644@N01&lang=en-us&format=kml' );

	}

} );

```

Or, you can bypass Mapstraction and use a provider's native API using the Mapstraction [getMap](http://mapstraction.com/mxn/build/latest/docs/symbols/mxn.Mapstraction.html#getMap) function. Here's a google V3 native example that might go in `custom-googlev3.js`:

```

// An Example Google V3 customization

GeoMashup.addAction( 'loadedMap', function ( properties, mxn ) {

	// Load some KML only into global maps - for instance pictures of squirrels

	var google_map = mxn.getMap();

	if (properties.map_content == 'global') {

		// Make the Google v3 call to add a Flickr KML layer
		var kml = new google.maps.KmlLayer( 'http://api.flickr.com/services/feeds/geo/?g=52241987644@N01&lang=en-us&format=kml', {
			map: google_map
		} );

	}

} );

```


All of the Geo Mashup methods and action events are listed in the [JavaScript API reference](http://code.cyberhobo.net/jsdoc/geo-mashup-1.8/symbols/GeoMashup.html).

There are more examples in [user wiki guides](http://wiki.geo-mashup.org/guides). It can also be very useful to view the source of the custom javascript files of [the author's projects](http://www.cyberhobo.net/my-work) and the user ExamplesInAction.

## Location Editor API ##

The back end editor API is a little different in that it uses [Mapstraction events](http://mapstraction.com/mxn/build/latest/docs/symbols/mxn.Event.html).

Here's an example of `location-editor.js` custom code that sets the center and zoom level of the editor map:

```

/* Specify the initial location/zoom level for the location editor map */

geo_mashup_location_editor.mapCreated.addHandler( function() {
	  geo_mashup_location_editor.map.setCenterAndZoom( new mxn.LatLonPoint( 39, -119.1 ), 8 );
} );

```

Methods and events are listed in the [JavaScript API reference](http://code.cyberhobo.net/jsdoc/geo-mashup-1.6/symbols/geo_mashup_location_editor.html).

