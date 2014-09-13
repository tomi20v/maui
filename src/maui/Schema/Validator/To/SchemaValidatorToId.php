<?php

namespace maui;

class SchemaValidatorToId extends \SchemaValidatorTo {

	const FORMAT = '/^[0-9a-f]{24}$/';

	/**
	 * @param $val
	 * @return bool
	 */
	public function validate($val, $Model=null) {
		if ((($val instanceof \MongoId) ||is_string($val)) && preg_match(static::FORMAT, (string)$val)) {
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
		if ($val instanceof \MongoId) {
			return $val;
		}
		elseif (is_string($val) && preg_match(static::FORMAT, $val)) {
			return new \MongoId($val);
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'shall be an ID';
	}

}
