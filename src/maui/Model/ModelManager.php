<?php

namespace maui;

class ModelManager extends \Model {

	/**
	 * @var int get only original (saved) data
	 */
	const DATA_ORIGINAL = 0;
	/**
	 * @var int get only changed data
	 */
	const DATA_CHANGED = 1;
	/**
	 * @var int get all data, prefer locally changed
	 */
	const DATA_ALL = 2;

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

	/**
	 * I compare two objects or similar data. If one argument is only a string, then I match only the ID. Otherwise,
	 * 		I check if $m1 matches $m2 (so $m2 might have extra data, but contains all of $m1). Input types:
	 *  string - treated as ID
	 *  array - treated as is
	 *  \Model - its data is retrieved by getData(false)
	 * @param mixed $m1
	 * @param mixed $m2
	 * @return bool|null
	 */
	public static function compare($m1, $m2, $strict=false) {
		// if one of them string ID, then compare by just ID
		if (is_string($m1) || is_string($m2)) {
			// if $m2 is the string, swap them
			if (!is_string($m1)) {
				$tmp = $m1;
				$m1 = $m2;
				$m2 = $tmp;
			}
			if (is_string($m2));
			elseif (is_array($m2)) {
				$m2 = isset($m2[\SchemaManager::KEY_ID]) ? $m2[\SchemaManager::KEY_ID] : null;
			}
			elseif ($m2 instanceof \Model) {
				$m2 = $m2->_id;
			}
			else return null;
			return $m1 == $m2;
		}
		else {
			if (is_array($m1));
			elseif ($m1 instanceof \Model) {
				$m1 = $m1->getData(\ModelManager::DATA_ALL);
			}
			else return null;
			if (is_array($m2));
			elseif ($m2 instanceof \Model) {
				$m2 = $m2->getData(\ModelManager::DATA_ALL);
			}
			else return null;
			foreach ($m1 as $eachKey=>$eachVal1) {
				if (!isset($m2[$eachKey])) {
					return false;
				}
				$eachVal2 = $m2[$eachKey];
				if (is_array($eachVal1) || is_object($eachVal1) || is_array($eachVal2) || is_object($eachVal2)) {
					if (!static::compare($eachVal1, $eachVal2, $strict)) {
						return false;
					}
				}
				else {
					if (($strict && ($eachVal1 !== $eachVal2)) ||
						(!$strict && ($eachVal1 != $eachVal2))) {
						return false;
					}
				}
			}
			return true;
		}
	}

}
