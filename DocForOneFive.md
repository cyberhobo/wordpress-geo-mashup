#summary Installation, Upgrading, Configuration, Use, and Troubleshooting.
#labels Phase-Deploy

This is the official Geo Mashup 1.5 documentation. (Looking for [1.4](DocForOneFour.md)?) The sidebar lists the main pages, and here is the table of contents for this page:



# System Requirements #

As of version 1.5, WordPress 3.4 or higher is required. Geo Mashup is intended to support [the same MySql versions as WordPress](http://wordpress.org/about/requirements/), but testing resources are limited. Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you believe there's a problem with your version.

# First Time Installation #

Geo Mashup is available in the [WordPress Plugin Directory](http://wordpress.org/extend/plugins/), which means it can be installed directly from the WordPress admin interface under Plugins / Add New. A search for Geo Mashup should work there.

## Downloading a .zip package ##

If you want to download you own package to install, you can get [your choice of Geo Mashup .zip packages](http://code.google.com/p/wordpress-geo-mashup/downloads/list).

Any Geo Mashup .zip package can be installed using the WordPress upload install interface, also under Plugins / Add new. This won't work, though, if you already have a version of Geo Mashup installed. If that's the case, WordPress 2.9 at least requires one of the following alternate installation methods.

## FTP or other upload method ##

  1. If a version of Geo Mashup is installed already, deactivate it.
  1. Extract the geo-mashup folder from the archive.
  1. Upload this entire folder to your wp-content/plugins directory (not just the files, the whole geo-mashup folder). Overwrite any existing Geo Mashup files.
  1. Activate the Geo Mashup plugin in the Plugins admin interface.

## Activation ##

After any install, you'll have to activate the plugin (from the plugins list if you're not prompted). You should then go to Settings / Geo Mashup to configure it.

# Upgrading #

There have been no changes to the upgrade procedure for a long time - if you've had one successful upgrade, the next should be the same.  For very old or customized installs look over the UpgradeConsiderations to see if they'll affect you.

You can upgrade to a new stable version right from the Plugins list via the "upgrade automatically" link. **This will delete files you've created in the geo-mashup folder.** Most users won't have to worry about this, but if you have created or edited files in the `geo-mashup` plugin folder, see UpgradeConsiderations.

To install beta versions, use the FTP or other upload method installation instructions above.

# Getting Started #

Geo Mashup lets you save location information for various "objects" such as posts (including custom types), pages, users, and comments. You can then put these objects on maps in posts, pages, or just about anywhere else on your site.

## Map Providers ##

Geo Mashup uses the [mapstraction library](http://www.mapstraction.com) to offer a choice of map providers. Visit the Geo Mashup settings to choose one in the Overall tab. Your choice has some consequences:

  * **Google Maps v2** support [expires soon](https://developers.google.com/maps/documentation/javascript/v2/reference).
  * **Google Maps v3** does not yet include marker clustering and will not include the Google bar. An [API key](https://developers.google.com/maps/documentation/javascript/tutorial#api_key) is optional for this choice.
  * **OpenLayers** does not yet include marker clustering, will not include the Google bar, and has some KML limitations. No API key is required for this choice.
  * More choices are possible and likely to be added - get in touch if you want to contribute!

## Save locations for things like posts ##

By default Geo Mashup won't show a map unless there is something like a located post to put on it. It's most common to save locations for posts and pages, but Geo Mashup can also save locations for custom post types, users, and comments. You can choose which of these you want to collect location for in the Overall settings - the default is posts and pages.

When adding or editing a post or paage, a Location area has been added near the bottom of the editor page:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg)

You can drag the location area closer nearer to the top of the page if you like. Click in the Find Location textbox and type a place name (like Chartre in this example) to do a search:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg)

Notice the help link next at the bottom. Clicking this will display the instructions for other ways to search for a location. The location that will be saved is marked with a green pin. If there's already a location saved, you can change it or delete it.

## Add a Map ##

You can put a map in any page or post by typing the shortcode `[geo_mashup_map]` into the editor. Look at the TagReference for more options.

### Single Map ###

If the post or page has a location, the map displays a Single Map showing only that location.

### Global Map ###

If the post or page has no location, a Global Map of all located posts and pages is displayed.

### Contextual Map ###

You can put a map outside posts and pages by using the template tag `<?php echo GeoMashup::map(); ?>` to make a Contextual Map that shows the located items listed nearby. (Technically contextual maps include located items in the current global query results - usually that's the main list of posts, or the page being viewed). The TagReference has details for template tags.

## What's Next ##

Now your imagination might go wild with the possibilities. Skim the rest of this page to see what's here. You might want to look at the [user-contributed guides](http://wiki.geo-mashup.org/guides) for ideas. When you're ready to add your map shortcodes and tags, look at the TagReference to see how far beyond a simple `[geo_mashup_map]` or `<?php echo GeoMashup::map(); ?>` you can go.

If you need help, you can offer to pay an amount of your choice for expert help at [WP Questions](http://wpquestions.com/affiliates/register/name/cyberhobo), or ask other users in the free [Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).

Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

# Geo Mashup Settings #

Find these in the WordPress administration area under Settings / Geo Mashup. You can get an overview of some Geo Mashup features here with brief descriptions.

# Using Features #

These are some of the common things you can do with Geo Mashup.

## Widgets ##

When the "Enable Geo Mashup Search Widget" setting is checked, a widget becomes available for doing a geo search for posts within a specified distance of a search location. A "find me" button allows a search near the user's current location.

Shortcodes will also work in a text widget.

## Taxonomies ##

There is an Overall setting that allows you to choose which taxonomies are included on maps. The default is post category only.

## Global Mashup Page ##

You can have a single page with a global map that will be the target of links generated by Geo Mashup.

Create a page, include the `[geo_mashup_map]` shortcode in it, and select the page in the Settings / Geo Mashup / Overall / Global Mashup Page dropdown list. The "Show on Map Link" tag and "Add Category Links" options will now create links to this page, which will load a global map appropriate for the link.

## Auto Zoom ##

Zoom levels can be set to 'auto', in both settings and tags. The zoom level is calculated to show all the content on the map. A consequence of this is that the content must first be added to the map before the final zoom level is set, and this operation may be visible when the map loads.

## Save a location by uploading a KML Attachment ##

Instead of searching for a location in the location editor, you can upload a KML [file attachment](http://codex.wordpress.org/Using_Image_and_File_Attachments) with a post or page using the "Add Media" button on the editor (see screenshot). Once uploaded, you can insert a link in your post or just close the upload window. The geographic center of all features in the KML file will be used to determine a default location for the post or page.

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg)

**Note**: Google maps can only load KML files that are accessible to its servers, so a KML attachment on a private domain (e.g. localhost) or site will not work with Google.

### Displaying a KML file ###

Once a page or post has a KML file attached, that file will be loaded in a single map.

You can also display KML files associated with the selected marker on a global map by checking "Show Geo Attachments" in Settings / Geo Mashup / Global Maps, or setting the marker\_select\_attachments parameter of the map tag to "true".

### Creating a KML file ###

You can create a map in the My Maps tab of the [Google Maps page](http://maps.google.com/) and save it as KML using [these instructions](http://rickcogley.blogspot.com/2008/08/exporting-googles-my-maps-as-kml.html).

## Add a "show on map" link to posts with a location ##

You can add a link from any located item to your designated global map page, with the global map centered on the clicked item. Use the `[show_on_map_link]` shortcode tag for a single item, or the template tag `<?php echo GeoMashup::show_on_map_link(); ?>` to add links to all posts or pages. See the TagReference for details.

## Customize the info window and other content ##

The content in the info window is generated by a template that works just like any other [WordPress template](http://codex.wordpress.org/Templates). Geo Mashup comes with a default template found in `default-templates/info-window.php` that generates content similar to prior versions. Don't edit this, but you can copy it to `geo-mashup-info-window.php` in your theme directory, and your custom template will be preserved in future upgrades. There are templates for other types of info window content that can also be customized by copying to your theme folder:

  * Copy `map-frame.php` to `geo-mashup-map-frame.php`
  * Copy `comment.php` to `geo-mashup-comment.php`
  * Copy `full-post.php` to `geo-mashup-full-post.php`
  * Copy `info-window-max.php` to `geo-mashup-info-window-max.php`
  * Copy `user.php` to `geo-mashup-user.php`
  * Copy `nearby-list.php` to `geo-mashup-nearby-list.php`
  * Copy `search-form.php` to `geo-mashup-search-form.php`
  * Copy `search-results.php` to `geo-mashup-search-results.php`

You can also change the styling of the info window and other map components, copy `css/map-style-default.css` to `map-style.css` in your theme folder to get started.

WordPress Background: [Templates](http://codex.wordpress.org/Stepping_Into_Templates) and [Template Tags](http://codex.wordpress.org/Stepping_Into_Template_Tags)

# More Feature Resources #

  * Most settings on the Geo Mashup settings page have a short description of the feature they enable. Those aren't repeated here, so look through them to be aware of top-level features.
  * Look through the list of tags in the TagReference to see the growing variety of Geo Mashup capabilities.
  * See [guides](http://wiki.geo-mashup.org/guides) by users who share how they accomplished their Geo Mashup setup.
  * Code snippets [tagged wordpress-geo-mashup in Snipplr](http://snipplr.com/all/tags/wordpress-geo-mashup/)
  * [The Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin) has many more snippets and wisdom compiled over the ages.
  * You can see [a fairly comprehensive list of enhancements](http://code.google.com/p/wordpress-geo-mashup/issues/list?can=1&q=type%3DEnhancement), with lots of related information.

# APIs For Developers #

I've been customizing Geo Mashup for my clients for years, and have developed decent APIs along the way to do it. Contact me [via this site](http://code.google.com/u/@VRBQRl1TDhNFXQB5/) or [my site](http://www.cyberhobo.net/freelance-work#contact) and I'll work with you. Or let me know why you won't use Geo Mashup, or why I should use something else. We developers don't talk enough, and this has resulted in a fragmented bunch of tools, none of which are as good as what we could make together. Talk to me!

## Custom JavaScript ##

JavaScript allows marker customization and other fine control over map behavior.
You'll install and activate the [Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip). This plugin will never be upgraded, and so provides a safe place for your edited files. Custom javascript code can be saved in a file called `custom.js` in the `geo-mashup-custom` plugin folder.

See UpgradeConsiderations for details about why a separate plugin is used - it protects your code from being overwritten by Geo Mashup upgrades.

If you need icon images, check out the [Google Maps Icons](http://code.google.com/p/google-maps-icons/) project.

### JavaScript and map providers ###

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

### JavaScript API ###

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


All of the Geo Mashup methods and action events are listed in the [JavaScript API reference](http://code.cyberhobo.net/jsdoc/geo-mashup-1.5/symbols/GeoMashup.html).

There are more examples in [user wiki guides](http://wiki.geo-mashup.org/guides). It can also be very useful to view the source of the custom javascript files of [the author's projects](http://www.cyberhobo.net/hire-me) and the user ExamplesInAction.

## Dependent Plugins ##

There are good possibilites for plugins to make use of Geo Mashup data. [Geo Mashup Search](https://github.com/cyberhobo/wp-geo-mashup-search) is an example.

If you need a Google API Key in your plugin, you can get Geo Mashup's with this PHP code: `$google_key = get_option( 'google_api_key' );`. This setting is not deleted if Geo Mashup is uninstalled.

## PHP Reference ##

Geo Mashup provides some WordPress filters documented in the FilterReference.

This version has complete inline [PhpDoc](http://www.phpdoc.org) documentation, which allows for [generated documentation](http://code.cyberhobo.net/phpdoc/geo-mashup-1.5/).

# Known Issues and General Help #

## Domain Name and API Key ##

The domain name you use to register your API key for the Google v2 provider can be important. If you are getting an API key error, this may be the reason.

Your domain name (cyberhobo.net, for me) is important to this plugin in a few ways. The most common issue is that most websites will work with either the bare domain name (http://cyberhobo.net), or with a www. prefix (http://www.cyberhobo.net/). This plugin **will not** work with both names unless you follow these guidelines:

  1. Register your Google Maps API key for your bare domain name (like http://cyberhobo.net). This key will also work with a www., and will work for all pages on your site.
  1. Decide whether you want to use the www. prefix or not.
  1. Update the WordPress address URL and Blog address URL settings in the WordPress options to use your chosen domain name. You're all set.

## Help, Questions, and Troubleshooting ##

  1. If you'd just like an expert answer to your question quickly, use [WP Questions](http://wpquestions.com/affiliates/register/name/cyberhobo) and mention that your question is about Geo Mashup.
  1. Make sure you haven't missed anything relevant in the documentation above.
  1. See if the [wiki for user-contributed documentation](http://wiki.geo-mashup.org/) has what you need.
  1. Search [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). This is the place for questions and help - if you think there's a bug in the plugin, go to the next step.
  1. Check the [issues page](http://code.google.com/p/wordpress-geo-mashup/issues/list) to see if your problem has been reported yet, and if so what progress has been made on it. Only unresolved issues are listed by default, it might be worth searching solved issues to look for workarounds.
  1. Submit a new issue if your problem is not there.
