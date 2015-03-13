<?php

namespace maui;

class SchemaValidatorRegexp extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			return preg_match($this->_value, $val) ? true : false;
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'does not match: ' . echon($this->_value) . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		return is_string($val) ? $val : null;
	}

}
