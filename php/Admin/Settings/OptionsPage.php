<?php
/**
 * Geo Mashup Options Page HTML Management
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

use GeoMashupOptions;

/**
 * @since 1.12.0
 */
class OptionsPage {
	private $options;
	private $db;
	private $tabs;
	private $overall_panel;
	private $single_map_panel;
	private $global_map_panel;
	private $context_map_panel;
	private $tests_panel;
	private $activated_copy_geodata = false;

	public function __construct(
		GeoMashupOptions $options = null,
		DbAdapter $db_adapter = null,
		Tabs $tabs = null,
		OverallPanel $overall_panel = null,
		SingleMapPanel $single_map_panel = null,
		GlobalMapPanel $global_map_panel = null,
		ContextMapPanel $context_map_panel = null,
		TestsPanel $tests_panel = null
	) {
		global $geo_mashup_options;
		$this->options           = $options === null ? $geo_mashup_options : $options;
		$this->db                = $db_adapter === null ? new DbAdapter() : $db_adapter;
		$this->tabs              = $tabs === null ? new Tabs() : $tabs;
		$this->overall_panel     = $overall_panel === null ? new OverallPanel() : $overall_panel;
		$this->single_map_panel  = $single_map_panel === null ? new SingleMapPanel() : $single_map_panel;
		$this->global_map_panel  = $global_map_panel === null ? new GlobalMapPanel() : $global_map_panel;
		$this->context_map_panel = $context_map_panel === null ? new ContextMapPanel() : $context_map_panel;
		$this->tests_panel       = $tests_panel === null ? new TestsPanel() : $tests_panel;
	}

	/**
	 * @param array $submission Submitted data
	 */
	public function render( $submission ) {

		echo $this->save( $submission );
		echo $this->duplicate_geodata();
		echo $this->bulk_reverse_geocode( $submission );
		echo $this->tests( $submission );
		echo $this->migrate();
		echo $this->delete_log( $submission );
		echo $this->corrupt_options();
		echo $this->validation_errors();

		$data                         = new PageData();
		$data->action                 = $_SERVER['REQUEST_URI'];
		$data->view_activation_log    = isset($_GET['view_activation_log']);
		$data->tabs_data              = TabsData::from_submission( $submission );
		$data->overall_panel_data     = new OverallPanelData();
		$data->single_map_panel_data  = new SingleMapPanelData();
		$data->global_map_panel_data  = new GlobalMapPanelData();
		$data->context_map_panel_data = new ContextMapPanelData();
		$data->tests_panel_data       = TestsPanelData::from_submission( $submission );

		PageView::render(
			$data,
			$this->tabs,
			$this->overall_panel,
			$this->single_map_panel,
			$this->global_map_panel,
			$this->context_map_panel,
			$this->tests_panel
		);
	}

	private function save( $submission ) {
		if ( ! isset( $submission['submit'] ) ) {
			return '';
		}

		check_admin_referer( 'geo-mashup-update-options' );

		// Make missing array options empty
		if ( empty( $submission['global_map']['add_map_type_control'] ) ) {
			$submission['global_map']['add_map_type_control'] = array();
		}

		if ( empty( $submission['single_map']['add_map_type_control'] ) ) {
			$submission['single_map']['add_map_type_control'] = array();
		}

		if ( empty( $submission['context_map']['add_map_type_control'] ) ) {
			$submission['context_map']['add_map_type_control'] = array();
		}

		if ( empty( $submission['overall']['located_post_types'] ) ) {
			$submission['overall']['located_post_types'] = array();
		}

		if ( empty( $submission['overall']['located_object_name'] ) ) {
			$submission['overall']['located_object_name'] = array();
		}

		if ( empty( $submission['overall']['include_taxonomies'] ) ) {
			$submission['overall']['include_taxonomies'] = array();
		}

		if ( isset( $submission['overall']['copy_geodata'] ) &&
		     'true' !== $this->options->get( 'overall', 'copy_geodata' ) ) {
			$this->activated_copy_geodata = true;
		}

		$this->options->set_valid_options( $submission );

		$saved = $this->options->save();
		if ( $saved ) {
			return '<div class="updated fade"><p>' .
			       __( 'Options updated.  Browser or server caching may delay updates for recently viewed maps.',
				       'GeoMashup' ) .
			       '</p></div>';
		}


		return '';
	}

