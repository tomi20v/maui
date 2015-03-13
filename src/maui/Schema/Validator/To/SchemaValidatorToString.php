<?php

namespace maui;

class SchemaValidatorToString extends \SchemaValidatorTo {

	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return true;
		}
		elseif (is_object($val)) {
			return method_exists($val, '__toString');
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'shall be a string' . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		if (!$this->validate($val)) {
			return null;
		}
		$val = (string) $val;
		return true;
	}

}
