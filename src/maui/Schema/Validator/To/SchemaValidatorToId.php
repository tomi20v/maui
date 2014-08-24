<?php

namespace Maui;

class SchemaValidatorToId extends \SchemaValidatorTo {

	const FORMAT = '/^[0-9a-f]{24}$/';

	/**
	 * @param $val
	 * @return bool
	 */
	public function validate($val) {
		return ($val instanceof \MongoId) && preg_match(static::FORMAT, (string)$val);
	}

	/**
	 * @param $val
	 * @param $validatorValue
	 * @return int|null
	 */
	public function apply($val) {
		if ($val instanceof \MongoId) {
			return $val;
		}
		elseif (is_string($val) && preg_match(static::FORMAT, $val)) {
			return new \MongoId($val);
		}
		return null;
	}

	/**
	 * @todo here I should ensure ID is loaded after an insert
	 * @throws \Exception
	 */
	public function afterSave() {
		throw new \Exception('TBI');
	}

}
