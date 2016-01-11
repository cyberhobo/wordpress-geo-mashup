<?php
/**
 * Geo Mashup Geocoder
 *
 * @since 1.4
 * @package GeoMashup
 * @subpackage Geocoder
 */


if ( ! class_exists( 'GeoMashupHttpGeocoder' ) ) {
/**
 * The Geo Mashup Http Geocoder base class.
 *
 * @since 1.4
 * @package GeoMashup
 */
abstract class GeoMashupHttpGeocoder {
	/**
	 * WP_Http instance
	 * @since 1.4
	 * @var float
	 */
	protected $http;
	/**
	 * Parameters to use with http requests.
	 * @var array 
	 */
	public $request_params;
	/**
	 * Language code.
	 * @since 1.4
	 * @var string
	 */
	public $language;
	/**
	 * Maximum number of results
	 * @var int 
	 */
	public $max_results;

	/**
	 * Constructor
	 *
	 * @param string $args Optional array of arguments:
	 * 		language - two digit language code, defaults to blog language
	 * 		max_results - maximum number of results to fetch
	 * 		default http params - array of WP_Http request parameters, including timeout
	 */
	public function  __construct( $args = array() ) {
		$defaults = array(
			'language' => get_locale(),
			'max_results' => 10,
			'request_params' => array( 'timeout' => 3.0 )
		);
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		$this->language = GeoMashupDB::primary_language_code( $language );
		$this->max_results = absint( $max_results );
		$this->request_params = $request_params;
		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );
		$this->http = new WP_Http();
	}

	/**
	 * Look up locations from a text query, like an address or place name.
	 *
	 * @since 1.4
	 *
	 * @param string $query Text query.
	 * @return array|WP_Error Array of search result locations.
	 */
	abstract public function geocode( $query );

	/**
	 * Look up additional location information from coordinates.
	 *
	 * Resulting locations have as much address information as
	 * possible filled in. Most services return the nearest match
	 * first, and some return only one.
	 *
	 * @since 1.4
	 *
	 * @param float $lat Latitude
	 * @param float $lng Longitude
	 * @return array|WP_Error Array of search result locations.
	 */
	abstract public function reverse_geocode( $lat, $lng );

	/**
	 * Convert a text term to UTF-8 if necessary before URL encoding.
	 *
	 * @since 1.4.10
	 *
	 * @static
	 * @param $text
	 * @return string Encoded text.
	 */
	static protected function url_utf8_encode( $text ) {

		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( !mb_check_encoding( $text, 'UTF-8' ) )
				$text = mb_convert_encoding( $text, 'UTF-8' );
		} else {
			$msg = sprintf(
				__( '%s Multibyte string functions %s are not installed.', 'GeoMashup' ),
				'<a href="http://www.php.net/manual/en/mbstring.installation.php" title="">',
				'</a>'
			);
			$msg .= ' ' . sprintf(
				__( 'Geocoding will only work if "%s" is UTF-8 text.', 'GeoMashup' ),
				$text
			);
			trigger_error( $msg, E_USER_WARNING );
		}

		return urlencode( $text );
	}
}

/**
 * HTTP geocoder using the geonames web services.
 *
 * Includes an additional method for looking up administrative area names.
 *
 * @since 1.4
 * @package GeoMashup
 */
class GeoMashupGeonamesGeocoder extends GeoMashupHttpGeocoder {
	/**
	 * The application username to include in geonames API requests.
	 * @var string 
	 */
	private $geonames_username;

	public function  __construct( $args = array() ) {
		global $geo_mashup_options;
		parent::__construct( $args );
		$this->geonames_username = $geo_mashup_options->get( 'overall', 'geonames_username' );
	}

