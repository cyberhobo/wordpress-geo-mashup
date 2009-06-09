<?php 
/**
 * Geo Mashup Data Access
 */

// Actions to maintain data integrity with WordPress
add_action( 'delete_post', array( 'GeoMashupDB', 'delete_post' ) );
add_action( 'delete_comment', array( 'GeoMashupDB', 'delete_comment' ) );
add_action( 'delete_user', array( 'GeoMashupDB', 'delete_user' ) );
		 
/**
 * Static class used as a namespace for Geo Mashup data functions.
 *
 * @since 1.2
 */
class GeoMashupDB {

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

	function object_storage( $object_name, $new_storage = null ) {
		global $wpdb;
		static $objects = null;
		
		if ( is_null( $objects ) ) {
			$objects = array( 
				'post' => array( 
					'table' => $wpdb->posts, 
					'id_column' => 'ID', 
					'label_column' => 'post_title', 
					'sort' => 'post_status ASC, post_date DESC' ),
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

	function lookup_status( $new_status = null ) {
		static $status = '0';

		if ( $new_status ) {
			$status = $new_status;
		}
		return $status;
	}

	function install( ) {
		global $wpdb;

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
					saved_name VARCHAR( 50 ) NULL,
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
					PRIMARY KEY  ( object_name, object_id, location_id ),
					KEY object_name ( object_name, object_id )
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
				echo $errors;
				update_option( 'geo_mashup_activation_log', $errors );
				die( $errors );
			} else {
				if ( GeoMashupDB::convert_prior_locations( ) ) {
					GeoMashupDB::installed_version( GEO_MASHUP_DB_VERSION );
				}
			}
			$wpdb->show_errors( $old_show_errors );
		}
		return ( GeoMashupDB::installed_version( ) == GEO_MASHUP_DB_VERSION );
	}

	function get_simple_tag_content( $tag_name, $document ) {
		$content = null;
		$pattern = '/<' . $tag_name . '>(.*?)<\/' . $tag_name . '>/is';
		if ( preg_match( $pattern, $document, $match ) ) {
			$content .= $match[1];
		}
		return $content;
	}

	/**
	 * Try to get a language-sensitive place administrative name. First look in the 
	 * names cached in the database, then query geonames.org for it. If a name can't be 
	 * found for the requested language, a default name is returned, usually in the 
	 * local language. If nothing can be found, returns NULL.
	 * 
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string Place name in the appropriate language, or if not available in the default language.
	 */
	function get_administrative_name( $country_code, $admin_code = null, $language = null ) {
		$name = GeoMashupDB::get_cached_administrative_name( $country_code, $admin_code, $language );
		if ( empty( $name ) ) {
			$name = GeoMashupDB::get_geonames_administrative_name( $country_code, $admin_code, $language );
		}
		return $name;
	}


	/** 
	 * Look in the database for a cached administrative name. Return NULL if not found.
	 *
	 * @param string $country_code Two-character ISO country code.
	 * @param string $admin_code Code for the administrative area within the country, or NULL to get the country name.
	 * @param string $language Language code, defaults to the WordPress locale language.
	 * @return string Place name or NULL.
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

	function get_geonames_administrative_name( $country_code, $admin_code = '', $language = '' ) {
		$language = GeoMashupDB::primary_language_code( $language );

		// Country name - the easy case
		$country_info_url = 'http://ws.geonames.org/countryInfo?country=' . urlencode( $country_code ) .
			'&lang=' . urlencode( $language );
		$http = new WP_Http();
		$country_info_response = $http->get( $country_info_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $country_info_response ) ) {
			GeoMashupDB::lookup_status( $country_info_response->get_error_code() );
			return null;
		}
		GeoMashupDB::lookup_status( $country_info_response['response']['code'] );
		$country_name = GeoMashupDB::get_simple_tag_content( 'countryName', $country_info_response['body'] );
		$country_id = GeoMashupDB::get_simple_tag_content( 'geonameId', $country_info_response['body'] );
		if ( !empty( $country_name ) ) {
			GeoMashupDB::cache_administrative_name( $country_code, '', $language, $country_name, $country_id );
			if ( empty( $admin_code ) ) {
				return $country_name;
			}
		}

		// Administrative area (child of country)
		if ( empty( $country_id ) ) return null;

		$children_url = 'http://ws.geonames.org/children?style=short&geonameId=' . $country_id;
		$children_response = $http->get( $children_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $children_response ) ) {
			GeoMashupDB::lookup_status( $children_response->get_error_code() );
			return null;
		}
		preg_match_all( '/<geonameId>(\d*)<\/geonameId>/is', $children_response['body'], $matches );
		if ( empty( $matches ) ) return null;
		$requested_name = null;
		foreach ( $matches[1] as $child_id ) {
			// We have to query each child to get the admin code, so just cache them all
			$child_url = 'http://ws.geonames.org/get?geonameId=' . $child_id . '&lang=' . urlencode( $language );
			$child_response = $http->get( $child_url,  array( 'timeout' => 3.0 ) );
			if ( is_wp_error( $child_response ) ) {
				GeoMashupDB::lookup_status( $child_response->get_error_code() );
				return null;
			}
			$child_name = GeoMashupDB::get_simple_tag_content( 'name', $child_response['body'] );
			if ( !empty( $child_name ) ) {
				$child_admin_code = GeoMashupDB::get_simple_tag_content( 'adminCode1', $child_response['body'] );
				GeoMashupDB::cache_administrative_name( $country_code, $child_admin_code, $language, $child_name, $child_id );
				if ( $child_admin_code == $admin_code ) {
					$requested_name = $child_name;
				}
			}
		}
		return $requested_name;
	}

	function get_geonames_subdivision( $lat, $lng ) {
		$result = array( );

		$http = new WP_Http();
		$response = $http->get( "http://ws.geonames.org/countrySubdivision?lat=$lat&lng=$lng", array( 'timeout' => 3.0 ) );
		if ( !is_wp_error( $response ) ) {
			GeoMashupDB::lookup_status( $response['response']['code'] );
			$result['country_code'] = GeoMashupDB::get_simple_tag_content( 'countryCode', $response['body'] );
			$result['admin_code'] = GeoMashupDB::get_simple_tag_content( 'adminCode1', $response['body'] );
			// TODO: Save administrative names?
		} else {
			GeoMashupDB::lookup_status( $response->get_error_code() );
		}
		return $result;
	}

	function reverse_geocode_location( &$location, $language = '' ) {
		global $geo_mashup_options;

		// Don't bother unless there are missing geocodable fields
		$have_missing_field = false;
		foreach( array( 'country_code', 'admin_code', 'address', 'locality_name', 'postal_code' ) as $field ) {
			if ( empty( $location[$field] ) ) {
				$have_missing_field = true;
			}
		}
		if ( !$have_missing_field ) {
			return '0';
		}

		$language = GeoMashupDB::primary_language_code( $language );

		$google_geocode_url = 'http://maps.google.com/maps/geo?key=' .
			$geo_mashup_options->get( 'overall', 'google_key' ) .
			'&q=' . $location['lat']  . ',' . $location['lng'] .
			'&output=xml&oe=utf8&sensor=true&gl=' . $language;

		$http = new WP_Http();
		$response = $http->get( $google_geocode_url, array( 'timeout' => 3.0 ) );
		if ( is_wp_error( $response ) ) {
			return GeoMashupDB::lookup_status( $response->get_error_code() );
		}

		$status = GeoMashupDB::lookup_status( $response['response']['code'] );
		if ( 200 != $status ) {
			return $status;
		}

		if ( empty( $location['country_code'] ) ) {
			$location['country_code'] = GeoMashupDB::get_simple_tag_content( 'CountryNameCode', $response['body'] );
		}
		if ( empty( $location['admin_code'] ) ) {
			$admin_name = GeoMashupDB::get_simple_tag_content( 'AdministrativeAreaName', $response['body'] );
			// For US (and others?) Google returns uppercase admin code instead of name
			if ( !empty( $admin_name ) && strtoupper( $admin_name ) == $admin_name ) {
				$location['admin_code'] = $admin_name;
			}
		}
		if ( empty( $location['address'] ) ) {
			$location['address'] = GeoMashupDB::get_simple_tag_content( 'address', $response['body'] );
		}
		if ( empty( $location['postal_code'] ) ) {
			$location['postal_code'] = GeoMashupDB::get_simple_tag_content( 'PostalCodeNumber', $response['body'] );
		}
		if ( empty( $location['locality_name'] ) ) {
			$location['locality_name'] = GeoMashupDB::get_simple_tag_content( 'LocalityName', $response['body'] );
		}
		// Less accurate locality may exist in first address line
		if ( empty( $location['locality_name'] ) ) {
			$location['locality_name'] = GeoMashupDB::get_simple_tag_content( 'AddressLine', $response['body'] );
		}
		return $status;
	}

	function bulk_reverse_geocode( ) {
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
			GeoMashupDB::set_location( $location, true );
			$log .= 'id: ' . $location['id'] . ' '; 
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
			update_option( 'geo_mashup_activation_log', $wpdb->last_error );
			return false;
		}

		$unconverted_metadata = $wpdb->last_result;
		$start_time = time();
		$log = date( 'r' ) . '</br>';
		if ( $unconverted_metadata ) {
			$msg = '<p>' . __( 'Converting old locations', 'GeoMashup' );
			$log .= $msg;
			echo $msg;
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				list( $lat, $lng ) = split( ',', $postmeta->meta_value );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = GeoMashupDB::set_object_location( 'post', $post_id, $location, $do_lookups );
				if ( $set_id ) {
					add_post_meta( $post_id, '_geo_converted', $wpdb->prefix . 'geo_mashup_locations.id = ' . $set_id );
					// Echo a poor man's progress bar
					$log .= '<br/>OK: post_id ' . $post_id;
					echo '.';
					flush( );
				} else {
					$msg = '<br/>';
					$msg .= sprintf( __( 'Failed to convert location (%s). You can %sedit the post%s ' .
						'to update the location, and try again.', 'GeoMashup' ),
						$postmeta->meta_value, '<a href="post.php?action=edit&post=' . $post_id . '">', '</a>');
					$msg .= '<br/>';
					$log .= $msg;
					echo $msg;
				}
			}
			$log .= '</p>';
			echo '</p>';
		}

		$geo_locations = get_option( 'geo_locations' );
		if ( is_array( $geo_locations ) ) {
			$msg = '<p>'. __( 'Converting saved locations', 'GeoMashup' ) . ':<br/>';
			$log .= $msg;
			echo $msg;
			foreach ( $geo_locations as $saved_name => $coordinates ) {
				list( $lat, $lng, $converted ) = split( ',', $coordinates );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ), 'saved_name' => $saved_name );
				$do_lookups = ( ( time() - $start_time ) < 15 ) ? true : false;
				$set_id = GeoMashupDB::set_location( $location, $do_lookups );
				if ( $set_id ) {
					$geo_locations[$saved_name] .= ',' . $wpdb->prefix . 'geo_mashup_locations.id=' . $set_id;
					$msg = __( 'OK: ', 'GeoMashup' ) . $saved_name . '<br/>';
					$log .= $msg;
					echo $msg;
				} else {
					$msg = $saved_name . ' - ' . 
						sprintf( __( "Failed to convert saved location (%s). " .
							"You'll have to save it again, sorry.", 'GeoMashup' ),
						$coordinates );
					$msg .= '<br/>';
					$log .= $msg;
					echo $msg;
				}
			}
			$log .= '</p>';
			echo '</p>';
			update_option( 'geo_locations', $geo_locations );
		}

		update_option( 'geo_mashup_activation_log', $log );

		$wpdb->query( $unconverted_select );

		return ( empty( $wpdb->last_result ) );
	}

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
			'locality_name' => null);
		if ( $format == OBJECT ) {
			return (object) $blank_location;
		} else {
			return $blank_location;
		}
	}

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

	function get_post_location( $post_id ) {
		return GeoMashupDB::get_object_location( 'post', $post_id );
	}

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
	
	function get_post_locations( $query_args = '' ) {
		return GeoMashupDB::get_object_locations( $query_args );
	}

	function get_object_locations( $query_args = '' ) {
		global $wpdb;

		$default_args = array( 
			'minlat' => null, 
			'maxlat' => null, 
			'minlon' => null, 
			'maxlon' => null,
			'object_name' => 'post',
			'show_future' => 'false', 
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

		if ( 'post' == $object_name ) {
			if ( $query_args['show_future'] == 'true' ) {
				$wheres[] = 'post_status in ( \'publish\',\'future\' )';
			} else if ( $query_args['show_future'] == 'only' ) {
				$wheres[] = 'post_status = \'future\'';
			} else {
				$wheres[] = 'post_status = \'publish\'';
			}
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

		if ( !empty ( $query_args['map_cat'] ) ) {
			$table_string .= " JOIN $wpdb->term_relationships tr ON tr.object_id = gmlr.object_id " .
				"JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " .
				"AND tt.taxonomy = 'category'";
			$cat = $wpdb->escape( $query_args['map_cat'] );
			$wheres[] = "tt.term_id IN ($cat)";
		} 

		if ( isset( $query_args['object_id'] ) ) {
			$wheres[] = 'gmlr.object_id = ' . $wpdb->escape( $query_args['object_id'] );
		} else if ( isset( $query_args['object_ids'] ) ) {
			$wheres[] = 'gmlr.object_id in ( ' . $wpdb->escape( $query_args['object_ids'] ) .' )';
		}

		foreach ( GeoMashupDB::blank_location( ARRAY_A ) as $field => $blank ) {
			if ( isset( $query_args[$field] ) ) {
				$wheres[] = $wpdb->prepare( "gml.$field = %s", $query_args[$field] );
			}
		}

		$where = ( empty( $wheres ) ) ? '' :  'WHERE ' . implode( ' AND ', $wheres ); 
		$sort = ( isset( $query_args['sort'] ) ) ? $query_args['sort'] : $object_store['sort'];
		$sort = ( empty( $sort ) ) ? '' : 'ORDER BY ' . $wpdb->escape( $sort );

		$query_string = "SELECT $field_string FROM $table_string $where $sort";

		if ( !( $query_args['minlat'] && $query_args['maxlat'] && $query_args['minlon'] && $query_args['maxlon'] ) && !$query_args['limit'] ) {
			// result should contain all posts ( possibly for a category )
		} else if ( is_numeric( $query_args['limit'] ) && $query_args['limit']>0 ) {
			$query_string .= " LIMIT 0,{$query_args['limit']}";
		}

		$wpdb->query( $query_string );
		
		return $wpdb->last_result;
	}

	function set_object_location( $object_name, $object_id, $location, $do_lookups = true ) {
		global $wpdb;

		if ( is_numeric( $location ) ) {
			$location_id = $location;
		} 

		if ( !isset( $location_id ) ) {
			$location_id = GeoMashupDB::set_location( $location, $do_lookups );
		}

		if ( !is_numeric( $location_id ) ) {
			GeoMashupDB::delete_object_location( $object_name, $object_id );
			return false;
		}

		$relationship_table = "{$wpdb->prefix}geo_mashup_location_relationships"; 
		$select_string = "SELECT * FROM $relationship_table " .
			$wpdb->prepare( 'WHERE object_name = %s AND object_id = %d', $object_name, $object_id );

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );

		$set_id = null;
		if ( empty( $db_location ) ) {
			if ( $wpdb->insert( $relationship_table, compact( 'object_name', 'object_id', 'location_id' ) ) ) {
				$set_id = $location_id;
			}
		} else {
			$wpdb->update( $relationship_table, compact( 'location_id' ), compact( 'object_name', 'object_id' ) ); 
			$set_id = $location_id;
		}
		return $set_id;
	}

	function set_location( &$location, $do_lookups = null ) {
		global $wpdb, $geo_mashup_options;

		if ( is_null( $do_lookups ) ) {
			$do_lookups = ( $geo_mashup_options->get( 'overall', 'enable_reverse_geocoding' ) == 'true' );
		}

		if ( is_object( $location ) ) {
			$location = (array) $location;
		}

		if ( is_numeric( $location['lat'] ) && is_numeric( $location['lng'] ) ) {
			$location['lat'] = round( $location['lat'], 7 );
			$location['lng'] = round( $location['lng'], 7 );
		} else {
			return false;
		}

		// Check for existing location
		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$select_string = "SELECT id FROM $location_table ";
		if ( isset( $location['id'] ) && is_numeric( $location['id'] ) ) {
			$select_string .= $wpdb->prepare( 'WHERE id = %d', $location['id'] );
		} else {
			// MySql appears to only distinguish 5 decimal places, ~8 feet, in the index
			$delta = 0.00001;
			$select_string .= $wpdb->prepare( 'WHERE lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f', 
				$location['lat'] - $delta, $location['lat'] + $delta, $location['lng'] - $delta, $location['lng'] + $delta );
		} 
		$db_location = $wpdb->get_row( $select_string, ARRAY_A );

		if ( !empty( $db_location ) ) {
			$location = array_merge( $location, $db_location );
		}

		// Reverse geocode
		if ( $do_lookups ) {
			GeoMashupDB::reverse_geocode_location( $location );
		}
		$have_missing_area_code = empty( $location['country_code'] ) || empty( $location['admin_code'] );
		if ( $do_lookups && $have_missing_area_code ) {
			$location = array_merge( $location, GeoMashupDB::get_geonames_subdivision( $location['lat'], $location['lng'] ) );
		}

		// Don't set blank entries
		foreach ( $location as $name => $value ) {
			if ( !is_numeric( $value ) && empty( $value ) ) {
				unset( $location[$name] );
			}
		}

		$set_id = null;
		if ( empty( $location['id'] ) ) {
			if ( $wpdb->insert( $location_table, $location ) ) {
				$set_id = $wpdb->insert_id;
			}
		} else {
			// Don't update coordinates
			$tmp_lat = $location['lat']; 
			$tmp_lng = $location['lng']; 
			unset( $location['lat'] );
			unset( $location['lng'] );
			if ( !empty ( $location ) ) {
				$wpdb->update( $location_table, $location, array( 'id' => $db_location['id'] ) );
			}
			$set_id = $db_location['id'];
			$location['lat'] = $tmp_lat;
			$location['lng'] = $tmp_lng;
		}
		return $set_id;
	}

	function delete_object_location( $object_name, $object_id ) {
		global $wpdb;

		$delete_string = "DELETE FROM {$wpdb->prefix}geo_mashup_location_relationships " .
			$wpdb->prepare( 'WHERE object_name = %s AND object_id = %d', $object_name, $object_id );
		return $wpdb->query( $delete_string );
	}

	function delete_location( $id ) {
		global $wpdb;

		$delete_string = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}geo_mashup_locations WHERE id = %d", $id );
		return $wpdb->query( $delete_string );
	}

	function get_saved_locations( ) {
		global $wpdb;

		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$wpdb->query( "SELECT * FROM $location_table WHERE saved_name IS NOT NULL" );
		return $wpdb->last_result;
	}

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

	function delete_post( $id ) {
		GeoMashupDB::delete_object_location( 'post', $id );
	}

	function delete_comment( $id ) {
		GeoMashupDB::delete_object_location( 'comment', $id );
	}

	function delete_user( $id ) {
		GeoMashupDB::delete_object_location( 'user', $id );
	}
}

?>
