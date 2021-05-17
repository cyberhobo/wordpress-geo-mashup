<?php
/**
 * Options page global map panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class GlobalMapPanel {
	public function render( GlobalMapPanelData $data ) {
		?>
        <fieldset id="geo-mashup-global-map-settings">
            <p><?php _e( 'Default settings for global maps of located items.', 'GeoMashup' ); ?></p>
            <div class="submit"><input type="submit" name="submit"
                                       value="<?php _e( 'Update Options', 'GeoMashup' ); ?>"/></div>
            <table width="100%" cellspacing="2" cellpadding="5" class="editform">
                <tr>
                    <th scope="row"><?php _e( 'Map Width', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="map_width"
                               name="global_map[width]"
                               class="overall-submit"
                               type="text"
                               size="5"
                               value="<?php echo esc_attr( $data->options->width ); ?>"/>
						<?php _e( 'Pixels, or append %.', 'GeoMashup' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Map Height', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="map_height"
                               name="global_map[height]"
                               class="overall-submit"
                               type="text"
                               size="5"
                               value="<?php echo esc_attr( $data->options->height ); ?>"/>
						<?php _e( 'px', 'GeoMashup' ); ?>
                    </td>
                </tr>
				<?php if ( 'openlayers' === $data->overall->map_api ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Map Control', 'GeoMashup' ); ?></th>
                        <td>
                            <select id="map_control" name="global_map[map_control]">
								<?php foreach ( $data->map_controls as $type => $label ) : ?>
                                    <option value="<?php echo esc_attr( $type ); ?>"<?php
									if ( $type === $data->options->map_control ) {
										echo ' selected="selected"';
									}
									?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
				<?php endif; ?>
	            <?php if ( in_array( $data->overall->map_api, [ 'googlev3', 'leaflet' ], true ) ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Default Map Type', 'GeoMashup' ); ?></th>
                        <td>
                            <select id="global_map_type" name="global_map[map_type]">
					            <?php foreach ( $data->map_types[$data->overall->map_api] as $type => $label ) : ?>
                                    <option value="<?php echo esc_attr( $type ); ?>"<?php
						            if ( $type === $data->options->map_type ) {
							            echo ' selected="selected"';
						            }
						            ?>><?php echo esc_html( $label ); ?></option>
					            <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
	            <?php endif; ?>
	            <?php if ( 'googlev3' === $data->overall->map_api ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Add Map Type Control', 'GeoMashup' ); ?></th>
                        <td>
				            <?php foreach ( $data->map_types[$data->overall->map_api] as $type => $label ) : ?>
                                <input id="global_add_map_type_<?php echo esc_attr( $type ); ?>"
                                       name="global_map[add_map_type_control][]"
                                       type="checkbox"
                                       value="<?php echo esc_attr( $type ); ?>" <?php
					            if ( in_array( $type, $data->options->add_map_type_control, false ) ) {
						            echo ' checked="checked"';
					            }
					            ?> /> <?php echo esc_html( $label ); ?>
				            <?php endforeach; ?>
                        </td>
                    </tr>
	            <?php endif; ?>
				<?php if ( 'openlayers' === $data->overall->map_api ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Add Overview Control', 'GeoMashup' ); ?></th>
                        <td>
                            <input id="add_overview_control" name="global_map[add_overview_control]" type="checkbox"
                                   value="true"<?php
							if ( $data->options->add_overview_control ) {
								echo ' checked="checked"';
							}
							?> />
                        </td>
                    </tr>
				<?php endif; ?>
                <tr>
                    <th scope="row"><?php _e( 'Add Full Screen Control', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="global_add_full_screen_control" name="global_map[add_full_screen_control]"
                               type="checkbox" value="true"<?php
			            if ( $data->options->add_full_screen_control ) {
				            echo ' checked="checked"';
			            }
			            ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Enable Scroll Wheel Zoom', 'GeoMashup' ); ?></th>
                    <td><input id="enable_scroll_wheel_zoom" name="global_map[enable_scroll_wheel_zoom]"
                               type="checkbox" value="true"<?php
						if ( $data->options->enable_scroll_wheel_zoom ) {
							echo ' checked="checked"';
						}
						?> /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Marker Default Color', 'GeoMashup' ); ?></th>
                    <td>
                        <select id="marker_default_color" name="global_map[marker_default_color]">
							<?php foreach ( $data->color_names as $name => $rgb ) : ?>
                                <option value="<?php echo esc_attr( $name ); ?>"<?php
								if ( $data->options->marker_default_color === $name ) {
									echo ' selected="selected"';
								}
								?> style="background-color:<?php echo esc_attr( $rgb ); ?>;">
									<?php echo esc_html( $name ); ?>
                                </option>
							<?php endforeach; // color name ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Default Zoom Level', 'GeoMashup' ); ?></th>
                    <td>
                        <select id="zoom" name="global_map[zoom]">
							<?php foreach ( $data->zoom_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>"<?php
								if ( strcmp( $value, $data->options->zoom ) === 0 ) {
									echo ' selected="selected"';
								}
								?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
                        </select>
                        <span class="description"><?php
							_e( '0 is zoomed all the way out.', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
				<?php if ( 'googlev3' === $data->overall->map_api ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Clustering', 'GeoMashup' ); ?></th>
                        <td>
							<?php
							printf(
								__( 'Cluster markers from zoom level 0 to %s', 'GeoMashup' ),
								'' // just a placeholder, really expecting the input
							);
							?>
                            <select id="cluster_max_zoom" name="global_map[cluster_max_zoom]">
								<?php foreach ( $data->cluster_zoom_options as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>"<?php
									if ( $value === $data->options->cluster_max_zoom ) {
										echo ' selected="selected"';
									}
									?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
				<?php endif; ?>
                <tr>
                    <th scope="row"><?php _e( 'Marker Selection Behaviors', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="global_marker_select_info_window"
                               name="global_map[marker_select_info_window]" type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_info_window ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Open info window', 'GeoMashup' ); ?>
                        <input id="global_marker_select_highlight" name="global_map[marker_select_highlight]"
                               type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_highlight ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Highlight', 'GeoMashup' ); ?>
                        <input id="global_marker_select_center" name="global_map[marker_select_center]"
                               type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_center ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Center', 'GeoMashup' ); ?>
                        <input id="global_marker_select_attachments"
                               name="global_map[marker_select_attachments]" type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_attachments ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Show Geo Attachments', 'GeoMashup' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Info window thumbnail size', 'GeoMashup' ); ?></th>
                    <td>
                        <select id="thumbnail_size" name="global_map[thumbnail_size]">
							<?php foreach ( $data->thumbnail_sizes as $size ) : ?>
                                <option value="<?php echo esc_attr( $size ); ?>"<?php
								if ( $size === $data->options->thumbnail_size ) {
									echo ' selected="selected"';
								}
								?>><?php echo esc_html( $size ); ?></option>
							<?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Automatic Selection', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="auto_info_open"
                               name="global_map[auto_info_open]"
                               type="checkbox"
                               value="true"<?php
						if ( $data->options->auto_info_open ) {
							echo ' checked="checked"';
						}
						?> />
                        <span class="description"><?php
							_e( 'Selects the linked or most recent item when the map loads.', 'GeoMashup' );
							?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Show Only Most Recent Items', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="max_posts"
                               name="global_map[max_posts]"
                               class="overall-submit"
                               type="text"
                               size="4"
                               value="<?php echo esc_attr( $data->options->max_posts ); ?>"/>
                        <span class="description"><?php _e( 'Number of items to show, leave blank for all',
								'GeoMashup' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Show Future Posts', 'GeoMashup' ); ?></th>
                    <td><select id="show_future" name="global_map[show_future]">
							<?php foreach ( $data->future_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>"<?php
								if ( $value === $data->options->show_future ) {
									echo ' selected="selected"';
								}
								?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
                        </select></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Click To Load', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="click_to_load" name="global_map[click_to_load]" type="checkbox"
                               value="true"<?php
						if ( $data->options->click_to_load ) {
							echo ' checked="checked"';
						}
						?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Click To Load Text', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="click_to_load_text"
                               name="global_map[click_to_load_text]"
                               type="text"
                               size="50"
                               value="<?php echo esc_attr( $data->options->click_to_load_text ); ?>"/>
                    </td>
                </tr>
				<?php if ( ! empty( $data->overall->include_taxonomies ) && ! defined( 'GEO_MASHUP_DISABLE_CATEGORIES' ) ) : ?>
                    <tr>
                        <td colspan="2" align="center">
							<?php foreach ( $data->overall->include_taxonomies as $include_taxonomy ) : ?>
								<?php $taxonomy_object = get_taxonomy( $include_taxonomy ); ?>
								<?php $taxonomy_options = $data->options->term_options[ $include_taxonomy ] ?>
                                <table>
                                    <tr>
                                        <th><?php echo $taxonomy_object->label; ?></th>
                                        <th><?php _e( 'Color', 'GeoMashup' ); ?></th>
                                        <th><?php _e( 'Show Connecting Line Until Zoom Level (0-20 or none)',
												'GeoMashup' ); ?></th>
                                    </tr>
									<?php $terms = get_terms( $include_taxonomy, array( 'hide_empty' => false ) ); ?>
									<?php if ( is_array( $terms ) ) : ?>
										<?php foreach ( $terms as $term ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( $term->name ); ?></td>
                                                <td>
                                                    <select id="<?php echo $include_taxonomy; ?>_color_<?php echo esc_attr( $term->slug ); ?>"
                                                            name="global_map[term_options][<?php echo $include_taxonomy; ?>][color][<?php echo esc_attr( $term->slug ); ?>]">
														<?php foreach ( $data->color_names as $name => $rgb ) : ?>
                                                            <option value="<?php echo esc_attr( $name ); ?>"<?php
															if ( isset( $taxonomy_options['color'][ $term->slug ] ) && $taxonomy_options['color'][ $term->slug ] == $name ) {
																echo ' selected="selected"';
															}
															?>
                                                                    style="background-color:<?php echo esc_attr( $rgb ); ?>;"><?php echo esc_html( $name ); ?></option>
														<?php endforeach; // color name ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input id="<?php echo $include_taxonomy; ?>_line_zoom_<?php
													echo esc_attr( $term->slug ); ?>" class="overall-submit"
                                                           name="global_map[term_options][<?php
													       echo $include_taxonomy; ?>][line_zoom][<?php
													       echo esc_attr( $term->slug ); ?>]" value="<?php
													if ( isset( $taxonomy_options['line_zoom'][ $term->slug ] ) ) {
														echo esc_attr( $taxonomy_options['line_zoom'][ $term->slug ] );
													}
													?>" type="text" size="2" maxlength="2"/></td>
                                            </tr>
										<?php endforeach; // taxonomy term ?>
									<?php endif; ?>
                                </table>
							<?php endforeach; // included taxonomy ?>
                        </td>
                    </tr>
				<?php endif; ?>
            </table>
            <div class="submit">
                <input class="button button-primary" type="submit" name="submit"
                       value="<?php _e( 'Update Options', 'GeoMashup' ); ?>"/>
            </div>
        </fieldset>
		<?php
	}
}
