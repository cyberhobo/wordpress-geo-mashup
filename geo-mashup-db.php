<?php 
/**
 * Geo Mashup Data Access
 *
 * @package GeoMashup
 */

// Init at load time - just adds hooks
GeoMashupDB::init();

/**
 * Static class to provide a namespace for Geo Mashup data functions.
 *
 * @since 1.2
 * @package GeoMashup
 */
class GeoMashupDB {
	/**
	 * Current installed database version.
	 * 
	 * @since 1.4
	 * @var string 
	 */
	private static $installed_version = null;
	/**
	 * Flag for objects that have geodata fields copied to.
	 * Key $meta_type-$object_id, value true.
	 * 
	 * @since 1.4
	 * @var array  
	 */
	private static $copied_to_geodata = array();
	/**
	 * Meta keys used to store geodata
	 *
	 * @since 1.4
	 * @var array
	 */
	private static $geodata_keys = array( 'geo_latitude', 'geo_longitude', 'geo_address' );
	/**
	 * The last geocode error, or empty if no error.
	 * @var WP_Error
	 */
	public static $geocode_error = array();

	/**
	 * WordPress action to set up data-related WordPress hooks.
	 *
	 * @since 1.4
	 */
	public static function init() {
		global $geo_mashup_options;

		// Enable the geo_mashup_query var
		add_filter( 'query_vars', array( 'GeoMashupDB', 'query_vars' ) );
		add_filter( 'posts_fields', array( 'GeoMashupDB', 'posts_fields' ), 10, 2 );
		add_filter( 'posts_join', array( 'GeoMashupDB', 'posts_join' ), 10, 2 );
		add_filter( 'posts_where', array( 'GeoMashupDB', 'posts_where' ), 10, 2 );
		add_action( 'parse_query', array( 'GeoMashupDB', 'parse_query' ) );

		// Some caching plugins don't implement this
		if ( function_exists( 'wp_cache_add_global_groups' ) )
			wp_cache_add_global_groups( array( 'geo_mashup_object_locations', 'geo_mashup_locations' ) );

		// Avoid orphans
		add_action( 'delete_post', array( 'GeoMashupDB', 'delete_post' ) );
		add_action( 'delete_comment', array( 'GeoMashupDB', 'delete_comment' ) );
		add_action( 'delete_user', array( 'GeoMashupDB', 'delete_user' ) );

		if ( 'true' == $geo_mashup_options->get( 'overall', 'copy_geodata' ) or '' != $geo_mashup_options->get( 'overall', 'import_custom_field' ) )
			self::add_geodata_sync_hooks();
	}

