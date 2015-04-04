The [generated documentation](http://code.cyberhobo.net/phpdoc/geo-mashup-1.8/) covers all the classes and methods you can use.

Generated documentation doesn't include WordPress filters, so those are documented here along with some overviews and examples.



# Geographical Queries #

There are various ways to query by location, especially with posts.

## Location query variables ##

A subset of the TagReference#QueryVariables are used for a few kinds of geographical queries.

### Radius queries ###

A query to find all posts/objects within a distance from a search poing is made using **near\_lat**, **near\_lng**, and **radius\_km** or **radius\_mi**.

### Bounding box queries ###

A query to find all posts/objects within a bounding latitude and longitude range is made using **minlat**, **maxlat**, **minlng**, and **maxlng**.

### Named area queries ###

A query to find posts/objects in a named area is made using one of: **admin\_code** (state/province), **country\_code**, **postal\_code**, or **locality\_name** (city).

## WP\_Query Integration ##

Added in Geo Mashup 1.7.0.

Any of the location query variables can be used in a WP\_Query post query with the geo\_mashup\_query variable. Here are some examples:

```
$nv_query = new WP_Query( array(
	'posts_per_page' => -1,
	'geo_mashup_query' => array(
		'admin_code' => 'NV',
	)
) );
```

```
$zip_query = new WP_Query( array(
	'posts_per_page' => -1,
	'geo_mashup_query' => array(
		'postal_code' => '96161',
	),
) );
```

```
$radius_query = new WP_Query( array(
	'posts_per_page' => -1,
	'geo_mashup_query' => array(
		'near_lat' => 39,
		'near_lng' => -120,
		'radius_km' => 50,
	),
) );
```

Any other [WP\_Query parameters](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters) can be added, allowing for powerful combinations.

## The GM\_Location\_Query class ##

Added in Geo Mashup 1.7.0.

Geographical queries can be constructed for objects other than posts using this class, whose constructor takes an array of location query variables. An example of a user query:

```
$location_query = new GM_Location_Query( array(
	'minlat' => $nv_location->lat - 1,
	'maxlat' => $nv_location->lat + 120,
	'minlon' => $nv_location->lng - 1,
	'maxlon' => $nv_location->lng + 1,
) );

list( $cols, $join, $where, $groupby ) = $location_query->get_sql( $wpdb->users, 'ID' );

$sql = "SELECT {$wherepdb->users}.ID FROM {$wpdb->users}{$join} WHERE 1=1{$where}";
```

# Actions #

Geo Mashup behavior can be augmented with callbacks in the form of [WordPress actions](http://codex.wordpress.org/Plugin_API#Actions).

## geo\_mashup\_init ##

Called when Geo Mashup has loaded and its API is available for use.

## geo\_mashup\_render\_map ##

Arguments:
  * **$mashup\_script** - 'geo-mashup-mxn' or 'geo-mashup-google' for Google V2

Called when a map is being rendered and may be used to enqueue additional scripts.

### Example ###

This is how a script is enqueued to add the blue dot to search results maps:

```
function prefix_geo_mashup_render_map() {
  if ( 'search-results-map' == GeoMashupRenderMap::map_property( 'name' ) ) {
	  GeoMashup::register_script( 'geo-mashup-search-results', 'js/search-results.js', array( 'geo-mashup' ), GEO_MASHUP_VERSION, true );
	  GeoMashupRenderMap::enqueue_script( 'geo-mashup-search-results' );
  }
}
add_action( 'geo_mashup_render_map', 'prefix_geo_mashup_render_map' );
```

## geo\_mashup\_added\_object\_location ##

Called when an object location is created.

Arguments:
  * **$object\_name** - 'post', 'user', 'comment', etc.
  * **$object\_id**
  * **$geo\_date**
  * **$location** - the location object added

## geo\_mashup\_added\_updated\_location ##

Called when an object location is updated.

Arguments:
  * **$object\_name** - 'post', 'user', 'comment', etc.
  * **$object\_id**
  * **$geo\_date**
  * **$location** - the location object updated

## geo\_mashup\_added\_location ##

Called when a new location is added.

Arguments:
  * location**- the location added**

## geo\_mashup\_updated\_location ##

Called when a new location is updated.

Arguments:
  * location**- the location updated**

## geo\_mashup\_deleted\_object\_location ##

Called when an object location is deleted.

Arguments:
  * object\_location**- the object location deleted**

## geo\_mashup\_deleted\_location ##

Called when an location is deleted.

Arguments:
  * location**- the object location deleted**

# Filters #

Some Geo Mashup data can be altered using these [WordPress filters](http://codex.wordpress.org/Plugin_API#Filters).

## geo\_mashup\_locations\_json\_object ##

Allows you to change the object data that gets sent to a map. Called once for each object.

Arguments:

  * **$json\_properties** - an associative array of properties to include in the object, including `object_id, lat, lng, title, author, categories`
  * **$queried\_object** - the query result row returned for the object

### Example ###

Use the saved name instead of the default object title (post\_title for posts).

```
function my_geo_mashup_locations_json_filter( $json_properties, $queried_object ) {
	$json_properties['title'] = $queried_object->saved_name;
	return $json_properties;
}
add_filter( 'geo_mashup_locations_json_object', 'my_geo_mashup_locations_json_filter', 10, 2 );
```

Now you can access the data in [custom javascript](Documentation#Custom_JavaScript.md):

```
GeoMashup.addAction( 'objectIcon', function( properties, object ) {
	// Use a special icon for the saved location 'Swimming Pool'
	if ( 'Swimming Pool' == object.title ) {
		object.icon.image = properties.template_url_path + 'images/pool_icon.png';
	}
} );
```

## geo\_mashup\_locations\_fields ##

Modify the fields included in a map query.

## geo\_mashup\_locations\_join ##

Modify the tables joined in a map query.

## geo\_mashup\_locations\_where ##

Modify the where clause of a map query.

## geo\_mashup\_locations\_orderby ##

Modify the orderby clause of a map query.

## geo\_mashup\_locations\_limits ##

Modify the limit clause of a map query.

## geo\_mashup\_search\_query\_args ##

Modify the query arguments before a geo search is performed.

Arguments:

  * **$query\_args** - array of [TagReference#Query\_Variables](TagReference#Query_Variables.md).

## geo\_mashup\_static\_map ##

Modify the image HTML for static maps.

Arguments:

  * **$map\_image** - the HTML <img> tag. Return your filtered version.<br>
<ul><li><b>$map_data</b> - An array of all data used to build the map.<br>
</li><li><b>$click_to_load</b> - An array with click_to_load information:<br>
<ul><li>'click_to_load' - true if click_to_load is enabled<br>
</li><li>'click_to_load_text' - the text specified for the click_to_load link</li></ul></li></ul>


<h1>Dependent Plugins</h1>

There are good possibilites for companion plugins to make use of Geo Mashup data.<br>
<br>
If you need a Google API Key in your plugin, you can get Geo Mashup's with this PHP code: <code>$google_key = get_option( 'google_api_key' );</code>. This setting is not deleted if Geo Mashup is uninstalled.<br>
<br>
