<?php

/**
 * Freemius integration
 * @since 1.9.1
 * @since 1.10  Made instantiable.
 */
class GeoMashupFreemius {
	/**
	 * @since 1.10.0
	 * @var string
	 */
	protected static $init_data_option_name = 'geo_mashup_freemius_init';

	/**
	 * @since 1.10.0
	 * @var array
	 */
	protected $init_data;

	/**
	 * @since 1.10.0
	 * @var Freemius
	 */
	protected $freemius;


	/**
	 * Instantiate a Freemius integration object.
	 * @since 1.10.0
	 */
	public function __construct() {
	}

	/**
	 * @since 1.10.0
	 * @return bool
	 */
	public function is_loaded() {
		return isset( $this->freemius );
	}

	/**
	 * Load Freemius integrations.
	 * @since 1.9.1
	 */
	public function load() {

		if ( $this->is_loaded() ) {
			return;
		}

		include_once( GEO_MASHUP_DIR_PATH . '/vendor/freemius/wordpress-sdk/start.php' );

		$defaults = get_option( self::$init_data_option_name, array(
			'id' => '472',
			'slug' => 'geo-mashup',
			'type' => 'plugin',
			'public_key' => 'pk_c28784eaec74e8b93e422064f2f99',
			'is_premium' => false,
			'has_addons' => false,
			'has_paid_plans' => true,
			'menu' => array(
				'slug' => 'geo-mashup/geo-mashup.php',
				'contact' => false,
				'support' => false,
				'parent' => array(
					'slug' => 'options-general.php',
				),
			),
		) );

		$this->init_data = defined( 'GEO_MASHUP_FREEMIUS_INIT' ) ? unserialize( GEO_MASHUP_FREEMIUS_INIT ) : array();

		$this->init_data = array_replace_recursive( $defaults, $this->init_data );

		$this->freemius = fs_dynamic_init( $this->init_data );

		$this->freemius->add_action( 'after_license_change', array( $this, 'after_license_change' ) );

		$this->freemius->add_action( 'after_account_delete', array( $this, 'after_account_delete' ) );

		$this->freemius->add_action( 'after_uninstall', array( $this, 'uninstall' ) );
	}

	/**
	 * Track premium status when license changes.
	 *
	 * @since 1.10.0
	 * @param string $event The Freemius license event.
	 */
	public function after_license_change( $event ) {
		$is_premium = ! in_array( $event, array( 'cancelled', 'expired', 'trial_expired' ) );
		$this->update_init_data( $is_premium );
		//$this->maybe_redirect_to_settings();
	}

	/**
	 * Track premium status when account is deleted.
	 *
	 * @since 1.10.0
	 */
	public function after_account_delete() {
		$this->update_init_data( false );
		//$this->maybe_redirect_to_settings();
	}

	/**
	 * @since 1.9.1
	 */
	public function uninstall() {
		include_once( GEO_MASHUP_DIR_PATH . '/uninstaller.php' );
		$uninstaller = new GeoMashupUninstaller();
		$uninstaller->geo_mashup_uninstall_options();
	}

	/**
	 * @since 1.10.0
	 * @param bool $is_premium
	 */
	public function update_init_data( $is_premium = false ) {
		$this->init_data['is_premium'] = $is_premium;
		$this->init_data['menu']['contact'] = $is_premium;
		update_option( self::$init_data_option_name, $this->init_data, false );
	}

	/**
	 * After changing the init values a redirect can make sure the view reflects them.
	 *
	 * @since 1.10.0
	 */
	protected function maybe_redirect_to_settings() {
		if ( strpos(  $_SERVER['REQUEST_URI'], $this->init_data['menu']['parent']['slug'] ) > 0 ) {
			$url = add_query_arg(
				array( 'page' => $this->init_data['menu']['slug'] ),
				admin_url( $this->init_data['menu']['parent']['slug'] )
			);
			wp_redirect( $url );
		}
	}
}