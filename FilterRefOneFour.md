#summary Documentation on WordPress filters offered by Geo Mashup.



# Introduction #

Just like WordPress, some Geo Mashup data can be altered using [filters](http://codex.wordpress.org/Plugin_API#Filters). This is in the experimental phase in Geo Mashup 1.3, but hopefully will stabilize and grow.

# Filters #

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