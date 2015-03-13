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
			return in_array($val, $this->_getValue($Model));
		}
		elseif (is_array($val)) {
			$val = array_diff($val, $this->_getValue($Model));
			return empty($val);
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'must be in {' . implode(', ', $this->_getValue($Model)) . '}' . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
 		return is_scalar($val) || is_array($val) ? $val : null;
	}

}
