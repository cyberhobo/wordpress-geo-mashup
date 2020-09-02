<?php

namespace GeoMashup\Widgets;

use GeoMashup;
use WP_Widget;

class Search extends WP_Widget {

	// Construct Widget
	public function __construct() {
		$widget_options = array(
            'classname' => 'geomashupsearchwidget',
			'description' => __( 'Search content by Geo Mashup location.', 'GeoMashup' )
		);
		parent::__construct(
		        'geomashupsearchwidget',
                __( 'Geo Mashup Search', 'GeoMashup' ),
                $widget_options
        );
	}

	// Display Widget
	public function widget( $args, $instance ) {
	    global $geo_mashup_options;

		GeoMashup::register_style( 'front-style-widget', 'css/front-widget.css', GEO_MASHUP_VERSION );
		wp_enqueue_style( 'front-style-widget' );

		// Arrange footer scripts
		GeoMashup::register_script( 'geo-mashup-search-form', 'js/search-form.js', array(), GEO_MASHUP_VERSION, true );
		wp_enqueue_script( 'geo-mashup-search-form' );

		if ( ! empty( $instance['find_me_button'] ) ) {
			GeoMashup::register_script( 'geo-mashup-search-find-me', 'js/find-me.js', array( 'jquery' ), GEO_MASHUP_VERSION, true );
			wp_localize_script( 'geo-mashup-search-find-me', 'geo_mashup_search_find_me_env', array(
				'client_ip'           => $_SERVER['REMOTE_ADDR'],
				'fail_message'        => __( 'Couldn\'t find you...', 'GeoMashup' ),
				'my_location_message' => __( 'My Location', 'GeoMashup' ),
				'geonames_username' => $geo_mashup_options->get('overall', 'geonames_username'),
			) );
			wp_enqueue_script( 'geo-mashup-search-find-me' );
		}

		/** @var $before_widget */
		/** @var $after_widget */
		/** @var $before_title */
		/** @var $after_title */
		extract( $args, EXTR_OVERWRITE );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget . $before_title . $title . $after_title;
		$results_page_id = (int) $instance['results_page_id'];
		if ( ! $results_page_id ) {
			echo '<p class="error">';
			_e( 'No Geo Mashup Search result page found - check widget settings.', 'GeoMashup' );
			echo '</p>';

			return;
		}
		// Set up template variables
		$widget      = &$this;
		$action_url  = get_permalink( $results_page_id );
		$object_name = $instance['object_name'];
		$categories  = array();
		if ( ! empty( $instance['categories'] ) && $instance['taxonomy'] !== 'select' ) {
			if ( 'all' !== $instance['categories'] ) {
				$taxonomy_args = array(
					'taxonomy'   => $this->get_default_value( $instance, 'taxonomy', 'category' ),
					'include'    => $instance['categories'],
					'hide_empty' => false
				);
				$categories    = get_terms( $taxonomy_args );
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

		$instance                        = $old_instance;
		$instance['title']               = sanitize_text_field( $new_instance['title'] );
		$instance['default_search_text'] = sanitize_text_field( $new_instance['default_search_text'] );
		$instance['taxonomy']            = sanitize_text_field( $new_instance['taxonomy'] );
		$instance['categories']          = sanitize_text_field( $new_instance['categories'] );
		$instance['object_name']         = in_array( $new_instance['object_name'], array_merge( array(
			'any',
			'post',
			'user',
			'comment'
		), $geo_mashup_options->get( 'overall', 'located_post_types' ) ), false ) ? $new_instance['object_name'] : 'post';
		$instance['units']               = in_array( $new_instance['units'], array(
			'km',
			'mi',
            'nm'
		) ) ? $new_instance['units'] : 'km';
		$instance['radius_list']         = sanitize_text_field( $new_instance['radius_list'] );
		$instance['results_page_id']     = intval( $new_instance['results_page_id'] );
		$instance['find_me_button']      = sanitize_text_field( $new_instance['find_me_button'] );

		return $instance;
	}

	// Default value logic
	public function get_default_value( $instance, $field, $fallback = '', $escape_callback = 'esc_attr' ) {
		if ( isset( $instance[ $field ] ) ) {
			$value = $instance[ $field ];
		} else {
			$value = $fallback;
		}

		if ( function_exists( $escape_callback ) ) {
			$value = $escape_callback( $value );
		}

		return $value;
	}

	// Display Widget Control
	public function form( $instance ) {
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

						if ( ! empty( $located_post_types ) ) : ?>
                            <option
                                    value="any" <?php echo 'any' === $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
								<?php _e( 'All post types', 'GeoMashup' ) ?>
                            </option>
							<?php
							foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $post_type ) : ?>
								<?php
								if ( in_array( $post_type->name, $geo_mashup_options->get( 'overall', 'located_post_types' ), false ) ) { ?>
                                    <option
                                            value="<?php echo $post_type->name; ?>" <?php echo $post_type->name === $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
										<?php echo $post_type->labels->name; ?>
                                    </option>
								<?php }
							endforeach;
						endif; ?>
                        <option
                                value="user"<?php echo 'user' === $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'users', 'GeoMashup' ); ?>
                        </option>
                        <option
                                value="comment"<?php echo 'comment' === $this->get_default_value( $instance, 'object_name' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'comments', 'GeoMashup' ); ?>
                        </option>
                    </select>
                </label>
            </p>
			<?php
			$include_taxonomies         = $geo_mashup_options->get( 'overall', 'include_taxonomies' );

			if ( ! empty( $include_taxonomies ) && ! defined( 'GEO_MASHUP_DISABLE_CATEGORIES' ) ) :
				$taxonomies = [];
				$taxonomy_default_value = $this->get_default_value( $instance, 'object_name' ) === 'post' ? 'category' : 'select'; ?>

                <span class="taxonomy_section">
				<p>
				<label class="taxonomy_select"
                       title="<?php _e( 'To disable taxonomies and terms on search widget leave select', 'GeoMashup' ); ?>"
                       for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomies Menu:', 'GeoMashup' ); ?>
					<span class="help-tip">?</span>
					<select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" class="widefat"
                            name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
								<option
                                        value="select" <?php echo 'select' === $this->get_default_value( $instance, 'taxonomy', $taxonomy_default_value ) ? ' selected="selected"' : ''; ?>>
									<?php echo _e( 'Select', 'GeoMashup' ); ?>
								</option>
						<?php foreach ( $include_taxonomies as $include_taxonomy ) :
							$taxonomy_object = get_taxonomy( $include_taxonomy );
							$taxonomies[ $taxonomy_object->name ] = $taxonomy_object->label;
							?>
                            <option
                                    value="<?php echo $taxonomy_object->name; ?>" <?php echo $taxonomy_object->name === $this->get_default_value( $instance, 'taxonomy', $taxonomy_default_value ) ? ' selected="selected"' : ''; ?>>
									<?php echo $taxonomy_object->label; ?>
								</option>
						<?php endforeach; // included taxonomy
						?>
					</select>

				</label>
				</p>
				<fieldset id="<?php echo $this->get_field_id( 'tax-terms' ); ?>"
                          style="border: 1px #e5e5e5 solid; padding: 5px;">
				<legend><?php _e( 'Terms Menu:', 'GeoMashup' ); ?></legend>

					<?php $multi_values = ! is_array( $this->get_default_value( $instance, 'categories' ) ) ? explode( ',', $this->get_default_value( $instance, 'categories' ) ) : $this->get_default_value( $instance, 'categories' ); ?>

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
                                       value="all" <?php checked( in_array( 'all', $multi_values, false ) ); ?> />
						        <label for="all"><?php _e( 'All', 'GeoMashup' ); ?></label><br>
							<p>
							<?php foreach ( $terms as $term ) : ?>
                                <input id="<?php echo $key . esc_attr( $term->term_id ); ?>" class="checkbox"
                                       type="checkbox"
                                       value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $multi_values, false ) ); ?> />
                                <label
                                        for="<?php echo $key . esc_attr( $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></label>
                                <br>
							<?php endforeach // term
							?>
							</p>
						</fieldset>
					<?php endforeach; // taxonomy
					?>

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
                                value="mi"<?php echo 'mi' === $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'miles', 'GeoMashup' ); ?>
                        </option>
                        <option
                                value="km"<?php echo 'km' === $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
							<?php _e( 'kilometers', 'GeoMashup' ); ?>
                        </option>
                        <option
                                value="nm"<?php echo 'nm' === $this->get_default_value( $instance, 'units' ) ? ' selected="selected"' : ''; ?>>
		                    <?php _e( 'nautical miles', 'GeoMashup' ); ?>
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
						'id'       => $this->get_field_id( 'results_page_id' ),
						'class'    => 'widefat',
						'name'     => $this->get_field_name( 'results_page_id' ),
						'selected' => $this->get_default_value( $instance, 'results_page_id' ),
					) );
					?>
                </label>
            </p>
        </div><!-- #GeoMashup -->
		<?php
		// Only, if widget have id
		if ( $this->number !== '__i__' ) { ?>
            <script type="text/javascript">
              (function ($) {
                /**
                 * Document ready (jQuery)
                 */
                $(document).ready(
                  function () {

                    // Off script, when is customizer. On the live preview, works only script widget.js
                    if ($('body.widgets-php').length) {

                      let widget_id = $('.<?php echo $this->id; ?>'),
                        select_obj_name = widget_id.find('p.object-name select'),
                        select_tax = widget_id.find('select#<?php echo $this->get_field_id( 'taxonomy' ); ?>')

                      // Check Include Taxonomies in Geo Mashup Options
                      if (select_tax.length) {

                        let fieldset_terms = widget_id.find('#<?php echo $this->get_field_id( 'tax-terms' ); ?>'),
                          term_list = fieldset_terms.find('fieldset'),
                          hidden_input = fieldset_terms.find('input.hide-if-js')

                        // Reset terms
                        function reset_terms () {

                          // Reset terms
                          term_list.hide()
                          checkbox_array = []
                          hidden_input.val('')

                          fieldset_terms.find('input:checkbox').each(
                            function () {
                              $(this).prop('checked', false)
                            }
                          )

                        }

                        // function for object_name
                        function obj_name_action (select, action) {

                          // only if action changed
                          if (action === 'change') {

                            reset_terms()

                            if (select.val() === 'post') {
                              select_tax.val('category').change()
                            } else {
                              select_tax.val('select').change()
                            }
                          }

                          // always, when it is added widget and action changed
                          if (select.val() === 'user' || select.val() === 'comment') {

                            widget_id.find('span.taxonomy_section').hide()
                            reset_terms()
                            select_tax.val('select').change()

                          } else {
                            fieldset_terms.find('fieldset.' + select_tax.find('option:selected').val()).show()
                            widget_id.find('span.taxonomy_section').show()
                          }
                        }

                        /**
                         * Star action for widget form
                         */

                        // Action for object_name
                        obj_name_action(select_obj_name, 'add')

                        // hide all terms lists
                        term_list.hide()

                        let checkbox_array = [];
                        if (hidden_input.val().length !== 0 || select_tax.val() !== 'select') {

                          checkbox_array = hidden_input.val().split(',')
                          // Show only list of selected option taxonomy
                          fieldset_terms.find('fieldset.' + select_tax.find('option:selected').val()).show()

                        } else {

                          // Show only list of first option taxonomy
                          fieldset_terms.find('fieldset.' + select_tax.find('option:first-child').val()).show()
                        }

                        /**
                         * Change action for widget form
                         */

                        // Action for object_name
                        select_obj_name.change(
                          function () {
                            obj_name_action($(this), 'change')
                          }
                        )

                        // Action for Taxonomy Select
                        select_tax.change(
                          function () {
                            // Reset terms
                            reset_terms()
                            // Show terms list of select taxonomy
                            fieldset_terms.find('fieldset.' + $(this).val()).show()
                          }
                        )

                        // Action for Terms Checkbox
                        fieldset_terms.find('input:checkbox').change(
                          function () {

                            let input = $(this)

                            if (input.is(':checked')) {
                              checkbox_array.push(input.val())
                            } else {
                              checkbox_array.splice($.inArray(input.val(), checkbox_array), 1)
                            }

                            hidden_input.val(checkbox_array.join(','))

                          }
                        )
                      }// if select_tax.length
                    }// if body.widgets-php
                  }
                )
              })(jQuery)
            </script>
			<?php
		}
	}
}