	public function geocode( $query ) {
		$url = 'http://api.geonames.org/searchJSON?username=' . $this->geonames_username .
				'&maxRows=' .  $this->max_results . '&q=' . self::url_utf8_encode( $query ) .
				'&lang=' . $this->language;

		$response = $this->http->get( $url, $this->request_params );
		if ( is_wp_error( $response ) )
			return $response;

		$status = $response['response']['code'];
		if ( '200' != $status ) 
			return new WP_Error( 'geocoder_http_request_failed', $status . ': ' . $response['response']['message'], $response );

		$data = json_decode( $response['body'] );

		if ( isset( $data->status ) and isset( $data->status->message ) )
			return new WP_Error( 'geocoder_http_request_failed', $data->status->value . ': ' . $data->status->message, $data );

		if ( empty( $data ) or 0 == $data->totalResultsCount ) 
			return array();

		$locations = array();
		foreach( $data->geonames as $geoname ) {
			$location = GeoMashupDB::blank_location();
			$location->lat = $geoname->lat;
			$location->lng = $geoname->lng;
			if ( !empty( $geoname->countryCode ) )
				$location->country_code = $geoname->countryCode;
			if ( !empty( $geoname->adminCode1) )
				$location->admin_code = $geoname->adminCode1;
			if ( !empty( $geoname->fcode ) and 'PPL' == substr( $geoname->fcode, 0, 3 ) )
				$location->locality_name = $geoname->name;
			$locations[] = $location;
		}

		return $locations;
	}

	public function reverse_geocode( $lat, $lng ) {
		global $geo_mashup_options;

		if ( !is_numeric( $lat ) or !is_numeric( $lng ) ) // Bad Request
			return new WP_Error( 'bad_reverse_geocode_request', __( 'Reverse geocoding requires numeric coordinates.', 'GeoMashup' ) );

		$status = null;
		$url = 'http://api.geonames.org/countrySubdivisionJSON?style=FULL&username=' . $this->geonames_username . 
				'&lat=' . urlencode( $lat ) .  '&lng=' . urlencode( $lng );
		$response = $this->http->get( $url, $this->request_params );
		if ( is_wp_error( $response ) )
			return $response;

		$status = $response['response']['code'];
		$data = json_decode( $response['body'] );
		if ( empty( $data ) or !empty( $data->status ) )
			return array();
		$location = GeoMashupDB::blank_location();
		$location->lat = $lat;
		$location->lng = $lng;
		if ( !empty( $data->countryCode ) )
			$location->country_code = $data->countryCode;
		if ( !empty( $data->adminCode1 ) )
			$location->admin_code = $data->adminCode1;
		
		// Look up more things, postal code, locality or address in US
		if ( 'US' == $location->country_code and GeoMashupDB::are_any_location_fields_empty( $location, array( 'address', 'locality_name', 'postal_code' ) ) ) {
			$url = 'http://api.geonames.org/findNearestAddressJSON?style=FULL&username=' . $this->geonames_username . 
					'&lat=' . urlencode( $lat ) .  '&lng=' . urlencode( $lat );
			$response = $this->http->get( $url, $this->request_params );

			if ( !is_wp_error( $response ) ) {
				$status = $response['response']['code'];
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
					$location->address = implode( ', ', $address_parts );
					$location->locality_name = $data->address->placename;
					$location->postal_code = $data->address->postalcode;
				}
			}
		}
		if (  GeoMashupDB::are_any_location_fields_empty( $location, array( 'address', 'locality_name', 'postal_code' ) ) ) {
			// Just look for a postal code
			$url = 'http://api.geonames.org/findNearbyPostalCodesJSON?username=' . $this->geonames_username . 
					'&maxRows=1&lat=' . urlencode( $lat ) .  '&lng=' . urlencode( $lng );
			$response = $this->http->get( $url, $this->request_params );

			if ( !is_wp_error( $response ) ) {
				$status = $response['response']['code'];
				$data = json_decode( $response['body'] );
				if ( ! empty( $data->postalCodes ) ) {
					$postal_code = $data->postalCodes[0];
					$admin_name = ( empty( $postal_code->adminName1 ) ? '' : $postal_code->adminName1 );
					$location->address = $postal_code->placeName . ', ' .
						$admin_name . ', ' . $postal_code->postalCode . ', ' .
						$postal_code->countryCode;
					$location->locality_name = $postal_code->placeName;
					$location->postal_code = $postal_code->postalCode;
				}
			}
		}
		return array( $location );
	}

	/**
	 * Use the Geonames web service to look up an administrative name.
	 *
	 * @since 1.4
	 *
	 * @param string $country_code
	 * @param string $admin_code Admin area name to look up. If empty, look up the country name.
	 * @return string|WP_Error The administrative name.
	 */
	public function get_administrative_name( $country_code, $admin_code = '' ) {

		if ( empty( $admin_code ) ) {
			// Look up a country name
			$country_info_url = 'http://api.geonames.org/countryInfoJSON?username=' . $this->geonames_username .
					'&country=' . urlencode( $country_code ) .  '&lang=' . urlencode( $this->language );
			$country_info_response = $this->http->get( $country_info_url, $this->request_params );
			if ( is_wp_error( $country_info_response ) )
				return $country_info_response;
			
			$status = $country_info_response['response']['code'];
			if ( '200' != $status )
				return new WP_Error( 'geocoder_http_request_failed', $status . ': ' . $country_info_response['response']['message'], $country_info_response );

			$data = json_decode( $country_info_response['body'] );
			if ( empty( $data->geonames ) )
				return '';
			$country_name = $data->geonames[0]->countryName;
			$country_id = $data->geonames[0]->geonameId;
			if ( !empty( $country_name ) ) 
				GeoMashupDB::cache_administrative_name( $country_code, '', $this->language, $country_name, $country_id );
			return $country_name;
		} else {
			// Look up an admin name
			$admin_search_url = 'http://api.geonames.org/searchJSON?maxRows=1&style=SHORT&featureCode=ADM1&username=' . $this->geonames_username . 
					'&country=' .  urlencode( $country_code ) . '&adminCode1=' . urlencode( $admin_code );
			$admin_search_response = $this->http->get( $admin_search_url, $this->request_params );
			if ( is_wp_error( $admin_search_response ) )
				return $admin_search_response;

			$status = $admin_search_response['response']['code'];
			if ( '200' != $status )
				return new WP_Error( 'geocoder_http_request_failed', $status . ': ' . $admin_search_response['response']['message'], $admin_search_response );

			$data = json_decode( $admin_search_response['body'] );
			if ( empty( $data ) or 0 == $data->totalResultsCount )
				return '';
			$admin_name = $data->geonames[0]->name;
			$admin_id = $data->geonames[0]->geonameId;
			if ( !empty( $admin_name ) ) 
				GeoMashupDB::cache_administrative_name( $country_code, $admin_code, $this->language, $admin_name, $admin_id );
			return $admin_name;
		}
		return '';
	}
}

