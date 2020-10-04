<?php
/**
 * Options page overall tab panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class OverallPanel {
	public function render( OverallPanelData $data ) {
		?>
        <script type="text/javascript">
          jQuery(function ($) {
            let $obscure_settings = $('.obscure').hide()

            $('#import_custom_field').suggest(ajaxurl + '?action=geo_mashup_suggest_custom_keys', {
              multiple: true,
              multipleSep: ',',
              onSelect: function ($input) {
                // Remove the trailing comma
                $(this).val($(this).val().replace(/,\s*$/, ''))
              }
            })
            $('input.overall-submit').keypress(function (e) {
              if ((e.keyCode && e.keyCode === 13) || (e.which && e.which === 13)) {
                e.preventDefault()
                $('#overall-submit').click()
              }
            })
            $('#map_api').change(function () {
              $('#overall-submit').click()
            })
            $('#show_obscure_settings').click(function (e) {
              let $link = $(this)
              e.preventDefault()
              if ($link.hasClass('ui-icon-triangle-1-e')) {
                $link.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s')
                $obscure_settings.show()
              } else {
                $link.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e')
                $obscure_settings.hide()
              }
            })
          })
        </script>

        <fieldset id="geo-mashup-overall-settings">
			<?php wp_nonce_field( 'geo-mashup-update-options' ); ?>
            <p><?php _e( 'Overall Geo Mashup Settings', 'GeoMashup' ); ?></p>
            <table width="100%" cellspacing="2" cellpadding="5" class="editform">
                <tr>
                    <th scope="row">
						<?php _e( 'Google Server Key', 'GeoMashup' ); ?>
                    </th>
                    <td>
                        <input id="google_server_key"
                               name="overall[google_server_key]"
                               class="overall-submit"
                               type="text"
                               size="40"
                               value="<?php echo esc_attr( $data->options->google_server_key ); ?>"/>
                        <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key"
                           target="_blank"><?php _e( 'Get yours here', 'GeoMashup' ); ?></a>
                        <p class="description">
							<?php
							_e(
								'Used for address and location searches, recommended but not required. You can still use any map provider.',
								'GeoMashup'
							);
							?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
						<?php _e( 'Map Provider', 'GeoMashup' ); ?>
                    </th>
                    <td>
                        <select id="map_api" name="overall[map_api]"><?php
							foreach ( $data->map_apis as $value => $label ) : ?>
                                <option value="<?php echo $value; ?>"<?php
								if ( $data->options->map_api === $value ) {
									echo ' selected="selected"';
								}
								?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
                        </select>
                    </td>
                </tr>
				<?php if ( 'googlev3' === $data->options->map_api ) : ?>
                    <tr>
                        <th width="33%" scope="row"><?php _e( 'Google API Key', 'GeoMashup' ); ?></th>
                        <td>
                            <input id="googlev3_key"
                                   name="overall[googlev3_key]"
                                   class="overall-submit"
                                   type="text"
                                   size="40"
                                   value="<?php echo esc_attr( $data->options->googlev3_key ); ?>"/>
                            <a href="https://developers.google.com/maps/documentation/javascript/get-api-key"
                               target="_blank"><?php _e( 'Get yours here', 'GeoMashup' ); ?></a>
                            <p class="description">
								<?php
								_e(
									'Google now requires a key and will trigger a warning alert without one. Be ready to perform a few steps.',
									'GeoMashup'
								);
								?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th width="33%" scope="row"><?php _e( 'Snazzy Maps', 'GeoMashup' ); ?></th>
                        <td>
							<?php if ( class_exists( 'GeoMashupSnazzyMaps' ) ) : ?>
                                <span class="dashicons dashicons-yes"></span>
                                <a href="<?php echo admin_url( 'themes.php?page=snazzy_maps' ); ?>">
									<?php _e( 'Using current Snazzy styles', 'GeoMashup' ); ?>
                                </a>
                                <p class="description">
									<?php
									_e(
										'We\'ve detected that Snazzy Maps is installed and active, cool!',
										'GeoMashup'
									);
									?>
                                </p>
							<?php else : ?>
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <a href="https://snazzymaps.com/plugins/wordpress?utm_source=geomashup&utm_medium=plugin&utm_campaign=wordpress"
                                   target="_blank">
									<?php _e( 'Get the Snazzy Maps plugin', 'Postmatic' ); ?>
                                </a>
                                <p class="description">
									<?php
									_e(
										'We\'ve partnered with Atmist to make their great plugin work with Geo Mashup!',
										'GeoMashup'
									);
									?>
                                </p>
							<?php endif; ?>
                        </td>
                    </tr>
				<?php endif; ?>
                <tr>
                    <th scope="row" title="<?php _e( 'Generated links go here', 'GeoMashup' ); ?>">
						<?php _e( 'Global Mashup Page', 'GeoMashup' ); ?>
                    </th>
                    <td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'overall[mashup_page]',
							'id'                => 'mashup_page',
							'show_option_none'  => __( '&mdash; Select &mdash;' ),
							'option_none_value' => 0,
							'selected'          => $data->options->mashup_page,
						) );
						?>
                        <span class="description"><?php
							_e( 'Geo Mashup will use this page for generated location links', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Collect Location For', 'GeoMashup' ); ?></th>
                    <td>
						<?php foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $post_type ) : ?>
                            <input id="locate_posts" name="overall[located_post_types][]" type="checkbox"
                                   value="<?php echo $post_type->name; ?>"<?php
							if ( in_array( $post_type->name, $data->options->located_post_types, false ) ) {
								echo ' checked="checked"';
							}
							?> /> <?php echo $post_type->labels->name; ?>
						<?php endforeach; ?>
                        <input id="locate_users" name="overall[located_object_name][user]" type="checkbox"
                               value="true"<?php
						if ( $data->options->locate_users ) {
							echo ' checked="checked"';
						}
						?> /> <?php _e( 'Users', 'GeoMashup' ); ?>
                        <input id="locate_comments" name="overall[located_object_name][comment]" type="checkbox"
                               value="true"<?php
						if ( $data->options->locate_comments ) {
							echo ' checked="checked"';
						}
						?> /> <?php _e( 'Comments', 'GeoMashup' ); ?>
                    </td>
                </tr>
               <tr>
                    <th scope="row"><?php _e( 'Enable Geo Search', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="enable_geo_search" name="overall[enable_geo_search]" type="checkbox"
                               value="true"<?php
						if ( $data->options->enable_geo_search ) {
							echo ' checked="checked"';
						}
						?> />
                        <span class="description"><?php
							_e( 'Creates a customizable widget and other features for performing radius searches.',
								'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Include Taxonomies', 'GeoMashup' ); ?></th>
                    <td>
						<?php foreach ( get_taxonomies( array( 'show_ui' => true, ), 'objects' ) as $taxonomy ) : ?>
                            <input id="locate_posts" name="overall[include_taxonomies][]" type="checkbox"
                                   value="<?php echo $taxonomy->name; ?>"<?php
							if ( in_array( $taxonomy->name, $data->options->include_taxonomies, false ) ) {
								echo ' checked="checked"';
							}
							?> /> <?php echo $taxonomy->labels->name; ?>
						<?php endforeach; ?>
                        <br/>
                        <span class="description"><?php
							_e( 'Makes legends, colors, and other features available. Minimize use of these for best performance.',
								'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th width="33%" scope="row"><?php _e( 'Copy Geodata Meta Fields', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="copy_geodata"
                               name="overall[copy_geodata]"
                               type="checkbox"
                               value="true"<?php
						if ( $data->options->copy_geodata ) {
							echo ' checked="checked"';
						}
						?> />
                        <span class="description"><?php
							printf( __( 'Copy coordinates to and from %s geodata meta fields %s, for integration with the Geolocation and other plugins.',
								'GeoMashup' ),
								'<a href="http://codex.wordpress.org/Geodata" title="">', '</a>' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Geocode Custom Field', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="import_custom_field"
                               name="overall[import_custom_field]"
                               class="overall-submit"
                               type="text"
                               size="35"
                               value="<?php echo esc_attr( $data->options->import_custom_field ); ?>"/><br/>
                        <span class="description"><?php
							_e( 'Comma separated keys of custom fields to be geocoded when saved. Multiple fields will be combined in order before geocoding. Saves a location for the post if found.',
								'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Enable Reverse Geocoding', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="enable_reverse_geocoding" name="overall[enable_reverse_geocoding]"
                               type="checkbox" value="true"<?php
						if ( $data->options->enable_reverse_geocoding ) {
							echo ' checked="checked"';
						}
						?> />
                        <span class="description"><?php
							_e( 'Try to look up missing address fields for new locations.', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
				<?php if ( $data->options->enable_reverse_geocoding ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Bulk Reverse Geocode', 'GeoMashup' ); ?></th>
                        <td>
                            <input type="submit" name="bulk_reverse_geocode"
                                   value="<?php _e( 'Start', 'GeoMashup' ); ?>" class="button"/>
                            <span class="description"><?php
								_e( 'Try to look up missing address fields for existing locations. Could be slow.',
									'GeoMashup' );
								?></span>
                        </td>
                    </tr>
				<?php endif; ?>
                <tr>
                    <th scope="row"><?php _e( 'Use Theme Style Sheet with Maps', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="theme_stylesheet_with_maps"
                               name="overall[theme_stylesheet_with_maps]"
                               type="checkbox"
                               value="true"<?php
						if ( $data->options->theme_stylesheet_with_maps ) {
							echo ' checked="checked"';
						}
						?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'GeoNames ID', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="geonames_username_text"
                               name="overall[geonames_username]"
                               class="overall-submit"
                               type="text"
                               size="35"
                               value="<?php echo esc_attr( $data->options->geonames_username ); ?>"/><br/>
                        <span class="description"><?php
							printf( __( 'Your %sGeoNames username%s, used with GeoNames API requests. Leave the default value to use Geo Mashup\'s.',
								'GeoMashup' ),
								'<a href="http://geonames.wordpress.com/2011/01/28/application-identification-for-free-geonames-web-services/" title="">',
								'</a>' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Obscure Settings', 'GeoMashup' ); ?></th>
                    <td>
                        <a id="show_obscure_settings" href="#show_obscure_settings"
                           class="ui-icon ui-icon-triangle-1-e alignleft"></a>
                        <span class="description"><?php
							_e( 'Reveal some less commonly used settings.', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr class="obscure">
                    <th scope="row"><?php _e( 'Add Category Links', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="add_category_links" name="overall[add_category_links]" type="checkbox"
                               value="true"<?php
						if ( $data->options->add_category_links ) {
							echo ' checked="checked"';
						}
						?> />
                        <span class="description"><?php
							_e( 'Add map links to category lists. Categories must have descriptions for this to work.',
								'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr class="obscure">
                    <th scope="row"><?php _e( 'Category Link Separator', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="category_link_separator"
                               name="overall[category_link_separator]"
                               class="add-category-links-dep overall-submit"
                               type="text"
                               size="3"
                               value="<?php echo esc_attr( $data->options->category_link_separator ); ?>"/>
                    </td>
                </tr>
                <tr class="obscure">
                    <th scope="row"><?php _e( 'Category Link Text', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="category_link_text"
                               name="overall[category_link_text]"
                               class="overall-submit"
                               type="text"
                               size="5"
                               value="<?php echo esc_attr( $data->options->category_link_text ); ?>"/>
                    </td>
                </tr>
                <tr class="obscure">
                    <th scope="row"><?php _e( 'Category Link Zoom Level', 'GeoMashup' ); ?></th>
                    <td>
                        <select id="category_zoom" name="overall[category_zoom]">
							<?php foreach ( $data->zoom_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>"<?php
								if ( strcmp( $value, $data->options->category_zoom ) === 0 ) {
									echo ' selected="selected"';
								}
								?>><?php echo esc_attr( $label ); ?></option>
							<?php endforeach; ?>
                        </select>
                        <span class="description"><?php
							_e( '0 is zoomed all the way out.', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
            </table>
            <div class="submit">
                <input id="overall-submit" class="button button-primary" type="submit" name="submit"
                       value="<?php _e( 'Update Options', 'GeoMashup' ); ?>"/>
            </div>
        </fieldset>
		<?php
	}
}
