<?php
/**
 * Geo Mashup Search Module
 *
 * @package GeoMashup
 */

/**
 * The Geo Mashup Search class.
 *
 * @since 1.5
 * @package GeoMashup
 */
class GeoMashupSearch {
	const MILES_PER_KILOMETER = 0.621371;

	/**
	 * Plugin URL path.
	 * @deprecated Use GEO_MASHUP_URL_PATH.
	 */
	public $url_path;

	private $results;
	private $result;
	private $current_result;
	private $result_count;
	private $query_vars = array();
	private $near_location;
	private $units;
	private $max_km;
	private $distance_factor;

	/**
	 * Constructor.
	 *
	 * Sets up the query if included.
	 *
	 * @since 1.5
	 * @param string|array $query Search parameters.
	 */
	public function __construct( $query ) {

		// Back compat 
		$this->url_path = GEO_MASHUP_URL_PATH;

		if ( !empty( $query ) )
			$this->query( $query );
	}

	/**
	 * Run a search query.
	 *
	 * @since 1.5
	 * @uses apply_filters() geo_mashup_search_query_args Filter the geo query arguments.
	 *
	 * @param string|array $args Search parameters.
	 * @return array Search results.
	 **/
	public function query( $args ) {

		$default_args = array(
			'object_name' => 'post',
			'object_ids' => null,
			'exclude_object_ids' => null,
			'units' => 'km',
			'location_text' => '',
			'radius' => null,
			'sort' => 'distance_km ASC',
		);
		$this->query_vars = wp_parse_args( $args, $default_args );
		$this->query_vars['location_text'] = apply_filters( 'geo_mashup_search_query_location_text', $this->query_vars['location_text'] );

		/** @var string $units */
		/** @var string $object_name */
		/** @var string $taxonomy */
		extract( $this->query_vars );

		$this->results = array();
		$this->result_count = 0;
		$this->result = null;
		$this->current_result = -1;
		$this->units = $units;
		$this->max_km = 20000;
		$this->distance_factor = ( 'km' == $units ) ? 1 : self::MILES_PER_KILOMETER;
		$this->near_location = GeoMashupDB::blank_location( ARRAY_A );

		$geo_query_args = wp_array_slice_assoc(
			$this->query_vars,
			array( 'object_name', 'sort', 'exclude_object_ids', 'limit' )
		);

		if ( !empty( $near_lat ) and !empty( $near_lng ) ) {

			$this->near_location['lat'] = $near_lat;
			$this->near_location['lng'] = $near_lng;

		} else if ( !empty( $location_text ) ) {

			$geocode_text = empty( $geolocation ) ? $location_text : $geolocation;

			if ( !GeoMashupDB::geocode( $geocode_text, $this->near_location ) ) {
				// No search center was found, we can't continue
				return $this->results;
			}

		} else {

			// No coordinates to search near
			return $this->results;

		}

		$radius_km = $this->max_km;

		if ( !empty( $radius ) )
			$radius_km = abs( $radius ) / $this->distance_factor;

		$geo_query_args['radius_km'] = $radius_km;
		$geo_query_args['near_lat'] = $this->near_location['lat'];
		$geo_query_args['near_lng'] = $this->near_location['lng'];

		//Set tax_query
		if ( isset( $map_terms ) ) {

			if ( 'all' != $map_terms ) {

				$geo_query_args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
						'terms' => $map_terms,
						'field' => 'term_id',
					)
				);
			} else {
				$geo_query_args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
						'operator' => 'EXISTS'
					)
				);
			}
		}

        // Backward compatibility for categories
        if ( isset( $map_cat ) ) {
            $geo_query_args['map_cat'] = $map_cat;
        }

        // Set post_type
		if ( $object_name === 'post' && isset( $map_post_type ) ) {
			$geo_query_args['map_post_type'] = $map_post_type;
		}

		$geo_query_args = apply_filters( 'geo_mashup_search_query_args', $geo_query_args );

		$this->results = GeoMashupDB::get_object_locations( $geo_query_args );
		$this->result_count = count( $this->results );
		if ( $this->result_count > 0 )
			$this->max_km = $this->results[$this->result_count - 1]->distance_km;
		else
			$this->max_km = $radius_km;

		return $this->results;
	}

	/**
	 * WordPress filter to add geo mashup search results to page content
	 * when requested.
	 *
	 * @param string $template
	 * @return string Content including search results if requested.
	 */
	public function load_template( $template = 'search-results' ) {

		// Define variables for the template
		/** @var $object_name */
		/** @var $object_ids */
		/** @var $units */
		/** @var $location_text */
		/** @var $radius */
		/** @var $sort */
		extract( $this->query_vars );
		$search_text = $location_text;
		$distance_factor = $this->distance_factor;
		$near_location = $this->near_location;
		$result_count = $this->result_count;
		$geo_mashup_search = &$this;

		$approximate_zoom = absint( log( 10000 / $this->max_km, 2 ) );

		// Buffer output from the template
		$template = GeoMashup::locate_template( $template );

		// Load the template with local variables
		require( $template );
	}

	/**
	 * Whether there are more results to loop through.
	 *
	 * @return boolean True if there are more results, otherwise false.
	 */
	public function have_posts() {
		if ( $this->current_result + 1 < $this->result_count ) {
			return true;
		} elseif ( $this->current_result + 1 == $this->result_count and $this->result_count > 0 ) {
			wp_reset_postdata();
		}
		return false;
	}

	/**
	 * Get an array of the post IDs found.
	 *
	 * @return array Post IDs.
	 */
	public function get_the_IDs() {
		return wp_list_pluck( $this->results, 'object_id' );
	}

	/**
	 * Get a comma separated list of the post IDs found.
	 *
	 * @return string ID list.
	 */
	public function get_the_ID_list() {
		return implode( ',', $this->get_the_IDs() );
	}

	/**
	 * Get a comma separated list of the post IDs found.
	 *
	 * @return array|null User data if found.
	 */
	public function get_userdata() {
		$this->current_result++;
		$this->result = $this->results[$this->current_result];
		$user = null;
		if ( $this->result ) {
			$user = get_userdata( $this->result->object_id );
		}
		return $user;
	}

	/**
	 * Set up the the current post to use in the results loop.
	 */
	public function the_post() {
		global $post;
		$this->current_result++;
		$this->result = $this->results[$this->current_result];
		if ( $this->result ) {
			$post = get_post( $this->result->object_id );
			setup_postdata( $post );
		}
	}

	/**
	 * Display or retrieve the distance from the search point to the current result.
	 *
	 * @param string|array $args Tag arguments
	 * @return null|string Null on failure or display, string if echo is false.
	 */
	public function the_distance( $args = '' ) {

		if ( empty( $this->result ) )
			return null;

		$default_args = array(
			'decimal_places' => 2,
			'append_units' => true,
			'echo' => true
		);
		$args = wp_parse_args( $args, $default_args );
		/** @var $decimal_places */
		/** @var $append_units */
		/** @var $echo */
		extract( $args );
		$factor = ( 'km' == $this->units ) ? 1 : self::MILES_PER_KILOMETER;
		$distance = round( $this->result->distance_km * $factor, $decimal_places );
		$distance = number_format_i18n( $distance, $decimal_places );
		if ( $append_units )
			$distance .= ' ' . $this->units;
		if ( $echo )
			echo $distance;
		else
			return $distance;
	}

	/**
	 * Add a script to modify form behavior.
	 *
	 * @param string $handle Handle the script was registered with
	 */
	public function enqueue_script( $handle ) {
		// As of WP 3.3 we can enqueue scripts any time
		wp_enqueue_script( $handle );
	}

}

