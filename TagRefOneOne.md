#summary The list of tags, their format, and parameters.
#labels Phase-Deploy

# Tag Reference #

Contents:


# New Geo Mashup Tag Formats #

If you have tags from previous versions of Geo Mashup (like `<!--GeoMashup-->` or `GEO_MASHUP_MAP`), you'll have to change them to the new format. Since prior versions of Geo Mashup only supported these tags in pages, and was generally used on only one page, this shouldn't be too difficult.

Now tags in post and page content use standard WordPress [Shortcode format](http://codex.wordpress.org/Shortcode_API). Template tags have also been updated to use standard [template tag parameter format](http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters) in WordPress templates. These formats are not complicated - the following examples should be all you need to understand them.

## Shortcode Parameters ##

Here's an example of a tag you might put into a post or page:

`[geo_mashup_map height="200" width="400" zoom="2" add_overview_control="false" add_map_type_control="false"]`

The tag is `geo_mashup_map`, after which any number of parameters are specified. Any parameters that are not specified will use a default value from the Geo Mashup Options. If the post or page has a location saved, single default settings are used, otherwise the global defaults are used.

## Template Tag Parameters ##

To get a similar map in a template, use template tag syntax:

`<?php echo GeoMashup::map('height=200&width=400&zoom=2&add_overview_control=false&add_map_type_control=false');?>`

Note the 'echo' before GeoMashup. This is PHP for "display it right here", and as such is commonly used with template tags.

# Map Tag #

Inserts a map for the current post, page, or site.

Shortcode Tag: `[geo_mashup_map parameter="value"]`

Template Tag: `<?php echo GeoMashup::map('parameter=value') ?>`

Old Tags: `GEO_MASHUP_MAP`, `<!--GeoMashup-->`, `<?php echo GeoMashup::the_map(); `

Accepted Parameters:
  * **height** - the height of the map in pixels
  * **width** - the width of the map in pixels
  * **zoom** - the starting zoom level, 0 to 17
  * **add\_overview\_control** - true or false
  * **map\_control** - GSmallZoomControl, GSmallMapControl, GLargeMapControl
  * **add\_map\_control** - true or false
  * **map\_type** - G\_NORMAL\_MAP, G\_SATELLITE\_MAP, G\_HYBRID\_MAP, G\_PHYSICAL\_MAP
  * **map\_cat** - the ID of the category to display (you can edit the category and look for cat\_ID in the address bar)
  * **auto\_open\_info\_window** - true or false
  * **marker\_min\_zoom** - hide markers until zoom level, 0 to 17

# Map Link Tag #

Inserts a link for the current post or page to overall blog map.

Shortcode Tag: `[geo_mashup_show_on_map_link parameter="value"]`

Template Tag: `<?php echo GeoMashup::show_on_map_link('parameter=value') ?>`

Old Tag: `GEO_MASHUP_SHOW_ON_MAP_LINK`

Accepted Parameters:
  * **text** - the text of the link. Default is 'Show on map'.
  * **show\_icon** - true or false, whether to display the geotag icon before the link. Default is true.

# Category Name Tag #

Inserts the name of the currently displayed map category, if any.

Shortcode Tag: `[geo_mashup_category_name]`

Template Tag: `<?php echo GeoMashup::category_name(); ?>`

Old Tag: `GEO_MASHUP_CATEGORY_NAME` and `<!--GeoCategory-->`

# Category Legend Tag #

Inserts a table of colored map pins and the categories they go with.

Shortcode Tag: `[geo_mashup_category_legend]`

Template Tag: `<?php echo GeoMashup::category_legend(); ?>`

Old Tag: `GEO_MASHUP_CATEGORY_LEGEND`

# Located Post List Tag #

Inserts a list of all posts with location information.

Shortcode Tag: `[geo_mashup_list_located_posts]`

Template Tag: `<?php echo GeoMashup::list_located_posts(); ?>`

Old Tag: `GEO_MASHUP_LIST_LOCATED_POSTS`

# Full Post Tag #

Displays full post content on a map page if the 'Enable Full Post Display' option is checked.

Shortcode Tag: `[geo_mashup_full_post]`

Template Tag: `<?php echo GeoMashup::full_post(); ?>`

Old Tag: `GEO_MASHUP_FULL_POST` and `<!--GeoPost-->`

# Post Coordinates Tag #

This is currently only availabe as a template tag which you can use to display the coordinates
stored for a page or post. Have a look at [Issue 134](http://code.google.com/p/wordpress-geo-mashup/issues/detail?id=134)
for good examples of usage.

Template Tag: {{<?php $coordinates\_array = GeoMashup::post\_coordinates(10); ?>}}}

Accepted Parameters:
  * **places** - number of decimal places to include in coordinates

Returns:
  * An array containing 'lat' and 'lng' entries, so in the example $coordinates\_array['lat'] would contain latitude.

# Help, Comments #

Comments and questions are welcome in [our Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin). Bugs and feature requests go in the [issue tracker](http://code.google.com/p/wordpress-geo-mashup/issues/list). Search both - your answer may be there!