<?php

namespace Maui;

class SchemaValidatorMax extends \SchemaValidator {

	public static function _apply($val,  $validatorValue) {
		if (!is_scalar($val)) {
			return null;
		}
		$val = 0 + $val;
		return $val > $validatorValue ? null : $val;
	}

}