	private function duplicate_geodata() {
		if ( ! $this->activated_copy_geodata ) {
			return '';
		}
		$this->db->duplicate_geodata();

		return '<div class="updated fade"><p>' .
		       __( 'Copied existing geodata, see log for details.', 'GeoMashup' ) .
		       '</p></div>';
	}

	private function bulk_reverse_geocode( $submission ) {
		if ( ! isset( $submission['bulk_reverse_geocode'] ) ) {
			return '';
		}
		check_admin_referer( 'geo-mashup-update-options' );
		$log = $this->db->bulk_reverse_geocode();

		return '<div class="updated fade">' . $log . '</div>';
	}

	private function tests( $submission ) {
		if ( ! isset( $submission['geo_mashup_run_tests'] ) ) {
			// Set a test transient
			set_transient( 'geo_mashup_test', 'true', 60 * 60 );

			return '';
		}

		if ( ! function_exists( 'mb_check_encoding' ) ) {
			return '<div class="updated fade">' .
			       printf(
				       __( '%s Multibyte string functions %s are not installed.', 'GeoMashup' ),
				       '<a href="http://www.php.net/manual/en/mbstring.installation.php" title="">',
				       '</a>'
			       ) . ' ' .
			       _e( 'Geocoding and other web services may not work properly.', 'GeoMashup' ) .
			       '</div>';
		}

		$test_transient = get_transient( 'geo_mashup_test' );
		if ( ! $test_transient ) {
			unset( $submission['geo_mashup_run_tests'] );

			return '<div class="updated fade">' .
			       _e( 'WordPress transients may not be working. Try deactivating or reconfiguring caching plugins.',
				       'GeoMashup' ) .
			       ' <a href="https://github.com/cyberhobo/wordpress-geo-mashup/issues/425">issue 425</a></div>';
		}

		// load tests
		return '';
	}

	private function migrate() {
		if ( $this->db->is_install_needed() && $this->db->install() ) {
			return '<div class="updated fade"><p>' .
			       __( 'Database upgraded, see log for details.', 'GeoMashup' ) . '</p></div>';
		}

		return '';
	}

	private function delete_log( $submission ) {
		if ( ! isset( $submission['delete_log'] ) ) {
			return '';
		}
		check_admin_referer( 'geo-mashup-delete-log' );
		if ( update_option( 'geo_mashup_activation_log', '' ) ) {
			return '<div class="updated fade"><p>' . __( 'Log deleted.', 'GeoMashup' ) . '</p></div>';
		}

		return '';
	}

	private function corrupt_options() {
		if ( ! empty ( $this->options->corrupt_options ) ) {
			// Options didn't load correctly
			$message = __( 'Saved options may be corrupted, try updating again. Corrupt values: ', 'GeoMashup' ) .
			           '<code>' . $this->options->corrupt_options . '</code>';

			return '<div class="updated"><p>' . $message . '</p></div>';
		}

		return '';
	}

	private function validation_errors() {
		if ( empty ( $this->options->validation_errors ) ) {
			return '';
		}
		$html = '<div class="updated"><p>' .
		        __( 'Some invalid options will not be used. If you\'ve just upgraded, do an update to initialize new options.',
			        'GeoMashup' ) .
		        '<ul>';
		foreach ( $this->options->validation_errors as $message ) {
			$html .= "<li>$message</li>";
		}
		$html .= '</ul></p></div>';

		return $html;
	}
}