	/**
	 * Add hooks to synchronize Geo Mashup ojbect locations with WordPress geodata.
	 *
	 * @since 1.4
	 */
	public static function add_geodata_sync_hooks() {
		add_filter( 'update_post_metadata', array( 'GeoMashupDB', 'filter_update_post_metadata' ), 10, 5 );
		add_action( 'added_post_meta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		add_filter( 'update_user_metadata', array( 'GeoMashupDB', 'filter_update_user_metadata' ), 10, 5 );
		add_action( 'added_user_meta', array( 'GeoMashupDB', 'action_added_user_meta' ), 10, 4 );
		add_action( 'updated_user_meta', array( 'GeoMashupDB', 'action_added_user_meta' ), 10, 4 );
		add_filter( 'update_comment_metadata', array( 'GeoMashupDB', 'filter_update_comment_metadata' ), 10, 5 );
		add_action( 'added_comment_meta', array( 'GeoMashupDB', 'action_added_comment_meta' ), 10, 4 );
		add_action( 'updated_comment_meta', array( 'GeoMashupDB', 'action_added_comment_meta' ), 10, 4 );
		// AJAX calls use a slightly different hook - triksy!
		add_action( 'added_postmeta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		add_action( 'updated_postmeta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );

		add_action( 'geo_mashup_added_object_location', array( 'GeoMashupDB', 'copy_to_geodata' ), 10, 4 );
		add_action( 'geo_mashup_updated_object_location', array( 'GeoMashupDB', 'copy_to_geodata' ), 10, 4 );
	}
	
	/**
	 * Remove hooks to synchronize Geo Mashup ojbect locations with WordPress geodata.
	 *
	 * @since 1.4
	 */
	public static function remove_geodata_sync_hooks() {
		remove_filter( 'update_post_metadata', array( 'GeoMashupDB', 'filter_update_post_metadata' ), 10, 5 );
		remove_action( 'added_post_meta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		remove_action( 'updated_post_meta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		remove_filter( 'update_user_metadata', array( 'GeoMashupDB', 'filter_update_user_metadata' ), 10, 5 );
		remove_action( 'added_user_meta', array( 'GeoMashupDB', 'action_added_user_meta' ), 10, 4 );
		remove_action( 'updated_user_meta', array( 'GeoMashupDB', 'action_added_user_meta' ), 10, 4 );
		remove_filter( 'update_comment_metadata', array( 'GeoMashupDB', 'filter_update_comment_metadata' ), 10, 5 );
		remove_action( 'added_comment_meta', array( 'GeoMashupDB', 'action_added_comment_meta' ), 10, 4 );
		remove_action( 'updated_comment_meta', array( 'GeoMashupDB', 'action_added_comment_meta' ), 10, 4 );
		// AJAX calls use a slightly different hook - triksy!
		remove_action( 'added_postmeta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );
		remove_action( 'updated_postmeta', array( 'GeoMashupDB', 'action_added_post_meta' ), 10, 4 );

		remove_action( 'geo_mashup_added_object_location', array( 'GeoMashupDB', 'copy_to_geodata' ), 10, 4 );
		remove_action( 'geo_mashup_updated_object_location', array( 'GeoMashupDB', 'copy_to_geodata' ), 10, 4 );
	}

	/**
	 * WordPress action to update Geo Mashup post location when geodata custom fields are updated.
	 *
	 * @since 1.4
	 */
	public static function action_added_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
		self::copy_from_geodata( 'post', $meta_id, $post_id, $meta_key, $meta_value );
	}

	/**
	 * WordPress action to update Geo Mashup user location when geodata custom fields are updated.
	 *
	 * @since 1.4
	 */
	public static function action_added_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
		self::copy_from_geodata( 'user', $meta_id, $user_id, $meta_key, $meta_value );
	}

	/**
	 * WordPress action to update Geo Mashup comment location when geodata custom fields are updated.
	 *
	 * @since 1.4
	 */
	public static function action_added_comment_meta( $meta_id, $comment_id, $meta_key, $meta_value ) {
		self::copy_from_geodata( 'comment', $meta_id, $comment_id, $meta_key, $meta_value );
	}

	/**
	 * WordPress filter to prevent updates to geodata fields we've already updated.
	 * 
	 * @since 1.4
	 */
	public static function filter_update_post_metadata( $ok, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( !in_array( $meta_key, self::$geodata_keys ) )
			return $ok;
		if ( isset( self::$copied_to_geodata['post-' . $object_id] ) )
			return false;
		else
			return $ok;
	}

	/**
	 * WordPress filter to prevent updates to geodata fields we've already updated.
	 *
	 * @since 1.4
	 */
	public static function filter_update_user_metadata( $ok, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( !in_array( $meta_key, self::$geodata_keys ) )
			return $ok;
		if ( isset( self::$copied_to_geodata['user-' . $object_id] ) )
			return false;
		else
			return $ok;
	}

	/**
	 * WordPress filter to prevent updates to geodata fields we've already updated.
	 *
	 * @since 1.4
	 */
	public static function filter_update_comment_metadata( $ok, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( !in_array( $meta_key, self::$geodata_keys ) )
			return $ok;
		if ( isset( self::$copied_to_geodata['comment-' . $object_id] ) )
			return false;
		else
			return $ok;
	}

	/**
	 * Create a Geo Mashup object location from WordPress geodata.
	 *
	 * @since 1.4
	 * @see http://codex.wordpress.org/Geodata
	 */
	private static function copy_from_geodata( $meta_type, $meta_id, $object_id, $meta_key, $meta_value ) {
		global $geo_mashup_options, $wpdb;

		// Do nothing if meta_key is not a known location field
		$location_keys = array();
		$is_copy_geodata_on = ( 'true' === $geo_mashup_options->get( 'overall', 'copy_geodata' ) );
		$copy_imported_geodata = false;
		if ( $is_copy_geodata_on ) {
			$location_keys = array_merge( $location_keys, array( 'geo_latitude', 'geo_longitude', 'geo_lat_lng' ) );
		}
		$import_custom_keys = preg_split( '/\s*,\s*/', trim( $geo_mashup_options->get( 'overall', 'import_custom_field' ) ) );
		$location_keys = array_merge( $location_keys, $import_custom_keys );
		if ( ! in_array( $meta_key, $location_keys ) ) 
			return;

		$existing_location = self::get_object_location( $meta_type, $object_id );

		$location = array();

		// Do nothing unless both latitude and longitude exist for the object
		if ( 'geo_lat_lng' == $meta_key ) {

			$lat_lng = preg_split( '/\s*[, ]\s*/', trim( $meta_value ) );
			if ( 2 != count( $lat_lng ) ) {
				return; 
			}
			$location['lat'] = $lat_lng[0];
			$location['lng'] = $lat_lng[1];

		} else if ( 'geo_latitude' == $meta_key ) {

			$location['lat'] = $meta_value;
			$lng = get_metadata( $meta_type, $object_id, 'geo_longitude', true );
			if ( empty( $lng ) )
				return;
			$location['lng'] = $lng;

		} else if ( 'geo_longitude' == $meta_key ) {

			$location['lng'] = $meta_value;
			$lat = get_metadata( $meta_type, $object_id, 'geo_latitude', true );
			if ( empty( $lat ) )
				return;
			$location['lat'] = $lat;

		} else if ( in_array( $meta_key, $import_custom_keys ) ) {

			$lat_lng = preg_split( '/\s*[, ]\s*/', trim( $meta_value ) );
			if ( count( $lat_lng ) == 2 and is_numeric( $lat_lng[0] ) and is_numeric( $lat_lng[1] ) ) {
				$location['lat'] = $lat_lng[0];
				$location['lng'] = $lat_lng[1];
			} else if ( !empty( $meta_value ) ) {
				$geocode_values = array();
				foreach( $import_custom_keys as $import_custom_key ) {
					if ( $meta_key == $import_custom_key ) {
						$geocode_values[] = $meta_value;
					} else {

						// All keys must have a value - do nothing if not
						if ( !metadata_exists( $meta_type, $object_id, $import_custom_key ) )
							return;

						$geocode_values[] = get_metadata( $meta_type, $object_id, $import_custom_key, true );

					}
				}
				$location = self::blank_location( ARRAY_A );
				self::geocode( implode( ',', $geocode_values ), $location );
				if ( self::$geocode_error ) {
					update_metadata( $meta_type, $object_id, 'geocoding_error', self::$geocode_error->get_error_message() );
					return;
				}
				if ( $is_copy_geodata_on ) {
					$copy_imported_geodata = true;
				}
			}
		}

		// Do nothing if the location already exists
		if ( !empty( $existing_location ) ) {
			$epsilon = 0.00001;
			if ( abs( $location['lat'] - $existing_location->lat ) < $epsilon and abs( $location['lng'] - $existing_location->lng ) < $epsilon )
				return;
		}

		// Save the location, attempt reverse geocoding
		self::remove_geodata_sync_hooks();
		// Use geo date if it exists
		$geo_date = get_metadata( $meta_type, $object_id, 'geo_date', true );
		$location_id = self::set_object_location( $meta_type, $object_id, $location, null, $geo_date );
		if ( $copy_imported_geodata ) {
			self::copy_to_geodata( $meta_type, $object_id, $geo_date, $location_id );
		}
		self::add_geodata_sync_hooks();
	}

	/**
	 * Update object geodata if needed.
	 * 
	 * @since 1.4
	 * 
	 * @param string $meta_type 'post','user','comment'
	 * @param int $object_id
	 * @param string $geo_date
	 * @param int $location_id The location to copy from.
	 */
	public static function copy_to_geodata( $meta_type, $object_id, $geo_date, $location_id ) {

		$geo_latitude = get_metadata( $meta_type, $object_id, 'geo_latitude', true );
		$geo_longitude = get_metadata( $meta_type, $object_id, 'geo_longitude', true );
		$existing_object_location = self::get_object_location( $meta_type, $object_id );

		// Do nothing if the geodata already exists
		if ( $geo_latitude and $geo_longitude ) {
			$epsilon = 0.00001;
			if ( abs( $geo_latitude - $existing_object_location->lat ) < $epsilon and abs( $geo_longitude - $existing_object_location->lng ) < $epsilon )
				return;
		}
		
		self::remove_geodata_sync_hooks();
		update_metadata( $meta_type, $object_id, 'geo_latitude', $existing_object_location->lat );
		update_metadata( $meta_type, $object_id, 'geo_longitude', $existing_object_location->lng );
		update_metadata( $meta_type, $object_id, 'geo_address', $existing_object_location->address );
		update_metadata( $meta_type, $object_id, 'geo_date', $existing_object_location->geo_date );
		self::$copied_to_geodata[$meta_type . '-' . $object_id] = true;
		self::add_geodata_sync_hooks();
	}

	/**
	 * Set the installed database version.
	 * 
	 * @since 1.4
	 * 
	 * @param string $new_version 
	 */
	private static function set_installed_version( $new_version ) {
		self::$installed_version = $new_version;
		update_option( 'geo_mashup_db_version', $new_version );
	}

	/**
	 * Get the installed database version.
	 *
	 * @since 1.2
	 * 
	 * @return string The installed database version.
	 */
	public static function installed_version() {

		if ( is_null( self::$installed_version ) ) {
			self::$installed_version = get_option( 'geo_mashup_db_version' );
		}
		return self::$installed_version;
	}

	/**
	 * Get or set storage information for an object name.
	 *
	 * Potentially you could add storage information for a new kind of object:
	 * <code>
	 * GeoMashupDB::object_storage( 'foo', array(
	 * 	'table' => $wpdb->prefix . 'foos',
	 * 	'id_column' => 'foo_id',
	 * 	'label_column' => 'foo_display_name',
	 * 	'sort' => 'foo_order ASC' )
	 * ); 
	 * </code>
	 * Would add the necessary information for a custom table of foo objects. By convention the
	 * object name is the singular form of the table name without a prefix.
	 *
	 * @since 1.3
	 * 
	 * @param string $object_name A type of object to be stored, default is 'post', 'user', and 'comment'.
	 * @param array $new_storage If provided, adds or replaces the storage information for the object name.
	 * @return array|bool The storage information array, or false if not found.
	 */
	public static function object_storage( $object_name, $new_storage = null ) {
		global $wpdb;
		static $objects = null;
		
		if ( is_null( $objects ) ) {
			$objects = array( 
				'post' => array( 
					'table' => $wpdb->posts, 
					'id_column' => 'ID', 
					'label_column' => 'post_title', 
					'date_column' => 'post_date',
					'sort' => 'post_status ASC, geo_date DESC' ),
				'user' => array( 
					'table' => $wpdb->users, 
					'id_column' => 'ID', 
					'label_column' => 'display_name',
					'date_column' => 'user_registered',
			 		'sort' => 'display_name ASC' ),
				'comment' => array( 
					'table' => $wpdb->comments, 
					'id_column' => 'comment_ID', 
					'label_column' => 'comment_author',
					'date_column' => 'comment_date',
			 		'sort' => 'comment_date DESC'	) 
			);
		}

		if ( !empty( $new_storage ) ) {
			$objects[$object_name] = $new_storage;
		} 
		return ( isset( $objects[$object_name] ) ) ? $objects[$object_name] : false;
	}

	/**
	 * Return a conventional object name given a table name.
	 *
	 * @since 1.7
	 *
	 * @param string $table
	 * @return string
	 */
	public static function table_to_object_name( $table ) {
		global $wpdb;
		$object_name = str_replace( $wpdb->prefix, '', $table );
		if ( 's' === substr( $object_name, -1 ) ) {
			$object_name = substr( $object_name, 0, strlen( $object_name ) - 1 );
		}
		return $object_name;
	}

	/**
	 * Toggle joining of WordPress queries with Geo Mashup tables.
	 * 
	 * Use the public wrapper GeoMashup::join_post_queries()
	 * 
	 * @since 1.3
	 * @deprecated Use the geo_mashup_query query var
	 *
	 * @param bool $new_value If provided, replaces the current active state.
	 * @return bool The current state.
	 */
	public static function join_post_queries( $new_value = null) {
		static $active = null;

		if ( is_bool( $new_value ) ) {
			_deprecated_function( __METHOD__, '1.7', 'the geo_mashup_query query var' );
			$active = $new_value;
		}

		return $active;
	}

	/**
	 * WordPress filter to add Geo Mashup public query variables.
	 *
	 * query_vars {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by Wordpress.
	 *
	 * @since 1.3
	 */
	public static function query_vars( $public_query_vars ) {
		if ( self::join_post_queries() ) {
			$public_query_vars[] = 'geo_mashup_date';
			$public_query_vars[] = 'geo_mashup_saved_name';
			$public_query_vars[] = 'geo_mashup_country_code';
			$public_query_vars[] = 'geo_mashup_postal_code';
			$public_query_vars[] = 'geo_mashup_admin_code';
			$public_query_vars[] = 'geo_mashup_locality';
		}
		return $public_query_vars;
	}

	/**
	 * Set or get custom data associated with a WP_Query object.
	 *
	 * @since 1.7
	 *
	 * @param WP_Query $query
	 * @param string $key Optional - return all data for the query if missing.
	 * @param mixed $value Optional - set or overwrite data for the key if present.
	 * @return mixed Extension data if present.
	 */
	private static function query_extension( $query, $key = null, $value = null ) {
		static $extensions = array();

		$hash = spl_object_hash( $query );

		if ( is_null( $key ) )
			return $extensions;

		if ( !isset( $extensions[$hash] ) )
			$extensions[$hash] = array();

		if ( !is_null( $value ) )
			$extensions[$hash][$key] = $value;
		else if ( !isset( $extensions[$hash][$key] ) )
			return null;

		return $extensions[$hash][$key];
	}

	/**
	 * WordPress action to capture custom orderby field before it is removed.
	 *
	 * parse_query {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	public static function parse_query( $query ) {
		global $wpdb;

		if ( !self::join_post_queries() )
			return;

		// Check for geo mashup fields in the orderby before they are removed as invalid
		switch ( $query->query_vars['orderby'] ) {
			case 'geo_mashup_date':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_location_relationships.geo_date' );
				break;

			case 'geo_mashup_locality':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_locations.locality_name' );
				break;

			case 'geo_mashup_saved_name':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_locations.saved_name' );
				break;

			case 'geo_mashup_country_code':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_locations.country_code' );
				break;

			case 'geo_mashup_admin_code':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_locations.admin_code' );
				break;

			case 'geo_mashup_postal_code':
				self::query_extension( $query, 'orderby', $wpdb->prefix . 'geo_mashup_locations.postal_code' );
				break;
		}
	}

	/**
	 * WordPress filter to add Geo Mashup fields to WordPress post queries.
	 *
	 * posts_fields {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 */
	public static function posts_fields( $fields, $query ) {
		global $wpdb;

		if ( self::join_post_queries() ) {
			$fields .= ',' . $wpdb->prefix . 'geo_mashup_location_relationships.geo_date' .
				',' . $wpdb->prefix . 'geo_mashup_locations.*';
		}

		return $fields;
	}

	/**
	 * WordPress filter to join Geo Mashup tables to WordPress post queries.
	 * 
	 * posts_join {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 */
	public static function posts_join( $join, $query ) {
		global $wpdb;

		if ( self::join_post_queries() ) {
			$gmlr = $wpdb->prefix . 'geo_mashup_location_relationships';
			$gml = $wpdb->prefix . 'geo_mashup_locations';
			$join .= " INNER JOIN $gmlr ON ($gmlr.object_name = 'post' AND $gmlr.object_id = $wpdb->posts.ID)" .
				" INNER JOIN $gml ON ($gml.id = $gmlr.location_id) ";
		}

		return $join;
	}

	/**
	 * WordPress filter to incorporate geo mashup query vars in WordPress post queries.
	 *
	 * posts_where {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 */
	public static function posts_where( $where, $query ) {
		global $wpdb;

		if ( !self::join_post_queries() )
			return $where;

		$gmlr = $wpdb->prefix . 'geo_mashup_location_relationships';
		$gml = $wpdb->prefix . 'geo_mashup_locations';
		$geo_date = get_query_var( 'geo_mashup_date' );
		if ( $geo_date ) {
			$where .= $wpdb->prepare( " AND $gmlr.geo_date = %s ", $geo_date );
		}
		$saved_name = get_query_var( 'geo_mashup_saved_name' );
		if ( $saved_name ) {
			$where .= $wpdb->prepare( " AND $gml.saved_name = %s ", $saved_name );
		}
		$locality = get_query_var( 'geo_mashup_locality' );
		if ( $locality ) {
			$where .= $wpdb->prepare( " AND $gml.locality_name = %s ", $locality );
		}
		$country_code = get_query_var( 'geo_mashup_country_code' );
		if ( $country_code ) {
			$where .= $wpdb->prepare( " AND $gml.country_code = %s ", $country_code );
		}
		$admin_code = get_query_var( 'geo_mashup_admin_code' );
		if ( $admin_code ) {
			$where .= $wpdb->prepare( " AND $gml.admin_code = %s ", $admin_code );
		}
		$postal_code = get_query_var( 'geo_mashup_postal_code' );
		if ( $postal_code ) {
			$where .= $wpdb->prepare( " AND $gml.postal_code = %s ", $postal_code );
		}

		return $where;
	}

	/**
	 * Append to the activation log.
	 * 
	 * Add a message and optionally write the activation log.
	 * Needs to be written before the end of the request or it will not be saved.
	 *
	 * @since 1.4
	 *
	 * @param string $message The message to append.
	 * @param boolean $write Whether to save the log.
	 * @return string The current log.
	 */
	public static function activation_log( $message = null, $write = false ) {
		static $log = null;

		if ( is_null( $log ) ) {
			$log = get_option( 'geo_mashup_activation_log' );
		}
		if ( ! is_null( $message ) ) {
			$log .= "\n" . $message;
		}
		if ( $write ) {
			update_option( 'geo_mashup_activation_log', $log );
		}
		return $log;
	}

	/**
	 * Install or update Geo Mashup tables.
	 *
	 * @uses GeoMashupDB::activation_log()
	 * @since 1.2
	 */
	public static function install() {
		global $wpdb, $geo_mashup_options;

		self::activation_log( date( 'r' ) . ' ' . __( 'Activating Geo Mashup', 'GeoMashup' ) );
		$location_table_name = $wpdb->prefix . 'geo_mashup_locations';
		$relationships_table_name = $wpdb->prefix . 'geo_mashup_location_relationships';
		$administrative_names_table_name = $wpdb->prefix . 'geo_mashup_administrative_names';

		$charset_collate = '';

		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		if ( self::installed_version() != GEO_MASHUP_DB_VERSION ) {
			$sql = "
				CREATE TABLE $location_table_name (
					id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					lat FLOAT(11,7) NOT NULL,
					lng FLOAT(11,7) NOT NULL,
					address TINYTEXT NULL,
					saved_name VARCHAR(100) NULL,
					geoname TINYTEXT NULL, 
					postal_code TINYTEXT NULL,
					country_code VARCHAR(2) NULL,
					admin_code VARCHAR(20) NULL,
					sub_admin_code VARCHAR(80) NULL,
					locality_name TINYTEXT NULL,
					PRIMARY KEY  (id),
					UNIQUE KEY saved_name (saved_name),
					UNIQUE KEY latlng (lat,lng),
					KEY lat (lat),
					KEY lng (lng)
				) $charset_collate;
				CREATE TABLE $relationships_table_name (
					object_name VARCHAR(80) NOT NULL,
					object_id BIGINT(20) NOT NULL,
					location_id MEDIUMINT(9) NOT NULL,
					geo_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY  (object_name,object_id,location_id),
					KEY object_name (object_name,object_id),
					KEY object_date_key (object_name,geo_date)
				) $charset_collate;
				CREATE TABLE $administrative_names_table_name (
					country_code VARCHAR(2) NOT NULL,
					admin_code VARCHAR(20) NOT NULL,
					isolanguage VARCHAR(7) NOT NULL,
					geoname_id MEDIUMINT(9) NULL,
					name VARCHAR(200) NOT NULL,
					PRIMARY KEY  (country_code,admin_code,isolanguage)
				) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			// Capture error messages - some are ok
			$old_show_errors = $wpdb->show_errors( true );
			ob_start();
			dbDelta( $sql );
			$errors = ob_get_contents();
			ob_end_clean();
			$have_create_errors = preg_match( '/^(CREATE|INSERT|UPDATE)/m', $errors );
			$have_bad_alter_errors = preg_match( '/^ALTER TABLE.*ADD (?!KEY|PRIMARY KEY|UNIQUE KEY)/m', $errors ); 
			$have_bad_errors = $have_create_errors || $have_bad_alter_errors;
			if ( $errors && $have_bad_errors ) {
				// Any errors other than duplicate or multiple primary key could be trouble
				self::activation_log( $errors, true );
			} else {
				self::convert_prior_locations();
				self::set_installed_version( GEO_MASHUP_DB_VERSION );
			}
			$wpdb->show_errors( $old_show_errors );
		}
		if ( self::installed_version() == GEO_MASHUP_DB_VERSION ) {
			if ( 'true' == $geo_mashup_options->get( 'overall', 'copy_geodata' ) )
				self::duplicate_geodata();
			self::activation_log( __( 'Geo Mashup database is up to date.', 'GeoMashup' ), true );
			return true;
		} else {
			self::activation_log( __( 'Geo Mashup database upgrade failed.', 'GeoMashup' ), true );
			return false;
		}
	}

	/**
	 * Try to get a language-sensitive place administrative name.
	 *
	 * First look in the names cached in the database, then query geonames.org for it.
	 * If a name can't be found for the requested language, a default name is returned,
	 * usually in the local language. If nothing can be found, returns NULL.
	 *
	 * @since 1.4
	 *
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string|null Place name in the appropriate language, or if not available in the default language.
	 */
	public static function get_administrative_name( $country_code, $admin_code = null, $language = '' ) {
		$language = self::primary_language_code( $language );
		$name = GeoMashupDB::get_cached_administrative_name( $country_code, $admin_code, $language );
		if ( empty( $name ) ) {
			// Look it up with Geonames
			if ( !class_exists( 'GeoMashupHttpGeocoder' ) )
				include_once( path_join( GEO_MASHUP_DIR_PATH, 'geo-mashup-geocoders.php' ) );
			$geocoder = new GeoMashupGeonamesGeocoder();
			$name = $geocoder->get_administrative_name( $country_code, $admin_code );
			if ( is_wp_error( $name ) )
				$name = null;
		}
		return $name;
	}

	/** 
	 * Look in the database for a cached administrative name. 
	 *
	 * @since 1.2
	 *
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string|null Place name or NULL.
	 */
	private static function get_cached_administrative_name( $country_code, $admin_code = '', $language = '' ) {
		global $wpdb;

		$language = self::primary_language_code( $language );
		$select_string = "SELECT name
			FROM {$wpdb->prefix}geo_mashup_administrative_names
			WHERE " . 
			$wpdb->prepare( 'isolanguage = %s AND country_code = %s AND admin_code = %s', $language, $country_code, $admin_code ); 

		return $wpdb->get_var( $select_string );
	}

	/** 
	 * Trim a locale or browser accepted languages string down to the 2 or 3 character
	 * primary language code.
	 *
	 * @since 1.2
	 *
	 * @param string $language Local or language code string, NULL for blog locale.
	 * @return string Two (rarely three?) character language code.
	 */
	public static function primary_language_code( $language = null ) {
		if ( empty( $language ) ) {
			$language = get_locale( );
		}
		if ( strlen( $language ) > 3 ) {
			if ( ctype_alpha( $language[2] ) ) {
				$language = substr( $language, 0, 3 );
			} else {
				$language = substr( $language, 0, 2 );
			}
		}
		return $language;
	}

	/**
	 * Try to fill in coordinates and other fields of a location from a textual
	 * location search.
	 *
	 * Multiple geocoding services may be used. Google services are only used
	 * if the default map provider is Google.
	 * 
	 * @since 1.3
	 *
	 * @param mixed $query The search string.
	 * @param array $location The location array to geocode, modified.
	 * @param string $language 
	 * @return bool Whether a lookup succeeded.
	 */
	public static function geocode( $query, &$location, $language = '' ) {
		global $geo_mashup_options;

		if ( empty( $location ) ) {
			$location = self::blank_location();
		} else if ( !is_array( $location ) and !is_object( $location ) ) {
			return false;
		}

		$status = false;
		if ( !class_exists( 'GeoMashupHttpGeocoder' ) )
			include_once( path_join( GEO_MASHUP_DIR_PATH, 'geo-mashup-geocoders.php' ) );

		// Try GeoCoding services (google, nominatim, geonames) until one gives an answer
		$results = array();
		if ( $geo_mashup_options->get( 'overall', 'google_server_key' ) ) {
			// Only try the google service if a google server key is present
			$google_geocoder = new GeoMashupGoogleGeocoder( array( 'language' => $language ) );
			$results = $google_geocoder->geocode( $query );
		}
		if ( is_wp_error( $results ) or empty( $results ) ) {
			self::$geocode_error = $results;
			$nominatim_geocoder = new GeoMashupNominatimGeocoder( array( 'language' => $language ) );
			$results = $nominatim_geocoder->geocode( $query );
		}
		if ( is_wp_error( $results ) or empty( $results ) ) {
			self::$geocode_error = $results;
			$geonames_geocoder = new GeoMashupGeonamesGeocoder( array( 'language' => $language ) );
			$results = $geonames_geocoder->geocode( $query );
		}
		if ( is_wp_error( $results ) or empty( $results ) ) {
			self::$geocode_error = $results;
		} else {
			self::fill_empty_location_fields( $location, $results[0] );
			$status = true;
		}
		return $status;
	}

	/**
	 * Check a location for empty fields.
	 *
	 * @since 1.4
	 *
	 * @param array $location The location to check.
	 * @param array $fields The fields to check.
	 * @return bool Whether any of the specified fields are empty.
	 */
	public static function are_any_location_fields_empty( $location, $fields = null ) {
		if ( ! is_array( $location ) ) {
			$location = (array)$location;
		}
		if ( is_null( $fields ) ) {
			$fields = array_keys( $location );
		}
		foreach( $fields as $field ) {
			if ( empty( $location[$field] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Copy empty fields in one location array from another.
	 * 
	 * @since 1.4
	 * 
	 * @param array $primary Location to copy to, modified.
	 * @param array $secondary Location to copy from.
	 */
	private static function fill_empty_location_fields( &$primary, $secondary ) {
		$secondary = (array)$secondary;
		foreach( $primary as $field => $value ) {
			if ( empty( $value ) and !empty( $secondary[$field] ) ) {
				if ( is_object( $primary ) )
					$primary->$field = $secondary[$field];
				else
					$primary[$field] = $secondary[$field];
			}
		}
	}

	/**
	 * Add missing location fields, and update country and admin codes with
	 * authoritative Geonames values.
	 *
	 * @since 1.3
	 *
	 * @param array $location The location to geocode, modified.
	 * @param string $language Optional ISO language code.
	 * @return bool Success.
	 */
	private static function reverse_geocode_location( &$location, $language = '' ) {

		// Coordinates are required
		if ( self::are_any_location_fields_empty( $location, array( 'lat', 'lng' ) ) ) 
			return false;

		// Don't bother unless there are missing geocodable fields
		$geocodable_fields = array( 'country_code', 'admin_code', 'address', 'locality_name', 'postal_code' );
		$have_empties = self::are_any_location_fields_empty( $location, $geocodable_fields );
		if ( ! $have_empties ) 
			return false;

		$status = false;

		if ( !class_exists( 'GeoMashupHttpGeocoder' ) )
			include_once( path_join( GEO_MASHUP_DIR_PATH, 'geo-mashup-geocoders.php' ) );

		$geonames_geocoder = new GeoMashupGeonamesGeocoder();
		$geonames_results = $geonames_geocoder->reverse_geocode( $location['lat'], $location['lng'] );
		if ( is_wp_error( $geonames_results ) or empty( $geonames_results ) ) {
			self::$geocode_error = $geonames_results;
		} else {
			if ( !empty( $geonames_results[0]->admin_code ) )
				$location['admin_code'] = $geonames_results[0]->admin_code;
			if ( !empty( $geonames_results[0]->country_code ) )
				$location['country_code'] = $geonames_results[0]->country_code;
			self::fill_empty_location_fields( $location, (array)($geonames_results[0]) );
			$status = true;
		}

		$have_empties = self::are_any_location_fields_empty( $location, $geocodable_fields );
		$alternate_geocoder = self::make_alternate_reverse_geocoder();
		if ( $have_empties and $alternate_geocoder ) {
			$next_results = $alternate_geocoder->reverse_geocode( $location['lat'], $location['lng'] );
			if ( is_wp_error( $next_results ) or empty( $next_results ) )
				self::$geocode_error = $next_results;
			else
				self::fill_empty_location_fields( $location, (array)($next_results[0]) );
			$status = true;
		}
		return $status;
	}

	private static function make_alternate_reverse_geocoder() {
		global $geo_mashup_options;
		// Choose a geocoding service based on the default API in use
		if ( $geo_mashup_options->get( 'overall', 'google_server_key' ) ) {
			return new GeoMashupGoogleGeocoder();
		} else if ( 'openlayers' == $geo_mashup_options->get( 'overall', 'map_api' ) ) {
			return new GeoMashupNominatimGeocoder();
		}
		return null;
	}

	/**
	 * Try to reverse-geocode all locations with relevant missing data.
	 *
	 * Used by the options page. Tries to comply with the PHP maximum execution 
	 * time, and delay requests if Google sends a 604.
	 * 
	 * @since 1.3
	 * @return string An HTML log of the actions performed.
	 */
	public static function bulk_reverse_geocode() {
		global $wpdb;

		$log = date( 'r' ) . '<br/>';
		$select_string = 'SELECT * ' .
			"FROM {$wpdb->prefix}geo_mashup_locations ". 
			"WHERE country_code IS NULL ".
			"OR admin_code IS NULL " .
			"OR address IS NULL " .
			"OR locality_name IS NULL " .
			"OR postal_code IS NULL ";
		$locations = $wpdb->get_results( $select_string, ARRAY_A );
		if ( empty( $locations ) ) {
			$log .= __( 'No locations found with missing address fields.', 'GeoMashup' ) . '<br/>';
			return $log;
		}
		$log .= __( 'Locations to geocode: ', 'GeoMashup' ) . count( $locations ) . '<br />';
		$time_limit = ini_get( 'max_execution_time' );
		if ( empty( $time_limit ) || !is_numeric( $time_limit ) ) {
			$time_limit = 270;
		} else {
			$time_limit -= ( $time_limit / 10 );
		}
		$delay = 100000; // one tenth of a second
		$log .= __( 'Time limit: ', 'GeoMashup' ) . $time_limit . '<br />';
		$start_time = time();
		foreach ( $locations as $location ) {
			$set_id = self::set_location( $location, true );
			if ( is_wp_error( $set_id ) ) {
				$log .= 'error: ' . $set_id->get_error_message() . ' ';
			} else {
				$log .= 'id: ' . $location['id'] . ' '; 
			}
			if ( !empty( self::$geocode_error ) ) {
				$delay += 100000;
				$log .= __( 'Lookup error:', 'GeoMashup' ) . ' ' . self::$geocode_error->get_error_message() .
						' ' . __( 'Increasing delay to', 'GeoMashup' ) . ' ' . ( $delay / 1000000 ) .
						'<br/>';
			} else if ( isset( $location['address'] ) ) {
				$log .= __( 'address', 'GeoMashup' ) . ': ' . $location['address'];
				if ( isset( $location['postal_code'] ) ) 
					$log .= ' ' .  __( 'postal code', 'GeoMashup' ) . ': ' . $location['postal_code'];
				$log .= '<br />';
			} else {
				$log .= '(' .$location['lat'] . ', ' . $location['lng'] . ') ' . 
						__( 'No address info found.', 'GeoMashup' ) .  '<br/>';
			}
			if ( time() - $start_time > $time_limit ) {
				$log .= __( 'Time limit exceeded, retry to continue.', 'GeoMashup' ) . '<br />';
				break;
			}
			usleep( $delay );
		}
		return $log;
	}
			
	/**
	 * Store an administrative name in the database to prevent future web service lookups.
	 * 
	 * @since 1.2
	 *
	 * @param string $country_code 
	 * @param string $admin_code 
	 * @param string $isolanguage 
	 * @param string $name 
	 * @param string $geoname_id 
	 * @return int Rows affected.
	 */
	public static function cache_administrative_name( $country_code, $admin_code, $isolanguage, $name, $geoname_id = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'geo_mashup_administrative_names';
		$cached_name = self::get_cached_administrative_name( $country_code, $admin_code, $isolanguage );
		$rows = 0;
		if ( empty( $cached_name ) ) {
			$rows = $wpdb->insert( $table_name, compact( 'country_code', 'admin_code', 'isolanguage', 'name', 'geoname_id' ) );
		} else if ( $cached_name != $name ) {
			$rows = $wpdb->update( $table_name, compact( 'name' ), compact( 'country_code', 'admin_code', 'name' ) );
		}
		return $rows;
	}

	/**
	 * Copy missing geo data to and from the standard location (http://codex.wordpress.org/Geodata)
	 * for posts, users, and comments.
	 *
	 * @since 1.4
	 * @return bool True if no more orphan locations can be found.
	 */
	public static function duplicate_geodata() {
		self::duplicate_geodata_type( 'post' );
		self::duplicate_geodata_type( 'user' );
		self::duplicate_geodata_type( 'comment' );
		self::activation_log( __( 'Geodata duplication done.', 'GeoMashup' ), true );
	}

	/**
	 * Copy missing geo data to and from the standard location (http://codex.wordpress.org/Geodata)
	 * for a specific object type.
	 *
	 * @since 1.4
	 *
	 * @global object $wpdb
	 * @param string $meta_type One of the WP meta types, 'post', 'user', 'comment'
	 * @return bool True if no more orphan locations can be found.
	 */
	private static function duplicate_geodata_type( $meta_type ) {
		global $wpdb;
		$object_storage = self::object_storage( $meta_type );
		$meta_type = esc_sql( $meta_type );
		$meta_type_id = $meta_type . '_id';
		$meta_table = $meta_type . 'meta';
		// Copy from meta table to geo mashup
		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$meta_select = "SELECT pmlat.{$meta_type_id} as object_id, pmlat.meta_value as lat, pmlng.meta_value as lng, pmaddr.meta_value as address, o.{$object_storage['date_column']} as object_date
			FROM {$object_storage['table']} o
			INNER JOIN {$wpdb->$meta_table} pmlat ON pmlat.{$meta_type_id} = o.{$object_storage['id_column']} AND pmlat.meta_key = 'geo_latitude'
			INNER JOIN {$wpdb->$meta_table} pmlng ON pmlng.{$meta_type_id} = o.{$object_storage['id_column']} AND pmlng.meta_key = 'geo_longitude'
			LEFT JOIN {$wpdb->$meta_table} pmaddr ON pmaddr.{$meta_type_id} = o.{$object_storage['id_column']} AND pmaddr.meta_key = 'geo_address'
			LEFT JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = o.{$object_storage['id_column']} AND gmlr.object_name = '{$meta_type}'
			WHERE pmlat.meta_key = 'geo_latitude' 
			AND gmlr.object_id IS NULL";

		$wpdb->query( $meta_select );

		if ($wpdb->last_error) {
			self::activation_log( $wpdb->last_error );
			return false;
		}

		$unconverted_metadata = $wpdb->last_result;
		if ( $unconverted_metadata ) {
			$msg = sprintf( __( 'Copying missing %s geodata from WordPress', 'GeoMashup' ), $meta_type );
			self::activation_log( $msg );
			$start_time = time();
			foreach ( $unconverted_metadata as $objectmeta ) {
				$object_id = $objectmeta->object_id;
				$location = array( 'lat' => trim( $objectmeta->lat ), 'lng' => trim( $objectmeta->lng ), 'address' => trim( $objectmeta->address ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = self::set_object_location( $meta_type, $object_id, $location, $do_lookups, $objectmeta->object_date );
				if ( !is_wp_error( $set_id ) ) {
					self::activation_log( 'OK: ' . $meta_type . ' id ' . $object_id );
				} else {
					$msg = sprintf( __( 'Failed to duplicate WordPress location (%s). You can edit %s with id %s ' .
						'to update the location, and try again.', 'GeoMashup' ),
						$objectmeta->lat . ',' . $objectmeta->lng, $meta_type, $object_id );
					$msg .= ' (' . $set_id->get_error_message() . ')';
					self::activation_log( $msg, true );
				}
			}
		}

		// Copy from Geo Mashup to missing object meta
		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$geomashup_select = "SELECT gmlr.object_id, gml.lat, gml.lng, gml.address
			FROM {$wpdb->prefix}geo_mashup_locations gml
			INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id
			LEFT JOIN {$wpdb->$meta_table} pmlat ON pmlat.{$meta_type_id} = gmlr.object_id AND pmlat.meta_key = 'geo_latitude'
			WHERE gmlr.object_name = '{$meta_type}'
			AND pmlat.{$meta_type_id} IS NULL";

		$wpdb->query( $geomashup_select );

		if ($wpdb->last_error) {
			self::activation_log( $wpdb->last_error, true );
			return false;
		}

		$unconverted_geomashup_objects = $wpdb->last_result;
		if ( $unconverted_geomashup_objects ) {
			$msg = sprintf( __( 'Copying missing %s geodata from Geo Mashup', 'GeoMashup' ), $meta_type );
			self::activation_log( date( 'r' ) . ' ' . $msg );
			$start_time = time();
			foreach ( $unconverted_geomashup_objects as $location ) {
				$lat_success = update_metadata( $meta_type, $location->object_id, 'geo_latitude', $location->lat );
				$lng_success = update_metadata( $meta_type, $location->object_id, 'geo_longitude', $location->lng );
				if ( ! empty( $location->address ) ) {
					update_metadata( $meta_type, $location->object_id, 'geo_address', $location->address );
				}
				if ( $lat_success and $lng_success ) {
					self::activation_log( 'OK: ' . $meta_type . ' id ' . $location->object_id );
				} else {
					$msg = sprintf( __( 'Failed to duplicate Geo Mashup location for %s (%s).', 'GeoMashup' ), $meta_type, $location->object_id );
					self::activation_log( $msg );
				}
			}
		}

		$wpdb->query( $meta_select );

		return ( empty( $wpdb->last_result ) );
	}

	/**
	 * Convert Geo plugin locations to Geo Mashup format.
	 *
	 * @since 1.2
	 * @return bool True if no more unconverted locations can be found.
	 */
	private static function convert_prior_locations( ) {
		global $wpdb;

		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$unconverted_select = "SELECT pm.post_id, pm.meta_value
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->postmeta} lpm ON lpm.post_id = pm.post_id 
			AND lpm.meta_key = '_geo_converted'
			LEFT JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = pm.post_id
			AND gmlr.object_name = 'post'
			WHERE pm.meta_key = '_geo_location' 
			AND length( pm.meta_value ) > 3
			AND pm.meta_value LIKE '%,%'
			AND lpm.post_id IS NULL 
			AND gmlr.object_id IS NULL";

		$wpdb->query( $unconverted_select );

		if ($wpdb->last_error) {
			self::activation_log( $wpdb->last_error, true );
			return false;
		}

		$unconverted_metadata = $wpdb->last_result;
		if ( $unconverted_metadata ) {
			$msg = __( 'Converting old locations', 'GeoMashup' );
			self::activation_log( date( 'r' ) . ' ' . $msg );
			$start_time = time();
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				list( $lat, $lng ) = explode( ',', $postmeta->meta_value );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = self::set_object_location( 'post', $post_id, $location, $do_lookups );
				if ( !is_wp_error( $set_id ) ) {
					add_post_meta( $post_id, '_geo_converted', $wpdb->prefix . 'geo_mashup_locations.id = ' . $set_id );
					self::activation_log( 'OK: post_id ' . $post_id );
				} else {
					$msg = sprintf( __( 'Failed to convert location (%s). You can %s edit the post %s ' .
						'to update the location, and try again.', 'GeoMashup' ),
						$postmeta->meta_value, '<a href="post.php?action=edit&post=' . $post_id . '">', '</a>');
					$msg .= ' (' . $set_id->get_error_message() . ')';
					self::activation_log( $msg );
				}
			}
		}

		$geo_locations = get_option( 'geo_locations' );
		if ( is_array( $geo_locations ) ) {
			$msg = __( 'Converting saved locations', 'GeoMashup' );
			self::activation_log( $msg );
			foreach ( $geo_locations as $saved_name => $coordinates ) {
				list( $lat, $lng, $converted ) = explode( ',', $coordinates );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ), 'saved_name' => $saved_name );
				$do_lookups = ( ( time() - $start_time ) < 15 ) ? true : false;
				$set_id = self::set_location( $location, $do_lookups );
				if ( ! is_wp_error( $set_id ) ) {
					$geo_locations[$saved_name] .= ',' . $wpdb->prefix . 'geo_mashup_locations.id=' . $set_id;
					$msg = __( 'OK: ', 'GeoMashup' ) . $saved_name . '<br/>';
					self::activation_log( $msg );
				} else {
					$msg = $saved_name . ' - ' . 
						sprintf( __( "Failed to convert saved location (%s). " .
							"You'll have to save it again, sorry.", 'GeoMashup' ),
						$coordinates );
					self::activation_log( $set_id->get_error_message() );
					self::activation_log( $msg );
				}
			}
			delete_option( 'geo_locations', $geo_locations );
		}

		$geo_date_update = "UPDATE {$wpdb->prefix}geo_mashup_location_relationships gmlr, $wpdb->posts p " .
			"SET gmlr.geo_date = p.post_date " .
			"WHERE gmlr.object_name='post' " .
			"AND gmlr.object_id = p.ID " .
			"AND gmlr.geo_date = '0000-00-00 00:00:00'";

		$geo_date_count = $wpdb->query( $geo_date_update );

		if ( $geo_date_count === false ) {
			$msg = __( 'Failed to initialize geo dates from post dates: ', 'GeoMashup' );
			$msg .= $wpdb->last_error;
		} else {
			$msg = sprintf( __( 'Initialized %d geo dates from corresponding post dates.', 'GeoMashup' ), $geo_date_count );
		}

		self::activation_log( $msg, true );

		$wpdb->query( $unconverted_select );

		return ( empty( $wpdb->last_result ) );
	}

	/**
	 * Get a blank location.
	 *
	 * Used to return object fields too - use blank_object_location for that if desired.
	 *
	 * @since 1.2
	 * 
	 * @param string $format OBJECT or ARRAY_A
	 * @return array|object Empty location.
	 */
	public static function blank_location( $format = OBJECT ) {
		global $wpdb;
		static $blank_location = null;
		if ( is_null( $blank_location ) ) {
			$wpdb->query("SELECT * FROM {$wpdb->prefix}geo_mashup_locations WHERE 1=2" );
			$col_info = $wpdb->get_col_info();
			$blank_location = array();
			foreach( $col_info as $col_name ) {
				$blank_location[$col_name] = null;
			}
		}
		if ( $format == OBJECT ) {
			return (object) $blank_location;
		} else {
			return $blank_location;
		}
	}

	/**
	 * Get a blank object location.
	 *
	 * @since 1.4
	 *
	 * @param string $format OBJECT or ARRAY_A
	 * @return array|object Empty object location.
	 */
	public static function blank_object_location( $format = OBJECT ) {
		global $wpdb;
		static $blank_object_location = null;
		if ( is_null( $blank_object_location ) ) {
			$wpdb->query("SELECT * FROM {$wpdb->prefix}geo_mashup_locations gml
					JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id
					WHERE 1=2" );
			$col_info = $wpdb->get_col_info();
			$blank_object_location = array();
			foreach( $col_info as $col_name ) {
				$blank_object_location[$col_name] = null;
			}
		}
		if ( $format == OBJECT ) {
			return (object) $blank_object_location;
		} else {
			return $blank_object_location;
		}
	}

	/**
	 * Get distinct values of one or more object location fields.
	 *
	 * Can be used to get a list of countries with locations, for example.
	 *
	 * @since 1.2
	 * 
	 * @param string $names Comma separated table field names.
	 * @param array $where Associtive array of conditional field names and values.
	 * @return array WP_DB query results.
	 */
	public static function get_distinct_located_values( $names, $where = null ) {
		global $wpdb;

		if ( is_string( $names ) ) {
			$names = preg_split( '/\s*,\s*/', $names );
		}
		$wheres = array( );
		foreach( $names as $name ) {
			$wheres[] = esc_sql( $name ) . ' IS NOT NULL';
		}
		$names = implode( ',', $names );

		if ( is_object( $where ) ) {
			$where = (array) $where;
		}

		$select_string = 'SELECT DISTINCT ' . esc_sql( $names ) . "
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id";

		if ( is_array( $where ) && !empty( $where ) ) {
			foreach ( $where as $name => $value ) {
				$wheres[] = esc_sql( $name ) . ' = \'' . esc_sql( $value ) .'\'';
			}
			$select_string .= ' WHERE ' . implode( ' AND ', $wheres );
		}
		$select_string .= ' ORDER BY ' . esc_sql( $names );

		return $wpdb->get_results( $select_string );
	}

	/**
	 * Get the location of a post.
	 *
	 * @since 1.2
	 * @uses GeoMashupDB::get_object_location()
	 * 
	 * @param id $post_id 
	 * @return object Post location.
	 */
	public static function get_post_location( $post_id ) {
		return self::get_object_location( 'post', $post_id );
	}

	/**
	 * Format a query result as an object or array.
	 * 
	 * @since 1.3
	 *
	 * @param object $obj To be formatted.
	 * @param constant $output Format.
	 * @return object|array Result.
	 */
	private static function translate_object( $obj, $output = OBJECT ) {
		if ( !is_object( $obj ) ) {
			return $obj;
		}

		if ( $output == OBJECT ) {
		} elseif ( $output == ARRAY_A ) {
			$obj = get_object_vars($obj);
		} elseif ( $output == ARRAY_N ) {
			$obj = array_values(get_object_vars($obj));
		}
		return $obj;
	}

	/**
	 * Get the location of an object.
	 * 
	 * @since 1.3
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param int $object_id Object
	 * @param string $output (optional) one of ARRAY_A | ARRAY_N | OBJECT constants.  Return an
	 * 		associative array (column => value, ...), a numerically indexed array (0 => value, ...)
	 * 		or an object ( ->column = value ), respectively.
	 * @return object|array Result or null if not found.
	 */
	public static function get_object_location( $object_name, $object_id, $output = OBJECT ) {
		global $wpdb;

		$cache_id = $object_name . '-' . $object_id;

		$object_location = wp_cache_get( $cache_id, 'geo_mashup_object_locations' );
		if ( !$object_location ) {
			$object_store = self::object_storage( $object_name );
			$field_string = "gmlr.object_id, gmlr.geo_date, o.{$object_store['label_column']} as label, gml.*";

			if ( 'post' == $object_name )
				$field_string .= ', o.post_author';

			$select_string = "SELECT {$field_string}
				FROM {$wpdb->prefix}geo_mashup_locations gml
				INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr " .
				$wpdb->prepare( 'ON gmlr.object_name = %s AND gmlr.location_id = gml.id ', $object_name ) .
				"INNER JOIN {$object_store['table']} o ON o.{$object_store['id_column']} = gmlr.object_id " .
				$wpdb->prepare( 'WHERE gmlr.object_id = %d', $object_id ); 

			$object_location = $wpdb->get_row( $select_string );
			wp_cache_add( $cache_id, $object_location, 'geo_mashup_object_locations' );
		}
		return self::translate_object( $object_location, $output );
	}
	
	/**
	 * Get a location by ID.
	 * 
	 * @since 1.4
	 * 
	 * @param int $location_id
	 * @param string $output (optional) one of ARRAY_A | ARRAY_N | OBJECT constants.  Return an 
	 * 		associative array (column => value, ...), a numerically indexed array (0 => value, ...) 
	 * 		or an object ( ->column = value ), respectively.
	 * @return object|array Result or null if not found.
	 */
	public static function get_location( $location_id, $output = OBJECT ) {
		global $wpdb;

		$location = wp_cache_get( $location_id, 'geo_mashup_locations' );
		if ( !$location ) {
			$location = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}geo_mashup_locations WHERE id = %d", $location_id ) );
			wp_cache_add( $location_id, $location, 'geo_mashup_locations' );
		}
		return self::translate_object( $location, $output );
	}

	/**
	 * Get locations of posts.
	 * 
	 * @since 1.2
	 * @uses GeoMashupDB::get_object_locations()
	 * 
	 * @param string $query_args Same as GeoMashupDB::get_object_locations()
	 * @return array Array of matching rows.
	 */
	public static function get_post_locations( $query_args = '' ) {
		return self::get_object_locations( $query_args );
	}

	/**
	 * Sanitize an array of query arguments.
	 *
	 * @param array $query_args
	 *
	 * @return array
	 */
	public static function sanitize_query_args( $query_args ) {
		array_walk_recursive($query_args, array( __CLASS__, 'sanitize_query_arg' ) );
		return $query_args;
	}

	/**
	 * Sanitize a single query argument.
	 *
	 * @param mixed $value May be modified.
	 * @param string $name
	 */
	public static function sanitize_query_arg( &$value, $name ) {
		switch ($name) {
			case 'minlat':
			case 'maxlat':
			case 'minlng':
			case 'maxlng':
			case 'near_lat':
			case 'near_lng':
			case 'radius_km':
			case 'radius_mi':
				$value = (float) $value;
				break;

			case 'map_cat':
			case 'object_ids':
				$value = preg_replace( '/[^0-9,]', '', $value );
				break;

			case 'map_post_type':
			case 'object_name':
				$value = sanitize_key( $value );
				break;

			case 'limit':
			case 'map_offset':
				$value = (int) $value;
				break;

			case 'suppress_filters':
				$value = (bool) $value;
				break;

			default:
				$value = sanitize_text_field( $value );
		}
	}

	/**
	 * Get locations of objects.
	 *
	 * <code>
	 * $results = GeoMashupDB::get_object_locations( array( 
	 * 	'object_name' => 'user', 
	 * 	'minlat' => 30,
	 * 	'maxlat' => 40, 
	 * 	'minlon' => -106, 
	 * 	'maxlat' => -103 ) 
	 * );
	 * </code>
	 * 
	 * @since 1.3
	 *
	 * @param string $query_args Override default args.
	 * @return array Array of matching rows.
	 */
	public static function get_object_locations( $query_args = '' ) {
		global $wpdb;

		$default_args = array( 
			'minlat' => null, 
			'maxlat' => null, 
			'minlon' => null, 
			'maxlon' => null,
			'radius_km' => null,
			'radius_mi' => null,
			'map_cat' => null,
			'tax_query' => null,
			'map_post_type' => 'any',
			'object_name' => 'post',
			'show_future' => 'false', 
			'suppress_filters' => false,
	 		'limit' => 0,
			'map_offset' => 0,
		);
		$query_args = wp_parse_args( $query_args, $default_args );
		
		// Construct the query 
		$object_name = $query_args['object_name'];
		$object_store = self::object_storage( $object_name );
		if ( empty( $object_store ) ) {
			return null;
		}

		// Giving tables an alias was a mistake, now filters depend on them
		$field_string = "gmlr.object_id, gmlr.geo_date, o.{$object_store['label_column']} as label, gml.*";
		$table_string = "{$wpdb->prefix}geo_mashup_locations gml " . 
			"INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr " .
			$wpdb->prepare( 'ON gmlr.object_name = %s AND gmlr.location_id = gml.id ', $object_name ) .
			"INNER JOIN {$object_store['table']} o ON o.{$object_store['id_column']} = gmlr.object_id";
		$wheres = array( );
		$groupby = '';
		$having = '';

		if ( 'post' == $object_name ) {
			$field_string .= ', o.post_author';
			if ( $query_args['show_future'] == 'true' ) {
				$wheres[] = 'post_status in ( \'publish\',\'future\' )';
			} else if ( $query_args['show_future'] == 'only' ) {
				$wheres[] = 'post_status = \'future\'';
			} else {
				$wheres[] = 'post_status = \'publish\'';
			}

		} else if ( 'comment' == $object_name ) {
			$wheres[] = 'comment_approved = \'1\'';
		}

		$location_args = wp_array_slice_assoc( $query_args, array_keys( GM_Location_Query::get_defaults() ) );
		$location_query = new GM_Location_Query( $location_args );

		// Handle inclusion and exclusion of terms
		if ( ! empty( $query_args['tax_query'] ) and is_array( $query_args['tax_query'] ) )
			$tax_query = $query_args['tax_query'];
		else
			$tax_query = array();

		if ( ! empty( $query_args['map_cat'] ) ) {

			$cats = preg_split( '/[,\s]+/', $query_args['map_cat'] );

			$escaped_include_ids = array();
			$escaped_exclude_ids = array();

			foreach( $cats as $cat ) {

				if ( is_numeric( $cat ) ) {

					if ( $cat < 0 ) {
						$escaped_exclude_ids[] = abs( $cat );
						$escaped_exclude_ids = array_merge( $escaped_exclude_ids, get_term_children( $cat, 'category' ) );
					} else {
						$escaped_include_ids[] = intval( $cat );
						$escaped_include_ids = array_merge( $escaped_include_ids, get_term_children( $cat, 'category' ) );
					}

				} else {

					// Slugs might begin with a dash, so we only include them
					$term = get_term_by( 'slug', $cat, 'category' );
					if ( $term ) {
						$escaped_include_ids[] = $term->term_id;
						$escaped_include_ids = array_merge( $escaped_include_ids, get_term_children( $term->term_id, 'category' ) );
					}
				}
			} 

			if ( ! empty( $escaped_include_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'terms' => $escaped_include_ids,
					'field' => 'term_id',
				);
			}

			if ( ! empty( $escaped_exclude_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'terms' => $escaped_exclude_ids,
					'operator' => 'NOT IN',
					'field' => 'term_id',
				);
			}

		} // end if map_cat exists 

		if ( !empty( $tax_query ) ) {
			$tax_clauses = get_tax_sql( $tax_query, 'o', $object_store['id_column'] );
			$table_string .= $tax_clauses['join'];
			$wheres[] = preg_replace( '/^ AND/', '', $tax_clauses['where'] );
			$groupby = 'GROUP BY gmlr.object_id';
		}

		if ( 'post' == $object_name ) {
			// Handle inclusion and exclusion of post types
			if ( 'any' == $query_args['map_post_type'] ) {
				$include_post_types = '';
				$searchable_post_types = GeoMashup::get_searchable_post_types();

				if ( ! empty( $searchable_post_types ) )
					$include_post_types .= "o.post_type IN ('" . join("', '", array_map( 'esc_sql', $searchable_post_types ) ) . "')";

				$wheres[] = $include_post_types;
			} else {
				if ( !is_array( $query_args['map_post_type'] ) ) 
					$query_args['map_post_type'] = preg_split( '/[,\s]+/', $query_args['map_post_type'] );
				$wheres[] = "o.post_type IN ('" . join("', '", $query_args['map_post_type']) . "')";
			}
		} 

		if ( ! empty( $query_args['object_id'] ) ) {
			$wheres[] = 'gmlr.object_id = ' . esc_sql( $query_args['object_id'] );
		} else if ( ! empty( $query_args['object_ids'] ) ) {
			$wheres[] = 'gmlr.object_id IN ( ' . esc_sql( $query_args['object_ids'] ) .' )';
		}

		if ( ! empty( $query_args['exclude_object_ids'] ) ) 
			$wheres[] = 'gmlr.object_id NOT IN ( ' . esc_sql( $query_args['exclude_object_ids'] ) . ' )';

		list( $l_cols, $l_join, $l_where, $l_groupby ) = $location_query->get_sql( 'o', $object_store['id_column'] );
		$field_string .= $l_cols;
		$table_string .= $l_join;
		if ( empty( $groupby ) and !empty( $l_groupby ) )
			$groupby = 'GROUP BY ' . $l_groupby;
		$where = ( empty( $wheres ) ) ? '' :  'WHERE ' . implode( ' AND ', $wheres ) . $l_where;
		$sort = ( isset( $query_args['sort'] ) ) ? $query_args['sort'] : $object_store['sort'];
		$sort = ( empty( $sort ) ) ? '' : 'ORDER BY ' . esc_sql( $sort );
		$offset = absint( $query_args['map_offset'] );
		$limit = absint( $query_args['limit'] );
		if ( $limit or $offset )
			$limit = " LIMIT {$offset},{$limit}";
		else
			$limit = '';

		if ( ! $query_args['suppress_filters'] ) {
			$field_string = apply_filters( 'geo_mashup_locations_fields', $field_string, $query_args );
			$table_string = apply_filters( 'geo_mashup_locations_join', $table_string, $query_args );
			$where = apply_filters( 'geo_mashup_locations_where', $where, $query_args );
			$sort = apply_filters( 'geo_mashup_locations_orderby', $sort, $query_args );
			$groupby = apply_filters( 'geo_mashup_locations_groupby', $groupby, $query_args );
			$limit = apply_filters( 'geo_mashup_locations_limits', $limit, $query_args );
		}
		
		$query_string = "SELECT $field_string FROM $table_string $where $groupby $having $sort $limit";

		$wpdb->query( $query_string );
		
		return $wpdb->last_result;
	}

	/**
	 * Save an object location in the database.
	 *
	 * Object data is saved in the geo_mashup_location_relationships table, and 
	 * location data is saved in geo_mashup_locations.
	 * 
	 * @since 1.3
	 * @uses do_action() Calls 'geo_mashup_added_object_location' with the object name,
	 *       object id, geo date, and location array
	 * @uses do_action() Calls 'geo_mashup_updated_object_location' with the object name,
	 *       object id, geo date, and location array
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param id $object_id ID of the object to save the location for.
	 * @param id|array $location If an ID, the location is not modified. If an array of valid location fields,
	 * 		the location is added or updated. If empty, the object location is deleted.
	 * @param bool $do_lookups Whether to try looking up missing location information, which can take extra time.
	 * 		Default is to use the saved option.
	 * @param string $geo_date Optional geo date to associate with the object.
	 * @return id|WP_Error The location ID now assiociated with the object.
	 */
	public static function set_object_location( $object_name, $object_id, $location, $do_lookups = null, $geo_date = '' ) {
		global $wpdb;

		if ( is_numeric( $location ) ) {
			$location_id = $location;
		} 

		if ( !isset( $location_id ) ) {
			$location_id = self::set_location( $location, $do_lookups );
			if ( is_wp_error( $location_id ) ) {
				return $location_id;
			}
		}

		if ( !is_numeric( $location_id ) ) {
			self::delete_object_location( $object_name, $object_id );
			return 0;
		}

		if ( empty( $geo_date ) ) {
			$geo_date = date( 'Y-m-d H:i:s' );
		} else {
			$geo_date = date( 'Y-m-d H:i:s', strtotime( $geo_date ) );
		}

		$relationship_table = "{$wpdb->prefix}geo_mashup_location_relationships"; 
		$select_string = "SELECT * FROM $relationship_table " .
			$wpdb->prepare( 'WHERE object_name = %s AND object_id = %d', $object_name, $object_id );

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );

		$set_id = null;
		if ( empty( $db_location ) ) {
			if ( $wpdb->insert( $relationship_table, compact( 'object_name', 'object_id', 'location_id', 'geo_date' ) ) ) {
				$set_id = $location_id;
			} else { 
				return new WP_Error( 'db_insert_error', $wpdb->last_error );
			}
			do_action( 'geo_mashup_added_object_location', $object_name, $object_id, $geo_date, $location_id );
		} else {
			$wpdb->update( $relationship_table, compact( 'location_id', 'geo_date' ), compact( 'object_name', 'object_id' ) );
			if ( $wpdb->last_error ) 
				return new WP_Error( 'db_update_error', $wpdb->last_error );
			$set_id = $location_id;
			do_action( 'geo_mashup_updated_object_location', $object_name, $object_id, $geo_date, $location_id );
		}
		wp_cache_delete( $object_name . '-' . $object_id, 'geo_mashup_object_locations');

		return $set_id;
	}

	/**
	 * Save a location.
	 *
	 * This can create a new location or update an existing one. If a location exists within 5 decimal
	 * places of the passed in coordinates, it will be updated. If the saved_name of a different location
	 * is given, it will be removed from the other location and saved with this one. Blank fields will not
	 * replace existing data.
	 * 
	 * @since 1.2
	 * @uses do_action() Calls 'geo_mashup_added_location' with the location array added
	 * @uses do_action() Calls 'geo_mashup_updated_location' with the location array updated
	 *
	 * @param array $location Location to save, may be modified to match actual saved data.
	 * @param bool $do_lookups Whether to try to look up address information before saving,
	 * 		default is to use the saved option.
	 * @return id|WP_Error The location ID saved, or a WordPress error.
	 */
	public static function set_location( &$location, $do_lookups = null ) {
		global $wpdb, $geo_mashup_options;

		if ( is_null( $do_lookups ) ) {
			$do_lookups = ( $geo_mashup_options->get( 'overall', 'enable_reverse_geocoding' ) == 'true' );
		}

		$input_type = ARRAY_A;
		if ( is_object( $location ) ) {
			$input_type = OBJECT;
			$location = (array) $location;
		}

		// Check for existing location ID
		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$select_string = "SELECT id, saved_name FROM $location_table ";

		// If local has a different floating point format, change it temporarily
		$changed_locale = false;
		if ( (string) 1.1 != '1.1' ) {
			$original_locale = setlocale( constant( 'LC_NUMERIC' ), null );
			setlocale( constant( 'LC_NUMERIC' ), 'en_US' );
			$changed_locale = true;
		}

		if ( isset( $location['id'] ) && is_numeric( $location['id'] ) && $location['id'] > 0 ) {

			$select_string .= $wpdb->prepare( 'WHERE id = %d', $location['id'] );

		} else if ( isset( $location['lat'] ) and is_numeric( $location['lat'] ) and isset( $location['lng'] ) and is_numeric( $location['lng'] ) ) {

			// The database might round these, but let's be explicit and stymy evildoers too
			$location['lat'] = round( $location['lat'], 7 );
			$location['lng'] = round( $location['lng'], 7 );

			// MySql appears to only distinguish 5 decimal places, ~8 feet, in the index
			$delta = 0.00001;
			$select_string .= $wpdb->prepare( 'WHERE lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f', 
				$location['lat'] - $delta, $location['lat'] + $delta, $location['lng'] - $delta, $location['lng'] + $delta );

		} else {
			if ( $changed_locale )
				setlocale( constant( 'LC_NUMERIC' ), $original_locale );
			return new WP_Error( 'invalid_location', __( 'A location must have an ID or coordinates to be saved.', 'GeoMashup' ) );
		}

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );

		$found_saved_name = '';
		if ( ! empty( $db_location ) ) {
			// Use the existing ID
			$location['id'] = $db_location['id']; 
			$found_saved_name = $db_location['saved_name'];
		}

		// Reverse geocode
		if ( $do_lookups ) {
			self::reverse_geocode_location( $location );
		}

		// Don't set blank entries (use the set_null pseudo field)
		foreach ( $location as $name => $value ) {
			if ( !is_numeric( $value ) && empty( $value ) ) {
				unset( $location[$name] );
			}
		}

		// Replace any existing saved name
		if ( ! empty( $location['saved_name'] ) and $found_saved_name != $location['saved_name'] ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $location_table SET saved_name = NULL WHERE saved_name = %s", $location['saved_name'] ) );
		}

		self::truncate_location_fields( $location );

		$set_id = null;

		if ( empty( $location['id'] ) ) {

			// Create a new location
			if ( $wpdb->insert( $location_table, $location ) ) {
				$set_id = $wpdb->insert_id;
			} else {
				if ( $changed_locale )
					setlocale( constant( 'LC_NUMERIC' ), $original_locale );
				return new WP_Error( 'db_insert_error', $wpdb->last_error );
			}
			do_action( 'geo_mashup_added_location', $location );

		} else {

			// Update existing location, except for coordinates
			$tmp_lat = $location['lat']; 
			$tmp_lng = $location['lng']; 
			unset( $location['lat'] );
			unset( $location['lng'] );
			if ( !empty ( $location ) ) {

				if ( isset( $location['set_null'] ) ) {
					// WP doesn't yet have a mechanism for setting NULLs, https://core.trac.wordpress.org/ticket/15158
					$null_fields = $location['set_null'];
					if ( !is_array( $null_fields ) ) 
						$null_fields = explode( ',', $null_fields );
					$null_fields = array_map( create_function( '$field', 'return $field . "=NULL";' ), $null_fields );
					$wpdb->query( $wpdb->prepare( "UPDATE $location_table SET " . implode( ',', $null_fields) . " WHERE id=%d", $db_location['id'] ) );
					unset( $location['set_null'] );
				}

				$wpdb->update( $location_table, $location, array( 'id' => $db_location['id'] ) );
				if ( $wpdb->last_error ) {
					if ( $changed_locale )
						setlocale( constant( 'LC_NUMERIC' ), $original_locale );
					return new WP_Error( 'db_update_error', $wpdb->last_error );
				}
			}
			$set_id = $db_location['id'];
			do_action( 'geo_mashup_updated_location', $location );
			$location['lat'] = $tmp_lat;
			$location['lng'] = $tmp_lng;

		}

		if ( $changed_locale )
			setlocale( constant( 'LC_NUMERIC' ), $original_locale );

		wp_cache_delete( $set_id, 'geo_mashup_locations' );

		if( OBJECT === $input_type )
			$location = (object) $location;

		return $set_id;
	}

	/**
	 * Cut location fields to maximum length.
	 * @since 1.8.5
	 * @param array $location
	 */
	static private function truncate_location_fields( &$location ) {
		if ( isset( $location['saved_name'] ) )
			$location['saved_name'] = mb_substr( $location['saved_name'], 0, 100 );
		if ( isset( $location['country_code'] ) )
			$location['country_code'] = mb_substr( $location['country_code'], 0, 2 );
		if ( isset( $location['admin_code'] ) )
			$location['admin_code'] = mb_substr( $location['admin_code'], 0, 20 );
		if ( isset( $location['sub_admin_code'] ) )
			$location['sub_admin_code'] = mb_substr( $location['sub_admin_code'], 0, 80 );
	}

	/**
	 * Delete an object location or locations.
	 * 
	 * This removes the association of an object with a location, but does NOT
	 * delete the location.
	 * 
	 * @since 1.3
	 * @uses do_action() Calls 'geo_mashup_deleted_object_location' with the object location
	 * 		object that was deleted.
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param id|array $object_ids Object ID or array of IDs to remove the locations of.
	 * @return int|WP_Error Rows affected or WordPress error.
	 */
	public static function delete_object_location( $object_name, $object_ids ) {
		global $wpdb;

		$object_ids = ( is_array( $object_ids ) ? $object_ids : array( $object_ids ) );
		$rows_affected = 0;
		foreach( $object_ids as $object_id ) {
			$object_location = self::get_object_location( $object_name, $object_id );
			if ( $object_location ) {
				$delete_string = "DELETE FROM {$wpdb->prefix}geo_mashup_location_relationships " .
					$wpdb->prepare( 'WHERE object_name = %s AND object_id = %d', $object_name, $object_id );
				$rows_affected += $wpdb->query( $delete_string );
				if ( $wpdb->last_error )
					return new WP_Error( 'delete_object_location_error', $wpdb->last_error );

				wp_cache_delete( $object_name . '-' . $object_id, 'geo_mashup_object_locations' );

				do_action( 'geo_mashup_deleted_object_location', $object_location );
			}
		}

		return $rows_affected;
	}

	/**
	 * Delete a location or locations.
	 *
	 * @since 1.2
	 * @uses do_action() Calls 'geo_mashup_deleted_location' with the location object deleted.
	 * 
	 * @param id|array $ids Location ID or array of IDs to delete.
	 * @return int|WP_Error Rows affected or Wordpress error.
	 */
	public static function delete_location( $ids ) {
		global $wpdb;
		$ids = ( is_array( $ids ) ? $ids : array( $ids ) );
		$rows_affected = 0;
		foreach( $ids as $id ) {
			$location = self::get_location( $id );
			if ( $location ) {
				$rows_affected += $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}geo_mashup_locations WHERE id = %d", $id ) );
				if ( $wpdb->last_error )
					return new WP_Error( 'delete_location_error', $wpdb->last_error );
				wp_cache_delete( $id, 'geo_mashup_locations' );
				do_action( 'geo_mashup_deleted_location', $location );
			}
		}
		return $rows_affected;
	}

