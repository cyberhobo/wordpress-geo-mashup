#summary Installation, Upgrading, Configuration, Use, and Troubleshooting.
#labels Phase-Deploy

# Geo Mashup Plugin Documentation For Version 1.1.3 #

Contents:


You should find what you need to use the plugin here. If you have questions or need help, try searching or posting in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). Please [submit an issue](http://code.google.com/p/wordpress-geo-mashup/issues/list) if you run into problems, or would like to request a feature.

## Additional Guidance ##

If you find yourself wanting more than you find here, Bruce at bioneural.net has contributed an [implementation guide](http://www.bioneural.net/2008/09/21/geo-mashup-implementation-guide/) that steps through accomplishing many cool things with the Geo Mashup plugin.

## System Requirements ##

As of version 1.1, WordPress 2.5.1 or higher is required.

## Installation ##

  1. If you have the [Geo Plugin](http://dev.wp-plugins.org/wiki/GeoPlugin) installed, deactivate it.
    * Note: If your templates call any of the Geo tags, you'll have to remove these or replace them with GeoMashup calls.
  1. Download [the latest Geo Mashup archive](http://code.google.com/p/wordpress-geo-mashup/downloads/list).
  1. Extract the geo-mashup folder from the archive.  The folder name must be 'geo-mashup'.
  1. Upload this entire folder to your wp-content/plugins directory (not just the files, the whole geo-mashup folder).
  1. In the WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.
  1. Visit the Options | Geo Mashup tab to configure the plugin.

## Upgrading ##

**WARNING: Upgrading to 1.1 or later from earlier versions will probably break your current map.** At least some minor updates to geo mashup tags will be required to get things going again. See the TagReference for old and new tag formats.

In general you can follow the installation instructions, overwriting existing files.
It shouldn't hurt to deactivate the plugin first, but it doesn't seem to be necessary.

**You should update your Geo Mashup Options once to add any new values**.

If you have a `custom-marker.js` file, you'll need to rename it to `custom.js` and make a few changes. See `custom-sample.js` for an example.

## Getting Started ##

Geo Mashup lets you associate location information with any post or page, then use that information in Google maps.

### Adding the location to a post or page ###

Near the bottom of the advanced editing area below the editor, a Location area has been added. Look at the very bottom of this screenshot:

![http://wordpress-geo-mashup.googlecode.com/svn/trunk/geo-mashup/images/post_editor_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/trunk/geo-mashup/images/post_editor_screenshot.jpg)

The area can be collapsed and expanded by clicking on the +(or -) icon on the right side. After expanding, the map may not show until you perform a search. Click in the Find Location textbox and type a place name (like Chartre in this example) to do a search:

![http://wordpress-geo-mashup.googlecode.com/svn/trunk/geo-mashup/images/location_screenshot.jpg](http://wordpress-geo-mashup.googlecode.com/svn/trunk/geo-mashup/images/location_screenshot.jpg)

Notice the help link next to the Find Location textbox. Clicking this will display the following instructions for other ways to search for a location.

> Put a green pin at the location for this post. There are many ways to do it:

  * Search for a location name.
  * For multiple search results, mouse over pins to see location names, and click a result pin to select that location.
  * Search for a decimal latitude and longitude, like 40.123,-105.456.
  * Search for a street address, like 123 main st, anytown, acity.
  * Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.

> To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name so you can find it again with a quick search. Saved names are searched before doing a GeoNames search for location names.

> To remove the location (green pin) for a post, clear the search box and hit the enter key.

#### KML Attachments ####

Note: for now you'll have to use the browser uploader instead of the Flash uploader for KML files. See [Issue 164](https://code.google.com/p/wordpress-geo-mashup/issues/detail?id=164) for details.

Instead of searching for a location, you can upload a KML file with a post or page using the media uploader. The KML file (center) will be used to determine a default location for the post or page. If you include a map in that post or page, it will load and display the KML file.

You can create a map in the My Maps tab of the [Google Maps page](http://maps.google.com/) and save it as KML using [these instructions](http://rickcogley.blogspot.com/2008/08/exporting-googles-my-maps-as-kml.html).

### Adding a Map ###

You can put a map in any page or post by typing the shortcode `[geo_mashup_map]` into the editor. Look at the TagReference for more options.

If a post or page has location information associated with it, the map displays that. Otherwise, a global map of all located posts and pages is displayed. There are lots of ways to customize the map.

You can put a map outside posts and pages with the template tag `<?php echo GeoMashup::map(); ?>`. The TagReference has details for template tags too.

## More Advanced Features ##

### Global Mashup Page ###

You can have a single page with a global map that will be used in links generated by Geo Mashup.

Create a page, include the `[geo_mashup_map]` tag in it, and fill in the "Default Settings for Global Maps" in the Geo Mashup options. The `show_on_map_link` tag and "Add Category Links" options will now create links to this page, which will load a global map appropriate for the link.

### Adding a "show on map" link to posts with a location ###

You can add a link from any located item to your designated global map page, with the global map centered on the clicked item. Use the `[show_on_map_link]` shortcode tag for a single item, or the template tag `<?php echo GeoMashup::show_on_map_link(); ?>` to add links to all posts or pages. See the TagReference for details.

### Category Map Links ###

If you have a list of category links in your sidebar, you can add a map link for each category that contains posts with coordinates. First set up a Global Mashup Page if you haven't already. In the Geo Mashup options, check Add Category Links and fill in the options for separator text, link text, and zoom level. Note that this will not work for dropdown-style category lists. **Your categories must have descriptions** or a WordPress problem will cause the links to appear mangled.

### Category Colors and Lines ###

Markers in a category can be connected by a line on a global map. Categories can be assigned a color for lines and markers in the Geo Mashup options.

At the bottom of Geo Mashup Options for global maps you should see your categories listed. For each category you can choose a color for the category markers and lines, and a "Show Connecting Line Until Zoom Level (0-17 or none)" setting. This allows you to specify that a line is only drawn for the category only up to the specified zoom level. When zoomed in closer than that, the line will disappear.

### Showing posts with the map ###

This feature lets you display the full post that is currently selected on the map in a separate area on the page.  There's a template requirement: your single post template must put the post inside a div like this:`<div class="post">...</div>`. Most templates do this, but you might have to add this div to the template yourself.

Now you can use the Full Post tag as explained in the TagReference, then check 'Enable Full Post Display' in the options.

### Map Stylesheet ###

If the file `map-style.css` is present in the geo-mashup plugin directory, it will be applied to maps. This file will not be overwritten by upgrades. Look at `example-map-style.css` for examples of CSS classes available.

### Custom JavaScript ###

In the geo-mashup directory is a file named `custom-sample.js`. Rename this to
`custom.js` and edit it to draw things and do other kinds of customization using the [Googe Maps API](http://maps.google.com/apis/maps/documentation/).

### Widgets ###

A helpful user Marcel found this way to use Geo Mashup shortcodes in the WordPress text widget:

> I can use [geo\_mashup\_map](geo_mashup_map.md) like shortcodes in the text-widget by
> adding the following code at the start of "functions.php" of my active
> template, just after the "<?php" tag

> `add_filter('widget_text', 'do_shortcode');`

## Known Issues and General Help ##

### Domain Name and API Key ###

You can get away with skipping this section, but this is an issue that all websites have to deal with. Here is a solution all nicely explained for you, then you can rest at ease that your site works for everyone. If you are getting a blank gray map with no markers, this may solve the problem.

Your domain name (cyberhobo.net, for me) is important to this plugin in a few ways. The most common issue is that most websites will work with either the bare domain name (http://cyberhobo.net), or with a www. prefix (http://www.cyberhobo.net/). This plugin **will not** work with both names unless you follow these guidelines:

  1. Register your Google Maps API key for your bare domain name (like http://cyberhobo.net). This key will also work with a www., and will work for all pages on your site.
  1. Decide whether you want to use the www. prefix or not.
  1. Update the WordPress address URL and Blog address URL settings in the WordPress options to use your chosen domain name. You're all set.

Why is this important to the Geo Mashup plugin? [AJAX](http://en.wikipedia.org/wiki/AJAX). Geo Mashup uses this technology to communicate with your site behind the scenes to get markers and posts. Most browsers these days only allow this kind of communication with the same domain as the site being visited. If a visitor comes to cyberhobo.net, and the Geo Mashup plugin tries to communicate with www.cyberhobo.net, it's considered a security violation. Such is modern life. Until browsers behave differently, we have to accommodate them.

### Useful Background Links ###

Use of this plugin requires a little prior knowledge.  You don't have to be an expert on
these things, but some familiarity will help.

  1. [Managing Plugins](http://codex.wordpress.org/Managing_Plugins)
  1. [Creating and Using Pages](http://codex.wordpress.org/Pages)
  1. [Templates](http://codex.wordpress.org/Stepping_Into_Templates)
  1. [Template Tags](http://codex.wordpress.org/Stepping_Into_Template_Tags)
  1. [Decimal Geo Coordinates](http://ioc.unesco.org/oceanteacher/oceanteacher2/06_OcDtaMgtProc/01_DataOps/06_OcDtaForm/01_OcDtaFormFunda/02_Geography/01_Locations/coordinates.htm)

### Help, Questions, and Troubleshooting ###

  1. If you're not using the most recent version, your problem might have been fixed. You can just try an upgrade first, or check [recent development posts](http://www.cyberhobo.net/category/code) to see what work has been done since your version.
  1. Make sure you haven't missed anything relevant in the documentation above.
  1. Search [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). This is the place for questions and help - if you think there's a bug in the plugin, go to the next step.
  1. Check the [issues page](http://code.google.com/p/wordpress-geo-mashup/issues/list) to see if your problem has been reported yet, and if so what progress has been made on it. Only unresolved issues are listed by default, it might be worth searching solved issues to look for workarounds.
  1. Submit a new issue if your problem is not there.