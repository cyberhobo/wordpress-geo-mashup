<?php
/**
 * Options page tabs view.
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

class Tabs {

	public function render( TabsData $data ) {
		?>
        <input id="geo-mashup-selected-tab"
               name="geo_mashup_selected_tab"
               type="hidden"
               value="<?php echo $data->selected_tab; ?>"/>
        <script>
          jQuery(function ($) {
            let selector = '#geo-mashup-settings-form'
            $(selector).tabs({
              active: <?php echo $data->selected_tab ?>,
              activate: function (event, ui) {
                $('#geo-mashup-selected-tab').val(ui.newTab.index())
              }
            })
          })
        </script>
        <ul>
            <li><a href="#geo-mashup-overall-settings"><span><?php _e( 'Overall', 'GeoMashup' ); ?></span></a>
            </li>
            <li><a href="#geo-mashup-single-map-settings"><span><?php _e( 'Single Maps', 'GeoMashup' ); ?></span></a>
            </li>
            <li><a href="#geo-mashup-global-map-settings"><span><?php _e( 'Global Maps', 'GeoMashup' ); ?></span></a>
            </li>
            <li><a href="#geo-mashup-context-map-settings"><span><?php _e( 'Contextual Maps',
							'GeoMashup' ); ?></span></a></li>
			<?php if ( $data->include_tests ) : ?>
                <li><a href="#geo-mashup-tests"><span><?php _e( 'Tests', 'GeoMashup' ); ?></span></a></li>
			<?php endif; ?>
        </ul>
		<?php
	}
}
