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
include_once __DIR__ . '/php/Search.php';
class_alias( \GeoMashup\Search::class, 'GeoMashupSearch' );

include_once __DIR__ . '/php/Hooks/RenderSearchMap.php';
(new \GeoMashup\Hooks\RenderSearchMap())->add();

include_once __DIR__ . '/php/Hooks/SearchResults.php';
(new \GeoMashup\Hooks\SearchResults())->add();

include_once __DIR__ . '/php/Hooks/RegisterSearchWidget.php';
(new \GeoMashup\Hooks\RegisterSearchWidget())->add();

/**
 * Class GeoMashupSearchWidget
 *
 * @since 1.5
 * @package GeoMashup
 */
include_once __DIR__ . '/php/Widgets/Search.php';
class_alias( \GeoMashup\Widgets\Search::class, 'GeoMashupSearchWidget' );

