<?php

namespace Maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidator {

	protected $_value = null;

	protected $_parent = null;

	/**
	 * I consturct and return a SchemaValidator*** object based on definition
	 * @param string|array|callable $validator validator definition or direct callback
	 * @param mixed $validatorValue valud for validator
	 * @param \SchemaAttr|null $parent some validators might need accessing parent object
	 * @return \SchemaValidator
	 * @throws \Exception
	 */
	public static function from($validator, $validatorValue, &$parent=null) {
		if (is_string($validator) && class_exists('\\SchemaValidator' . $validator)) {
			$validatorClassname = '\\SchemaValidator' . $validator;
			$Validator = new $validatorClassname($validatorValue, $parent);
		}
		elseif (is_callable($validator)) {
			$Validator = new \SchemaValidatorCallback($validatorValue, $parent);
		}
		else {
			throw new \Exception('TBI');
		}
		return $Validator;
	}

	/**
	 * @param mixed $val parameter value for validator
	 * @param \Model $parent some validators (eg. which compare to or default to another field value) need this, some not
	 */
	function __construct($val, $parent=null) {
		$this->_value = $val;
		$this->_parent = $parent;
	}

	/**
	 * I return true if value is valid for me. Eg. a ToInt validator will return true if $val can be cast to (int)
	 * @param $val
	 * @return bool
	 * @extendMe
	 */
	public function validate($val) {
		return true;
	}

	/**
	 * return error message. Field key shall be prepended later
	 * @param $val
	 * @return string
	 * @extendMe
	 */
	public function getError($val) {
		return 'failed ' . get_called_class() . '(' . $this->_value . ')';
	}

	/**
	 * I return a value applied to this attr. Note it can still be invalid.
	 * @param $val
	 * @return mixed|null apply() should return null if $val is not applicable
	 * @extendMe
	 */
	public function apply($val) {
		return $val;
	}

	/**
	 * I return a value similar to $val but which passes my rule. Eg. a max(5) validator returns 5 if $val>5
	 * @param $val
	 * @return mixed
	 */
	public function filter($val) {
		return $val;
	}

}
