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

		self::$freemius = fs_dynamic_init( array(
			'id' => '472',
			'slug' => 'geo-mashup',
			'type' => 'plugin',
			'public_key' => 'pk_c28784eaec74e8b93e422064f2f99',
			'is_premium' => false,
			'has_addons' => false,
			'has_paid_plans' => false,
			'menu' => array(
				'slug' => 'geo-mashup/geo-mashup.php',
				'account' => false,
				'contact' => false,
				'support' => false,
				'parent' => array(
					'slug' => 'options-general.php',
				),
			),
		) );

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