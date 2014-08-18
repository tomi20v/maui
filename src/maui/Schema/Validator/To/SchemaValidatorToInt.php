<?php

namespace Maui;

class SchemaValidatorToInt extends \SchemaValidatorTo {

	public static function validate($val) {
		return is_scalar($val);
	}

	public static function _apply($val,  $validatorValue) {
		if (!static::validate($val)) {
			return null;
		}
		$val = (int) $val;
		return $val;
	}

}
