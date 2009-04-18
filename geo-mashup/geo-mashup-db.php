<?php 
/**
 * Geo Mashup Data Access
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
		$pattern = '/<' . $tag_name . '>(.*)<\/' . $tag_name . '>/is';
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
		$country_info_url = 'http://ws.geonames.org/countryInfo?country=' . urlencode( $country_code ) .
			'&lang=' . urlencode( $language );

		// Country name - the easy case
		$country_info_xml = @ file_get_contents( $country_info_url );
		$country_name = GeoMashupDB::get_simple_tag_content( 'countryName', $country_info_xml );
		$country_id = GeoMashupDB::get_simple_tag_content( 'geonameId', $country_info_xml );
		if ( !empty( $country_name ) ) {
			GeoMashupDB::cache_administrative_name( $country_code, '', $language, $country_name, $country_id );
			if ( empty( $admin_code ) ) {
				return $country_name;
			}
		}

		// Administrative area (child of country)
		if ( empty( $country_id ) ) return null;

		$children_url = 'http://ws.geonames.org/children?style=short&geonameId=' . $country_id;
		$children_xml = @ file_get_contents( $children_url );
		preg_match_all( '/<geonameId>(\d*)<\/geonameId>/is', $children_xml, $matches );
		if ( empty( $matches ) ) return null;
		$requested_name = null;
		foreach ( $matches[1] as $child_id ) {
			// We have to query each child to get the admin code, so just cache them all
			$child_url = 'http://ws.geonames.org/get?geonameId=' . $child_id . '&lang=' . urlencode( $language );
			$child_xml = @ file_get_contents( $child_url );
			$child_name = GeoMashupDB::get_simple_tag_content( 'name', $child_xml );
			if ( !empty( $child_name ) ) {
				$child_admin_code = GeoMashupDB::get_simple_tag_content( 'adminCode1', $child_xml );
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
		$xml_string = @ file_get_contents( "http://ws.geonames.org/countrySubdivision?lat=$lat&lng=$lng" );
		$result['country_code'] = GeoMashupDB::get_simple_tag_content( 'countryCode', $xml_string );
		$result['admin_code'] = GeoMashupDB::get_simple_tag_content( 'adminCode1', $xml_string );
		// TODO: Save administrative names?
		return $result;
	}
			
	function cache_administrative_name( $country_code, $admin_code, $isolanguage, $name, $geoname_id = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'geo_mashup_administrative_names';
		$cached_name = GeoMashupDB::get_cached_administrative_name( $country_code, $admin_code, $language ); 
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
		$log = '';
		if ( $unconverted_metadata ) {
			$msg = '<p>' . __( 'Converting old locations', 'GeoMashup' );
			$log .= $msg;
			echo $msg;
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				list( $lat, $lng ) = split( ',', $postmeta->meta_value );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ) );
				$do_lookups = ( ( time() - $start_time ) < 10 ) ? true : false;
				$set_id = GeoMashupDB::set_post_location( $post_id, $location, $do_lookups );
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

		if ( is_array( $names ) ) {
			$names = implode( ',', $names );
		}

		if ( is_object( $where ) ) {
			$where = (array) $where;
		}

		$select_string = 'SELECT DISTINCT ' . $wpdb->escape( $names ) . "
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id";

		if ( is_array( $where ) && !empty( $where ) ) {
			$wheres = array( );
			foreach ( $where as $name => $value ) {
				$wheres[] = $wpdb->escape( $name ) . ' = \'' . $wpdb->escape( $value ) .'\'';
			}
			$select_string .= ' WHERE ' . implode( ' AND ', $wheres );
		}

		return $wpdb->get_results( $select_string );
	}

	function get_post_location( $post_id ) {
		global $wpdb;

		$select_string = "SELECT * 
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id " .
			$wpdb->prepare( 'WHERE gmlr.object_name = \'post\' AND gmlr.object_id = %d', $post_id );

		$location = false;
		$wpdb->query( $select_string );
		if ( $wpdb->last_result ) {
			$location = $wpdb->last_result[0];
		}
		return $location;
	}
	
	function get_post_locations( $query_args = '' )
	{
		global $wpdb;

		$default_args = array( 'sort' => 'post_date DESC', 
			'minlat' => null, 
			'maxlat' => null, 
			'minlon' => null, 
			'maxlon' => null,
			'show_future' => 'false', 
	 		'limit' => 0 );
		$query_args = wp_parse_args( $query_args, $default_args );
		
		// Construct the query 
		$field_string = 'p.ID as post_id, post_title, gml.*';
		$table_string = "{$wpdb->prefix}geo_mashup_locations gml
			INNER JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.object_name = 'post' AND gmlr.location_id = gml.id
			INNER JOIN $wpdb->posts p ON p.ID = gmlr.object_id";
		$wheres = array( );

		if ( $query_args['show_future'] == 'true' ) {
			$wheres[] = 'post_status in ( \'publish\',\'future\' )';
		} else if ( $query_args['show_future'] == 'only' ) {
			$wheres[] = 'post_status = \'future\'';
		} else {
			$wheres[] = 'post_status = \'publish\'';
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
			$table_string .= " JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID " .
				"JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " .
				"AND tt.taxonomy = 'category'";
			$cat = $wpdb->escape( $query_args['map_cat'] );
			$wheres[] = "tt.term_id IN ($cat)";
		} 

		if ( isset( $query_args['post_id'] ) ) {
			$wheres[] = 'p.ID = ' . $wpdb->escape( $query_args['post_id'] );
		} else if ( isset( $query_args['post_ids'] ) ) {
			$wheres[] = 'p.ID in ( ' . $wpdb->escape( $query_args['post_ids'] ) .' )';
		}

		foreach ( GeoMashupDB::blank_location( ARRAY_A ) as $field => $blank ) {
			if ( isset( $query_args[$field] ) ) {
				$wheres[] = $wpdb->prepare( "gml.$field = %s", $query_args[$field] );
			}
		}

		$query_string = "SELECT $field_string FROM $table_string WHERE " . implode( ' AND ', $wheres ) . 
			' ORDER BY post_status ASC, ' . $wpdb->escape( $query_args['sort'] );

		if ( !( $query_args['minlat'] && $query_args['maxlat'] && $query_args['minlon'] && $query_args['maxlon'] ) && !$query_args['limit'] ) {
			// result should contain all posts ( possibly for a category )
		} else if ( is_numeric( $query_args['limit'] ) && $query_args['limit']>0 ) {
			$query_string .= " LIMIT 0,{$query_args['limit']}";
		}

		$wpdb->query( $query_string );
		
		return $wpdb->last_result;
	}

	function set_post_location( $object_id, $location, $do_lookups = true ) {
		global $wpdb;

		if ( is_numeric( $location ) ) {
			$location_id = $location;
		} 

		if ( !isset( $location_id ) ) {
			$location_id = GeoMashupDB::set_location( $location, $do_lookups );
		}

		if ( !is_numeric( $location_id ) ) {
			GeoMashupDB::delete_post_location( $object_id );
			return false;
		}

		$relationship_table = "{$wpdb->prefix}geo_mashup_location_relationships"; 
		$select_string = "SELECT * FROM $relationship_table " .
			$wpdb->prepare( 'WHERE object_name = \'post\' AND object_id = %d', $object_id );

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );

		$set_id = null;
		$object_name = 'post';
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

	function set_location( $location, $do_lookups = true ) {
		global $wpdb;

		if ( is_object( $location ) ) {
			$location = (array) $location;
		}

		if ( is_numeric( $location['lat'] ) && is_numeric( $location['lng'] ) ) {
			$location['lat'] = round( $location['lat'], 7 );
			$location['lng'] = round( $location['lng'], 7 );
		} else {
			return false;
		}

		// Don't set blank entries
		foreach ( $location as $name => $value ) {
			if ( empty( $value ) ) {
				unset( $location[$name] );
			}
		}

		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$select_string = "SELECT id FROM $location_table ";
		// Check for existing location
		if ( isset( $location['id'] ) && is_numeric( $location['id'] ) ) {
			$select_string .= $wpdb->prepare( 'WHERE id = %d', $location['id'] );
		} else {
			// MySql appears to only distinguish 5 decimal places, ~8 feet, in the index
			$delta = 0.00001;
			$select_string .= $wpdb->prepare( 'WHERE lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f', 
				$location['lat'] - $delta, $location['lat'] + $delta, $location['lng'] - $delta, $location['lng'] + $delta );
		} 

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );
		
		$set_id = null;
		if ( empty( $db_location ) ) {

			$have_missing_area_code = empty( $location['country_code'] ) || empty( $location['admin_code'] );
			if ( $do_lookups && $have_missing_area_code ) {
				$location = array_merge( $location, GeoMashupDB::get_geonames_subdivision( $location['lat'], $location['lng'] ) );
			}

			if ( $wpdb->insert( $location_table, $location ) ) {
				$set_id = $wpdb->insert_id;
			}
		} else {
			unset( $location['lat'] );
			unset( $location['lng'] );
			if ( !empty ( $location ) ) {
				$wpdb->update( $location_table, $location, array( 'id' => $db_location['id'] ) );
			}
			$set_id = $db_location['id'];
		}
		return $set_id;
	}

	function delete_post_location( $post_id ) {
		global $wpdb;

		$delete_string = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}geo_mashup_location_relationships WHERE object_name = 'post' AND object_id = %d", $post_id );
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
			WHERE gmlr.object_name = 'post'
			AND tt.taxonomy='category'
			ORDER BY t.slug ASC";
		return $wpdb->get_results( $select_string );
	}
}

?>
