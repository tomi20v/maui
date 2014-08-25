<?php

namespace Maui;

class SchemaValidatorMin extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (!is_scalar($val)) {
			return false;
		}
		return (int)$val >= $this->_value;
	}

	public function getError($val, $Model=null) {
		return 'min ' . $this->_value;
	}

	public function apply($val, $Model=null) {
		return is_scalar($val) ? $val : null;
	}

}
