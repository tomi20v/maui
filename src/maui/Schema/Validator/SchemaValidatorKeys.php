<?php

namespace maui;

class SchemaValidatorKeys extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			return !count(array_diff_key($val, array_flip($this->_getValue($Model))));
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'keys in array shall be {' . implode(', ', $this->_getValue($Model)) . ')' . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		// should be $val = is_array($val) ? $val : [$val]; return true; no ?
		return is_array($val) ? true : array($val);
	}

	public function filter($val, $Model=null) {
		if (!is_array($val)) {
			return null;
		}
		$val = array_intersect_key($val, array_flip($this->_getValue($Model)));
		return $val;
	}

}
