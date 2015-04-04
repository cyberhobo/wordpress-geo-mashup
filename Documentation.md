#summary Installation, Upgrading, Configuration, Use, and Troubleshooting.
#labels Featured,Phase-Deploy

This is the official Geo Mashup documentation. The sidebar lists the main pages, and here is the table of contents for this page:



# System Requirements #

As of version 1.6, WordPress 3.5 or higher is required. Geo Mashup is intended to support [the same MySql versions as WordPress](http://wordpress.org/about/requirements/), but not all configurations are tested. Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you believe there's a problem with your version.

# Installation and Upgrades #

Geo Mashup supports [standard WordPress methods](http://codex.wordpress.org/Managing_Plugins) for installation and upgrades.

# Configuration #

After installing, activate the plugin (from the plugins list if you're not prompted). You should then go to Settings / Geo Mashup to configure it.

# Getting Started #

Geo Mashup lets you save location information for various "objects" such as posts (including custom types), pages, users, and comments. You can then put these objects on maps in posts, pages, or just about anywhere else on your site.

## Map Providers ##

Geo Mashup uses the [mapstraction library](http://www.mapstraction.com) to offer a choice of map providers. Visit the Geo Mashup settings to choose one in the Overall tab. Your choice has some consequences:

  * **Google Maps v2** support [expires soon](https://developers.google.com/maps/documentation/javascript/v2/reference).
  * **Google Maps v3** now supports clustering. An [API key](https://developers.google.com/maps/documentation/javascript/tutorial#api_key) is optional for this choice.
  * **OpenLayers** does not yet include marker clustering, will not include the Google bar, and has some KML limitations. No API key is required for this choice.
  * More choices are possible and likely to be added - get in touch if you want to contribute!

## Save locations for things like posts ##

By default Geo Mashup won't show a map unless there is something like a located post to put on it. It's most common to save locations for posts and pages, but Geo Mashup can also save locations for custom post types, users, and comments. You can choose which of these you want to collect location for in the Overall settings - the default is posts and pages.

When adding or editing a post or page, a Location area has been added near the bottom of the editor page:

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

### Getting the wrong kind of map? ###

There are situations when Geo Mashup won't output the kind of map you want. You can specify one of the types with the map\_content parameter, like `[geo_mashup_map map_content="single"]` or `<?php echo GeoMashup::map( "map_content=global" ); ?>`. See [TagReference#Query\_Variables](TagReference#Query_Variables.md) for details.

## What's Next ##

Now your imagination might go wild with the possibilities. Skim the rest of this page to see what's here. You might want to look at the [user-contributed guides](http://wiki.geo-mashup.org/guides) for ideas. When you're ready to add your map shortcodes and tags, look at the TagReference to see how far beyond a simple `[geo_mashup_map]` or `<?php echo GeoMashup::map(); ?>` you can go.

If you need help, you can offer to pay an amount of your choice for expert help at [WP Questions](http://wpquestions.com/affiliates/register/name/cyberhobo), or ask other users in the free [Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).

Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

# Geo Mashup Settings #

Find these in the WordPress administration area under Settings / Geo Mashup. You can get an overview of some Geo Mashup features here with brief descriptions.

# Using Features #

These are some of the common things you can do with Geo Mashup.

## Widgets ##

Shortcodes will work in a text widget.

### Geo Mashup Search Widget ###

Geo Mashup can provide a geo search widget to find posts within a specified distance of a search location. A "find me" button allows a search near the user's current location.

  1. Under Settings / Geo Mashup / Overall check the "Enable Geo Search" setting and update options.
  1. Create a page where search results will be displayed.
  1. Under Appearance / Widgets place the Geo Mashup Search widget where you would like it to appear.
  1. Fill in the widget settings, making sure to select the results page you created in step 2, and save.

## Clustering ##

Marker clustering is currently supported with Google maps only.

For global maps with many markers, it can be more meaningful to see how many markers are in an area than masses of overlapping markers. Clustering does this for you. Because the problem is more severe at lower zoom levels, it's enabled by zoom level in the "Cluster Markers Until Zoom Level" setting for global maps.

Clusters are represented by a partially transparent red circle icon. The minimum cluster size is 4 markers, and clicking a cluster will zoom to that area in the map. [Custom javascript](Documentation#Custom_JavaScript.md) code can modify these [MarkerClustererPlus options](http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/docs/reference.html#MarkerClustererOptions) using the 'markerClustererOptions' action.

## Taxonomies ##

There is an Overall setting that allows you to choose which taxonomies are included on maps. The default is post category only.

## Global Mashup Page ##

You can have a single page with a global map that will be the target of links generated by Geo Mashup.

Create a page, include the `[geo_mashup_map]` shortcode in it, and select the page in the Settings / Geo Mashup / Overall / Global Mashup Page dropdown list. The "Show on Map Link" tag and "Add Category Links" options will now create links to this page, which will load a global map appropriate for the link.

## Auto Zoom ##

Zoom levels can be set to 'auto', in both settings and tags. The zoom level is calculated to show all the content on the map. A consequence of this is that the content must first be added to the map before the final zoom level is set, and this operation may be visible when the map loads.

## Save a location by uploading a KML Attachment ##

Instead of searching for a location in the location editor, you can upload a KML [file attachment](http://codex.wordpress.org/Using_Image_and_File_Attachments) with a post or page using the "Add Media" button above the editor. Once uploaded, you can insert a link in your post or just close the upload window. The geographic center of all features in the KML file will be used to determine a default location for the post or page. Review this location in the location editor, adjust if needed, and save it.

**Note**: Google maps can only load KML files that are accessible to its servers, so a KML attachment on a private domain (e.g. localhost) or site will not work with Google.

### Displaying a KML file ###

Once a page or post has a KML file attached, that file will be loaded in a single map.

You can also display KML files associated with the selected marker on a global map by checking "Show Geo Attachments" in Settings / Geo Mashup / Global Maps, or setting the marker\_select\_attachments parameter of the map tag to "true".

### Creating a KML file ###

You can create a map in the My Maps tab of the [Google Maps page](http://maps.google.com/) and save it as KML using [these instructions](http://rickcogley.blogspot.com/2008/08/exporting-googles-my-maps-as-kml.html).

## Add a "show on map" link to posts with a location ##

You can add a link from any located item to your designated global map page, with the global map centered on the clicked item. Use the `[show_on_map_link]` shortcode tag for a single item, or the template tag `<?php echo GeoMashup::show_on_map_link(); ?>` to add links to all posts or pages. See the TagReference for details.

## Customize the info window and other content (templating) ##

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

# APIs For More Customization #

Most map customization is done via the [javascript API](JavaScriptApi.md), while the [PHP API](PhpApi.md) applies mostly to map data.

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
