<?php

namespace maui;

class SchemaValidatorRegexp extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			return preg_match($this->_value, $val) ? true : false;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'does not match: ' . $val;
	}

	public function apply(&$val, $Model=null) {
		return is_string($val) ? $val : null;
	}

}
