<?php 
/**
 * Geo Mashup Data Access
 *
 * @package GeoMashup
 */

// init action
add_action( 'init', array( 'GeoMashupDB', 'init' ) );

/**
 * Static class to provide a namespace for Geo Mashup data functions.
 *
 * @since 1.2
 * @package GeoMashup
 * @access public
 * @static
 */
class GeoMashupDB {

	/**
	 * At init time set up data-related WordPress hooks.
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 * @usedby do_action() init
	 */
	function init() {
		// Sync deletions
		add_action( 'delete_post', array( 'GeoMashupDB', 'delete_post' ) );
		add_action( 'delete_comment', array( 'GeoMashupDB', 'delete_comment' ) );
		add_action( 'delete_user', array( 'GeoMashupDB', 'delete_user' ) );

		// Action to update Geo Mashup post location when custom fields are added
		add_action( 'added_post_meta', array( 'GeoMashupDB', 'added_post_meta' ), 10, 4 );
		// AJAX calls use a slightly different hook - triksy!
		add_action( 'added_postmeta', array( 'GeoMashupDB', 'added_post_meta' ), 10, 4 );
	}
	
	/**
	 * WordPress action to update Geo Mashup post location when geodata custom fields are updated.
	 *
	 * @since 1.4
	 * @private
	 * @static
	 * @see http://codex.wordpress.org/Geodata
	 * @usedby do_action() added_post_meta and added_postmeta
	 */
	function added_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) {

		// Do nothing if meta_key is not a known location field
		$location_keys = array( 'geo_latitude', 'geo_longitude', 'geo_lat_lng' );
		if ( ! in_array( $meta_key, $location_keys ) ) {
			return;
		}

		// Do nothing if Geo Mashup location already exists
		$existing_location = GeoMashupDB::get_object_location( 'post', $post_id );
		if ( ! empty( $existing_location ) ) {
			return;
		}

		$location = array();

		// Do nothing unless both latitude and longitude exist for the post
		if ( 'geo_lat_lng' == $meta_key ) {

			$lat_lng = preg_split( '/\s*[, ]\s*/', trim( $meta_value ) );
			if ( 2 != count( $lat_lng ) ) {
				return; 
			}
			$location['lat'] = $lat_lng[0];
			$location['lng'] = $lat_lng[1];

		} else if ( 'geo_latitude' == $meta_key ) {

			$location['lat'] = $meta_value;
			$lngs = get_post_custom_values( 'geo_longitude', $post_id );
			if ( empty( $lngs ) ) {
				return;
			}
			$location['lng'] = $lngs[0];

		} else if ( 'geo_longitude' == $meta_key ) {

			$location['lng'] = $meta_value;
			$lats = get_post_custom_values( 'geo_latitude', $post_id );
			if ( empty( $lats ) ) {
				return;
			}
			$location['lat'] = $lats[0];

		}

		// Save the location, attempt reverse geocoding
		GeoMashupDB::set_object_location( 'post', $post_id, $location, null );
	}

	/**
	 * Get or set the installed database version.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 * 
	 * @param string $new_version If provided, overwrites any currently installed version.
	 * @return string The installed database version.
	 */
	function installed_version( $new_version = null ) {
		static $version = null;

		if ( !is_null( $new_version ) ) {
			$version = $new_version;
			update_option( 'geo_mashup_db_version', $version );
		}
		if ( is_null( $version ) ) {
			$version = get_option( 'geo_mashup_db_version' );
		}
		return $version;
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
	 * Would add the necessary information for a custom table of foo objects.
	 *
	 * @since 1.3
	 * @access public
	 * @static
	 * 
	 * @param string $object_name A type of object to be stored, default is 'post', 'user', and 'comment'.
	 * @param array $new_storage If provided, adds or replaces the storage information for the object name.
	 * @return array|bool The storage information array, or false if not found.
	 */
	function object_storage( $object_name, $new_storage = null ) {
		global $wpdb;
		static $objects = null;
		
		if ( is_null( $objects ) ) {
			$objects = array( 
				'post' => array( 
					'table' => $wpdb->posts, 
					'id_column' => 'ID', 
					'label_column' => 'post_title', 
					'sort' => 'post_status ASC, geo_date DESC' ),
				'user' => array( 
					'table' => $wpdb->users, 
					'id_column' => 'ID', 
					'label_column' => 'display_name',
			 		'sort' => 'display_name ASC' ),
				'comment' => array( 
					'table' => $wpdb->comments, 
					'id_column' => 'comment_ID', 
					'label_column' => 'comment_author',
			 		'sort' => 'comment_date DESC'	) 
			);
		}

		if ( !empty( $new_storage ) ) {
			$objects[$object_name] = $new_storage;
		} 
		return ( isset( $objects[$object_name] ) ) ? $objects[$object_name] : false;
	}

	/**
	 * Get or set the current lookup status.
	 * 
	 * Tracks the status of the most recent web service lookup.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @param string $new_status If provided, overwrites any current status.
	 * @return string The current lookup status.
	 */
	function lookup_status( $new_status = null ) {
		static $status = '0';

		if ( $new_status ) {
			$status = $new_status;
		}
		return $status;
	}

	/**
	 * Toggle joining of WordPress queries with Geo Mashup tables.
	 * 
	 * Use the public wrapper GeoMashup::join_post_queries()
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @param bool $new_value If provided, replaces the current active state.
	 * @return bool The current state.
	 */
	function join_post_queries( $new_value = null) {
		static $active = null;

		if ( is_bool( $new_value ) ) {
			if ( $new_value ) {

				add_filter( 'query_vars', array( 'GeoMashupDB', 'query_vars' ) );
				add_filter( 'posts_fields', array( 'GeoMashupDB', 'posts_fields' ) );
				add_filter( 'posts_join', array( 'GeoMashupDB', 'posts_join' ) );
				add_filter( 'posts_where', array( 'GeoMashupDB', 'posts_where' ) );
				add_filter( 'posts_orderby', array( 'GeoMashupDB', 'posts_orderby' ) );
				add_action( 'parse_query', array( 'GeoMashupDB', 'parse_query' ) );

			} else if ( ! is_null( $active ) ) {

				remove_filter( 'query_vars', array( 'GeoMashupDB', 'query_vars' ) );
				remove_filter( 'posts_fields', array( 'GeoMashupDB', 'posts_fields' ) );
				remove_filter( 'posts_join', array( 'GeoMashupDB', 'posts_join' ) );
				remove_filter( 'posts_where', array( 'GeoMashupDB', 'posts_where' ) );
				remove_filter( 'posts_orderby', array( 'GeoMashupDB', 'posts_orderby' ) );
				remove_action( 'parse_query', array( 'GeoMashupDB', 'parse_query' ) );
			}
 
			$active = $new_value;
		}
		return $active;
	}

	/**
	 * Add Geo Mashup public query variables.
	 *
	 * query_vars {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by Wordpress.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function query_vars( $public_query_vars ) {
		$public_query_vars[] = 'geo_mashup_date';
		$public_query_vars[] = 'geo_mashup_saved_name';
		$public_query_vars[] = 'geo_mashup_country_code';
		$public_query_vars[] = 'geo_mashup_postal_code';
		$public_query_vars[] = 'geo_mashup_admin_code';
		$public_query_vars[] = 'geo_mashup_locality';
		return $public_query_vars;
	}

	/**
	 * Set or get custom orderby field.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 * 
	 * @param string $new_value Replace any current value.
	 * @return string Current orderby field.
	 */
	function query_orderby( $new_value = null ) {
		static $orderby = null;

		if ( !is_null( $new_value ) ) {
			$orderby = $new_value;
		}
		return $orderby;
	}

	/**
	 * Capture custom orderby field before it is removed.
	 *
	 * parse_query {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function parse_query( $query ) {
		global $wpdb;

		if ( empty( $query->query_vars['orderby'] ) ) 
			return;

		// Check for geo mashup fields in the orderby before they are removed as invalid
		switch ( $query->query_vars['orderby'] ) {
			case 'geo_mashup_date':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_location_relationships.geo_date' );
				break;

			case 'geo_mashup_locality':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_locations.locality_name' );
				break;

			case 'geo_mashup_saved_name':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_locations.saved_name' );
				break;

			case 'geo_mashup_country_code':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_locations.country_code' );
				break;

			case 'geo_mashup_admin_code':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_locations.admin_code' );
				break;

			case 'geo_mashup_postal_code':
				GeoMashupDB::query_orderby( $wpdb->prefix . 'geo_mashup_locations.postal_code' );
				break;
		}
	}

	/**
	 * Add Geo Mashup fields to WordPress post queries.
	 *
	 * posts_fields {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function posts_fields( $fields ) {
		global $wpdb;

		$fields .= ',' . $wpdb->prefix . 'geo_mashup_location_relationships.geo_date' .
			',' . $wpdb->prefix . 'geo_mashup_locations.*';

		return $fields;
	}

	/**
	 * Join Geo Mashup tables to WordPress post queries.
	 * 
	 * posts_join {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function posts_join( $join ) {
		global $wpdb;

		$gmlr = $wpdb->prefix . 'geo_mashup_location_relationships';
		$gml = $wpdb->prefix . 'geo_mashup_locations';
		$join .= " INNER JOIN $gmlr ON ($gmlr.object_name = 'post' AND $gmlr.object_id = $wpdb->posts.ID)" .
			" INNER JOIN $gml ON ($gml.id = $gmlr.location_id) ";

		return $join;
	}

	/**
	 * Incorporate geo mashup query vars in WordPress post queries.
	 *
	 * posts_where {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function posts_where( $where ) {
		global $wpdb;

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
			$where .= $wpdb->prepare( " AND $gml.locality = %s ", $locality );
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
	 * Replace a WordPress post query orderby with a requested Geo Mashup field.
	 *
	 * posts_orderby {@link http://codex.wordpress.org/Plugin_API/Filter_Reference filter}
	 * called by WordPress.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 */
	function posts_orderby( $orderby ) {
		global $wpdb;

		if ( GeoMashupDB::query_orderby() ) {

			// Now our "invalid" value has been replaced by post_date, we change it back
			$orderby = str_replace( "$wpdb->posts.post_date", GeoMashupDB::query_orderby(), $orderby );

			// Reset for subsequent queries
			GeoMashupDB::query_orderby( false );
		}
		return $orderby;
	}

	/**
	 * Append to the activation log.
	 * 
	 * Add a message and optionally write the activation log.
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param string $message The message to append.
	 * @param boolean $write Whether to save the log.
	 * @return string The current log.
	 */
	function activation_log( $message = null, $write = false ) {
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
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function install( ) {
		global $wpdb;

		GeoMashupDB::activation_log( date( 'r' ) . ' ' . __( 'Activating Geo Mashup', 'GeoMashup' ) );
		$location_table_name = $wpdb->prefix . 'geo_mashup_locations';
		$relationships_table_name = $wpdb->prefix . 'geo_mashup_location_relationships';
		$administrative_names_table_name = $wpdb->prefix . 'geo_mashup_administrative_names';
		if ( GeoMashupDB::installed_version( ) != GEO_MASHUP_DB_VERSION ) {
			$sql = "
				CREATE TABLE $location_table_name (
					id MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT,
					lat FLOAT( 11,7 ) NOT NULL,
					lng FLOAT( 11,7 ) NOT NULL,
					address TINYTEXT NULL,
					saved_name VARCHAR( 100 ) NULL,
					geoname TINYTEXT NULL, 
					postal_code TINYTEXT NULL,
					country_code VARCHAR( 2 ) NULL,
					admin_code VARCHAR( 20 ) NULL,
					sub_admin_code VARCHAR( 80 ) NULL,
					locality_name TINYTEXT NULL,
					PRIMARY KEY  ( id ),
					UNIQUE KEY saved_name ( saved_name ),
					UNIQUE KEY latlng ( lat, lng ),
					KEY lat ( lat ),
					KEY lng ( lng )
				);
				CREATE TABLE $relationships_table_name (
					object_name VARCHAR( 80 ) NOT NULL,
					object_id BIGINT( 20 ) NOT NULL,
					location_id MEDIUMINT( 9 ) NOT NULL,
					geo_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY  ( object_name, object_id, location_id ),
					KEY object_name ( object_name, object_id ),
					KEY object_date_key ( object_name, geo_date )
				);
				CREATE TABLE $administrative_names_table_name (
					country_code VARCHAR( 2 ) NOT NULL,
					admin_code VARCHAR( 20 ) NOT NULL,
					isolanguage VARCHAR( 7 ) NOT NULL,
					geoname_id MEDIUMINT( 9 ) NULL,
					name VARCHAR( 200 ) NOT NULL,
					PRIMARY KEY admin_id ( country_code, admin_code, isolanguage )
				);";
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
				GeoMashupDB::activation_log( $errors, true );
				die( $errors );
			} else {
				if ( GeoMashupDB::convert_prior_locations( ) ) {
					GeoMashupDB::installed_version( GEO_MASHUP_DB_VERSION );
				}
			}
			$wpdb->show_errors( $old_show_errors );
		}
		if ( GeoMashupDB::installed_version( ) == GEO_MASHUP_DB_VERSION ) {
			GeoMashupDB::duplicate_geodata();
			GeoMashupDB::activation_log( __( 'Geo Mashup database is up to date.', 'GeoMashup' ), true );
			return true;
		} else {
			GeoMashupDB::activation_log( __( 'Geo Mashup database upgrade failed.', 'GeoMashup' ), true );
			return false;
		}
	}

	/**
	 * Parse a value from small, flat XML data.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 *
	 * @param string $tag_name The tag containing the desired value.
	 * @param string $document The XML.
	 * @return string|null The contents of first tag found, or null.
	 */
	function get_simple_tag_content( $tag_name, $document ) {
		$content = null;
		$pattern = '/<' . $tag_name . '>(.*?)<\/' . $tag_name . '>/is';
		if ( preg_match( $pattern, $document, $match ) ) {
			$content .= $match[1];
		}
		return $content;
	}

	/**
	 * Try to get a language-sensitive place administrative name. 
	 *
	 * First look in the names cached in the database, then query geonames.org for it. 
	 * If a name can't be found for the requested language, a default name is returned, 
	 * usually in the local language. If nothing can be found, returns NULL.
	 * 
	 * @since 1.2
	 * @access public
	 * @static
	 *
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string|null Place name in the appropriate language, or if not available in the default language, or null.
	 */
	function get_administrative_name( $country_code, $admin_code = null, $language = null ) {
		$name = GeoMashupDB::get_cached_administrative_name( $country_code, $admin_code, $language );
		if ( empty( $name ) ) {
			$name = GeoMashupDB::get_geonames_administrative_name( $country_code, $admin_code, $language );
		}
		return $name;
	}


	/** 
	 * Look in the database for a cached administrative name. 
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 *
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string|null Place name or NULL.
	 */
	function get_cached_administrative_name( $country_code, $admin_code = '', $language = '' ) {
		global $wpdb;

		$language = GeoMashupDB::primary_language_code( $language );
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
	 * @access private 
	 * @static
	 *
	 * @param string $language Local or language code string, NULL for blog locale.
	 * @return string Two (rarely three?) character language code.
	 */
	function primary_language_code( $language = null ) {
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
	 * Use the Geonames web service to look up an administrative name.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 * 
	 * @param string $country_code 
	 * @param string $admin_code Admin area name to look up. If empty, look up the country name.
	 * @param string $language 
	 * @return string|null Name or null.
	 */
	function get_geonames_administrative_name( $country_code, $admin_code = '', $language = '' ) {
		$language = GeoMashupDB::primary_language_code( $language );

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		if ( empty( $admin_code ) ) {
			// Look up a country name
			$country_info_url = 'http://ws.geonames.org/countryInfoJSON?country=' . urlencode( $country_code ) .
				'&lang=' . urlencode( $language );
			$http = new WP_Http();
			$country_info_response = $http->get( $country_info_url, array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $country_info_response ) ) {
				GeoMashupDB::lookup_status( $country_info_response->get_error_code() );
				return null;
			}
			$status = GeoMashupDB::lookup_status( $country_info_response['response']['code'] );
			if ( '200' != $status ) 
				return null;
			$data = json_decode( $country_info_response['body'] );
			$country_name = $data->geonames[0]->countryName;
			$country_id = $data->geonames[0]->geonameId;
			if ( !empty( $country_name ) ) {
				GeoMashupDB::cache_administrative_name( $country_code, '', $language, $country_name, $country_id );
			}
			return $country_name;
		} else {
			// Look up an admin name
			$admin_search_url = 'http://ws.geonames.org/searchJSON?maxRows=1&style=SHORT&featureCode=ADM1&country=' .
				urlencode( $country_code ) . '&adminCode1=' . urlencode( $admin_code );
			$http = new WP_Http();
			$admin_search_response = $http->get( $admin_search_url, array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $admin_search_response ) ) {
				GeoMashupDB::lookup_status( $admin_search_response->get_error_code() );
				return null;
			}
			$status = GeoMashupDB::lookup_status( $admin_search_response['response']['code'] );
			if ( '200' != $status ) 
				return null;
			$data = json_decode( $admin_search_response['body'] );
			if ( empty( $data ) or 0 == $data->totalResultsCount ) 
				return null;
			$admin_name = $data->geonames[0]->name;
			$admin_id = $data->geonames[0]->geonameId;
			if ( !empty( $admin_name ) ) {
				GeoMashupDB::cache_administrative_name( $country_code, $admin_code, $language, $admin_name, $admin_id );
			}
			return $admin_name;
		}
		return null;
	}

	/**
	 * Use the Google geocoding service to complete a location.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param mixed $query The search string.
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function google_geocode( $query, &$location, $language = '' ) {

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		$language = GeoMashupDB::primary_language_code( $language );

		$google_geocode_url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address=' .
			urlencode( utf8_encode( $query ) ) . '&language=' . $language;

		// Remove whitespace from lat/lng queries - (or restrict to reverse geocoding?)
		if ( preg_match( '/^[\s\d\.,-]*$/', $query ) ) {
			$query = preg_replace( '/\s*/', '', $query );
		}

		$http = new WP_Http();
		$response = $http->get( $google_geocode_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $response ) ) {
			return GeoMashupDB::lookup_status( $response->get_error_code() );
		}

		$status = GeoMashupDB::lookup_status( $response['response']['code'] );
		if ( '200' != $status ) {
			return $status;
		}
		
		$data = json_decode( $response['body'] );
		if ( 'OK' != $data->status ) {
			// status of ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED, INVALID_REQUEST, etc.
			return GeoMashupDB::lookup_status( $data->status );
		}

		$first_result = $data->results[0];
		if ( empty( $location['lat'] ) or empty( $location['lng'] ) ) {
			$location['lat'] = $first_result->geometry->location->lat;
			$location['lng'] = $first_result->geometry->location->lng;
		}
		if ( empty( $location['address'] ) ) {
			$location['address'] = $first_result->formatted_address;
		}
		if ( ! empty( $first_result->address_components ) ) {
			foreach( $first_result->address_components as $component ) {
				if ( empty( $location['country_code'] ) and in_array( 'country', $component->types ) ) {
					$location['country_code'] = $component->short_name;
				}
				if ( empty( $location['admin_code'] ) and in_array( 'administrative_area_level_1', $component->types ) ) {
					$location['admin_code'] = $component->short_name;
				}
				if ( empty( $location['sub_admin_code'] ) and in_array( 'administrative_area_level_2', $component->types ) ) {
					$location['sub_admin_code'] = $component->short_name;
				}
				if ( empty( $location['postal_code'] ) and in_array( 'postal_code', $component->types ) ) {
					$location['postal_code'] = $component->short_name;
				}
				if ( empty( $location['locality_name'] ) and in_array( 'locality', $component->types ) ) {
					$location['locality_name'] = $component->short_name;
				}
			}
		}

		return $status;
	}

	/**
	 * Use the Geonames search service to complete a location.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param mixed $query The search string.
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function geonames_geocode( $query, &$location, $language = '' ) {

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		$language = GeoMashupDB::primary_language_code( $language );

		$url = 'http://ws.geonames.org/searchJSON?username=cyberhobo&maxRows=1&q=' .
			urlencode( utf8_encode( $query ) ) . '&lang=' . $language;

		$http = new WP_Http();
		$response = $http->get( $url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $response ) ) {
			return GeoMashupDB::lookup_status( $response->get_error_code() );
		}

		$status = GeoMashupDB::lookup_status( $response['response']['code'] );
		if ( '200' != $status ) {
			return $status;
		}
		
		$data = json_decode( $response['body'] );
		if ( empty( $data ) or 0 == $data->totalResultsCount ) {
			return GeoMashupDB::lookup_status( '404' );
		}

		$first_result = $data->geonames[0];
		if ( empty( $location['lat'] ) or empty( $location['lng'] ) ) {
			$location['lat'] = $first_result->lat;
			$location['lng'] = $first_result->lng;
		}
		if ( empty( $location['country_code'] ) ) {
			$location['country_code'] = $first_result->countryCode;
		}
		if ( empty( $location['admin_code'] ) ) {
			$location['admin_code'] = $first_result->countryCode;
		}

		return $status;
	}


	/**
	 * Use the nominatum geocoding service to complete a location.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param mixed $query The search string.
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function nominatim_geocode( $query, &$location, $language = '' ) {

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		$language = GeoMashupDB::primary_language_code( $language );

		$geocode_url = 'http://nominatim.openstreetmap.org/search?format=json&polygon=0&addressdetails=1&q=' .
			urlencode( utf8_encode( $query ) ) . '&accept-language=' . $language . 
			'&email=' . urlencode( get_option( 'admin_email' ) );

		$http = new WP_Http();
		$response = $http->get( $geocode_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $response ) ) {
			return GeoMashupDB::lookup_status( $response->get_error_code() );
		}

		$status = GeoMashupDB::lookup_status( $response['response']['code'] );
		if ( '200' != $status ) {
			return $status;
		}
		
		$data = json_decode( $response['body'] );
		if ( empty( $data ) ) {
			return GeoMashupDB::lookup_status( '404' );
		}

		$first_result = $data[0];
		if ( empty( $location['lat'] ) or empty( $location['lng'] ) ) {
			$location['lat'] = $first_result->lat;
			$location['lng'] = $first_result->lon;
		}
		if ( empty( $location['address'] ) ) {
			$location['address'] = $first_result->display_name;
		}
		if ( ! empty( $first_result->address ) ) {
			if ( empty( $location['country_code'] ) and ! empty( $first_result->address->country_code ) ) {
				$location['country_code'] = strtoupper( $first_result->address->country_code );
			}
			// Returns admin name in address->state, but no code
			if ( empty( $location['sub_admin_code'] ) and ! empty( $first_result->address->county ) ) {
				$location['sub_admin_code'] = $first_result->address->county;
			}
			if ( empty( $location['postal_code'] ) and ! empty( $first_result->address->postcode ) ) {
				$location['postal_code'] = $first_result->address->postcode;
			}
			if ( empty( $location['locality_name'] ) and ! empty( $first_result->address->city ) ) {
				$location['locality_name'] = $first_result->address->city;
			}
		}

		return $status;
	}

	/**
	 * Use the Geonames web service to look up missing location fields from coordinates.
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 * 
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function geonames_reverse_geocode_location( &$location, $language = '' ) {

		if ( empty( $location['lat'] ) or empty( $location['lng'] ) ) {
			// Bad Request
			return GeoMashupDB::lookup_status( '400' );
		}

		$language = GeoMashupDB::primary_language_code( $language );

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		$status = null;
		// GeoNames is our standard for admin_code and country_code, so we'll replace 
		// existing values to be safe
		//if ( empty( $location['admin_code'] ) or strpos( $location['admin_code'], ' ' ) !== false or strlen( $location['admin_code'] ) > 20 or empty( $location['country_code'] ) or strlen( $location['country_code'] ) != 2 ) {
			$url = 'http://ws.geonames.org/countrySubdivisionJSON?style=FULL&lat=' . urlencode( $location['lat'] ) .
				'&lng=' . urlencode( $location['lng'] );
			$http = new WP_Http();
			$response = $http->get( $url, array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $response ) ) {
				return GeoMashupDB::lookup_status( $response->get_error_code() );
			}
			$status = GeoMashupDB::lookup_status( $response['response']['code'] );
			$data = json_decode( $response['body'] );
			if ( empty( $data ) ) {
				return GeoMashupDB::lookup_status( '404' );
			}
			$location['country_code'] = $data->countryCode;
			$location['admin_code'] = $data->adminCode1;
		//}
		// Look up more things, postal code, locality or address in US
		if ( 'US' == $location['country_code'] and GeoMashupDB::are_any_location_fields_empty( $location, array( 'address', 'locality_name', 'postal_code' ) ) ) {
			$url = 'http://ws.geonames.org/findNearestAddressJSON?style=FULL&lat=' . urlencode( $location['lat'] ) .
				'&lng=' . urlencode( $location['lng'] );
			$http = new WP_Http();
			$response = $http->get( $url, array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $response ) ) {
				return GeoMashupDB::lookup_status( $response->get_error_code() );
			}
			$status = GeoMashupDB::lookup_status( $response['response']['code'] );
			$data = json_decode( $response['body'] );
			if ( ! empty( $data->address ) ) {
				$address_parts = array();
				if ( ! empty( $data->address->street ) ) {
					$address_parts[] = ( empty( $data->address->streetNumber ) ? '' : $data->address->streetNumber . ' ' ) . 
						$data->address->street;
				}
				if ( ! empty( $data->address->adminName1 ) ) {
					$address_parts[] = $data->address->adminName1; 
				}
				if ( ! empty( $data->address->postalcode ) ) {
					$address_parts[] = $data->address->postalcode; 
				}
				$address_parts[] = $data->address->countryCode;
				$location['address'] = implode( ', ', $address_parts );
				$location['locality_name'] = $data->address->placename;
				$location['postal_code'] = $data->address->postalcode;
			}
		} 
		if (  GeoMashupDB::are_any_location_fields_empty( $location, array( 'address', 'locality_name', 'postal_code' ) ) ) {
			// Just look for a postal code
			$url = 'http://ws.geonames.org/findNearbyPostalCodesJSON?maxRows=1&lat=' . urlencode( $location['lat'] ) .
				'&lng=' . urlencode( $location['lng'] );
			$http = new WP_Http();
			$response = $http->get( $url, array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $response ) ) {
				return GeoMashupDB::lookup_status( $response->get_error_code() );
			}
			$status = GeoMashupDB::lookup_status( $response['response']['code'] );
			$data = json_decode( $response['body'] );
			if ( ! empty( $data->postalCodes ) ) {
				$postal_code = $data->postalCodes[0];
				$location['address'] = $postal_code->placeName . ', ' . 
					$postal_code->adminName1 . ', ' . $postal_code->postalCode . ', ' .
					$postal_code->countryCode;
				$location['locality_name'] = $postal_code->placeName;
				$location['postal_code'] = $postal_code->postalCode;
			}
		}
		return $status;
	}

	/**
	 * Use the nominatum geocoding to complete a location based on existing coordinates.
	 * 
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function nominatim_reverse_geocode_location( &$location, $language = '' ) {

		if ( empty( $location['lat'] ) or empty( $location['lng'] ) ) {
			// Bad Request
			return GeoMashupDB::lookup_status( '400' );
		}

		$language = GeoMashupDB::primary_language_code( $language );

		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );

		$geocode_url = 'http://nominatim.openstreetmap.org/reverse?format=json&zoom=18&address_details=1&lat=' . 
			$location['lat'] . '&lon=' . $location['lng'] . 
			'&email=' . urlencode( get_option( 'admin_email' ) );

		$http = new WP_Http();
		$response = $http->get( $geocode_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $response ) ) {
			return GeoMashupDB::lookup_status( $response->get_error_code() );
		}

		$status = GeoMashupDB::lookup_status( $response['response']['code'] );
		if ( '200' != $status ) {
			return $status;
		}
		
		$data = json_decode( $response['body'] );
		if ( empty( $data ) ) {
			return GeoMashupDB::lookup_status( '404' );
		}

		if ( empty( $location['address'] ) ) {
			$location['address'] = $data->display_name;
		}
		if ( ! empty( $data->address ) ) {
			if ( empty( $location['country_code'] ) and ! empty( $data->address->country_code ) ) {
				$location['country_code'] = strtoupper( $data->address->country_code );
			}
			// Returns admin name in address->state, but no code
			if ( empty( $location['sub_admin_code'] ) and ! empty( $data->address->county ) ) {
				$location['sub_admin_code'] = $data->address->county;
			}
			if ( empty( $location['postal_code'] ) and ! empty( $data->address->postcode ) ) {
				$location['postal_code'] = $data->address->postcode;
			}
			if ( empty( $location['locality_name'] ) and ! empty( $data->address->city ) ) {
				$location['locality_name'] = $data->address->city;
			}
		}

		return $status;
	}

	/**
	 * Use a geocoding service to find coordinates and possibly other fields for a location.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @param mixed $query The search string.
	 * @param array $location The location to geocode, modified.
	 * @param string $language 
	 * @return string The status code of the request.
	 */
	function geocode( $query, &$location, $language = '' ) {
		global $geo_mashup_options;

		if ( empty( $location ) ) {
			$location = GeoMashupDB::blank_location( ARRAY_A );
		} else if ( ! is_array( $location ) ) {
			return GeoMashupDB::lookup_status( '500' );
		}

		$status = null;
		// Try GeoCoding services (google, nominatim, geonames) until one gives an answer
		if ( 'google' == substr( $geo_mashup_options->get( 'overall', 'map_api' ), 0, 6 ) ) {
			// Only try the google service if a google API is selected as the default
			$status = GeoMashupDB::google_geocode( $query, $location, $language );
		}
		if ( '200' != $status ) {
			$status = GeoMashupDB::nominatim_geocode( $query, $location, $language );
		}
		if ( '200' != $status ) {
			$status = GeoMashupDB::geonames_geocode( $query, $location, $language );
		}
		return $status;
	}

	/**
	 * Check a location for empty fields.
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 *
	 * @param array $location The location to check.
	 * @param array $fields The fields to check.
	 * @return bool Whether any of the specified fields are empty.
	 */
	function are_any_location_fields_empty( $location, $fields = null ) {
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
	 * Use an HTTP geocoding service to find an address from coordinates only.
	 * 
	 * Adds missing location fields, but does not replace existing data.
	 *
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @param array $location The location to geocode, modified.
	 * @param string $language Optional ISO language code.
	 * @return string The final status.
	 */
	function reverse_geocode_location( &$location, $language = '' ) {
		global $geo_mashup_options;

		// Coordinates are required
		if ( GeoMashupDB::are_any_location_fields_empty( $location, array( 'lat', 'lng' ) ) ) {
			return '0';
		}

		// Don't bother unless there are missing geocodable fields
		$geocodable_fields = array( 'country_code', 'admin_code', 'address', 'locality_name', 'postal_code' );
		$have_empties = GeoMashupDB::are_any_location_fields_empty( $location, $geocodable_fields );
		if ( ! $have_empties ) {
			return '0';
		}

		$status = null;

		// Let GeoNames have a crack at country and admin codes
		GeoMashupDB::geonames_reverse_geocode_location( $location, $language );

		$have_empties = GeoMashupDB::are_any_location_fields_empty( $location, $geocodable_fields );
		if ( $have_empties ) {
			// Choose a geocoding service based on the default API in use
			if ( 'google' == substr( $geo_mashup_options->get( 'overall', 'map_api' ), 0, 6 ) ) {
				$query = $location['lat']  . ',' . $location['lng'];
				$status = GeoMashupDB::google_geocode( $query, $location, $language );
			} else if ( 'openlayers' == $geo_mashup_options->get( 'overall', 'map_api' ) ) {
				$status = GeoMashupDB::nominatim_reverse_geocode_location( $location, $language );
			}
		}
		return $status;
	}

	/**
	 * Try to reverse-geocode all locations with relevant missing data.
	 *
	 * Used by the options page. Tries to comply with the PHP maximum execution 
	 * time, and delay requests if Google sends a 604.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 * @return string An HTML log of the actions performed.
	 */
	function bulk_reverse_geocode() {
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
			$set_id = GeoMashupDB::set_location( $location, true );
			if ( is_wp_error( $set_id ) ) {
				$log .= 'error: ' . $set_id->get_error_message() . ' ';
			} else {
				$log .= 'id: ' . $location['id'] . ' '; 
			}
			if ( GeoMashupDB::lookup_status() == 604 ) {
				$delay += 100000;
				$log .= __( 'Too many requests, increasing delay to', 'GeoMashup' ) . ' ' . ( $delay / 1000000 ) .
					'<br/>';
			} else if ( isset( $location['address'] ) ) {
				$log .= __( 'address', 'GeoMashup' ) . ': ' . $location['address'] . ' ' .
					__( 'postal code', 'GeoMashup' ) . ': ' . $location['postal_code'] . '<br />';
			} else {
				$log .= '(' .$location['lat'] . ', ' . $location['lng'] . ') ' . 
					__( 'No address info found, status', 'GeoMashup' ) .  ': ' . GeoMashupDB::lookup_status() .  
					'<br/>';
			}
			if ( time() - $start_time > $time_limit ) {
				$log .= __( 'Time limit exceeded, retry to continue.', 'GeoMashup' ) . '<br />';
				break;
			}
			if ( function_exists( 'usleep' ) ) {
				usleep( $delay );
			} else {
				// PHP 4 has to settle for a full second
				sleep( 1 );
			}
		}
		return $log;
	}
			
	/**
	 * Store an administrative name in the database to prevent future web service lookups.
	 * 
	 * @since 1.2
	 * @access private
	 * @static
	 *
	 * @param string $country_code 
	 * @param string $admin_code 
	 * @param string $isolanguage 
	 * @param string $name 
	 * @param string $geoname_id 
	 * @return int Rows affected.
	 */
	function cache_administrative_name( $country_code, $admin_code, $isolanguage, $name, $geoname_id = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'geo_mashup_administrative_names';
		$cached_name = GeoMashupDB::get_cached_administrative_name( $country_code, $admin_code, $isolanguage ); 
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
	 *
	 * @since 1.4
	 * @access private
	 * @static
	 * @return bool True if no more orphan locations can be found.
	 */
	function duplicate_geodata() {
		global $wpdb;

		// Copy from postmeta to geo mashup
		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$postmeta_select = "SELECT pmlat.post_id, pmlat.meta_value as lat, pmlng.meta_value as lng, pmaddr.meta_value as address, p.post_date
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pmlat ON pmlat.post_id = p.ID AND pmlat.meta_key = 'geo_latitude'
			INNER JOIN {$wpdb->postmeta} pmlng ON pmlng.post_id = p.ID AND pmlng.meta_key = 'geo_longitude'
			LEFT JOIN {$wpdb->postmeta} pmaddr ON pmaddr.post_id = p.ID AND pmaddr.meta_key = 'geo_address'
			LEFT JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = pmlat.post_id AND gmlr.object_name = 'post'
			WHERE pmlat.meta_key = 'geo_latitude' 
			AND gmlr.object_id IS NULL";

		$wpdb->query( $postmeta_select );

		if ($wpdb->last_error) {
			GeoMashupDB::activation_log( $wpdb->last_error );
			return false;
		}

		$unconverted_metadata = $wpdb->last_result;
		if ( $unconverted_metadata ) {
			$msg = __( 'Copying geodata from WordPress', 'GeoMashup' );
			GeoMashupDB::activation_log( date( 'r' ) . ' ' . $msg );
			$start_time = time();
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				$location = array( 'lat' => trim( $postmeta->lat ), 'lng' => trim( $postmeta->lng ), 'address' => trim( $postmeta->address ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = GeoMashupDB::set_object_location( 'post', $post_id, $location, $do_lookups, $postmeta->post_date );
				if ( $set_id ) {
					GeoMashupDB::activation_log( 'OK: post_id ' . $post_id );
				} else {
					$msg = sprintf( __( 'Failed to duplicate WordPress location (%s). You can %sedit the post%s ' .
						'to update the location, and try again.', 'GeoMashup' ),
						$postmeta->lat . ',' . $postmeta->lng, '<a href="post.php?action=edit&post=' . $post_id . '">', '</a>');
					GeoMashupDB::activation_log( $msg, true );
				}
			}
		}

		// Copy from Geo Mashup to missing postmeta
		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$geomashup_select = "SELECT gmlr.object_id as post_id, gml.lat, gml.lng, gml.address
			FROM {$wpdb->prefix}geo_mashup_locations gml
			INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id
			LEFT JOIN {$wpdb->postmeta} pmlat ON pmlat.post_id = gmlr.object_id AND pmlat.meta_key = 'geo_latitude'
			WHERE gmlr.object_name = 'post'
			AND pmlat.post_id IS NULL";

		$wpdb->query( $geomashup_select );

		if ($wpdb->last_error) {
			GeoMashupDB::activation_log( $wpdb->last_error, true );
			return false;
		}

		$unconverted_geomashup_posts = $wpdb->last_result;
		if ( $unconverted_geomashup_posts ) {
			$msg = __( 'Copying geodata from Geo Mashup', 'GeoMashup' );
			GeoMashupDB::activation_log( date( 'r' ) . ' ' . $msg );
			$start_time = time();
			foreach ( $unconverted_geomashup_posts as $location ) {
				$lat_success = update_post_meta( $location->post_id, 'geo_latitude', $location->lat );
				$lng_success = update_post_meta( $location->post_id, 'geo_longitude', $location->lng );
				if ( ! empty( $location->address ) ) {
					update_post_meta( $location->post_id, 'geo_address', $location->address );
				}
				if ( $lat_success and $lng_success ) {
					GeoMashupDB::activation_log( 'OK: post_id ' . $location->post_id );
				} else {
					$msg = sprintf( __( 'Failed to duplicate Geo Mashup location for post (%s).', 'GeoMashup' ), $location->post_id );
					GeoMashupDB::activation_log( $msg );
				}
			}
		}

		$wpdb->query( $postmeta_select );

		return ( empty( $wpdb->last_result ) );
	}

	/**
	 * Convert Geo plugin locations to Geo Mashup format.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 * @return bool True if no more unconverted locations can be found.
	 */
	function convert_prior_locations( ) {
		global $wpdb;

		// NOT EXISTS doesn't work in MySQL 4, use left joins instead
		$unconverted_select = "SELECT pm.post_id, pm.meta_value
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->postmeta} lpm ON lpm.post_id = pm.post_id 
			AND lpm.meta_key = '_geo_converted'
			LEFT JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = pm.post_id
			AND gmlr.object_name = 'post'
			WHERE pm.meta_key = '_geo_location' 
			AND length( pm.meta_value ) > 1
			AND lpm.post_id IS NULL 
			AND gmlr.object_id IS NULL";

		$wpdb->query( $unconverted_select );

		if ($wpdb->last_error) {
			GeoMashupDB::activation_log( $wpdb->last_error, true );
			return false;
		}

		$unconverted_metadata = $wpdb->last_result;
		if ( $unconverted_metadata ) {
			$msg = __( 'Converting old locations', 'GeoMashup' );
			GeoMashupDB::activation_log( date( 'r' ) . ' ' . $msg );
			$start_time = time();
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				list( $lat, $lng ) = split( ',', $postmeta->meta_value );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = GeoMashupDB::set_object_location( 'post', $post_id, $location, $do_lookups );
				if ( $set_id ) {
					add_post_meta( $post_id, '_geo_converted', $wpdb->prefix . 'geo_mashup_locations.id = ' . $set_id );
					GeoMashupDB::activation_log( 'OK: post_id ' . $post_id );
				} else {
					$msg = sprintf( __( 'Failed to convert location (%s). You can %sedit the post%s ' .
						'to update the location, and try again.', 'GeoMashup' ),
						$postmeta->meta_value, '<a href="post.php?action=edit&post=' . $post_id . '">', '</a>');
					GeoMashupDB::activation_log( $msg );
				}
			}
		}

		$geo_locations = get_option( 'geo_locations' );
		if ( is_array( $geo_locations ) ) {
			$msg = __( 'Converting saved locations', 'GeoMashup' );
			GeoMashupDB::activation_log( $msg );
			foreach ( $geo_locations as $saved_name => $coordinates ) {
				list( $lat, $lng, $converted ) = split( ',', $coordinates );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ), 'saved_name' => $saved_name );
				$do_lookups = ( ( time() - $start_time ) < 15 ) ? true : false;
				$set_id = GeoMashupDB::set_location( $location, $do_lookups );
				if ( ! is_wp_error( $set_id ) ) {
					$geo_locations[$saved_name] .= ',' . $wpdb->prefix . 'geo_mashup_locations.id=' . $set_id;
					$msg = __( 'OK: ', 'GeoMashup' ) . $saved_name . '<br/>';
					GeoMashupDB::activation_log( $msg );
				} else {
					$msg = $saved_name . ' - ' . 
						sprintf( __( "Failed to convert saved location (%s). " .
							"You'll have to save it again, sorry.", 'GeoMashup' ),
						$coordinates );
					GeoMashupDB::activation_log( $set_id->get_error_message() );
					GeoMashupDB::activation_log( $msg );
				}
			}
			update_option( 'geo_locations', $geo_locations );
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

		GeoMashupDB::activation_log( $msg, true );

		$wpdb->query( $unconverted_select );

		return ( empty( $wpdb->last_result ) );
	}

	/**
	 * Get a blank object location.
	 * 
	 * Ambiguous - treated like both a location and object location. 
	 * @todo Clean up.
	 * @since 1.2
	 * @access private
	 * @static
	 * 
	 * @param constant $format 
	 * @return array|object Empty object location.
	 */
	function blank_location( $format = OBJECT ) {
		$blank_location = array(
			'id' => null,
			'lat' => null,
			'lng' => null,
			'address' => null,
			'saved_name' => null,
			'geoname' => null,
			'postal_code' => null,
			'country_code' => null,
			'admin_code' => null,
			'sub_admin_code' => null,
			'locality_name' => null,
		  'object_name' => null,
		  'object_id' => null,
		  'geo_date' => null );
		if ( $format == OBJECT ) {
			return (object) $blank_location;
		} else {
			return $blank_location;
		}
	}

	/**
	 * Get distinct values of one or more object location fields.
	 *
	 * Can be used to get a list of countries with locations, for example.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * 
	 * @param string $names Comma separated table field names.
	 * @param array $where Associtive array of conditional field names and values.
	 * @return object WP_DB query results.
	 */
	function get_distinct_located_values( $names, $where = null ) {
		global $wpdb;

		if ( is_string( $names ) ) {
			$names = preg_split( '/\s*,\s*/', $names );
		}
		$wheres = array( );
		foreach( $names as $name ) {
			$wheres[] = $wpdb->escape( $name ) . ' IS NOT NULL';
		}
		$names = implode( ',', $names );

		if ( is_object( $where ) ) {
			$where = (array) $where;
		}

		$select_string = 'SELECT DISTINCT ' . $wpdb->escape( $names ) . "
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id";

		if ( is_array( $where ) && !empty( $where ) ) {
			foreach ( $where as $name => $value ) {
				$wheres[] = $wpdb->escape( $name ) . ' = \'' . $wpdb->escape( $value ) .'\'';
			}
			$select_string .= ' WHERE ' . implode( ' AND ', $wheres );
		}
		$select_string .= ' ORDER BY ' . $wpdb->escape( $names );

		return $wpdb->get_results( $select_string );
	}

	/**
	 * Get the location of a post.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * @uses GeoMashupDB::get_object_location()
	 * 
	 * @param id $post_id 
	 * @return object Post location.
	 */
	function get_post_location( $post_id ) {
		return GeoMashupDB::get_object_location( 'post', $post_id );
	}

	/**
	 * Format a query result as an object or array.
	 * 
	 * @since 1.3
	 * @access private
	 * @static
	 *
	 * @param object $obj To be formatted.
	 * @param constant $output Format.
	 * @return object|array Result.
	 */
	function translate_object( $obj, $output = OBJECT ) {
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
	 * @access public
	 * @static
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param id $object_id Object 
	 * @param string $output (optional) one of ARRAY_A | ARRAY_N | OBJECT constants.  Return an associative array (column => value, ...), a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
id.
	 * @return object|array Result.
	 */
	function get_object_location( $object_name, $object_id, $output = OBJECT ) {
		global $wpdb;

		$cache_id = $object_name . '-' . $object_id;

		$location = wp_cache_get( $cache_id, 'locations' );
		if ( !$location ) {
			$select_string = "SELECT * 
				FROM {$wpdb->prefix}geo_mashup_locations gml
				JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id " .
				$wpdb->prepare( 'WHERE gmlr.object_name = %s AND gmlr.object_id = %d', $object_name, $object_id );

			$location = $wpdb->get_row( $select_string );
			wp_cache_add( $cache_id, $location, 'locations' );
		}
		return GeoMashupDB::translate_object( $location, $output );
	}
	
	/**
	 * Get locations of posts.
	 * 
	 * @since 1.2
	 * @access public
	 * @static
	 * @uses GeoMashupDB::get_object_locations()
	 * 
	 * @param string $query_args Same as GeoMashupDB::get_object_locations()
	 * @return array Array of matching rows.
	 */
	function get_post_locations( $query_args = '' ) {
		return GeoMashupDB::get_object_locations( $query_args );
	}

	/**
	 * Get locations of objects.
	 *
	 * <code>
	 * $results = GeoMashupDB::get_object_locations( array( 
	 * 	'object_name' => 'user', 
	 * 	'map_cat' => '3,4,8', 
	 * 	'minlat' => 30, 
	 * 	'maxlat' => 40, 
	 * 	'minlon' => -106, 
	 * 	'maxlat' => -103 ) 
	 * );
	 * </code>
	 * 
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param string $query_args Override default args.
	 * @return array Array of matching rows.
	 */
	function get_object_locations( $query_args = '' ) {
		global $wpdb;

		$default_args = array( 
			'minlat' => null, 
			'maxlat' => null, 
			'minlon' => null, 
			'maxlon' => null,
			'radius_km' => null,
			'radius_mi' => null,
			'object_name' => 'post',
			'show_future' => 'false', 
			'suppress_filters' => false,
	 		'limit' => 0 );
		$query_args = wp_parse_args( $query_args, $default_args );
		
		// Construct the query 
		$object_name = $query_args['object_name'];
		$object_store = GeoMashupDB::object_storage( $object_name );
		if ( empty( $object_store ) ) {
			return null;
		}
		$field_string = "gmlr.object_id, o.{$object_store['label_column']} as label, gml.*";
		$table_string = "{$wpdb->prefix}geo_mashup_locations gml " . 
			"INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr " .
			$wpdb->prepare( 'ON gmlr.object_name = %s AND gmlr.location_id = gml.id ', $object_name ) .
			"INNER JOIN {$object_store['table']} o ON o.{$object_store['id_column']} = gmlr.object_id";
		$wheres = array( );
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
		}

		// Check for a radius query
		if ( ! empty( $query_args['radius_mi'] ) ) {
			$query_args['radius_km'] = 1.609344 * floatval( $query_args['radius_mi'] );
		}
		if ( ! empty( $query_args['radius_km'] ) and ! empty( $query_args['near_lat'] ) and ! empty( $query_args['near_lng'] ) ) {
			// Earth radius = 6371 km, 3959 mi
			$near_lat = floatval( $query_args['near_lat'] );
			$near_lng = floatval( $query_args['near_lng'] );
			$radius_km = floatval( $query_args['radius_km'] );
			$field_string .= ", 6371 * 2 * ASIN( SQRT( POWER( SIN( ( $near_lat - ABS( gml.lat ) ) * PI() / 180 / 2 ), 2 ) + COS( $near_lat * PI() / 180 ) * COS( ABS( gml.lat ) * PI() / 180 ) * POWER( SIN( ( $near_lng - gml.lng ) * PI() / 180 / 2 ), 2 ) ) ) as distance_km";
			$having = "HAVING distance_km < $radius_km";
			// approx 111 km per degree latitude
			$query_args['min_lat'] = $near_lat - ( $radius_km / 111 );
			$query_args['max_lat'] = $near_lat + ( $radius_km / 111 );
			$query_args['min_lon'] = $near_lng - ( $radius_km / ( abs( cos( deg2rad( $near_lat ) ) ) * 111 ) );
			$query_args['max_lon'] = $near_lng + ( $radius_km / ( abs( cos( deg2rad( $near_lat ) ) ) * 111 ) );
		}

		// Ignore nonsense bounds
		if ( $query_args['minlat'] && $query_args['maxlat'] && $query_args['minlat'] > $query_args['maxlat'] ) {
			$query_args['minlat'] = $query_args['maxlat'] = null;
		}
		if ( $query_args['minlon'] && $query_args['maxlon'] && $query_args['minlon'] > $query_args['maxlon'] ) {
			$query_args['minlon'] = $query_args['maxlon'] = null;
		}

		// Build bounding where clause
		if ( is_numeric( $query_args['minlat'] ) ) $wheres[] = "lat > {$query_args['minlat']}";
		if ( is_numeric( $query_args['minlon'] ) ) $wheres[] = "lng > {$query_args['minlon']}";
		if ( is_numeric( $query_args['maxlat'] ) ) $wheres[] = "lat < {$query_args['maxlat']}";
		if ( is_numeric( $query_args['maxlon'] ) ) $wheres[] = "lng < {$query_args['maxlon']}";

		// Handle inclusion and exclusion of categories
		if ( ! empty( $query_args['map_cat'] ) ) {

			$cats = preg_split( '/[,\s]+/', $query_args['map_cat'] );

			$escaped_include_ids = array();
			$escaped_include_slugs = array();
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

			$table_string .= " JOIN $wpdb->term_relationships tr ON tr.object_id = gmlr.object_id " .
				"JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " .
				"AND tt.taxonomy = 'category'";

			if ( ! empty( $escaped_include_ids ) ) {
				$wheres[] = 'tt.term_id IN (' . implode( ',', $escaped_include_ids ) . ')';
			}

			if ( ! empty( $escaped_exclude_ids ) ) {
				$wheres[] = 'tt.term_id NOT IN (' . implode( ',', $escaped_exclude_ids ) . ')';
			}
		} // end if map_cat exists 

		if ( isset( $query_args['object_id'] ) ) {
			$wheres[] = 'gmlr.object_id = ' . $wpdb->escape( $query_args['object_id'] );
		} else if ( isset( $query_args['object_ids'] ) ) {
			$wheres[] = 'gmlr.object_id in ( ' . $wpdb->escape( $query_args['object_ids'] ) .' )';
		}

		$no_where_fields = array( 'object_name', 'object_id', 'geo_date' );
		foreach ( GeoMashupDB::blank_location( ARRAY_A ) as $field => $blank ) {
			if ( !in_array( $field, $no_where_fields) && isset( $query_args[$field] ) ) {
				$wheres[] = $wpdb->prepare( "gml.$field = %s", $query_args[$field] );
			}
		}

		$where = ( empty( $wheres ) ) ? '' :  'WHERE ' . implode( ' AND ', $wheres ); 
		$sort = ( isset( $query_args['sort'] ) ) ? $query_args['sort'] : $object_store['sort'];
		$sort = ( empty( $sort ) ) ? '' : 'ORDER BY ' . $wpdb->escape( $sort );
		$limit = ( is_numeric( $query_args['limit'] ) && $query_args['limit']>0 ) ? " LIMIT 0,{$query_args['limit']}" : '';

		if ( ! $query_args['suppress_filters'] ) {
			$field_string	= apply_filters( 'geo_mashup_locations_fields', $field_string );
			$table_string = apply_filters( 'geo_mashup_locations_join', $table_string );
			$where = apply_filters( 'geo_mashup_locations_where', $where );
			$sort = apply_filters( 'geo_mashup_locations_orderby', $sort );
			$limit = apply_filters( 'geo_mashup_locations_limits', $limit );
		}
		
		$query_string = "SELECT $field_string FROM $table_string $where $having $sort $limit";

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
	 * @access public
	 * @static
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param id $object_id ID of the object to save the location for.
	 * @param id|array $location If an ID, the location is not modified. If an array of valid location fields, the location is added or updated. If empty, the object location is deleted.
	 * @param bool $do_lookups Whether to try looking up missing location information, which can take extra time, default is true.
	 * @param string $geo_date Optional geo date to associate with the object.
	 * @return id|WP_Error The location ID now assiociated with the object.
	 */
	function set_object_location( $object_name, $object_id, $location, $do_lookups = true, $geo_date = '' ) {
		global $wpdb;

		if ( is_numeric( $location ) ) {
			$location_id = $location;
		} 

		if ( !isset( $location_id ) ) {
			$location_id = GeoMashupDB::set_location( $location, $do_lookups );
			if ( is_wp_error( $location_id ) ) {
				return $location_id;
			}
		}

		if ( !is_numeric( $location_id ) ) {
			GeoMashupDB::delete_object_location( $object_name, $object_id );
			return 0;
		}

		if ( empty( $geo_date ) ) {
			$geo_date = date( 'Y-m-d H:i:s' );
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
		} else {
			$wpdb->update( $relationship_table, compact( 'location_id', 'geo_date' ), compact( 'object_name', 'object_id' ) );
			if ( $wpdb->last_error ) 
				return new WP_Error( 'db_update_error', $wpdb->last_error );
			$set_id = $location_id;
		}
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
	 * @access public
	 * @static
	 *
	 * @param array $location Location to save, may be modified to match actual saved data.
	 * @param bool $do_lookups Whether to try to look up address information before saving, defaults to no.
	 * @return id|WP_Error The location ID saved, or a WordPress error.
	 */
	function set_location( &$location, $do_lookups = null ) {
		global $wpdb, $geo_mashup_options;

		if ( is_null( $do_lookups ) ) {
			$do_lookups = ( $geo_mashup_options->get( 'overall', 'enable_reverse_geocoding' ) == 'true' );
		}

		if ( is_object( $location ) ) {
			$location = (array) $location;
		}

		// Check for existing location ID
		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$select_string = "SELECT id, saved_name FROM $location_table ";

		if ( isset( $location['id'] ) && is_numeric( $location['id'] ) ) {

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
			GeoMashupDB::reverse_geocode_location( $location );
		}

		// Don't set blank entries
		foreach ( $location as $name => $value ) {
			if ( !is_numeric( $value ) && empty( $value ) ) {
				unset( $location[$name] );
			}
		}

		// Replace any existing saved name
		if ( ! empty( $location['saved_name'] ) and $found_saved_name != $location['saved_name'] ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $location_table SET saved_name = NULL WHERE saved_name = %s", $location['saved_name'] ) );
		}

		$set_id = null;

		if ( empty( $location['id'] ) ) {

			// Create a new location
			if ( $wpdb->insert( $location_table, $location ) ) {
				$set_id = $wpdb->insert_id;
			} else {
				return new WP_Error( 'db_insert_error', $wpdb->last_error );
			}

		} else {

			// Update existing location, except for coordinates
			$tmp_lat = $location['lat']; 
			$tmp_lng = $location['lng']; 
			unset( $location['lat'] );
			unset( $location['lng'] );
			if ( !empty ( $location ) ) {
				$wpdb->update( $location_table, $location, array( 'id' => $db_location['id'] ) );
				if ( $wpdb->last_error ) 
					return new WP_Error( 'db_update_error', $wpdb->last_error );
			}
			$set_id = $db_location['id'];
			$location['lat'] = $tmp_lat;
			$location['lng'] = $tmp_lng;

		}
		return $set_id;
	}

	/**
	 * Delete an object location or locations.
	 * 
	 * This removes the association of an object with a location, but does NOT
	 * delete the location.
	 * 
	 * @since 1.3
	 * @access public
	 * @static
	 *
	 * @param string $object_name 'post', 'user', a GeoMashupDB::object_storage() index.
	 * @param id|array $object_ids Object ID or array of IDs to remove the locations of.
	 * @return int|WP_Error Rows affected or WordPress error.
	 */
	function delete_object_location( $object_name, $object_ids ) {
		global $wpdb;

		$object_ids = ( is_array( $object_ids ) ? $object_ids : array( $object_ids ) );
		$rows_affected = 0;
		foreach( $object_ids as $object_id ) {
			$delete_string = "DELETE FROM {$wpdb->prefix}geo_mashup_location_relationships " .
				$wpdb->prepare( 'WHERE object_name = %s AND object_id = %d', $object_name, $object_id );
			$rows_affected += $wpdb->query( $delete_string );
			if ( $wpdb->last_error ) 
				return new WP_Error( 'delete_object_location_error', $wpdb->last_error );
		}

		return $rows_affected;
	}

	/**
	 * Delete a location or locations.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 * 
	 * @param id|array $ids Location ID or array of IDs to delete.
	 * @return int|WP_Error Rows affected or Wordpress error.
	 */
	function delete_location( $ids ) {
		global $wpdb;
		$ids = ( is_array( $ids ) ? $ids : array( $ids ) );
		$rows_affected = 0;
		foreach( $ids as $id ) {
			$delete_string = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}geo_mashup_locations WHERE id = %d", $id );
			$rows_affected += $wpdb->query( $delete_string );
			if ( $wpdb->last_error ) 
				return new WP_Error( 'delete_location_error', $wpdb->last_error );
		}

		return $rows_affected;
	}

	/**
	 * Get locations with saved names.
	 *
	 * @since 1.2
	 * @access public
	 * @static
	 *
	 * @return array|WP_Error Array of location rows or WP_Error.
	 */
	function get_saved_locations() {
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
	 * @access public
	 * @static
	 *
	 * @param id $category_id 
	 * @return int
	 */
	function category_located_post_count( $category_id ) {
		global $wpdb;

		$select_string = "SELECT count(*) FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID 
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_id = p.ID AND gmlr.object_name = 'post'
			WHERE tt.term_id = " . $wpdb->escape( $category_id ) ."
			AND p.post_status='publish'";
		return $wpdb->get_var( $select_string );
	}

	/**
	 * Get categories that contain located objects.
	 *
	 * Not sufficient - probably want parent categories.
	 *
	 * @access private
	 * @static
	 */
	function get_located_categories( ) {
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
	 * @access private
	 * @static
	 * @return array Comments.
	 */
	function get_comment_in( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args );
		if ( is_array( $args['comment__in'] ) ) {
			$comment_ids = implode( ',', $args['comment__in'] );
		} else {
			$comment_ids = ( isset( $args['comment__in'] ) ) ? $args['comment__in'] : '0';
		}
		$select_string = "SELECT * FROM $wpdb->comments WHERE comment_ID IN (" .
			$wpdb->prepare( $comment_ids ) . ') ORDER BY comment_date_gmt DESC';
		return $wpdb->get_results( $select_string );
	}

	/**
	 * Get multiple users.
	 *
	 * What is the WordPress way? Expect deprecation.
	 * 
	 * @access private
	 * @static
	 * @return array Users.
	 */
	function get_user_in( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args );
		if ( is_array( $args['user__in'] ) ) {
			$user_ids = implode( ',', $args['user__in'] );
		} else {
			$user_ids = ( isset( $args['user__in'] ) ) ? $args['user__in'] : '0';
		}
		$select_string = "SELECT * FROM $wpdb->users WHERE ID IN (" .
			$wpdb->prepare( $user_ids ) . ') ORDER BY display_name ASC';
		return $wpdb->get_results( $select_string );
	}

	/**
	 * When a post is deleted, remove location relationships for it.
	 *
	 * delete_post {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function delete_post( $id ) {
		return GeoMashupDB::delete_object_location( 'post', $id );
	}

	/**
	 * When a comment is deleted, remove location relationships for it.
	 *
	 * delete_comment {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function delete_comment( $id ) {
		return GeoMashupDB::delete_object_location( 'comment', $id );
	}

	/**
	 * When a user is deleted, remove location relationships for it.
	 *
	 * delete_user {@link http://codex.wordpress.org/Plugin_API/Action_Reference action}
	 * called by WordPress.
	 *
	 * @since 1.2
	 * @access private
	 * @static
	 */
	function delete_user( $id ) {
		return GeoMashupDB::delete_object_location( 'user', $id );
	}
}
?>
