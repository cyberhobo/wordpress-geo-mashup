#summary The list of tags, their format, and parameters.
#labels Phase-Deploy



# Geo Mashup Tag Formats #

If you have tags from previous versions of Geo Mashup (like `<!--GeoMashup-->` or `GEO_MASHUP_MAP`), you'll have to change them to the new format. Since early versions of Geo Mashup only supported these tags in pages, and was generally used on only one page, this shouldn't be too difficult.

Now tags in post and page content use standard WordPress [Shortcode format](http://codex.wordpress.org/Shortcode_API). Template tags have also been updated to use standard [template tag parameter format](http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters) in WordPress templates. These formats are not complicated - the following examples should be all you need to understand them.

## Shortcode Parameters ##

Here's an example of a tag you might put into a post or page:

`[geo_mashup_map height="200" width="400" zoom="2" add_overview_control="false" add_map_type_control="false"]`

The tag is `geo_mashup_map`, after which any number of parameters are specified. Any parameters that are not specified will use a default value from the Geo Mashup Options. If the post or page has a location saved, single default settings are used, otherwise the global defaults are used.

## Template Tag Parameters ##

To get a similar map in a template, use template tag syntax:

`<?php echo GeoMashup::map('height=200&width=400&zoom=2&add_overview_control=false&add_map_type_control=false');?>`

Note the 'echo' before GeoMashup. This is PHP for "display it right here", and as such is commonly used with template tags.

# Tags #

## Category Legend ##

Inserts a table of colored map pins and the categories they go with. Checkboxes will show or hide category markers on the map. See the map tag for ways to customize.

Shortcode Tag: `[geo_mashup_category_legend]`

Template Tag: `<?php echo GeoMashup::category_legend() ?>`

Accepted Parameters:
  * **for\_map** - the name of the map this category legend should work with. Default is the first map on the page.

## Category Name ##

Inserts the name of the currently displayed map category, if any.

Shortcode Tag: `[geo_mashup_category_name]`

Template Tag: `<?php echo GeoMashup::category_name(); ?>`

## Full Post ##

Displays full post content on a global map page if the 'Enable Full Post Display' option is checked.

Shortcode Tag: `[geo_mashup_full_post]`

Template Tag: `<?php echo GeoMashup::full_post(); ?>`

## List Located Posts ##

Inserts a list of all posts with location information.

Shortcode Tag: `[geo_mashup_list_located_posts]`

Template Tag: `<?php echo GeoMashup::list_located_posts(); ?>`

## List Located Posts By Area ##

Generates a list of located posts broken down by country and administrative area (state/province).
Country heading is only included if there is more than one country.  Names should be in the blog
language if available.

Shortcode Tag: `[geo_mashup_list_located_posts_by_area]`

Template Tag: `<?php echo GeoMashup::list_located_posts_by_area() ?>`

Parameters:
  * **include\_address** - true (omit for false). Includes post address when available.

## Map ##

Inserts a map.

When the tag is in a located post or page, Single Maps settings are used by default.
In a post or page without a location, Global Maps settings are used. In all other locations, Contextual Maps
settings are used.  You can change the default map content with the map\_content parameter, such as
`[geo_mashup_map map_content="global"]`.

Shortcode Tag: `[geo_mashup_map]`

Template Tag: `<?php echo GeoMashup::map() ?>`

Accepted Parameters:
  * **add\_map\_control** - true or false. Adds zoom/pan controls.
  * **add\_map\_type\_control** - true or false. Adds map type selector controls.
  * **add\_overview\_control** - true or false. Adds the overview control.
  * **auto\_info\_open** - true or false. Opens the info window of the most recent or link source post.
  * **center\_lat** - decimal latitude. With center\_lng, an initial center location for the map.
  * **center\_lng** - decimal longitude. With center\_lat, an initial center location for the map.
  * **click\_to\_load** - true or false. Activates the click-to-load feature described above. Default is false.
  * **click\_to\_load\_text** - the text displayed in the click-to-load pane.
  * **disable\_tab\_auto\_select** - For use with the tabbed category index, shows all posts to begin with. Allows the info window to remain open.
  * **height** - the height of the map in pixels
  * **interactive\_legend** - true or false. Controls the display of checkboxes in map legend. Default is true.
  * **legend\_format** - dl or table. Formats the category legend using a definition list or table. Default is table.
  * **limit** - the maximum number of items to include on a global map or contextual map.
  * **map\_content** - global, single, or contextual. Overrides the default content of a map.
  * **map\_control** - !GSmallZoomControl, !GSmallMapControl, !GLargeMapControl, !GSmallZoomControl3d, !GLargeMapControl3D
  * **map\_type** - G\_NORMAL\_MAP, G\_SATELLITE\_MAP, G\_HYBRID\_MAP, G\_PHYSICAL\_MAP
  * **map\_cat** - the ID of the category to display (you can edit the category and look for cat\_ID in the address bar)
  * **marker\_min\_zoom** - hide markers until zoom level, 0 to 20
  * **name** - name for this map. You can then use the name in the **for\_map** parameters of other tags.
  * **show\_future** - true or false. Includes future-dated posts. Default is false.
  * **show\_inactive\_tab\_markers** - Display markers for all tabs, not just the selected one. For use with the tabbed category index.
  * **start\_tab\_category\_id** - Generate tabs for the children of this category id. For use with the tabbed category index.
  * **static** - true or false. Attempts to use the Google Static Maps API to create a fast-loading static image map. Currently limited to the normal map type with one marker color and style.
  * **tab\_index\_group\_size** - Break subcategory lists into groups of this size. For use with the tabbed category index, can be used to make a column layout.
  * **width** - the width of the map. Use a plain number like 400 for pixels, or include a percentage sign like 85%.
  * **zoom** - the starting zoom level, 0 to 20

## Post Coordinates ##

This is currently only available as a template tag which you can use to display the coordinates
stored for a page or post. Have a look at [Issue 134](http://code.google.com/p/wordpress-geo-mashup/issues/detail?id=134)
for good examples of usage.

Template Tag: `<?php $coordinates_array = GeoMashup::post_coordinates() ?>`

Accepted Parameters:
  * **places** - number of decimal places to include in coordinates. Default is 10.

Returns:
  * An array containing 'lat' and 'lng' entries, so in the example `$coordinates_array['lat']` would contain latitude.

## Save Location ##

This can be used to set the location for a post when the post editor location search interface is not available.

When a post or page is saved, the location is read from a special shortcode in the content. The
location is saved, and the special shortcode is removed.

For now at least, decimals must be a period, not a comma.

Shortcode Tag: `[geo_mashup_save_location]`

Parameters:
  * **lat** - decimal latitude. Required.
  * **lng** - decimal longitude. Required.
  * **saved name** - if you want the location added to the saved location menu, the name.

## Show on Map Link ##

Inserts a link for the current post or page to overall blog map.

Shortcode Tag: `[geo_mashup_show_on_map_link]`

Template Tag: `<?php echo GeoMashup::show_on_map_link() ?>`

Accepted Parameters:
  * **text** - the text of the link. Default is 'Show on map'.
  * **show\_icon** - true or false, whether to display the geotag icon before the link. Default is true.
  * **zoom** - the zoom level to start at, 0 to 20.

## Tabbed Category Index ##

Generates output that lists located posts by category in markup that can be styled as tabs.
Subcategories are included in their parent category tab under their own heading. Click on post
titles opens the corresponding marker's info window on the map. The map can show all markers, or
just those for the active tab.

Additional CSS is required in the theme stylesheet to get the tab appearance, otherwise the
markup will just look like a series of lists. An example is included in `tab-style-sample.css`.

Shortcode Tag: `[geo_mashup_tabbed_category_index]`

Template Tag: `<?php echo GeoMashup::tabbed_category_index() ?>`

Parameters:
  * **for\_map** - the name of the map this list should work with. Default is the first map on the page.
  * see the map tag for additional options - the map creates the control from the content included on it.

## Visible Posts List ##

Generates a list of post titles that are currently visible on the map.  Clicking the title opens the info window for that post.

Shortcode Tag: `[geo_mashup_visible_posts_list]`

Template Tag: `<?php echo GeoMashup::visible_posts_list() ?>`

Parameters:
  * **for\_map** - the name of the map this list should work with. Default is the first map on the page.

# Help, Comments #

Comments and questions are welcome in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). Bugs and feature requests go in the [issue tracker](http://code.google.com/p/wordpress-geo-mashup/issues/list). Search both - your answer may be there!