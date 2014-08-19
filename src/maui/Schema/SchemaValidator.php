<?php

namespace Maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidator {

	protected $_value = null;

	protected $_parent = null;

	public static function from($validator, $validatorValue, &$parent=null) {
		if (is_callable($validator)) {
			$Validator = new \SchemaValidatorCallback($validatorValue, $parent);
		}
		elseif (is_string($validator) && class_exists('\\SchemaValidator' . $validator)) {
			$validatorClassname = '\\SchemaValidator' . $validator;
			$Validator = new $validatorClassname($validatorValue, $parent);
		}
		else {
			throw new \Exception('TBI');
		}
		return $Validator;
	}

	/**
	 * @param mixed $value parameter value for validator
	 * @param \Model $parent some validators (eg. which compare to or default to another field value) need this, some not
	 */
	function __construct($value, $parent=null) {
		$this->_value = $value;
		$this->_parent = $parent;
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
