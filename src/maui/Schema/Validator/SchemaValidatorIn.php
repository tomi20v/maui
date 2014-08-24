<?php

namespace Maui;

class SchemaValidatorIn extends \SchemaValidator {

	public static function from($validator, $validatorValue, &$parent=null) {
		if (!is_array($validatorValue)) {
			throw new \Exception(echop($validatorValue, 1));
		}
		return parent::from($validator, $validatorValue, $parent);
	}

	public function validate($val) {
		if (is_scalar($val)) {
			return in_array($val, $this->_value);
		}
		elseif (is_array($val)) {
			$val = array_diff($val, $this->_value);
			return empty($val);
		}
		return null;
	}

	public function getError($val) {
		return 'not in {' . implode(', ', $this->_value) . '}';
	}

	public function filter($val) {
		if (is_scalar($val)) {
			return in_array($val, $this->_value) ? $val : null;
		}
		elseif (is_array($val)) {
			return array_intersect($val, $this->_value);
		}
		return null;
	}

}
