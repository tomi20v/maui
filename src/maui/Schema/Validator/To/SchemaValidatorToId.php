<?php

namespace maui;

class SchemaValidatorToId extends \SchemaValidatorTo {

	const FORMAT = '/^[0-9a-f]{24}$/';

	public function validate($val, $Model=null) {
		if ((($val instanceof \MongoId) ||is_string($val)) && preg_match(static::FORMAT, (string)$val)) {
			return true;
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'shall be an ID' . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		if ($val instanceof \MongoId) {
			return true;
		}
		elseif (is_string($val) && preg_match(static::FORMAT, $val)) {
			$val = new \MongoId($val);
			return true;
		}
		return null;
	}

}
