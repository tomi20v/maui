<?php

namespace maui;

class SchemaValidatorToArray extends \SchemaValidatorTo {

	public function validate($val, $Model=null) {
		if (is_scalar($val) || is_array($val)) {
			return true;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'shall be an array';
	}

	public function apply(&$val, $Model=null) {
		if (!$this->validate($val)) {
			return null;
		}
		$val = (array) $val;
		return true;
	}

}
