Geo Mashup Plugin 0.3 Beta
--------------------------

Tags: geo, geographic, coordinates, gps, map, maps, mashup, google, api
Contributors: cyberhobo

This plugin works with the Geo Plugin to plot posts on a Google Maps Mashup, with a pin at each post. Clicking on a pin pops up a summary and link to the post. Wordpress 1.5.1 or later is required.

Documentation

This readme.txt document is a copy of the online documentation at release time.  Check the online documentation at the WordPress plugin repository for the latest information:
http://dev.wp-plugins.org/wiki/GeoMashup

This is a Beta version. Please be willing to help test if you use it. If you find bugs, consider adding a ticket or leaving a comment.
Installation

   1. Download the latest archive.
   2. Extract the geo-mashup folder from the archive.
   3. Upload this entire folder to your wp-content/plugins directory.
   4. In the WordPress Admin Panel, activate the Geo Mashup plugin on the Plugins tab.
   5. Visit the Options | Geo Mashup tab to configure the plugin. 

Upgrading

It seems to work to just overwrite the files of an earlier version with the latest one. It shouldn't hurt to deactivate the plugin first, but it doesn't seem to be necessary.
Configuration

The Geo Mashup plugin provides some configuration options for customizing a Google Maps mashup page on your blog. Find these options in the Administration Panel under Options | Geo Mashup.
Quick Start: Adding a Geo Mashup Page

   1. Make sure you have the Geo Plugin installed and at least one post with coordinates entered.
   2. Get your Google Maps API key and enter it in the options.
   3. Create a new page with the tag <!--GeoMashup--> in the page content.
   4. Choose the page slug in the options.
   5. Edit your page or header template so the body tag looks like this: <body<?php GeoMashup::body_attribute(); ?>>
   6. View the page and play with your new blog map!
   7. If nothing shows up, you may have to add <?php wp_head();?> in your page template header before the </head> tag. 

Styling the map info window

If the "Use inline style" option is checked, the presentation options are used to generate some simple CSS code for you. You can uncheck this and write your own CSS in your theme stylesheet instead. Look at the post.xsl and posts.xsl to see the tags, and classes that are used in the info window HTML.
Template Tags

    * <?php GeoMashup::body_attribute() ?>
          o This is a special tag used only in the body tag as illustrated in the Quick Start section above. This tag adds some JavaScript necessary for embedded Google maps to work in Internet Explorer. I hope that a future Google Maps API release will no longer require this. 
    * <?php GeoMashup::the_map() ?>
          o This tag may be used inside "The Loop" in the page template to generate the map instead of using <!--GeoMashup--> in the page content. 
    * <?php GeoMashup::post_link('link_text') ?>
          o This tag may be used inside "The Loop" in the blog template to generate a link to the current post on the mashup map. The link will only be generated if there are Geo coordinates for the post.
          o The 'link_text' parameter is a string to use as the text of the link. Default value is 'Geo Mashup'. 