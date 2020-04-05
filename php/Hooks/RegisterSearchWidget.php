<?php

namespace GeoMashup\Hooks;

include_once __DIR__ . '/Base.php';

class RegisterSearchWidget extends Base {

	public function add() {
		add_action( 'widgets_init', [ $this, 'act' ] );
		return $this;
	}

	public function remove() {
		remove_action( 'widgets_init', [ $this, 'act' ] );
		return $this;
	}

	public function act() {
		// Register with backwards-compatible class name
		register_widget( 'GeoMashupSearchWidget' );
	}

}
