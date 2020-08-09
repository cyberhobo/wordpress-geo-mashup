<?php
/**
 * Geo Mashup Search Module
 *
 * @package GeoMashup
 */

/**
 * The Geo Mashup Search class.
 *
 * @since 1.5
 * @package GeoMashup
 */
class_alias( \GeoMashup\Search::class, 'GeoMashupSearch' );

(new \GeoMashup\Hooks\RenderSearchMap())->add();

(new \GeoMashup\Hooks\SearchResults())->add();

(new \GeoMashup\Hooks\RegisterSearchWidget())->add();

/**
 * Class GeoMashupSearchWidget
 *
 * @since 1.5
 * @package GeoMashup
 */
class_alias( \GeoMashup\Widgets\Search::class, 'GeoMashupSearchWidget' );