// Add search handling hooks
add_action( 'init', array( 'GeoMashupSearchHandling', 'action_init' ) );
add_action( 'widgets_init', array( 'GeoMashupSearchHandling', 'action_widgets_init' ) );

/**
 * Geo Mashup Search Handling class.
 *
 * Catch and handle requests from a search widget.
 *
 * @package GeoMashup
 * @static
 **/
class GeoMashupSearchHandling {
	/**
	 * No constructor - static class
	 **/
	private function __construct() {
	}

	/**
	 * Add hooks needed for the current request.
	 */
	public static function action_init() {
		if ( isset( $_POST['location_text'] ) ) {
			// Add search results to page content
			add_filter( 'the_content', array( __CLASS__, 'filter_the_content' ) );
		}
		add_action( 'geo_mashup_render_map', array( __CLASS__, 'action_geo_mashup_render_map' ) );
	}

	/**
	 * Register the search widget.
	 */
	public static function action_widgets_init() {
		register_widget( 'GeoMashupSearchWidget' );
	}

	/**
	 * Queue custom script for the results map.
	 */
	public static function action_geo_mashup_render_map() {
		if ( 'search-results-map' == GeoMashupRenderMap::map_property( 'name' ) ) {
			// Custom javascript for optional use in template
			GeoMashup::register_script( 'geo-mashup-search-results', 'js/search-results.js', array( 'geo-mashup' ), GEO_MASHUP_VERSION, true );
			GeoMashupRenderMap::enqueue_script( 'geo-mashup-search-results' );
		}
	}

