<?php

namespace maui;

class SchemaValidatorKeysRequired extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			foreach ($this->_getValue($Model) as $eachRequiredKey) {
				if (!isset($val[$eachRequiredKey])) {
					return false;
				}
			}
			return true;
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'values must contain all keys from (' . implode(', ', $this->_getValue($Model)) . ')' . $this->_getSawValueString(func_num_args(), implode(', ', array_keys($val)), $Model);
	}

	public function apply(&$val, $Model=null) {
		return is_array($val) ? true : null;
	}

	public function filter($val, $Model=null) {
		if (!$this->validate($val, $Model)) {
			return null;
		}
		return $val;
	}

}
