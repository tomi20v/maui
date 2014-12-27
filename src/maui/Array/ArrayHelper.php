<?php

namespace maui;

/**
 * Class ArrayHelper - some common array helper method collection
 *
 * @package maui
 */
class ArrayHelper {

	/**
	 * check if $arr contains only elements specified by $what
	 * @param mixed[] $arr
	 * @param string[] $what - 'string', 'array', or class name
	 * @return bool|null
	 */
	public static function containsOnly($arr, $what) {
		$what = (array) $what;

		foreach ($arr as $eachVal) {
			foreach ($what as $eachWhat) {
				switch(true) {
				case $what === 'string':
					if (is_string($eachVal)) {
						continue 2;
					}
					break;
				case $what === 'array':
					if (is_array($eachVal)) {
						continue 2;
					}
					break;
				case is_string($what):
					if (!class_exists($what)) {
						return null;
					}
					if ($eachVal instanceof $what) {
						continue 2;
					}
					break;
				}
			}
		}

		return false;

	}

	/**
	 * I keep only unique entries in an array. Comparison is recursive but not done recursively.
	 *
*@param $arr
	 * @return array
	 */
	public static function arrayUnique($arr) {

		$serialized = array();
		foreach ($arr as $eachKey => $eachVal) {
			$serialized[$eachKey] = serialize($eachVal);
		}
		$ret = array_intersect_key($arr, array_unique($serialized));
		return $ret;
	}

	public static function camelJoin($glue, $arr=null, $castToLower=false) {
		if (func_num_args() == 1) {
			$arr = $glue;
			$glue = '';
		}
		if (!is_array($arr)) {
			return null;
		}
		foreach ($arr as $eachKey=>$eachVal) {
			if ($castToLower) {
				$eachVal = strtolower($eachVal);
			}
			$eachVal = ucfirst($eachVal);
			$arr[$eachKey] = $eachVal;
		}
		return implode($glue, $arr);
	}

	public static function camelSplit($string, $castToLower=false) {
		$parts = [];
		$l = strlen($string);
		$i0 = 0;
		for ($i=1; $i<$l; $i++) {
			if ($string[$i] === strtoupper($string[$i])) {
				$str = substr($string, $i0, $i-$i0);
				$parts[] = $castToLower
					? strtolower($str)
					: $str;
				$i0 = $i;
			}
		}
		if ($i != $i0) {
			$parts[] = substr($string, $i0);
		}
		return $parts;
	}

}
