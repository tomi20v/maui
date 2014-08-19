<?php

namespace Maui;

class SchemaValidatorIn extends \SchemaValidator {

	/**
	 * I make sure $validatorValue is an array
	 * @see SchemaValidator::from()
	 */
	public static function from($validator, $validatorValue, &$parent=null) {
		if (!is_array($validatorValue)) {
			throw new \Exception(echop($validatorValue, 1));
		}
		return parent::from($validator, $validatorValue, $parent);
	}

	public static function _apply($val,  $validatorValue) {
		return in_array($val, $validatorValue);
	}

}