/**
 * HTTP geocoder using the Google geocoding web service
 *
 * @since 1.4
 * @package GeoMashup
 */
class GeoMashupGoogleGeocoder extends GeoMashupHttpGeocoder {

	public function  __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * Do regular or reverse geocoding
	 *
	 * @since 1.4
	 *
	 * @param string $query_type 'address' or 'latlng'
	 * @param string $query
	 * @return array Locations.
	 */
	private function query( $query_type, $query ) {
		global $geo_mashup_options;

		$google_geocode_url = 'http://maps.google.com/maps/api/geocode/json?' . $query_type . '=' .
			self::url_utf8_encode( $query ) . '&language=' . $this->language;

		if ( $key = $geo_mashup_options->get( 'overall', 'googlev3_key' ) ) {
		$google_geocode_url .= '&key=' . rawurlencode( $key );
		}

		$response = $this->http->get( $google_geocode_url, $this->request_params );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = $response['response']['code'];
		if ( '200' != $status ) {
			return new WP_Error(
				'geocoder_http_request_failed',
				$status . ': ' . $response['response']['message'],
				$response
			);
		}

		$data = json_decode( $response['body'] );
		if ( 'ZERO_RESULTS' == $data->status ) {
			return array();
		}

		if ( 'OK' != $data->status ) {
			// status of OVER_QUERY_LIMIT, REQUEST_DENIED, INVALID_REQUEST, etc.
			return new WP_Error(
				'geocoder_request_failed',
				sprintf(
					__( 'Failed to geocode "%s" with status %s and body %s.', 'GeoMashup' ),
					$query,
					$status,
					$data
				),
				$data
			);
		}

		if ( count( $data->results ) > $this->max_results )
			$data->results = array_slice( $data->results, 0, $this->max_results );

		$locations = array();
		foreach( $data->results as $result ) {
			$location = GeoMashupDB::blank_location();
			$location->lat = $result->geometry->location->lat;
			$location->lng = $result->geometry->location->lng;
			$location->address = $result->formatted_address;
			if ( ! empty( $result->address_components ) ) {
				foreach( $result->address_components as $component ) {
					if ( in_array( 'country', $component->types ) )
						$location->country_code = $component->short_name;
					if ( in_array( 'administrative_area_level_1', $component->types ) )
						$location->admin_code = $component->short_name;
					if ( in_array( 'administrative_area_level_2', $component->types ) )
						$location->sub_admin_code = $component->short_name;
					if ( in_array( 'postal_code', $component->types ) )
						$location->postal_code = $component->short_name;
					if ( in_array( 'locality', $component->types ) )
						$location->locality_name = $component->short_name;
				}
			}
			$locations[] = $location;
		}
		return $locations;

	}

