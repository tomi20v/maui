<?php

namespace Maui;

class ModelManager extends \Model {

	/**
	 * @var string[] I hold names of classes which have been inited already
	 */
	protected static $_initedClasses=array();

	/**
	 * @var \Model[string][string] I hold model references keyed by classname (first) and ID (second index)
	 */
	protected static $_modelPool = array();

	/**
	 * @var array[string][string] I hold model data keyed by classname (first) and ID (second index)
	 */
	protected static $_modelData = array();

	/**
	 * I tell if class has been inited already
	 * @param string $classname
	 * @return bool
	 */
	public static function isInited($classname) {
		return in_array($classname, self::$_initedClasses);
	}

	/**
	 * I register a class as inited
	 * @param string $classname
	 */
	public static function registerInited($classname) {
		self::$_initedClasses[] = $classname;
		self::$_modelPool[$classname] = array();
		self::$_modelData[$classname] = array();
	}

}
