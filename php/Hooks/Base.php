<?php

namespace GeoMashup\Hooks;

abstract class Base {
	/** @var Base[] */
	protected static $_instances = [];

	public function __construct()
	{
		self::$_instances[] = $this;
	}

	public function __destruct()
	{
		unset(self::$_instances[array_search($this, self::$_instances, true)]);
	}

	public function instances()
	{
		return array_filter(self::$_instances, function ($instance) {
			return $instance instanceof $this;
		});
	}

	abstract public function add();

	abstract public function remove();
}
