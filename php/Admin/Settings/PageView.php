<?php
/**
 * Options page view.
 *
 * @package GeoMashup
 *
 * @since 1.12.0
 */

namespace GeoMashup\Admin\Settings;

class PageView {
	public static function render(
		PageData $data,
		Tabs $tabs,
		OverallPanel $overall_panel,
		SingleMapPanel $single_map_panel,
		GlobalMapPanel $global_map_panel,
		ContextMapPanel $context_map_panel,
		TestsPanel $tests_panel
	) {
		?>
        <div class="wrap">
            <h2><?php _e( 'Geo Mashup Options', 'GeoMashup' ); ?></h2>
            <form method="post" id="geo-mashup-settings-form" action="<?php echo $data->action; ?>">
				<?php
				$tabs->render( $data->tabs_data );
				$overall_panel->render( $data->overall_panel_data );
				$single_map_panel->render( $data->single_map_panel_data );
				$global_map_panel->render( $data->global_map_panel_data );
				$context_map_panel->render( $data->context_map_panel_data );
				$tests_panel->render( $data->tests_panel_data );
				?>
            </form>
			<?php if ( $data->view_activation_log ) : ?>
                <div class="updated">
                    <p><strong><?php _e( 'Update Log', 'GeoMashup' ); ?></strong></p>
                    <pre><?php echo get_option( 'geo_mashup_activation_log' ) ?></pre>
                    <form method="post" id="geo-mashup-log-form" action="<?php echo $data->action; ?>">
						<?php wp_nonce_field( 'geo-mashup-delete-log' ); ?>
                        <input type="submit" name="delete_log" value="<?php _e( 'Delete Log', 'GeoMashup' ); ?>"
                               class="button"/>
                        <p>
							<?php _e( 'You can keep this log as a record, future entries will be appended.', 'GeoMashup' ); ?>
                        </p>
                    </form>
                </div>
			<?php else : ?>
                <p>
                    <a href="<?php echo $data->action; ?>&amp;view_activation_log=1"><?php
						_e( 'View Update Log', 'GeoMashup' ); ?></a>
                </p>
			<?php endif; ?>
            <p>
                <a href="https://github.com/cyberhobo/wordpress-geo-mashup/wiki/Getting-Started"><?php
					_e( 'Geo Mashup Documentation', 'GeoMashup' ); ?></a>
            </p>
            <script type="text/javascript"> jQuery(function ($) {
                $('#geo-mashup-credit-input').focus(function () {
                  this.select()
                })
              })
            </script>
        </div>
		<?php
	}
}
