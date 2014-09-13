<?php

namespace maui;

/**
 * Class TraitNamedInstances adds functionality to get objects by ::instance($name)
 *
 * @package Maui
 */
trait TraitNamedInstances {

	/**
	 * @var mixed[] pool of instances
	 */
	private static $_instances = array();

	/**
	 * I get/set an instance
	 * @param null|string $key instance name to get/set, null for default
	 * @param null $Instance send an instance of same or extending class to set
	 * @return static the instance
	 * @throws \Exception
	 */
	public static function instance($key=null, $Instance=null) {
		if (is_null($key)) {
			$key = \Maui::D;
		}
		if (is_a($Instance, get_called_class())) {
			self::$_instances[$key] = $Instance;
		}
		elseif (is_null($Instance)) {
			if (!isset(self::$_instances[$key])) {
				self::$_instances[$key] = static::_instance();
			}
		}
		else {
			throw new \Exception(echon($key) . ' / ' . echon($Instance));
		}
		return self::$_instances[$key];
	}

}