	/**
	 * Get locations with saved names.
	 *
	 * @since 1.2
	 *
	 * @return array|WP_Error Array of location rows or WP_Error.
	 */
	public static function get_saved_locations() {
		global $wpdb;

		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$wpdb->query( "SELECT * FROM $location_table WHERE saved_name IS NOT NULL ORDER BY saved_name ASC" );
		if ( $wpdb->last_error ) 
			return new WP_Error( 'saved_locations_error', $wpdb->last_error );

		return $wpdb->last_result;
	}

	/**
	 * Get the number of located posts in a category.
	 * 
	 * @since 1.2
	 *
	 * @param id $category_id 
	 * @return int
	 */
	public static function category_located_post_count( $category_id ) {
		global $wpdb;

		$select_string = "SELECT count(*) FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID 
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = p.ID AND gmlr.object_name = 'post'
			WHERE tt.term_id = " . esc_sql( $category_id ) ."
			AND p.post_status='publish'";
		return $wpdb->get_var( $select_string );
	}

	/**
	 * Get categories that contain located objects.
	 *
	 * Not sufficient - probably want parent categories.
	 *
	 * @return array Located category id, name, slug, description, and parent id
	 */
	private static function get_located_categories() {
		global $wpdb;

		$select_string = "SELECT DISTINCT t.term_id, t.name, t.slug, tt.description, tt.parent
			FROM {$wpdb->prefix}geo_mashup_location_relationships gmlr
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = gmlr.object_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			WHERE tt.taxonomy='category'
			ORDER BY t.slug ASC";
		return $wpdb->get_results( $select_string );
	}

