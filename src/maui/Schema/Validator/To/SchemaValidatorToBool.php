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
	public function getError($val=null, $Model=null) {
		return 'shall be true/false' . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		$val = (bool) $val;
		return true;
	}

}
