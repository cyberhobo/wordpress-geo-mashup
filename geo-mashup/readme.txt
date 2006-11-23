=== Geo Plugin ===
Tags: geo, geographic, coordinates, gps, map, maps, mashup, google, api
Contributors: cyberhobo

This plugin works with the Geo Plugin to plot posts on a Google Maps Mashup, with a pin at each post. Clicking on a pin pops up a summary and link to the post. Wordpress 1.5.1 or later is required.

== Documentation ==

This readme.txt document is a basic introduction to using the Geo Mashup plugin.  Please refer to the documentation at the WordPress plugin repository for the latest information:
http://dev.wp-plugins.org/wiki/GeoMashup

== Installation ==

1. Download the latest archive.
2. Extract the geo-mashup folder from the archive.
3. Upload this entire folder to your wp-content/plugins directory.
4. In the WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.
5. Visit the Options | Geo Mashup tab to configure the plugin.


== Configuration ==

The Geo Mashup plugin provides some configuration options for customizing a Google Maps mashup page on your blog.  Find these options in the Administration Panel under Options | Geo Mashup.

= Plugin Options =

TODO

== Adding a Geo Mashup page ==

1.  Make sure you have the Geo Plugin installed and at least one post with coordinates entered.
2. Get your Google Maps API key and enter it in the options.
3. Create a new page, and enter the page slug in the options.
4. Edit your page template so the body tag looks like this:
	<body <?php GeoMashup::body_attribute(); ?>>
5. View the page and play with your new blog map!
6. If nothing shows up, you may have to add <?php wp_head();?> in your page template header before the </head> tag.

== Styling the map info window ==

If the info window is too big, try adding something like this to your stylesheet:

.locationinfo {
width:300px;
font-size:70%;
}

You can also change the style of these classes inside locationinfo: storytitle, blogdate, storycontent, meta. 

