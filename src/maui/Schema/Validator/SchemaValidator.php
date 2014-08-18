<?php

namespace Maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidator {

	protected $_value = null;

	public static function from($validator, $validatorValue) {
		if (is_callable($validator)) {
			$Validator = new \SchemaValidatorCallback($validatorValue, $validator);
		}
		return $Validator;
	}

	function __construct($value) {
		$this->_value = $value;
	}

	/**
	 * I return true if value can be applied to schema without transformation
	 * @param $val
	 * @return bool
	 */
	public static function validate($val) {
		return $val == static::apply($val);
	}

	public function apply($val) {
		return (static::_apply($val, $this->_value));
	}

	/**
	 * To apply a value to a schema attribute it might have to be transformed (eg. to string, to numeric)
	 * 	apply shall also cast the value to a proper type, and return null if not possible. this will make the validate() fail
	 * @param $val value to set
	 * @param null $default default value to return in case $val is null
	 * @return null|mixed
	 */
	protected static function _apply($val, $validatorValue) {
		return $val;
	}

}
