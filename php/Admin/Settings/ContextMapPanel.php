<?php
/**
 * Options page context map panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class ContextMapPanel {
	public function render( ContextMapPanelData $data ) {
		?>
        <fieldset id="geo-mashup-context-map-settings">
            <p><?php _e( 'Default settings for contextual maps, which include just the items shown on a page, for example.',
					'GeoMashup' ); ?></p>
            <table width="100%" cellspacing="2" cellpadding="5" class="editform">
                <tr>
                    <th scope="row"><?php _e( 'Map Width', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="context_map_width"
                               name="context_map[width]"
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
                        <input id="context_map_height"
                               name="context_map[height]"
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
                            <select id="context_map_control" name="context_map[map_control]">
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
                            <select id="context_map_type" name="context_map[map_type]">
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
                                <input id="context_add_map_type_<?php echo esc_attr( $type ); ?>"
                                       name="context_map[add_map_type_control][]"
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
                            <input id="context_add_overview_control" name="context_map[add_overview_control]"
                                   type="checkbox" value="true"<?php
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
                        <input id="context_add_full_screen_control" name="context_map[add_full_screen_control]"
                               type="checkbox" value="true"<?php
						if ( $data->options->add_full_screen_control ) {
							echo ' checked="checked"';
						}
						?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Enable Scroll Wheel Zoom', 'GeoMashup' ); ?></th>
                    <td><input id="context_enable_scroll_wheel_zoom"
                               name="context_map[enable_scroll_wheel_zoom]" type="checkbox" value="true"<?php
						if ( $data->options->enable_scroll_wheel_zoom ) {
							echo ' checked="checked"';
						}
						?> /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Marker Default Color', 'GeoMashup' ); ?></th>
                    <td>
                        <select id="marker_default_color" name="context_map[marker_default_color]">
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
                        <select id="context_zoom" name="context_map[zoom]">
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
                <tr>
                    <th scope="row"><?php _e( 'Marker Selection Behaviors', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="context_marker_select_info_window"
                               name="context_map[marker_select_info_window]" type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_info_window ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Open info window', 'GeoMashup' ); ?>
                        <input id="context_marker_select_highlight" name="context_map[marker_select_highlight]"
                               type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_highlight ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Highlight', 'GeoMashup' ); ?>
                        <input id="context_marker_select_center" name="context_map[marker_select_center]"
                               type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_center ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Center', 'GeoMashup' ); ?>
                        <input id="context_marker_select_attachments"
                               name="context_map[marker_select_attachments]" type="checkbox" value="true"<?php
						echo ( $data->options->marker_select_attachments ) ? ' checked="checked"' : '';
						?> />
						<?php _e( 'Show Geo Attachments', 'GeoMashup' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Click To Load', 'GeoMashup' ); ?></th>
                    <td>
                        <input id="context_click_to_load" name="context_map[click_to_load]" type="checkbox"
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
                        <input id="context_click_to_load_text"
                               name="context_map[click_to_load_text]"
                               class="overall-submit"
                               type="text"
                               size="50"
                               value="<?php echo esc_attr( $data->options->click_to_load_text ); ?>"/>
                    </td>
                </tr>
            </table>
            <div class="submit">
                <input class="button button-primary" type="submit" name="submit"
                       value="<?php _e( 'Update Options', 'GeoMashup' ); ?>"/>
            </div>
        </fieldset>
		<?php
	}
}
