<?php
/**
 * Geo Mashup Search Module
 *
 * @package GeoMashup
 */

/**
 * Singleton houses Geo Mashup Search.
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
	private $units;
	private $object_name;

	/**
	 * Static instance access.
	 * @static
	 */
	public static function get_instance() {
		static $the_instance = null;
		if ( is_null( $the_instance ) ) {
			$the_instance = new GeoMashupSearch();
		}
		return $the_instance;
	}

	/**
	 * Singleton constructor executed at load time
	 */
	public function __construct() {

		// Back compat 
		$this->url_path = GEO_MASHUP_URL_PATH;

		// Initialize
		add_action( 'init', array( $this, 'action_init' ) );

		// Add the search widget
		add_action( 'widgets_init', array( $this, 'action_widgets_init' ) );
	}

	/**
	 * Add hooks needed for the current request.
	 */
	public function action_init() {
		if ( isset( $_REQUEST['location_text'] ) ) {
			// Add search results to page content
			add_filter( 'the_content', array( $this, 'filter_the_content' ) );
		}
		add_action( 'geo_mashup_render_map', array( $this, 'action_geo_mashup_render_map' ) );
	}

	/**
	 * Register the search widget.
	 */
	public function action_widgets_init() {
		register_widget( 'GeoMashupSearchWidget' );
	}

	/**
	 * Queue custom script for the results map.
	 */
	public function action_geo_mashup_render_map() {
		if ( 'search-results-map' == GeoMashupRenderMap::map_property( 'name' ) ) {
			// Custom javascript for optional use in template
			GeoMashup::register_script( 'geo-mashup-search-results', 'js/search-results.js', array( 'geo-mashup' ), GEO_MASHUP_VERSION, true );
			GeoMashupRenderMap::enqueue_script( 'geo-mashup-search-results' );
		}
	}

	/**
	 * WordPress filter to add geo mashup search results to page content
	 * when requested.
	 *
	 * @uses apply_filters() geo_mashup_search_query_args Filter the geo query arguments.
	 * @param string $content
	 * @return string Content including search results if requested.
	 */
	public function filter_the_content( $content ) {

		if ( !isset( $_REQUEST['location_text'] ) )
			return $content;

		// Remove this filter to prevent recursion
		remove_filter( 'the_content', array( $this, 'filter_the_content' ) );

		$this->results = array( );
		$this->result_count = 0;
		$this->result = null;
		$this->current_result = -1;
		$this->units = isset( $_REQUEST['units'] ) ? $_REQUEST['units'] : 'km';

		$this->object_name = (isset( $_REQUEST['object_name'] ) && in_array( $_REQUEST['object_name'], array( 'post', 'user', 'comment' ) ) ) ? $_REQUEST['object_name'] : 'post';

		// Define variables for the template
		$search_text = isset( $_REQUEST['location_text'] ) ? $_REQUEST['location_text'] : '';
		$units = $this->units; // Put $units in template scope
		$object_name = $this->object_name;
		$radius = isset( $_REQUEST['radius'] ) ? $_REQUEST['radius'] : '';
		$distance_factor = ( 'km' == $this->units ) ? 1 : self::MILES_PER_KILOMETER;
		$max_km = 20000;
		$geo_mashup_search = &$this;

		if ( !empty( $_REQUEST['location_text'] ) ) {

			$near_location = GeoMashupDB::blank_location( ARRAY_A );
			$geocode_text = empty( $_REQUEST['geolocation'] ) ? $_REQUEST['location_text'] : $_REQUEST['geolocation'];

			if ( GeoMashupDB::geocode( $geocode_text, $near_location ) ) {

				// A search center was found, we can continue
				$geo_query_args = array(
					'object_name' => $object_name,
					'near_lat' => $near_location['lat'],
					'near_lng' => $near_location['lng'],
					'sort' => 'distance_km ASC'
				);
				$radius_km = $max_km;

				if ( isset( $_REQUEST['radius'] ) )
					$radius_km = absint( $_REQUEST['radius'] ) / $distance_factor;

				$geo_query_args['radius_km'] = $radius_km;

				if ( isset( $_REQUEST['map_cat'] ) )
					$geo_query_args['map_cat'] = $_REQUEST['map_cat'];

				$geo_query_args = apply_filters( 'geo_mashup_search_query_args', $geo_query_args );

				$this->results = GeoMashupDB::get_object_locations( $geo_query_args );
				$this->result_count = count( $this->results );
				if ( $this->result_count > 0 )
					$max_km = $this->results[$this->result_count - 1]->distance_km;
				else
					$max_km = $radius_km;
			}
		}

		$approximate_zoom = absint( log( 10000 / $max_km, 2 ) );

		// Buffer output from the template
		$template = GeoMashup::locate_template( 'search-results' );
		ob_start();
		require( $template );
		$content .= ob_get_clean();

		// This filter shouldn't run more than once per request, so don't bother adding it again

		return $content;
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
	 * @return string ID list.
	 */
	public function get_userdata() {
		$this->current_result++;
		$this->result = $this->results[$this->current_result];
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
		extract( $args );
		$factor = ( 'km' == $this->units ) ? 1 : self::MILES_PER_KILOMETER;
		$distance = round( $this->result->distance_km * $factor, $decimal_places );
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

// Instantiate our singleton
GeoMashupSearch::get_instance();

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
		// Arrange footer scripts
		$geo_mashup_search = GeoMashupSearch::get_instance();

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

		add_action( 'wp_footer', array( $geo_mashup_search, 'action_wp_footer' ) );

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
		$categories = array( );
		if ( !empty( $instance['categories'] ) ) {
			$category_args = '';
			if ( 'all' != $instance['categories'] ) {
				$category_args = 'include=' . $instance['categories'];
			}
			$categories = get_categories( $category_args );
		}
		$radii = empty( $instance['radius_list'] ) ? array( ) : wp_parse_id_list( $instance['radius_list'] );

		// Load the template
		$template = GeoMashup::locate_template( 'search-form' );
		require( $template );

		echo $after_widget;
	}

	// Update Widget
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['default_search_text'] = sanitize_text_field( $new_instance['default_search_text'] );
		$instance['categories'] = sanitize_text_field( $new_instance['categories'] );
		$instance['object_name'] = in_array( $new_instance['object_name'], array( 'post', 'user', 'comment' ) ) ? $new_instance['object_name'] : 'post';
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
		$categories = get_categories( 'hide_empty=0' );
		$pages = get_pages();
		?>
		<script type="text/javascript">
						
			if ( typeof jQuery !== 'undefined' ) {
				jQuery(document).ready(function() { 
					function check_content_type(select) {
						if (select.val() != 'post'){
							jQuery("input#<?php echo $this->get_field_id( 'categories' ); ?>").parents('p:first').hide();
						}else{
							jQuery("input#<?php echo $this->get_field_id( 'categories' ); ?>").parents('p:first').show();
						}
					}
					check_content_type( jQuery("select#<?php echo $this->get_field_id( 'object_name' ); ?>") );
								
					jQuery("select#<?php echo $this->get_field_id( 'object_name' ); ?>").change(function() {
						check_content_type( jQuery(this) );
					});
				});
			}
		</script>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"
					 title="<?php _e( 'Widget heading, leave blank to omit.', 'GeoMashup' ); ?>">
		<?php _e( 'Title:', 'GeoMashup' ); ?>
				<span class="help-tip">?</span>
				<input class="widefat"
						 id="<?php echo $this->get_field_id( 'title' ); ?>"
						 name="<?php echo $this->get_field_name( 'title' ); ?>"
						 type="text"
						 value="<?php echo $this->get_default_value( $instance, 'title' ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'default_search_text' ); ?>"
					 title="<?php _e( 'Default text in the search text box for use as a prompt, leave blank to omit.', 'GeoMashup' ); ?>">
		<?php _e( 'Default Search Text:', 'GeoMashup' ); ?>
				<span class="help-tip">?</span>
				<input class="widefat"
						 id="<?php echo $this->get_field_id( 'default_search_text' ); ?>"
						 name="<?php echo $this->get_field_name( 'default_search_text' ); ?>"
						 type="text"
						 value="<?php echo $this->get_default_value( $instance, 'default_search_text', __( 'city, state or zip', 'GeoMashup' ) ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'find_me_button' ); ?>"
					 title="<?php _e( 'Text for the user locate button, leave blank to omit.', 'GeoMashup' ); ?>">
		<?php _e( 'Find Me Button:', 'GeoMashup' ); ?>
				<span class="help-tip">?</span>
				<input class="widefat"
						 id="<?php echo $this->get_field_id( 'find_me_button' ); ?>"
						 name="<?php echo $this->get_field_name( 'find_me_button' ); ?>"
						 type="text"
						 value="<?php echo $this->get_default_value( $instance, 'find_me_button', __( 'Find Me', 'GeoMashup' ) ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'object_name' ); ?>">
		<?php _e( 'What to search:', 'GeoMashup' ); ?>
				<select id="<?php echo $this->get_field_id( 'object_name' ); ?>" name="<?php echo $this->get_field_name( 'object_name' ); ?>">
					<option value="post"<?php echo 'post' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
		<?php _e( 'posts', 'GeoMashup' ); ?>
					</option>
					<option value="user"<?php echo 'user' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
		<?php _e( 'users', 'GeoMashup' ); ?>
					</option>
					<option value="comment"<?php echo 'comment' == $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
		<?php _e( 'comments', 'GeoMashup' ); ?>
					</option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories' ); ?>"
					 title="<?php _e( 'Category dropdown contents. Blank to omit, \'all\' for all post categories, or comma separated category IDs to include.', 'GeoMashup' ); ?>">
		<?php _e( 'Category Menu:', 'GeoMashup' ); ?>
				<span class="help-tip">?</span>
				<input class="widefat"
						 id="<?php echo $this->get_field_id( 'categories' ); ?>"
						 name="<?php echo $this->get_field_name( 'categories' ); ?>"
						 type="text"
						 value="<?php echo $this->get_default_value( $instance, 'categories' ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'units' ); ?>">
		<?php _e( 'Units:', 'GeoMashup' ); ?>
				<select id="<?php echo $this->get_field_id( 'units' ); ?>" name="<?php echo $this->get_field_name( 'units' ); ?>">
					<option value="mi"<?php echo 'mi' == $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
		<?php _e( 'miles', 'GeoMashup' ); ?>
					</option>
					<option value="km"<?php echo 'km' == $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
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
						 value="<?php echo $this->get_default_value( $instance, 'radius_list' ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'results_page_id' ); ?>"
					 title="<?php _e( 'The page where search results should be displayed.', 'GeoMashup' ); ?>">
		<?php _e( 'Results Page:', 'GeoMashup' ); ?>
				<select id="<?php echo $this->get_field_id( 'results_page_id' ); ?>" name="<?php echo $this->get_field_name( 'results_page_id' ); ?>">
						 <?php foreach ( $pages as $page ) : ?>
						<option value="<?php echo $page->ID; ?>"<?php echo $page->ID == $this->get_default_value( $instance, 'results_page_id' ) ? ' selected="selected"' : ''; ?>>
						<?php echo $page->post_name; ?>
						</option>
						<?php endforeach; ?>
				</select>
			</label>
		</p>
		<?php
	}

}

