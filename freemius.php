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

		$id = defined( 'GEO_MASHUP_FREEMIUS_ID' ) ? GEO_MASHUP_FREEMIUS_ID : '534';
		$public_key = defined( 'GEO_MASHUP_FREEMIUS_KEY' ) ? GEO_MASHUP_FREEMIUS_KEY : 'pk_c28784eaec74e8b93e422064f2f99';
		$secret_key = defined( 'GEO_MASHUP_FREEMIUS_DEV_KEY' ) ? GEO_MASHUP_FREEMIUS_KEY : null;

		self::$freemius = fs_dynamic_init( array(
			'id' => $id,
			'slug' => 'geo-mashup',
			'type' => 'plugin',
			'public_key' => $public_key,
			'is_live' => false,
			'is_premium' => true,
			'has_addons' => false,
			'has_paid_plans' => true,
			'menu' => array(
				'slug' => 'geo-mashup/geo-mashup.php',
				'account' => false,
				'contact' => false,
				'support' => false,
				'parent' => array(
					'slug' => 'options-general.php',
				),
			),
			'secret_key' => $secret_key,
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