	/**
	 * Display geo search results when detected.
	 *
	 * @since 1.5.0
	 * @param string $content
	 * @return string
	 */
	public static function filter_the_content( $content ) {

		// Ignore unless a search was posted for this page
		$ignore = true;

		// Older search forms did not include result page id, but always location text
		if ( !isset( $_POST['results_page_id'] ) and isset( $_POST['location_text'] ) ) {
			$ignore = false;
		}

		if ( isset( $_POST['results_page_id'] ) ) {
			$results_page_id = apply_filters( 'geo_mashup_results_page_id', intval( $_POST['results_page_id'] ) );
			$ignore = ( $results_page_id != get_the_ID() );
		}

		if ( $ignore ) {
			return $content;
		}

		// Remove slashes added to form input
		$_POST = stripslashes_deep( $_POST );

		// Remove this filter to prevent recursion
		remove_filter( 'the_content', array( __CLASS__, 'filter_the_content' ) );

		$geo_search = new GeoMashupSearch( $_POST );

		// Buffer templated results and append to content
		ob_start();
		$geo_search->load_template( 'search-results' );
		$content .= ob_get_clean();

		// Add the filter back - it's possbible that content preprocessors will cause it to be run again
		add_filter( 'the_content', array( __CLASS__, 'filter_the_content' ) );

		return $content;
	}

}

/**
 * Class GeoMashupSearchWidget
 *
 * @since 1.5
 * @package GeoMashup
 */
class GeoMashupSearchWidget extends WP_Widget {

	// Construct Widget
	function __construct() {
		$default_options = array(
			'description' => __( 'Search content by Geo Mashup location.', 'GeoMashup' )
		);
		parent::__construct( false, __( 'Geo Mashup Search', 'GeoMashup' ), $default_options );
	}

