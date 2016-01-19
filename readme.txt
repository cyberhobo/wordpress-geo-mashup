=== Geo Mashup ===
Contributors: cyberhobo
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=11045324
Tags: map, maps, google maps, google map, mapping, mashup, geo, google, geocms
Requires at least: 3.7
Tested up to: 4.4.1
Stable tag: 1.8.7
License: GPL2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Include Google and OpenStreetMap maps in posts and pages, and map posts, pages, and other objects on global maps. Make WordPress into a GeoCMS.

== Description ==

This plugin lets you save location information with posts, pages, and other WordPress objects. 
These can then be presented on interactive maps in many ways.

= Key Features =

Geo Mashup, like WordPress, has a simple interface for basic features,
templates for control of output, and APIs for endless customization options.
Some popular features are:

  * Use different map providers: [Google Maps v3](https://developers.google.com/maps/documentation/javascript/reference), [OpenStreetMap](http://openstreetmap.org), [Leaflet](http://leafletjs.com)
  * Global maps can present your posts in many ways
	They can show just one category, custom taxonomy term, or custom post type, for example
	Clicking on a post marker shows a customizable summary of the post or page in an info window
  * A Geo Search widget enables searching for content by distance from a search location
  * Marker clustering for Google maps
  * Location can be saved for all post types (including pages) users, and comments
  * Synchronize [Geodata](http://codex.wordpress.org/Geodata) with the Geo location mobile client and other plugins
  * Support for both standard WordPress [shortcodes](http://codex.wordpress.org/Shortcode_API) and [template tags](http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters) to add maps to your site.
  * Reverse geocoding to fill in address information for locations
  * GeoRSS automatically added to feeds
  * Attach KML files to posts and pages
  * Connect category markers with a colored line

If you need features that are aren't listed here, check 
[the documentation](https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Getting-Started)
and the [tag reference](https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Tag-Reference).

= Comparison to Other Mapping Plugins =

Geo Mashup was one of the earliest WordPress mapping plugins, first released
in 2005. Eventually the author began using it for freelance jobs, which he
still does. As such Geo Mashup is more tailored to customization and hacking than ease of
use, but many user-requested features have been
[released](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+is%3Aclosed+label%3AType-Enhancement+)
over the years. Geo Mashup creates three tables to optimize location
searches.

It's just crazy how many mapping plugins have sprung up since then. If all
those developers had worked together we'd have just a few much better plugins.
I've been guilty of not reaching out to other authors. If you are a developer
interested in working together for a better WordPress mapping future, contact
me via [my site](http://www.cyberhobo.net/hire-me) and I'll work with you.

= Support =

The author monitors [WP Questions](http://wpquestions.com/affiliates/register/name/cyberhobo),
and there is a public [Google Group](http://groups.google.com/group/wordpress-geo-mashup-plugin).
The author doesn't promise free support, but is amenable to questions that contribute something
to the community.

= Translations =

* Ukranian by [Ivanka of Everycloudtech](http://everycloudtech.com/) updated in version 1.8.6
* Dutch by [delicatesther](http://delicatesther.nl/) updated in version 1.8.5
* German by [Thomas Morvay](http://internet-dienste.biz/) added in version 1.5.4
* Slovak by B. Radenovich of [Web Hosting Watch](http://webhostingw.com) updated in version 1.5.4
* Romanian by [Florin Dumitru](http://www.fitnesstimisoara.ro/) added in version 1.5
* Irish by [Vikas Arora](http://www.apoto.com) added in version 1.4.11
* Russian by [Tony](http://tohapro.com) added in version 1.4.10
* Hindi by [Outshine Solutions](http://outshinesolutions.com) added in version 1.4.9
* Polish by [Kamil](http://wbartoszycach.pl) added in version 1.4.7
* Portugese by [Antonio Xeira](http://flyingsouth.thehappytoadfish.com/) added in version 1.4.2
* Italian by [Federico](http://thrifytuscany.com/) added in version 1.3.10
* French updated in version 1.3.7
* Spanish by [Reven](http://www.reven.org/blog/2010/03/15/traduccion-de-geo-mashup/) added in version 1.3.3
* Swedish by [Joakim Green](http://www.joakimgreen.com/) added in version 1.3.3
* Belorussian by [FatCow](http://www.fatcow.com) added in version 1.2.8

[Translators welcome](https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Translating).

= Thanks =

Thanks to [JetBrains](https://www.jetbrains.com) for providing cutting edge development tools to this project.

= Mashup Ingredients =

Geo Mashup combines WordPress, [Google Maps](http://maps.google.com), [OpenStreetMap](http://openstreetmap.org),
[GeoNames](http://geonames.org), and [geoPlugin](http://geoplugin.net) to create a GeoCMS that puts you in control
of all your content, including geographic data.

== Installation ==

GeoMashup supports [standard WordPress plugin installation].

== Upgrade Notice ==

= 1.8.3 =
This version fixes a security related bug.  Upgrade immediately.

== Change Log ==

Features are generally added in one-dot releases, while two-dot releases contain fixes and small updates.

= 1.8.7 Jan 19 2016 =

[milestone 1.8.7 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.7)

= 1.8.6 Sep 30 2015 =

[milestone 1.8.6 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.6)

= 1.8.5 Sep 27 2015 =

[milestone 1.8.5 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.5)

= 1.8.4 May 18 2015 =

Move official source from Google Code to Github.

= 1.8.3 Jan 11 2015 =

Thanks to Paolo Perego of [armoredcode.com](http://armoredcode.com) for finding and fixing an XSS bug in the
geo search widget.

[milestone 1.8.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.3)

= 1.8.2 Nov 17 2014 =
[milestone 1.8.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.2)

= 1.8.1 Oct 9 2014 =
[milestone 1.8.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.1)

= 1.8.0 Jul 14 2014 =
[milestone 1.8.0 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.8.0)

= 1.7.3 Jan 22 2014 =
[milestone 1.7.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.7.3)

= 1.7.2 Nov 1 2013 =
[milestone 1.7.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.7.2)

= 1.7.1 Oct 29 2013 =
[milestone 1.7.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.7.1)

= 1.7.0 Sep 24 2013 =
[milestone 1.7.0 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.7.0)

= 1.6.2 Jul 9 2013 =
[milestone 1.6.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.6.2)

= 1.6.1 Jun 22 2013 =
[milestone 1.6.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.6.1)

= 1.6.0 May 1 2013 =
[milestone 1.6 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.6)

= 1.5.3 Feb 25 2013 =
[milestone 1.5.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.5.3)

= 1.5.2 Feb 11 2013 =
[milestone 1.5.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.5.2)

= 1.5.1 Feb 9 2013 =
[milestone 1.5.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.5.1)

= 1.5 Feb 7 2013 =
[milestone 1.5 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.5)

= 1.4.12 Dec 6 2012 =
[milestone 1.4.12 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.12)

= 1.4.11 Dec 5 2012 =
[milestone 1.4.11 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.11)

= 1.4.10 Aug 5 2012 =
[milestone 1.4.10 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.10)

= 1.4.8 Mar 27 2012 =
[milestone 1.4.8 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.8)

= 1.4.7 Feb 11 2012 =
[milestone 1.4.7 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.7)

= 1.4.6 Jan 2012 =
[milestone 1.4.6 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.6)

= 1.4.5 Dec 2011 =
[milestone 1.4.5 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.5)

= 1.4.4 Nov 2011 =
[milestone 1.4.4 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.4)

= 1.4.3 Nov 2011 =
[milestone 1.4.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.3)

= 1.4.2 Nov 2011 =
[milestone 1.4.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.2)

= 1.4.1 Jul 2011 =
[milestone 1.4.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4.1)

= 1.4 Jul 2011 =
[milestone 1.4 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.4)

= 1.3.11 =
[milestone 1.3.11 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.11)

= 1.3.10 =
[milestone 1.3.10 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.10)

= 1.3.9 =
[milestone 1.3.9 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.9)

= 1.3.8 =
[milestone 1.3.8 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.8)

= 1.3.7 =
[milestone 1.3.7 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.7)

= 1.3.6 =
[milestone 1.3.6 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.6)

= 1.3.5 =
[milestone 1.3.5 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.5)

= 1.3.4 =
[milestone 1.3.4 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.4)

= 1.3.3 =
[milestone 1.3.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.3)

= 1.3.2 =
[milestone 1.3.2 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.2)

= 1.3.1 =
[milestone 1.3.1 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3.1)

= 1.3 =
[milestone 1.3 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.3)

= 1.2.10 =
[milestone 1.2.10 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.2.10)

= 1.2.9 =
[milestone 1.2.9 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.2.9)

= 1.2.8 =
[milestone 1.2.8 changes](https://github.com/cyberhobo/wordpress-geo-mashup/issues?utf8=%E2%9C%93&q=is%3Aissue+label%3AMilestone-1.2.8)

= 1.2.7 =
Fixed:

* [Issue 228][228] Inline location (geo_mashup_save_location) not working
* [Issue 219][219] locate_template() undefined in WordPress 2.6
* Adjusted sub-cat titles in the tabbed index control to show only when they have located children

[228]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/228
[219]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/219

= 1.2.6 =
Fixed:

* [Issue 226][226] Wrong icons in the visible post list
* [Issue 227][227] No info window for future posts

[226]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/226
[227]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/227

= 1.2.5 =
Fixed:

* [Issue 208][208] Category lines gone crazy
* [Issue 199][199] Upgrades overwrite custom files

[208]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/208
[199]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/199

= 1.2.4 =
Fixed:

* [Issue 194][194] Post locations not saving

[194]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/194

= 1.2.3 = 
Fixed:

* [Issue 185][185] Sticky posts appear in all info windows
* [Issue 183][183] Percentage not allowed for width settings

[185]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/185
[183]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/183

= 1.2.2 = 
Fixed:

* [Issue 181][181] Marker is not showing up after update
* [Issue 177][177] Info window for post not loading (spinning wait icon) 

[181]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/181
[177]: https://github.com/cyberhobo/wordpress-geo-mashup/issues/177

= 1.2.1 =
Fixed:

* MySQL 4 incompatibilities

= 1.2 =

* New features lost to the fogs of time.

= First released on [Nov 15 2005][inception] =

[inception]: http://plugins.trac.wordpress.org/timeline?from=11%2F15%2F05&daysback=1&authors=cyberhobo&ticket=on&changeset=on&wiki=on&update=Update
