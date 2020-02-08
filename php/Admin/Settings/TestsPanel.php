<?php
/**
 * Options page tests panel.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class TestsPanel {
	public function render( TestsPanelData $data ) {
		if ( ! $data->include_tests ) {
			return;
		}
		?>
        <fieldset id="geo-mashup-tests">
            <p>
				<?php _e( 'Some checks that Geo Mashup is working properly.', 'GeoMashup' ); ?>
            </p>
			<?php if ( $data->run_tests ) : ?>
                <div id="qunit-fixture"></div>
                <div id="qunit"></div>
			<?php else : ?>
                <input type="submit" name="geo_mashup_run_tests"
                       value="<?php _e( 'Run Tests', 'GeoMashup' ); ?>" class="button"/>
			<?php endif; ?>
        </fieldset>
		<?php
	}
}