	// Display Widget
	function widget( $args, $instance ) {

		GeoMashup::register_style( 'front-style-widget', 'css/front-widget.css', GEO_MASHUP_VERSION );
		wp_enqueue_style( 'front-style-widget' );

		// Arrange footer scripts
		GeoMashup::register_script( 'geo-mashup-search-form', 'js/search-form.js', array(), GEO_MASHUP_VERSION, true );
		wp_enqueue_script( 'geo-mashup-search-form' );

		if ( !empty( $instance['find_me_button'] ) ) {
			GeoMashup::register_script( 'geo-mashup-search-find-me', 'js/find-me.js', array( 'jquery' ), GEO_MASHUP_VERSION, true );
			wp_localize_script( 'geo-mashup-search-find-me', 'geo_mashup_search_find_me_env', array(
				'client_ip' => $_SERVER['REMOTE_ADDR'],
				'fail_message' => __( 'Couldn\'t find you...', 'GeoMashup' ),
				'my_location_message' => __( 'My Location', 'GeoMashup' ),
			) );
			wp_enqueue_script( 'geo-mashup-search-find-me' );
		}

		/** @var $before_widget */
		/** @var $after_widget */
		/** @var $before_title */
		/** @var $after_title */
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget . $before_title . $title . $after_title;
		$results_page_id = intval( $instance['results_page_id'] );
		if ( !$results_page_id ) {
			echo '<p class="error">';
			_e( 'No Geo Mashup Search result page found - check widget settings.', 'GeoMashup' );
			echo '</p>';
			return;
		}
		// Set up template variables 
		$widget = &$this;
		$action_url = get_permalink( $results_page_id );
		$object_name = $instance['object_name'];
		$categories = array();
		if ( !empty( $instance['categories'] ) && $instance['taxonomy'] !== 'select' ) {
			if ( 'all' != $instance['categories'] ) {
				$taxonomy_args = array(
					'taxonomy' => $this->get_default_value( $instance, 'taxonomy', 'category' ),
					'include' => $instance['categories'],
					'hide_empty' => false
				);
				$categories = get_terms( $taxonomy_args );
			} else {
				$categories = $instance['categories'];
			}
		}
		$radii = empty( $instance['radius_list'] ) ? array() : wp_parse_id_list( $instance['radius_list'] );

		// Load the template
		$template = GeoMashup::locate_template( 'search-form' );
		require( $template );

		echo $after_widget;
	}

	// Update Widget
	function update( $new_instance, $old_instance ) {
		global $geo_mashup_options;

		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['default_search_text'] = sanitize_text_field( $new_instance['default_search_text'] );
		$instance['taxonomy'] = sanitize_text_field( $new_instance['taxonomy'] );
		$instance['categories'] = sanitize_text_field( $new_instance['categories'] );
		$instance['object_name'] = in_array( $new_instance['object_name'], array_merge( array( 'any', 'post', 'user', 'comment' ), $geo_mashup_options->get( 'overall', 'located_post_types' ) ) ) ? $new_instance['object_name'] : 'post';
		$instance['units'] = in_array( $new_instance['units'], array( 'km', 'mi' ) ) ? $new_instance['units'] : 'km';
		$instance['radius_list'] = sanitize_text_field( $new_instance['radius_list'] );
		$instance['results_page_id'] = intval( $new_instance['results_page_id'] );
		$instance['find_me_button'] = sanitize_text_field( $new_instance['find_me_button'] );

		return $instance;
	}

	// Default value logic
	function get_default_value( $instance, $field, $fallback = '', $escape_callback = 'esc_attr' ) {
		if ( isset( $instance[$field] ) )
			$value = $instance[$field];
		else
			$value = $fallback;

		if ( function_exists( $escape_callback ) )
			$value = call_user_func( $escape_callback, $value );

		return $value;
	}

