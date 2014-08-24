<?php

namespace Maui;

class SchemaValidatorMaxLength extends \SchemaValidator {

	public function validate($val) {
		if (is_string($val)) {
			return mb_strlen($val) <= $this->_value;
		}
		else if (is_array($val)) {
			return count($val) <= $this->_value;
		}
		return null;
	}

	public function getError($val) {
		return 'max length' . $this->_value;
	}

	public function apply($val) {
		return (is_string($val) || is_array($val)) ? $val : null;
	}

	public function filter($val) {
		if (is_string($val)) {
			return mb_substr($val, 0,  $this->_value);
		}
		else if (is_array($val)) {
			return array_slice($val, 0, $this->_value, true);
		}
		else return null;
	}

}
