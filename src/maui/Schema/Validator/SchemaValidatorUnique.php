<?php

namespace maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidatorUnique extends \SchemaValidator {

	/**
	 * I always return true
	 * @param $val
	 * @return bool true
	 */
	public function validate($val, $Model=null) {
		return true;
	}

	public function getError($val=null, $Model=null) {
		return 'not unique';
	}

}
