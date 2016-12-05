<?php

/**
 * Freemius integration
 * @since 1.9.1
 */
class GeoMashupFreemius {
	/**
	 * @since 2.0.0
	 * @var Freemius
	 */
	protected static $freemius;

	/**
	 * @since 2.0.0
	 * @return bool
	 */
	public static function is_loaded() {
		return isset( self::$freemius );
	}

	/**
	 * Load Freemius integrations.
	 * @since 1.9.1
	 */
	public static function load() {

		if ( self::is_loaded() ) {
			return;
		}

		include_once( GEO_MASHUP_DIR_PATH . '/vendor/freemius/wordpress-sdk/start.php' );

		$defaults = array(
			'id'                => '472',
			'slug'              => 'geo-mashup',
			'type'              => 'plugin',
			'public_key'        => 'pk_c28784eaec74e8b93e422064f2f99',
			'is_premium'        => true,
			'has_addons'        => false,
			'has_paid_plans'    => true,
			'menu'              => array(
				'slug'       => 'geo-mashup/geo-mashup.php',
				'support'    => false,
				'parent'     => array(
					'slug' => 'options-general.php',
				),
			),
		);

		$init_data = defined( 'GEO_MASHUP_FREEMIUS_INIT' ) ? unserialize( GEO_MASHUP_FREEMIUS_INIT ) : array();

		$init_data = array_replace_recursive( $defaults, $init_data );

		self::$freemius = fs_dynamic_init( $init_data );

		self::$freemius->add_action( 'after_uninstall', array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * @since 1.9.1
	 */
	public static function uninstall() {
		include_once( GEO_MASHUP_DIR_PATH . '/uninstaller.php' );
		$uninstaller = new GeoMashupUninstaller();
		$uninstaller->geo_mashup_uninstall_options();
	}

}