<?php

namespace maui;

class SchemaValidatorToInt extends \SchemaValidatorTo {

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return true;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'shall be a number';
	}

	public function apply(&$val, $Model=null) {
		if (!$this::validate($val)) {
			return null;
		}
		$val = (int) $val;
		return true;
	}

}
