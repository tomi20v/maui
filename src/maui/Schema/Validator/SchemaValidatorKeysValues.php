<?php

namespace maui;

/**
 * Class SchemaValidatorKeysValues - this validator is for checking a given field in
 *
 * @package maui
 */
class SchemaValidatorKeysValues extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			$key = $this->_value[0];
			$values = $this->_value[1];
			if (isset($val[$key]) && !in_array($val[$key], $values)) {
				return false;
			}
			return true;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		$key = $this->_value[0];
		return 'value on key ' . $key . ' should be in (' . implode(', ', $this->_value[1]) . '), but saw: ' . @$val[$key];
	}

	public function apply(&$val, $Model=null) {
		return is_array($val) ? true : null;
	}

	public function filter($val, $Model=null) {
		if (!is_array($val)) {
			return null;
		}
		$key = $this->_value[0];
		if (isset($val[$key]) && !in_array($val[$key], $this->_value[1])) {
			unset($val[$key]);
		}
		return $val;
	}

}
