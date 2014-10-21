<?php

namespace maui;

class SchemaValidatorToBool extends \SchemaValidatorToInt {

	const FORMAT = '/^[0-9a-f]{24}$/';

	public function validate($val, $Model=null) {
		return $val ? true : false;
	}

	/**
	 * this error actually cannot happen...
	 * @param $val
	 * @param null $Model
	 * @return string
	 */
	public function getError($val, $Model=null) {
		return 'shall be true/false';
	}

	public function apply(&$val, $Model=null) {
		$val = (bool) $val;
		return true;
	}

}
