<?php

namespace Maui;

class SchemaValidatorToInt extends \SchemaValidatorTo {

	/**
	 * @param $val
	 * @return bool
	 */
	public function validate($val, $Model=null) {
		if (is_scalar($val)) {
			return true;
		}
		return null;
	}

	/**
	 * @param $val
	 * @param $validatorValue
	 * @return int|null
	 */
	public function apply($val, $Model=null) {
		if (!$this::validate($val)) {
			return null;
		}
		$val = (int) $val;
		return $val;
	}

	public function getError($val, $Model=null) {
		return 'shall be a number';
	}

}
