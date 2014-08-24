<?php

namespace Maui;

class SchemaValidatorMax extends \SchemaValidator {

	public function validate($val) {
		if (!is_scalar($val)) {
			return false;
		}
		return (int)$val <= $this->_value;
	}

	public function getError($val) {
		return 'max ' . $this->_value;
	}

	public function apply($val) {
		return is_scalar($val) ? $val : null;
	}

	public function filter($val) {
		if (!is_scalar($val)) {
			return null;
		}
		return min((int)$val, $this->_value);
	}

}
