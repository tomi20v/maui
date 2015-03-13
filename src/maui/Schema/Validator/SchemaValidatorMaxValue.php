<?php

namespace maui;

class SchemaValidatorMaxValue extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return (int)$val <= $this->_getValue($Model);
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'max ' . $this->_getValue($Model) . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		return is_scalar($val) ? true : null;
	}

}
