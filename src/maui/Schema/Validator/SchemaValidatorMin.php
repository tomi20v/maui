<?php

namespace Maui;

class SchemaValidatorMin extends \SchemaValidator {

	public function validate($val) {
		if (!is_scalar($val)) {
			return false;
		}
		return (int)$val >= $this->_value;
	}

	public function getError($val) {
		return 'min ' . $this->_value;
	}

	public function apply($val) {
		return is_scalar($val) ? $val : null;
	}

	public function filter($val) {
		if (!is_scalar($val)) {
			return null;
		}
		return max((int)$val, $this->_value);
	}

}
