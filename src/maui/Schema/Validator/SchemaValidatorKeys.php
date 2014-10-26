<?php

namespace maui;

class SchemaValidatorKeys extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			return !count(array_diff_key($val, array_flip($this->_getValue($Model))));
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'keys in array shall be {' . implode(', ', $this->_getValue($Model)) . ')';
	}

	public function apply(&$val, $Model=null) {
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
