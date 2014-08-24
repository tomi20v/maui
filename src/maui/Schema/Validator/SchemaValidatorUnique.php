<?php

namespace Maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidatorUnique extends \SchemaValidator {

	/**
	 * I always return true
	 * @param $val
	 * @return bool true
	 */
	public function validate($val) {
		return true;
	}

	/**
	 * I don't convert
	 */
	protected static function _apply_($val, $validatorValue) {
		return $val;
	}

	public function beforeSave() {
		throw new \Exception('TBI');
		return true;
	}

}
