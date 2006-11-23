= Geo Mashup Plugin =

Contributors: [http://www.cyberhobo.net/ cyberhobo]

This plugin works with the GeoPlugin to plot posts on a Google Maps Mashup, with a pin at each post. Clicking on a pin pops up a customizable summary and link to the post. See [http://www.cyberhobo.net/downloads/geo-mashup-plugin/ the GeoMashup home page] for a examples, news, and discussion.

== Installation ==

 1. GeoPlugin must be installed and active.  If you're starting from scratch follow the instructions at that link before proceeding.
   * Note: The current Geo plugin appears to work only with WordPress 2.0 or later.  If you have a 1.5.x installation, [http://dev.wp-plugins.org/file/geo/trunk/geo.php?rev=1741&format=raw use this older version].
 1. Download [http://www.cyberhobo.net/Software/geo-mashup-0.5.zip the latest GeoMashup archive].
 1. Extract the geo-mashup folder from the archive.  The folder name must be 'geo-mashup'.
 1. Upload this entire folder to your wp-content/plugins directory (not just the files, the whole geo-mashup folder).
 1. In the !WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.
 1. Visit the Options | Geo Mashup tab to configure the plugin.

== Upgrading ==

It seems to work to just overwrite the files of an earlier version with the latest one.  It shouldn't hurt to deactivate the plugin first, but it doesn't seem to be necessary.  You should update your Geo Mashup Options once to add any new values.

As of 0.5, the template tag {{{<?php GeoMashup::body_attribute()?>}}} is no longer needed in the template body tag. You don't have to remove it, but you can.

The template for your map page must now have a footer, however (most do these days).

== Configuration ==

The Geo Mashup plugin provides some configuration options for customizing a Google Maps mashup page on your blog.  Find these options in the Administration Panel under Options | Geo Mashup.

=== Quick Start: Adding a Geo Mashup Page ===

 1. Make sure you have the Geo Plugin installed and at least one post with coordinates entered.
 1. Get your Google Maps API key and enter it in the options.
 1. Create a new page with the tag {{{<!--GeoMashup-->}}} in the page content.
   * If you are using the rich text editor, you'll have to use the HTML source button to enter this tag.  It won't show up in the rich text editor.
   * If you want the category name to appear for category queries, enter the tag {{{<!--GeoCategory-->}}} where it should appear.
   * If you want to create an area for the full post to be shown, you can enter the tag {{{<!--GeoPost-->}}} where it should appear.
 1. Choose the page slug in the options.
 1. View the page and play with your new blog map!

=== Customizing the marker icon ===

In the geo-mashup directory is a file named {{{custom-marker-sample.js}}}. Rename this to {{{custom-marker.js}}} and edit it to use your own icon (see the [http://maps.google.com/apis/maps/documentation/#Creating_Icons Google API documentation] for more). 

=== Adding a "show on map" link to posts with coordinates ===

See the {{{<?php GeoMashup::show_on_map_link()?>}}} template tag below.

=== Using Categories ===

If you have a list of category links in your sidebar, you can add a map link for each category that contains posts with coordinates. In the Geo Mashup options, check Add Category Links and fill in the options for separator text, link text, and zoom level. Note that this will not work for dropdown-style category lists.

You can make your own links to the map page for a specific category, just add these query parameters to your map page URL {{{?cat=1&zoom=7}}} where 1 is the category ID and 7 is the zoom level to start the map on. A full example URL: {{{http://www.cyberhobo.net/hobomap/?cat=14&zoom=4}}}.

=== Showing the post with the map ===

This feature lets you display the full post that is currently selected on the map in a separate area on the page.  You can use the {{{<!--GeoPost-->}}} tag as explained in the Quick Start, or add {{{<div id="geoPost"></div>}}} to your map page template where you'd like the post to display.  Then check 'Enable Full Post Display' in the options.

=== Styling the map info window ===

If the "Include style settings" option is checked, the presentation options are used to generate some simple CSS code for you.  You can uncheck this and write your own CSS in your theme stylesheet instead.  These ids and classes can be styled: {{{#geoMashup, #geoPost, .locationinfo, h2, .meta, .blogdate, .storycontent}}}.

== Template Tags ==

 * {{{<?php GeoMashup::show_on_map('link_text') ?>}}}
   * This tag may be used inside "The Loop" in the blog template to generate a link to the current post on the mashup map.  The link will only be generated if there are Geo coordinates for the post.
   * The {{{'link_text'}}} parameter is a string to use as the text of the link.  Default value is 'Geo Mashup'.
   * Send {{{false}}} as a second parameter to return the link as a string rather than displaying it.
   * This tag used to be called {{{<?php GeoMashup::post_link()?>}}}, which will still work.
 * {{{<?php GeoMashup::the_map() ?>}}}
   * This tag may be used inside "The Loop" in the page template to generate the map instead of using {{{<!--GeoMashup-->}}} in the page content.
 * {{{<?php GeoMashup::body_attribute() ?>}}}
   * DEPRECATED. No longer needed, now does nothing.

== Helpful Links ==

Use of this plugin requires a little prior knowledge.  You don't have to be an expert on these things, but some familiarity will help.

 1. [http://codex.wordpress.org/Managing_Plugins Managing Plugins]
 1. [http://codex.wordpress.org/Pages Creating and Using Pages]
 1. [http://codex.wordpress.org/Stepping_Into_Templates Templates]
 1. [http://codex.wordpress.org/Stepping_Into_Template_Tags Template Tags]
 1. [http://ioc.unesco.org/oceanteacher/oceanteacher2/06_OcDtaMgtProc/01_DataOps/06_OcDtaForm/01_OcDtaFormFunda/02_Geography/01_Locations/coordinates.htm Decimal Geo Coordinates]
 
== Troubleshooting ==

 * No map appears on the map page
   * Your map page template must have a header and footer. If not, you can add {{{<?php wp_head();?>}}} in your page template header before the {{{</head>}}} tag, and {{{<?php wp_footer();?>}}} before the {{{</body>}}} tag.
 * Info windows say "Loading..." but never load.
   * I've made many efforts to fix this, but I still see it now & then. Usually a reload will get rid of it.
 * "Permission denied to set property EventTarget.addEventListener" appears on the initial page load.
   * This is one of the causes of the previous problem. Usually it does not happen again on reload. I'm aware of it, but haven't solved it yet.
 * Strange looking blocky rendering of the map.
   * Some themes have stylesheets that interfere with Google Maps. In K2, for instance, removing the {{{.primary .item .itemtext div}}} and {{{.primary img}}} selectors fixes the Google map.