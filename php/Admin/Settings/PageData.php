<?php
/**
 * Data required to render the settings page.
 *
 * @package GeoMashup
 */
namespace GeoMashup\Admin\Settings;

use GeoMashupOptions;

class PageData
{
	/** @var TabsData */
	public $tabs_data;
	/** @var OverallPanelData */
	public $overall_panel_data;
	/** @var SingleMapPanelData */
	public $single_map_panel_data;
	/** @var GlobalMapPanelData */
	public $global_map_panel_data;
	/** @var ContextMapPanelData */
	public $context_map_panel_data;
	/** @var TestsPanelData */
	public $tests_panel_data;
	/** @var string */
	public $action;
	/** @var bool */
	public $view_activation_log;
}
