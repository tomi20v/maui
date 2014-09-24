<?php

namespace maui;

class SchemaValidatorIn extends \SchemaValidator {

	public static function from($validator, $validatorValue, &$parent=null) {
		if (!is_array($validatorValue)) {
			throw new \Exception(echop($validatorValue, 1));
		}
		return parent::from($validator, $validatorValue, $parent);
	}

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return in_array($val, $this->_value);
		}
		elseif (is_array($val)) {
			$val = array_diff($val, $this->_value);
			return empty($val);
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'not in {' . implode(', ', $this->_value) . '}';
	}

	public function apply(&$val, $Model=null) {
 		return is_scalar($val) || is_array($val) ? $val : null;
	}

}
