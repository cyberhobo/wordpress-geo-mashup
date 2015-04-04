#summary Installation, Upgrading, Configuration, Use, and Troubleshooting.
#labels Phase-Deploy



# Geo Mashup Plugin Documentation #

This is the official Geo Mashup documentation.  There is now a [wiki for user-contributed documentation](http://wiki.geo-mashup.org/) also. If you have questions or need help, try searching or posting in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).  Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

# System Requirements #

As of version 1.3, WordPress 2.8 or higher is required. Geo Mashup is intended to support [the same PHP and MySql versions as WordPress](http://wordpress.org/about/requirements/), but testing resources are limited. Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you believe there's a problem with your version.

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

If you've upgraded in the past year or so, the same method you used should work again.  Otherwise, look over the UpgradeConsiderations to see if they'll affect you.

You can upgrade to a new stable version right from the Plugins list via the "upgrade automatically" link. **This will delete files you've created in the geo-mashup folder.** Most users won't have to worry about this, but if you have created or edited files in the `geo-mashup` plugin folder, see UpgradeConsiderations.

In Geo Mashup 1.3.x, the map type control options have changed such that you may have to update your Geo Mashup settings to get the same map type control you had in 1.2.x.

To install beta versions, use the FTP or other upload method installation instructions above.

# Getting Started #

Geo Mashup lets you save location information for various "objects" such as posts, pages, users, and comments. You can then put these objects on maps in posts, pages, or just about anywhere else on your site.

## Get your Google Key ##

After Geo Mashup is installed and activated, you may get a message that a Google API key is required. It's a quick, free, one-step process to get one. Visit the Geo Mashup settings page and follow the link to get your key, then copy and paste it into the Geo Mashup setting and update. This is the only required setting.

## Save locations for posts or pages ##

Geo Mashup maps will be blank, centered a latitude and longitude zero off the coast of Africa, until you've saved at least one location to put on the map. Most commonly you'll save locations for posts and pages.

Near the bottom of the advanced editing area below the editor, a Location area has been added. Look at the very bottom of this screenshot:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg)

You can drag the location area closer nearer to the top of the page, as in the screenshot. Click in the Find Location textbox and type a place name (like Chartre in this example) to do a search:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg)

Notice the help link next to the Find Location textbox (now at the bottom). Clicking this will display the instructions for other ways to search for a location. The location that will be saved is marked with a green pin. If there's already a location saved, you can change it or delete it.

## Add a Map ##

You can put a map in any page or post by typing the shortcode `[geo_mashup_map]` into the editor. Look at the TagReference for more options.

If a post or page has location information associated with it, the map displays that. Otherwise, a global map of all located posts and pages is displayed.

You can put a map outside posts and pages with the template tag `<?php echo GeoMashup::map(); ?>`. The TagReference has details for template tags too.

## What's Next ##

Now your imagination might go wild with the possibilities. The next section outlines the Geo Mashup settings page in the WordPress admin, which should give you a sense of the different types of maps and their settings. After that more involved features are described, followed my more resources for Geo Mashup explorers. Finally you'll find links to resources for developers to accomplish almost any kind of customization.

Don't forget - if you have questions or need help, try searching or posting in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).  Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

# Geo Mashup Options #

In Settings / Geo Mashup there are four tabs where settings can be set: Overall, Global Maps, Single Maps, Contextual Maps.

## Overall ##

These options affect general behavior and do not apply to one particular kind of map.

### Google API Key ###

The only required setting, this key is used to create Google maps on your site. It's a quick, free process to get one at the link provided.

### Global Mashup Page ###

You can have a single page with a global map that will be the target of links generated by Geo Mashup.

Create a page, include the `[geo_mashup_map]` tag in it, and select the page in this dropdown list. The "Show on Map Link" tag and "Add Category Links" options will now create links to this page, which will load a global map appropriate for the link.

