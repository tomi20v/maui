<?php

namespace maui;

class SchemaValidatorMinLength extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			return mb_strlen($val) >= $this->_getValue($Model);
		}
		else if (is_array($val)) {
			return count($val) >= $this->_getValue($Model);
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'min length ' . $this->_getValue($Model);
	}

	public function apply(&$val, $Model=null) {
		return (is_string($val) || is_array($val)) ? true : null;
	}

}