	public function geocode( $query ) {
		return $this->query( 'address', $query );
	}

	public function reverse_geocode( $lat, $lng ) {

		if ( !is_numeric( $lat ) or !is_numeric( $lng ) ) // Bad Request
			return new WP_Error( 'bad_reverse_geocode_request', __( 'Reverse geocoding requires numeric coordinates.', 'GeoMashup' ) );

		return $this->query( 'latlng', $lat . ',' . $lng );
	}
}

/**
 * HTTP geocoder using the nominatim web service.
 *
 * @since 1.4
 * @package GeoMashup
 */
class GeoMashupNominatimGeocoder extends GeoMashupHttpGeocoder {

	public function  __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function geocode( $query ) {

		$geocode_url = 'http://nominatim.openstreetmap.org/search?format=json&polygon=0&addressdetails=1&q=' .
			self::url_utf8_encode( $query ) . '&accept-language=' . $this->language .
			'&email=' . urlencode( get_option( 'admin_email' ) );

		$response = $this->http->get( $geocode_url, $this->request_params );
		if ( is_wp_error( $response ) )
			return $response;

		$status = $response['response']['code'];
		if ( '200' != $status )
			return new WP_Error( 'geocoder_http_request_failed', $status . ': ' . $response['response']['message'], $response );

		$data = json_decode( $response['body'] );
		if ( empty( $data ) )
			return array();

		if ( count( $data ) > $this->max_results )
			$data = array_slice( $data, 0, $this->max_results );

		$locations = array();
		foreach( $data as $result ) {
			$location = GeoMashupDB::blank_location();
			$location->lat = $result->lat;
			$location->lng = $result->lon;
			$location->address = $result->display_name;
			if ( !empty( $result->address ) ) {
				if ( !empty( $result->address->country_code ) )
					$location->country_code = strtoupper( $result->address->country_code );
				// Returns admin name in address->state, but no code
				if ( !empty( $result->address->county ) )
					$location->sub_admin_code = $result->address->county;
				if ( !empty( $result->address->postcode ) )
					$location->postal_code = $result->address->postcode;
				if ( !empty( $result->address->city ) )
					$location->locality_name = $result->address->city;
			}
			$locations[] = $location;
		}
		return $locations;
	}

	public function reverse_geocode( $lat, $lng ) {

		if ( !is_numeric( $lat ) or !is_numeric( $lng ) ) // Bad Request
			return new WP_Error( 'bad_reverse_geocode_request', __( 'Reverse geocoding requires numeric coordinates.', 'GeoMashup' ) );

		$geocode_url = 'http://nominatim.openstreetmap.org/reverse?format=json&zoom=18&address_details=1&lat=' .
			$lat . '&lon=' . $lng . '&email=' . urlencode( get_option( 'admin_email' ) );

		$response = $this->http->get( $geocode_url, $this->request_params );
		if ( is_wp_error( $response ) )
			return $response;

		$status = $response['response']['code'];
		if ( '200' != $status )
			return new WP_Error( 'geocoder_http_request_failed', $status . ': ' . $response['response']['message'], $response );

		$data = json_decode( $response['body'] );
		if ( empty( $data ) )
			return array();

		$location = GeoMashupDB::blank_location();
		$location->lat = $lat;
		$location->lng = $lng;
		$location->address = $data->display_name;
		if ( !empty( $data->address ) ) {
			if ( !empty( $data->address->country_code ) )
				$location->country_code = strtoupper( $data->address->country_code );
			// Returns admin name in address->state, but no code
			if ( !empty( $data->address->county ) )
				$location->sub_admin_code = $data->address->county;
			if ( !empty( $data->address->postcode ) )
				$location->postal_code = $data->address->postcode;
			if ( !empty( $data->address->city ) )
				$location->locality_name = $data->address->city;
		}
		return array( $location );
	}
}

}
