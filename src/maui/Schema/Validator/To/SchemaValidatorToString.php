<?php

namespace Maui;

class SchemaValidatorToString extends \SchemaValidatorTo {

	/**
	 * I accept scalars and objects with __toString() method
	 * @param $val
	 * @return bool
	 */
	public static function validate($val) {
		return is_scalar($val) || (is_object($val) && method_exists($val, '__toString'));
	}

	public static function _apply($val,  $validatorValue) {
		if (!static::validate($val)) {
			return null;
		}
		$val = (int) $val;
		return $val;
	}

}
