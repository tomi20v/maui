<?php

namespace maui;

class SchemaValidatorKeys extends \SchemaValidatorTo {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			return !count(array_diff_key($val, array_flip($this->_value)));
		}
		return null;
	}

	public function apply($val, $Model=null) {
		return is_array($val) ? $val : array($val);
	}

	public function getError($val, $Model=null) {
		return 'keys in array shall be {' . implode(', ', $this->_value) . ')';
	}

	public function filter($val, $Model=null) {
		if (!is_array($val)) {
			return null;
		}
		$val = array_intersect_key($val, array_flip($this->_value));
		return $val;
	}

}