	// Display Widget Control
	function form( $instance ) {
		global $geo_mashup_options;
		$pages = get_pages();
		?>
		<div class="GeoMashup-search <?php echo $this->id; ?>">
			<p>
				<label
					for="<?php echo $this->get_field_id( 'title' ); ?>"
					title="<?php _e( 'Widget heading, leave blank to omit.', 'GeoMashup' ); ?>">
					<?php _e( 'Title:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<input class="widefat" type="text"
					       id="<?php echo $this->get_field_id( 'title' ); ?>"
					       name="<?php echo $this->get_field_name( 'title' ); ?>"
					       value="<?php echo $this->get_default_value( $instance, 'title' ); ?>"/>
				</label>
			</p>
			<p>
				<label
					for="<?php echo $this->get_field_id( 'default_search_text' ); ?>"
					title="<?php _e( 'Default text in the search text box for use as a prompt, leave blank to omit.', 'GeoMashup' ); ?>">
					<?php _e( 'Default Search Text:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<input class="widefat" type="text"
					       id="<?php echo $this->get_field_id( 'default_search_text' ); ?>"
					       name="<?php echo $this->get_field_name( 'default_search_text' ); ?>"
					       value="<?php echo $this->get_default_value( $instance, 'default_search_text', __( 'city, state or zip', 'GeoMashup' ) ); ?>"/>
				</label>
			</p>
			<p>
				<label
					for="<?php echo $this->get_field_id( 'find_me_button' ); ?>"
					title="<?php _e( 'Text for the user locate button, leave blank to omit.', 'GeoMashup' ); ?>">
					<?php _e( 'Find Me Button:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<input class="widefat" type="text"
					       id="<?php echo $this->get_field_id( 'find_me_button' ); ?>"
					       name="<?php echo $this->get_field_name( 'find_me_button' ); ?>"
					       value="<?php echo $this->get_default_value( $instance, 'find_me_button', __( 'Find Me', 'GeoMashup' ) ); ?>"/>
				</label>
			</p>
			<p class="object-name">
				<label for="<?php echo $this->get_field_id( 'object_name' ); ?>">
					<?php _e( 'What to search:', 'GeoMashup' ); ?>
					<select
						id="<?php echo $this->get_field_id( 'object_name' ); ?>"
						class="widefat"
						name="<?php echo $this->get_field_name( 'object_name' ); ?>">
						<?php

						$located_post_types = $geo_mashup_options->get( 'overall', 'located_post_types' );

						if ( !empty( $located_post_types ) ) : ?>
							<option
								value="any" <?php echo 'any' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
								<?php _e( 'All post types', 'GeoMashup' ) ?>
							</option>
							<?php
							foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $post_type ) : ?>
								<?php
								if ( in_array( $post_type->name, $geo_mashup_options->get( 'overall', 'located_post_types' ) ) ) { ?>
									<option
										value="<?php echo $post_type->name; ?>" <?php echo $post_type->name == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
										<?php echo $post_type->labels->name; ?>
									</option>
								<?php }
							endforeach;
						endif; ?>
						<option
							value="user"<?php echo 'user' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'users', 'GeoMashup' ); ?>
						</option>
						<option
							value="comment"<?php echo 'comment' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'comments', 'GeoMashup' ); ?>
						</option>
					</select>
				</label>
			</p>
			<?php
			$include_taxonomies = $geo_mashup_options->get( 'overall', 'include_taxonomies' );

			if ( !empty( $include_taxonomies ) and !defined( 'GEO_MASHUP_DISABLE_CATEGORIES' ) ) : 
				
				$taxonomy_default_value = $this->get_default_value( $instance, 'object_name' ) == 'post' ? 'category' : 'select'; ?>

				<span class="taxonomy_section">
				<p>
				<label class="taxonomy_select"
				       title="<?php _e( 'To disable taxonomies and terms on search widget leave select', 'GeoMashup' ); ?>"
				       for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomies Menu:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" class="widefat"
					        name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
								<option
									value="select" <?php echo 'select' == $this->get_default_value( $instance, 'taxonomy', $taxonomy_default_value ) ? ' selected="selected"' : ''; ?>>
									<?php echo _e( 'Select', 'GeoMashup' ); ?>
								</option>
						<?php foreach ( $include_taxonomies as $include_taxonomy ) :
							$taxonomy_object = get_taxonomy( $include_taxonomy );
							$taxonomies[$taxonomy_object->name] = $taxonomy_object->label;
							?>
							<option
								value="<?php echo $taxonomy_object->name; ?>" <?php echo $taxonomy_object->name == $this->get_default_value( $instance, 'taxonomy', $taxonomy_default_value ) ? ' selected="selected"' : ''; ?>>
									<?php echo $taxonomy_object->label; ?>
								</option>
						<?php endforeach; // included taxonomy ?>
					</select>
					
				</label>
				</p>
				<fieldset id="<?php echo $this->get_field_id( 'tax-terms' ); ?>"
				          style="border: 1px #e5e5e5 solid; padding: 5px;">
				<legend><?php _e( 'Terms Menu:', 'GeoMashup' ); ?></legend>

					<?php $multi_values = !is_array( $this->get_default_value( $instance, 'categories' ) ) ? explode( ',', $this->get_default_value( $instance, 'categories' ) ) : $this->get_default_value( $instance, 'categories' ); ?>

					<fieldset class="hide-if-no-js select" style="display: none; margin: 10px 20px;">
						<legend><?php echo _e( 'First, choose Taxonomy', 'Geomashup' ) ?></legend>
					</fieldset>

					<?php foreach ( $taxonomies as $key => $value ) :
						$terms = get_terms( $key, array( 'hide_empty' => false ) ); ?>
						<fieldset id="<?php echo $this->get_field_id( 'terms' ); ?>-<?php echo $key; ?>"
						          class="hide-if-no-js <?php echo $key; ?>" style="margin: 10px 20px;">
							<legend><?php echo $value; ?></legend>
								<br>
								<input class="all-checkbox" id="all" type="checkbox"
								       value="all" <?php checked( in_array( 'all', $multi_values ) ); ?> />
						        <label for="all"><?php _e( 'All', 'GeoMashup' ); ?></label><br>
							<p>
							<?php foreach ( $terms as $term ) : ?>
								<input id="<?php echo $key . esc_attr( $term->term_id ); ?>" class="checkbox"
								       type="checkbox"
								       value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $multi_values ) ); ?> />
								<label
									for="<?php echo $key . esc_attr( $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></label>
								<br>
							<?php endforeach // term
							?>
							</p>
						</fieldset>
					<?php endforeach; // taxonomy ?>

					<label class="hide-if-js"
					       for="<?php echo $this->get_field_id( 'categories' ); ?>"><?php _e( 'Taxonomies Terms dropdown contents. Blank to omit, \'all\' for all post categories, or comma separated category IDs to include.', 'GeoMashup' ); ?>
						"</label>
					<input class="hide-if-js widefat"
					       id="<?php echo $this->get_field_id( 'categories' ); ?>"
					       name="<?php echo $this->get_field_name( 'categories' ); ?>"
					       type="text"
					       value="<?php echo $this->get_default_value( $instance, 'categories' ); ?>"/>
					
				</fieldset><!-- #widget-geomashupsearchwidget-__i__-tax-terms -->
			</span>
			<?php endif; // include_taxonomies ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'units' ); ?>">
					<?php _e( 'Units:', 'GeoMashup' ); ?>
					<select id="<?php echo $this->get_field_id( 'units' ); ?>" class="widefat"
					        name="<?php echo $this->get_field_name( 'units' ); ?>">
						<option
							value="mi"<?php echo 'mi' == $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'miles', 'GeoMashup' ); ?>
						</option>
						<option
							value="km"<?php echo 'km' == $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'kilometers', 'GeoMashup' ); ?>
						</option>
					</select>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'radius_list' ); ?>"
				       title="<?php _e( 'Radius dropdown contents. Blank to omit, or comma separated numeric distances in selected units.', 'GeoMashup' ); ?>">
					<?php _e( 'Radius Menu:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<input class="widefat"
					       id="<?php echo $this->get_field_id( 'radius_list' ); ?>"
					       name="<?php echo $this->get_field_name( 'radius_list' ); ?>"
					       type="text"
					       value="<?php echo $this->get_default_value( $instance, 'radius_list' ); ?>"/>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'results_page_id' ); ?>"
				       title="<?php _e( 'The page where search results should be displayed.', 'GeoMashup' ); ?>">
					<?php _e( 'Results Page:', 'GeoMashup' ); ?>
					<?php
					wp_dropdown_pages( array(
						'id' => $this->get_field_id( 'results_page_id' ),
						'class' => 'widefat',
						'name' => $this->get_field_name( 'results_page_id' ),
						'selected' => $this->get_default_value( $instance, 'results_page_id' ),
					) );
					?>
				</label>
			</p>
		</div><!-- #GeoMashup -->
		<?php
		// Only, if widget have id
		if ( $this->number !== "__i__" ) { ?>
			<script type="text/javascript">
				(function ( $ ) {
					/**
					 * Document ready (jQuery)
					 */
					$( document ).ready(
						function () {

							// Off script, when is customizer. On the live preview, works only script widget.js
							if ( $( 'body.widgets-php' ).length ) {

								var widget_id = $( '.<?php echo $this->id; ?>' ),
									select_obj_name = widget_id.find( 'p.object-name select' ),
									select_tax = widget_id.find( 'select#<?php echo $this->get_field_id( 'taxonomy' ); ?>' );

								// Check Include Taxonomies in Geo Mashup Options
								if ( select_tax.length ) {

									var fieldset_tems = widget_id.find( '#<?php echo $this->get_field_id( 'tax-terms' ); ?>' ),
										termlist = fieldset_tems.find( 'fieldset' ),
										hidden_input = fieldset_tems.find( 'input.hide-if-js' );

									// Reset terms
									function reset_terms() {

										// Reset terms
										termlist.hide();
										checkbox_array = [];
										hidden_input.val( '' );

										fieldset_tems.find( 'input:checkbox' ).each(
											function () {
												$( this ).prop( "checked", false );
											}
										);

									}

									// function for object_name
									function obj_name_action( select, action ) {
										
										// only if action changed
										if (action == 'change' ) {
											
											reset_terms();
											
											if (select.val() == 'post') {
												select_tax.val( 'category' ).change();
											} else {
												select_tax.val( 'select' ).change();
											}
										}
										
										// always, when it is added widget and action changed
										if ( select.val() == 'user' || select.val() == 'comment' ) {
											
											
											widget_id.find( 'span.taxonomy_section' ).hide();
											reset_terms();
											select_tax.val( 'select' ).change();

										} else {
											fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:selected' ).val() ).show();
											widget_id.find( 'span.taxonomy_section' ).show();
										}
									}

									/**
									 * Star action for widget form
									 */

									// Action for object_name
									obj_name_action( select_obj_name, 'add');

									// hide all terms lists
									termlist.hide();

									if ( hidden_input.val().length !== 0 || select_tax.val() !== 'select' ) {

										var checkbox_array = hidden_input.val().split( "," );
										// Show only list of selected option taxonomy
										fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:selected' ).val() ).show();

									} else {

										var checkbox_array = [];
										// Show only list of first option taxonomy
										fieldset_tems.find( 'fieldset.' + select_tax.find( 'option:first-child' ).val() ).show();
									}

									/**
									 * Change action for widget form
									 */

									// Action for object_name
									select_obj_name.change(
										function () {
											obj_name_action( $( this ),'change');
										}
									);

									// Action for Taxonomy Select
									select_tax.change(
										function () {
											// Reset terms
											reset_terms();
											// Show terms list of select taxonomy
											fieldset_tems.find( 'fieldset.' + $( this ).val() ).show();
										}
									);

									// Action for Terms Checkbox
									fieldset_tems.find( 'input:checkbox' ).change(
										function () {


											input = $( this );

											if ( input.is( ':checked' ) ) {
												checkbox_array.push( input.val() );
											} else {
												checkbox_array.splice( $.inArray( input.val(), checkbox_array ), 1 );
											}

											hidden_input.val( checkbox_array.join( ',' ) );


										}
									);
								}// if select_tax.length
							}// if body.widgets-php
						}
					);
				})( jQuery );
			</script>
			<?php
		}
	}

}