	/**
	 * Get multiple comments.
	 *
	 * What is the WordPress way? Expect deprecation.
	 * 
	 * @return array Comments.
	 */
	public static function get_comment_in( $args ) {
		global $wpdb;

		$default_args = array( 'comment__in' => '' );
		$args = wp_parse_args( $args, $default_args );
		if ( is_array( $args['comment__in'] ) ) {
			$comment_ids = implode( ',', $args['comment__in'] );
		} else {
			$comment_ids = ( isset( $args['comment__in'] ) ) ? $args['comment__in'] : '0';
		}
		$select_string = "SELECT * FROM $wpdb->comments WHERE comment_ID IN (" .
			implode( ',', wp_parse_id_list( $args['comment__in'] ) ) . ') ORDER BY comment_date_gmt DESC';
		return $wpdb->get_results( $select_string );
	}

	/**
	 * Get multiple users.
	 *
	 * Now just a wrapper for get_users().
	 * 
	 * @return array Users.
	 */
	public static function get_user_in( $args ) {
		global $wpdb;

		if ( isset( $args['user__in'] ) ) {
			$args['include'] = $args['user__in'];
			unset( $args['user__in'] );
		}
		return get_users( $args );
	}

	/**
	 * When a post is deleted, remove location relationships for it.
	 *
	 * delete_post {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 */
	public static function delete_post( $id ) {
		return self::delete_object_location( 'post', $id );
	}

	/**
	 * When a comment is deleted, remove location relationships for it.
	 *
	 * delete_comment {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 */
	public static function delete_comment( $id ) {
		return self::delete_object_location( 'comment', $id );
	}

	/**
	 * When a user is deleted, remove location relationships for it.
	 *
	 * delete_user {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 */
	public static function delete_user( $id ) {
		return self::delete_object_location( 'user', $id );
	}

	/**
	 * Geo Mashup action to echo post meta keys that match a jQuery suggest query.
	 *
	 * @since 1.4
	 */
	public static function post_meta_key_suggest() {
		global $wpdb;
		if ( isset( $_GET['q'] ) ) {
			$limit = (int) apply_filters( 'postmeta_form_limit', 30 );
			$terms = explode( ',', $_GET['q'] );
			$stub = trim( array_pop( $terms ) );
			$like = esc_sql( $stub );
			$keys = $wpdb->get_col( "
				SELECT meta_key
				FROM $wpdb->postmeta
				GROUP BY meta_key
				HAVING meta_key LIKE '$like%'
				ORDER BY meta_key
				LIMIT $limit" );
			foreach( $keys as $key ) {
				echo "$key\n";
			}
		}
		exit;
	}
}
