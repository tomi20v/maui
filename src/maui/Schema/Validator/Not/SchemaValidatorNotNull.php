<?php

namespace Maui;

class SchemaValidatorNotNull extends \SchemaValidator {

	/**
	 * @param $val
	 * @return bool
	 */
	public static function validate($val) {
		return !is_null($val);
	}

	public static function _apply($val,  $validatorValue) {
		return $val;
	}

}
