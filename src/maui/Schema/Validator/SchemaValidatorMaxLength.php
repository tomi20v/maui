<?php

namespace Maui;

class SchemaValidatorMaxLength extends \SchemaValidator {

	public static function _apply($val,  $validatorValue) {
		if (is_string($val)) {
			$val = mb_substr($val, 0,  $validatorValue);
		}
		else if (is_array($val)) {
			//$len = count($val);
			$val = array_slice($val, 0, $validatorValue, true);
		}
		else return null;
		return $val;
	}

}
