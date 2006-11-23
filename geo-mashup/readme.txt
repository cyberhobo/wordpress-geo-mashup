Geo Mashup Plugin 0.4 
---------------------
= Geo Mashup Plugin =

This plugin works with the GeoPlugin to plot posts on a Google Maps Mashup, with a pin at each post. Clicking on a pin pops up a summary and link to the post. See [http://www.cyberhobo.net/hobomap/ my hobomap] for an example.

== Installation ==

 1. GeoPlugin must be installed and active.  If you're starting from scratch follow the instructions at that link before proceeding.
   * Note: The current Geo plugin appears to work only with WordPress 2.0 or later.  If you have a 1.5.x installation, [http://dev.wp-plugins.org/file/geo/trunk/geo.php?rev=1741&format=raw use this older version].
 2. Download [http://www.cyberhobo.net/Software/geo-mashup-0.4.zip the latest GeoMashup archive].
 3. Extract the geo-mashup folder from the archive.  The folder name must be 'geo-mashup'.
 4. Upload this entire folder to your wp-content/plugins directory (not just the files, the whole geo-mashup folder).
 5. In the !WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.
 6. Visit the Options | Geo Mashup tab to configure the plugin.

== Upgrading ==

It seems to work to just overwrite the files of an earlier version with the latest one.  It shouldn't hurt to deactivate the plugin first, but it doesn't seem to be necessary.  You should update your Geo Mashup Options once to add any new values.

== Configuration ==

The Geo Mashup plugin provides some configuration options for customizing a Google Maps mashup page on your blog.  Find these options in the Administration Panel under Options | Geo Mashup.

=== Quick Start: Adding a Geo Mashup Page ===

 1. Make sure you have the Geo Plugin installed and at least one post with coordinates entered.
 1. Get your Google Maps API key and enter it in the options.
 1. Create a new page with the tag <!--GeoMashup--> in the page content.
   * If you are using the rich text editor, you'll have to use the HTML source button to enter this tag.  It won't show up in the rich text editor.
   * If you want to create an area for the full post to be shown, you can enter the tag <!--GeoPost--> also.
 1. Choose the page slug in the options.
 1. Edit your page or header template so the body tag looks like this: <body<?php GeoMashup::body_attribute(); ?>> 
 1. View the page and play with your new blog map!
 1. If nothing shows up, you may have to add <?php wp_head();?> in your page template header before the {{{</head>}}} tag.

=== Styling the map info window ===

If the "Include style settings" option is checked, the presentation options are used to generate some simple CSS code for you.  You can uncheck this and write your own CSS in your theme stylesheet instead.  These ids and classes can be styled: #geoMashup, #geoPost, .locationinfo, h2, .meta, .blogdate, .storycontent.

== Template Tags ==

 * <?php GeoMashup::body_attribute() ?>
   * This is a special tag used only in the body tag as illustrated in the Quick Start section above.  This tag adds some JavaScript necessary for embedded Google maps to work in Internet Explorer.  I hope that a future Google Maps API release will no longer require this.
 * <?php GeoMashup::the_map() ?>
   * This tag may be used inside "The Loop" in the page template to generate the map instead of using <!--GeoMashup--> in the page content.
 * <?php GeoMashup::post_link('link_text') ?>
   * This tag may be used inside "The Loop" in the blog template to generate a link to the current post on the mashup map.  The link will only be generated if there are Geo coordinates for the post.
   * The 'link_text' parameter is a string to use as the text of the link.  Default value is 'Geo Mashup'.

=== Troubleshooting ===

 * Page or template updates have no effect
   * Firefox especially seems to cache a lot of things used by the map.  Try Shift+Reload, or even clearing the cache.
 * Info windows say "Loading..." but never load.
   * I don't think this will happen in the latest version. Please [http://www.cyberhobo.net/downloads/geo-mashup-plugin post a comment] for me if you have this issue.
 * Strange looking blocky rendering of the map in Firefox.
   * In WordPress 2.x, you may need to use the HTML button in the new editor to delete any <tt> tags that are added on the map page.


