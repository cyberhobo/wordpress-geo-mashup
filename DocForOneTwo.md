#summary Installation, Upgrading, Configuration, Use, and Troubleshooting.
#labels Phase-Deploy



# Geo Mashup Plugin Documentation #

You should find what you need to use the current version of the plugin here.  The [documentation for version 1.1.3](DocForOneOneThree.md) is available if you're using that version. If you have questions or need help, try searching or posting in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).  Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

# Additional Guidance #

If you find yourself wanting more than you find here, Bruce at bioneural.net has contributed an [implementation guide](http://www.bioneural.net/2008/09/21/geo-mashup-implementation-guide/) that steps through accomplishing many cool things with the Geo Mashup plugin. Note that the implementation guide is based on Geo Mashup version 1.1.3.

# System Requirements #

As of version 1.2, WordPress 2.6 or higher is required. Geo Mashup is intended to support [the same PHP and MySql versions as WordPress](http://wordpress.org/about/requirements/), but testing resources are limited. Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you believe there's a problem with your version.

# First Time Installation #

Geo Mashup is available in the [WordPress Plugin Directory](http://wordpress.org/extend/plugins/), which means it can be installed directly from the WordPress admin interface in WordPress 2.7 and higher under Plugins / Add New. A search for Geo Mashup should work there.

## Downloading a .zip package ##

If you want to download you own package to install, you can get [your choice of Geo Mashup .zip packages](http://code.google.com/p/wordpress-geo-mashup/downloads/list).

Any Geo Mashup .zip package can be installed using the WordPress .zip install interface, also under Plugins / Add new. **Don't use this to upgrade, though.**

## FTP or other upload method ##

  1. Extract the geo-mashup folder from the archive.
  1. Upload this entire folder to your wp-content/plugins directory (not just the files, the whole geo-mashup folder).
  1. In the WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.

## Activation ##

After any install, you'll have to activate the plugin (from the plugins list if you're not prompted). You should then go to Settings / Geo Mashup to configure it.

# Upgrading #

Before you take the plunge, look over the UpgradeConsiderations to see if they'll affect you.

In WordPress 2.7 and higher, you can upgrade to a new stable version right from the Plugins list via the "upgrade automatically" link. **This will delete files you've created in the geo-mashup folder, like custom.js, or info-window.php.** See UpgradeConsiderations for safe places to put your custom files.

For beta versions, use the FTP or other upload method installation instructions above, overwriting existing files. It may be slightly safer to deactivate Geo Mashup before doing this.

After installing and activating a new version of Geo Mashup, you should update your Geo Mashup Options once to complete any necessary configuration changes.

# Getting Started #

Geo Mashup lets you associate location information with any post or page, then use that information in Google maps.

## Adding the location to a post or page ##

Near the bottom of the advanced editing area below the editor, a Location area has been added. Look at the very bottom of this screenshot:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/post_editor_screenshot.jpg)

In WordPress 2.7 and up you can drag the location area closer nearer to the top of the page, as in the screenshot. Click in the Find Location textbox and type a place name (like Chartre in this example) to do a search:

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/location_screenshot.jpg)

Notice the help link next to the Find Location textbox. Clicking this will display the following instructions for other ways to search for a location.

> Put a green pin at the location for this post. There are many ways to do it:

  * Search for a location name.
  * For multiple search results, mouse over pins to see location names, and click a result pin to select that location.
  * Search for a decimal latitude and longitude, like 40.123,-105.456.
  * Search for a street address, like 123 main st, anytown, acity.
  * Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.

> To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name and added to the Saved Locations dropdown list.

> To remove the location (green pin) for a post, clear the search box and hit the enter key.

> When you are satisfied with the location, save or update the post.

### KML Attachments ###

Instead of searching for a location, you can upload a KML
[file attachment](http://codex.wordpress.org/Using_Image_and_File_Attachments)
with a post or page using the "Add Media" button on the editor (see
screenshot). Once uploaded, you can insert a link in your post or just close the
upload window. The KML file (center) will be used to determine a default location
for the post or page. If you include a map in that post or page, it will load
and display the KML file.

![http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg](http://wordpress-geo-mashup.googlecode.com/svn/wiki/images/add_media_snip.jpg)

You can create a map in the My Maps tab of the [Google Maps page](http://maps.google.com/) and save it as KML using [these instructions](http://rickcogley.blogspot.com/2008/08/exporting-googles-my-maps-as-kml.html).

## Adding a Map ##

You can put a map in any page or post by typing the shortcode `[geo_mashup_map]` into the editor. Look at the TagReference for more options.

If a post or page has location information associated with it, the map displays that. Otherwise, a global map of all located posts and pages is displayed. There are lots of ways to customize the map.

You can put a map outside posts and pages with the template tag `<?php echo GeoMashup::map(); ?>`. The TagReference has details for template tags too.

## Adding a "show on map" link to posts with a location ##

You can add a link from any located item to your designated global map page, with the global map centered on the clicked item. Use the `[show_on_map_link]` shortcode tag for a single item, or the template tag `<?php echo GeoMashup::show_on_map_link(); ?>` to add links to all posts or pages. See the TagReference for details.

## Info Window Templating ##

The content in the info window is now generated by a template that works just like any other [WordPress template](http://codex.wordpress.org/Templates). Geo Mashup comes with a default template called `info-window-default.php` that generates content similar to prior versions. Don't edit this, but you can copy it to `geo-mashup-info-window.php` in your theme directory, and your custom template will be preserved in future upgrades.

You can also change the appearance of the info window and other map components, copy `map-style-default.css` to `map-style.css` in your theme folder to get started.

## Widgets ##

A helpful user Marcel found this way to use Geo Mashup shortcodes in the WordPress text widget:

> I can use `[geo_mashup_map]` like shortcodes in the text-widget by
> adding the following code at the start of "functions.php" of my active
> template, just after the "<?php" tag

> `add_filter('widget_text', 'do_shortcode');`

## Custom JavaScript ##

You can put custom javascript in a file called `custom.js`. Until Geo Mashup 1.2.5, this file had to be located in the
`geo-mashup` plugin folder, where it would be deleted when doing an automatic upgrade. To prevent this, you can now install the
[Geo Mashup Custom plugin](http://wordpress-geo-mashup.googlecode.com/files/geo-mashup-custom-1.0.zip), and put the `custom.js` file
in the `geo-mashup-custom` directory, where it will not be deleted. See UpgradeConsiderations for details.

In the `geo-mashup` folder is a file named `custom-sample.js` with some examples of what you can do in
`custom.js` using the [Googe Maps API](http://maps.google.com/apis/maps/documentation/).  `custom-sample.js`
contains examples for customizing marker icons based on category, assigned color, and number of posts at the location. There are also examples for recentering the map, and adding a GeoRSS feed to a map. Much more is possible.

If you need icon images, check out the [Google Maps Icons](http://code.google.com/p/google-maps-icons/) project.

## There's More ##

Look through the list of tags in the TagReference to see the growing variety of Geo Mashup capabilities.

# Geo Mashup Options #

In Settings / Geo Mashup there are four tabs where options can be set: Overall, Global Maps, Single Maps, Contextual Maps. Here is some more information on some of the more complex options.

## Overall ##

These options affect general behavior and do not apply to one particular kind of map.

### Global Mashup Page ###

You can have a single page with a global map that will be the target of links generated by Geo Mashup.

Create a page, include the `[geo_mashup_map]` tag in it, and select the page in this dropdown list. The "Show on Map Link" tag and "Add Category Links" options will now create links to this page, which will load a global map appropriate for the link.

### Use Theme Stylesheet ###

If checked, applies the main theme stylesheet to maps. Be careful! Some styles can mangle a Google map, or even make it disappear altogether.

Copy `map-style-default.css` to `map-style.css` in your theme folder for alternate ways to style the map.

### Add Category Links ###

If you have a list of category links in your sidebar, you can add a map link for each category that contains posts with coordinates. First set up a Global Mashup Page if you haven't already. In the Geo Mashup options, check Add Category Links and fill in the options for separator text, link text, and zoom level. Note that this will not work for dropdown-style category lists. **Your categories must have descriptions** or a WordPress problem will cause the links to appear mangled.

The "Category Link Seperator" and "Category Link Text" options are used with this option.

## Global Maps ##

Global maps can include multiple located posts in various combinations.

### Click to Load ###

This will create a gray panel with a Geo Mashup icon in place of a global, single, or contextual map, and some text to prompt the user to click the panel to load the map. This treats maps more like videos, leaving the overhead of playing them up to the user.

The "Click to Load Text" is displayed at the top of the panel.

### Enable Full Post Display ###

This feature lets you display the full post that is currently selected on the map in a separate area on the page.  Geo Mashup comes with a default template called `full-post-default.php` that generates the full post content. Don't edit this, but you can copy it to `geo-mashup-full-post.php` in your theme directory, and your custom template will be preserved in future upgrades.

You can use the Full Post tag as explained in the TagReference, then check 'Enable Full Post Display' in the options.

### Category Colors and Lines ###

Markers in a category can be connected by a line on a global map. Categories can be assigned a color for lines and markers in the Geo Mashup options.

At the bottom of Geo Mashup Options for global maps you should see your categories listed. For each category you can choose a color for the category markers and lines, and a "Show Connecting Line Until Zoom Level (0-17 or none)" setting. This allows you to specify that a line is only drawn for the category only up to the specified zoom level. When zoomed in closer than that, the line will disappear. Lines will not be drawn unless a zoom level is entered.

## Single Maps ##

These options are like the the Global Maps options, but are applied to maps in and of single posts.

## Contextual Maps ##

These options are like the Global Maps options, but are applied to maps that are based on a list of posts currently being displayed.

# Known Issues and General Help #

## Domain Name and API Key ##

The domain name you use to register your API key can be important. If you are getting an API key error, this may be the reason.

Your domain name (cyberhobo.net, for me) is important to this plugin in a few ways. The most common issue is that most websites will work with either the bare domain name (http://cyberhobo.net), or with a www. prefix (http://www.cyberhobo.net/). This plugin **will not** work with both names unless you follow these guidelines:

  1. Register your Google Maps API key for your bare domain name (like http://cyberhobo.net). This key will also work with a www., and will work for all pages on your site.
  1. Decide whether you want to use the www. prefix or not.
  1. Update the WordPress address URL and Blog address URL settings in the WordPress options to use your chosen domain name. You're all set.

Why is this important to the Geo Mashup plugin? [AJAX](http://en.wikipedia.org/wiki/AJAX). Geo Mashup uses this technology to communicate with your site behind the scenes to get markers and posts. Most browsers these days only allow this kind of communication with the same domain as the site being visited. If a visitor comes to cyberhobo.net, and the Geo Mashup plugin tries to communicate with www.cyberhobo.net, it's considered a security violation. Such is modern life. Until browsers behave differently, we have to accommodate them.

## Useful Background Links ##

Some uses of this plugin require background knowledge.  You don't have to be an expert on these things, but some familiarity will help.

  1. [Managing Plugins](http://codex.wordpress.org/Managing_Plugins)
  1. [Creating and Using Pages](http://codex.wordpress.org/Pages)
  1. [Templates](http://codex.wordpress.org/Stepping_Into_Templates)
  1. [Template Tags](http://codex.wordpress.org/Stepping_Into_Template_Tags)
  1. [Decimal Geo Coordinates](http://ioc.unesco.org/oceanteacher/oceanteacher2/06_OcDtaMgtProc/01_DataOps/06_OcDtaForm/01_OcDtaFormFunda/02_Geography/01_Locations/coordinates.htm)

## Help, Questions, and Troubleshooting ##

  1. Make sure you haven't missed anything relevant in the documentation above.
  1. Search [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). This is the place for questions and help - if you think there's a bug in the plugin, go to the next step.
  1. Check the [issues page](http://code.google.com/p/wordpress-geo-mashup/issues/list) to see if your problem has been reported yet, and if so what progress has been made on it. Only unresolved issues are listed by default, it might be worth searching solved issues to look for workarounds.
  1. Submit a new issue if your problem is not there.