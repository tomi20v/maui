<?php

namespace maui;

class SchemaValidatorToBool extends \SchemaValidatorTo {

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
