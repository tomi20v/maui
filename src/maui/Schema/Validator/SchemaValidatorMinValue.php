<?php

namespace maui;

class SchemaValidatorMinValue extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return (int)$val >= $this->_value;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'min ' . $this->_value;
	}

	public function apply(&$val, $Model=null) {
		return is_scalar($val) ? true : null;
	}

}
