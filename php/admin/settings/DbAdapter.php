<?php
/**
 * Geo Mashup Options Page Database Adapter
 *
 * @package GeoMashup
 */

namespace GeoMashup\Admin\Settings;

use GeoMashupDB;

/**
 * Wrap options page data operations in an instantiable class.
 *
 * @since 1.12.0
 */
class DbAdapter {

    public function duplicate_geodata()
    {
        return GeoMashupDB::duplicate_geodata();
    }

    public function bulk_reverse_geocode()
    {
        return GeoMashupDB::bulk_reverse_geocode();
    }

    public function is_install_needed()
    {
        return GEO_MASHUP_DB_VERSION !== GeoMashupDB::installed_version();
    }

    public function install()
    {
        return GeoMashupDB::install();
    }
}
