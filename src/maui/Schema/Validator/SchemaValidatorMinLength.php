<?php

namespace Maui;

class SchemaValidatorMinLength extends \SchemaValidator {

	public static function _apply($val,  $validatorValue) {
		if (is_string($val)) {
			if (mb_strlen($val) < $validatorValue) {
				return null;
			}
		}
		else if (is_array($val)) {
			if (count($val) < $validatorValue) {
				return null;
			}
		}
		else return null;
		return $val;
	}

}