WordPress background: [Creating and Using Pages](http://codex.wordpress.org/Pages)

### Use Theme Stylesheet ###

If checked, applies the main theme stylesheet to maps. Be careful! Some styles can mangle a Google map, or even make it disappear altogether.

Copy `map-style-default.css` to `map-style.css` in your theme folder for alternate ways to style the map.

### Add Category Links ###

If you have a list of category links in your sidebar, you can add a map link for each category that contains posts with coordinates. First set up a Global Mashup Page if you haven't already. In the Geo Mashup options, check Add Category Links and fill in the options for separator text, link text, and zoom level. Note that this will not work for dropdown-style category lists. **Your categories must have descriptions** or a WordPress problem will cause the links to appear mangled.

The "Category Link Seperator" and "Category Link Text" options are used with this option.

### Collect Location For ###

If Posts is checked, Geo Mashup's map-based location editing interface is included in both of the WordPress post and page editing interfaces.

If Users is checked, Geo Mashup's map-based location editing interface is included in the WordPress user and profile editing interfaces.

If Comments is checked, Google's [client location API](http://code.google.com/apis/ajax/documentation/#ClientLocation) is used to store an approximate location for the comment.

### Enable Reverse Geocoding ###

If checked, an attempt is made to look up address information for locations that are saved without some address elements (locality/city, admin code/state, country, postal code).

### Bulk Reverse Geocode ###

This button triggers a process that runs the reverse geocoding lookup described above on all saved locations that are missing those address elements.

### AdSense For Search ID ###

If the Google bar is enabled (another setting), this [AdSense](https://www.google.com/adsense/) ID is used to connect Google bar search results with your account.

## Global Maps ##

Global maps can include multiple located objects in various combinations.

### Basic Properties ###

These properties are common to all maps that and are fairly self-explanatory: Map Height, Map Width, Map Control, Default Map Type, Add Map Type Control, Add Overview Control, Add Google Bar, Enable Scroll Wheel Zoom, and Default Zoom Level. Experiment with any of these to see how they affect your maps.

### Cluster Makers Until Zoom Level ###

Clustering combines overlapping with single markers displaying the number of underlying markers that have been combined. Clicking a cluster zooms the map to an area that includes all the markers in that cluster.

Zooming in beyond the set the zoom level will again disable clustering, so all markers are shown.

### Marker Selection Behaviors ###

Check what you would like to happen when a marker is clicked.

### Click to Load ###

This will create a gray panel with a Geo Mashup icon in place of a global, single, or contextual map, and some text to prompt the user to click the panel to load the map. This treats maps more like videos, leaving the overhead of playing them up to the user.

The "Click to Load Text" is displayed at the top of the panel.

### Enable Full Post Display ###

This feature lets you display the full post that is currently selected on the map in a separate area on the page, where you'll place the [full post tag](http://code.google.com/p/wordpress-geo-mashup/wiki/TagReference#Full_Post).

#### Full Post Template ####

Geo Mashup comes with a default template called `full-post-default.php` that generates the full post content. Don't edit this, but you can copy it to `geo-mashup-full-post.php` in your theme directory, and your custom template will be preserved in future upgrades.

WordPress Background: [Templates](http://codex.wordpress.org/Stepping_Into_Templates) and [Template Tags](http://codex.wordpress.org/Stepping_Into_Template_Tags)

### Category Colors and Lines ###

Markers in a category can be connected by a line on a global map. Categories can be assigned a color for lines and markers in the Geo Mashup options.

At the bottom of Geo Mashup Options for global maps you should see your categories listed. For each category you can choose a color for the category markers and lines, and a "Show Connecting Line Until Zoom Level (0-17 or none)" setting. This allows you to specify that a line is only drawn for the category only up to the specified zoom level. When zoomed in closer than that, the line will disappear. Lines will not be drawn unless a zoom level is entered.

## Single Maps ##

These options are like the the Global Maps options, but are applied to maps in and of single posts.

## Contextual Maps ##

These options are like the Global Maps options, but are applied to maps that are based on a list of posts currently being displayed.

# Using Features #

These are some of the common things you can do with Geo Mashup.

## Auto Zoom ##

Zoom levels can be set to 'auto', in both settings and tags. The zoom level is calculated to show all the content on the map. A consequence of this is that the content must first be added to the map before the final zoom level is set.

## Save a location by uploading a KML Attachment ##

Instead of searching for a location in the location editor, you can upload a KML [file attachment](http://codex.wordpress.org/Using_Image_and_File_Attachments) with a post or page using the "Add Media" button on the editor (see screenshot). Once uploaded, you can insert a link in your post or just close the upload window. The geographic center of all features in the KML file will be used to determine a default location for the post or page.

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg)

### Displaying a KML file ###

Once a page or post has a KML file attached, that file will be loaded in a single
map.

You can also display KML files associated with the selected marker on a global map
by checking "Show Geo Attachments" in Settings / Geo Mashup / Global Maps, or setting
the marker\_select\_attachments parameter of the map tag to "true".

### Creating a KML file ###

You can create a map in the My Maps tab of the [Google Maps page](http://maps.google.com/) and save it as KML using [these instructions](http://rickcogley.blogspot.com/2008/08/exporting-googles-my-maps-as-kml.html).

## Add a "show on map" link to posts with a location ##

You can add a link from any located item to your designated global map page, with the global map centered on the clicked item. Use the `[show_on_map_link]` shortcode tag for a single item, or the template tag `<?php echo GeoMashup::show_on_map_link(); ?>` to add links to all posts or pages. See the TagReference for details.

## Customize the Info Window contents ##

The content in the info window is generated by a template that works just like any other [WordPress template](http://codex.wordpress.org/Templates). Geo Mashup comes with a default template called `info-window-default.php` that generates content similar to prior versions. Don't edit this, but you can copy it to `geo-mashup-info-window.php` in your theme directory, and your custom template will be preserved in future upgrades. There are templates for other types of info window content that can also be customized by copying to your theme folder:

  * Copy `comment-default.php` to `geo-mashup-comment.php`
  * Copy `full-post-default.php` to `geo-mashup-full-post.php`
  * Copy `info-window-max-default.php` to `geo-mashup-info-window-max.php`
  * Copy `user-default.php` to `geo-mashup-user.php`

You can also change the styling of the info window and other map components, copy `map-style-default.css` to `map-style.css` in your theme folder to get started.

WordPress Background: [Templates](http://codex.wordpress.org/Stepping_Into_Templates) and [Template Tags](http://codex.wordpress.org/Stepping_Into_Template_Tags)

## Widgets ##

Geo Mashup doesn't create new widgets, but you can use Geo Mashup shortcodes in a standard WordPress text widget. See the TagReference for shortcodes.

# More Feature Resources #

  * Look through the list of tags in the TagReference to see the growing variety of Geo Mashup capabilities.
  * [Tutorials](Tutorials.md) by users who share how they accomplished their Geo Mashup setup.
  * Code snippets [tagged wordpress-geo-mashup in Snipplr](http://snipplr.com/all/tags/wordpress-geo-mashup/)
  * [The Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin) has many more snippets and wisdom compiled over the ages.
  * You can see [a fairly comprehensive list of enhancements](http://code.google.com/p/wordpress-geo-mashup/issues/list?can=1&q=type%3DEnhancement), with lots of related information.

# APIs For Developers #

The methods for heavily customizing Geo Mashup in an upgradeable way are improving. Help and feedback on this front is welcome.

## Custom JavaScript ##

JavaScript allows marker customization and other fine control over map behavior.
You'll install and activate the [Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip). This plugin will never be upgraded, and so provides a safe place for your edited files. Custom javascript goes in a file called `custom.js` in the `geo-mashup-custom` plugin folder.

See UpgradeConsiderations for details about why a separate plugin is used.

There is a richer Javascript API available than in previous versions, but I don't yet consider it stable. See [issue 292](https://code.google.com/p/wordpress-geo-mashup/issues/detail?id=292) for some sample code.

If you need icon images, check out the [Google Maps Icons](http://code.google.com/p/google-maps-icons/) project.

## Dependent Plugins ##

There are good possibilites for plugins to make use of Geo Mashup data. I'll put up an example when I have a good one.

If you need a Google API Key in your plugin, you can get Geo Mashup's with this PHP code: `$google_key = get_option( 'google_api_key' );`. This setting is not deleted if Geo Mashup is uninstalled.

## PHP Reference ##

Geo Mashup provides some WordPress filters documented in the FilterReference.

This version has complete inline [PhpDoc](http://www.phpdoc.org) documentation, which allows for [generated documentation](http://code.cyberhobo.net/phpdoc/geo-mashup-1.3/).

# Known Issues and General Help #

## Domain Name and API Key ##

The domain name you use to register your API key can be important. If you are getting an API key error, this may be the reason.

Your domain name (cyberhobo.net, for me) is important to this plugin in a few ways. The most common issue is that most websites will work with either the bare domain name (http://cyberhobo.net), or with a www. prefix (http://www.cyberhobo.net/). This plugin **will not** work with both names unless you follow these guidelines:

  1. Register your Google Maps API key for your bare domain name (like http://cyberhobo.net). This key will also work with a www., and will work for all pages on your site.
  1. Decide whether you want to use the www. prefix or not.
  1. Update the WordPress address URL and Blog address URL settings in the WordPress options to use your chosen domain name. You're all set.

## Help, Questions, and Troubleshooting ##

  1. Make sure you haven't missed anything relevant in the documentation above.
  1. Search [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). This is the place for questions and help - if you think there's a bug in the plugin, go to the next step.
  1. Check the [issues page](http://code.google.com/p/wordpress-geo-mashup/issues/list) to see if your problem has been reported yet, and if so what progress has been made on it. Only unresolved issues are listed by default, it might be worth searching solved issues to look for workarounds.
  1. Submit a new issue if your problem is not there.