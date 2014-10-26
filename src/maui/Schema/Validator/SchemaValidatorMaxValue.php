<?php

namespace maui;

class SchemaValidatorMaxValue extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return (int)$val <= $this->_getValue($Model);
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'max ' . $this->_getValue($Model);
	}

	public function apply(&$val, $Model=null) {
		return is_scalar($val) ? true : null;
	}

}
