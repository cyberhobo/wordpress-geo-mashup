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
					admin_name TINYTEXT NULL,
					sub_admin_code VARCHAR( 80 ) NULL,
					sub_admin_name TINYTEXT NULL,
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
				);";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			// Not sure keys are handled properly by dbDelta on upgrade, so there may be errors
			$old_show_errors = $wpdb->show_errors( true );
			dbDelta( $sql );
			if ( !$wpdb->last_error ) {
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
		$pattern = '/<' . $tag_name . '>(\w.*)<\/' . $tag_name . '>/is';
		if ( preg_match( $pattern, $document, $match ) ) {
			$content .= $match[1];
		}
		return $content;
	}

	function get_geoname_subdivision( $lat, $lng ) {
		$result = array( );
		$xml_string = @file_get_contents( "http://ws.geonames.org/countrySubdivision?lat=$lat&lng=$lng" );
		$result['country_code'] = GeoMashupDB::get_simple_tag_content( 'countryCode', $xml_string );
		$result['admin_code'] = GeoMashupDB::get_simple_tag_content( 'adminCode1', $xml_string );
		$result['admin_name'] = GeoMashupDB::get_simple_tag_content( 'adminName1', $xml_string );
		return $result;
	}
			
	function convert_prior_locations( ) {
		global $wpdb;

		$unconverted_select = "SELECT post_id, meta_value
			FROM {$wpdb->postmeta} pm
			WHERE meta_key = '_geo_location' 
			AND length( meta_value ) > 1
			AND NOT EXISTS (
				SELECT 1 
				FROM {$wpdb->postmeta} ipm 
				WHERE ipm.post_id = pm.post_id 
				AND ipm.meta_key = '_geo_converted' )
		  AND NOT EXISTS (
				SELECT 1
				FROM {$wpdb->prefix}geo_mashup_location_relationships gmlr
				WHERE gmlr.object_name = 'post' 
				AND gmlr.object_id = pm.post_id )";

		$wpdb->query( $unconverted_select );
		
		$unconverted_metadata = $wpdb->last_result; 
		if ( $unconverted_metadata ) {
			foreach ( $unconverted_metadata as $postmeta ) {
				$post_id = $postmeta->post_id;
				list( $lat, $lng ) = split( ',', $postmeta->meta_value );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ) );
				$set_id = GeoMashupDB::set_post_location( $post_id, $location );
				if ( $set_id ) {
					add_post_meta( $post_id, '_geo_converted', $wpdb->prefix . 'geo_mashup_locations.id = ' . $set_id );
				} 
			}
		}

		$geo_locations = get_settings( 'geo_locations' );
		if ( is_array( $geo_locations ) ) {
			foreach ( $geo_locations as $saved_name => $coordinates ) {
				list( $lat, $lng, $converted ) = split( ',', $coordinates );
				$location = array( 'lat' => trim( $lat ), 'lng' => trim( $lng ), 'saved_name' => $saved_name );
				$set_id = GeoMashupDB::set_location( $location );
				if ( $set_id ) {
					$geo_locations[$saved_name] .= ',' . $wpdb->prefix . 'geo_mashup_locations.id=' . $set_id;
				}
			}
			update_option( 'geo_locations', $geo_locations );
		}

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
			'admin_name' => null,
			'sub_admin_code' => null,
			'sub_admin_name' => null,
			'locality_name' => null);
		if ( $format == OBJECT ) {
			return (object) $blank_location;
		} else {
			return $blank_location;
		}
	}

	function get_distinct_located_values( $names ) {
		global $wpdb;

		if ( is_array( $names ) ) {
			$names = implode( ',', $names );
		}

		$select_string = 'SELECT DISTINCT ' . $wpdb->escape( $names ) . "
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id";

		return $wpdb->get_results( $select_string );
	}

	function get_post_location( $post_id ) {
		global $wpdb;

		$select_string = "SELECT * 
			FROM {$wpdb->prefix}geo_mashup_locations gml
			JOIN {$wpdb->prefix}geo_mashup_location_relationships gmlr ON gmlr.location_id = gml.id " .
			$wpdb->prepare( 'WHERE gmlr.object_name = \'post\' AND gmlr.object_id = %d', $post_id );

		$location = false;
		$wpdb->query( $select_string, $output_type );
		if ( $wpdb->last_result ) {
			$location = $wpdb->last_result[0];
		}
		return $location;
	}
	
	function get_post_locations( $query_args = '' )
	{
		global $wpdb;

		$default_args = array( 'sort' => 'post_date DESC' );
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

		if ( is_numeric( $query_args['map_cat'] ) ) {
			$table_string .= " JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID " .
				"JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " .
				"AND tt.taxonomy = 'category'";
			$cat = $wpdb->escape( $query_args['map_cat'] );
			$wheres[] = "tt.term_id = $cat";
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

	function set_post_location( $object_id, $location ) {
		global $wpdb;

		if ( is_numeric( $location ) ) {
			$location_id = $location;
		} 

		if ( !isset( $location_id ) ) {
			$location_id = GeoMashupDB::set_location( $location );
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

	function set_location( $location ) {
		global $wpdb;

		if ( is_object( $location ) ) {
			$location = (array) $location;
		}

		// Don't set blank entries
		foreach ( $location as $name => $value ) {
			if ( empty( $value ) ) {
				unset( $location[$name] );
			}
		}

		if ( is_numeric( $location['lat'] ) && is_numeric( $location['lng'] ) ) {
			$location['lat'] = round( $location['lat'], 7 );
			$location['lng'] = round( $location['lng'], 7 );
		} else {
			return false;
		}

		$location_table = $wpdb->prefix . 'geo_mashup_locations';
		$select_string = "SELECT id FROM $location_table ";
		// Check for existing location
		if ( is_numeric( $location['id'] ) ) {
			$select_string .= $wpdb->prepare( 'WHERE id = %d', $location['id'] );
		} else {
			$delta = 0.000005;
			$select_string .= $wpdb->prepare( 'WHERE lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f', 
				$location['lat'] - $delta, $location['lat'] + $delta, $location['lng'] - $delta, $location['lng'] + $delta );
		} 

		$db_location = $wpdb->get_row( $select_string, ARRAY_A );
		
		$set_id = null;
		if ( empty( $db_location ) ) {

			if ( empty( $location['country_code'] ) || empty( $location['admin_code'] ) || empty( $location['admin_name'] ) ) {
				$location = array_merge( $location, GeoMashupDB::get_geoname_subdivision( $location['lat'], $location['lng'] ) );
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
}

?>
