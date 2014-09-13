<?php

namespace maui;

/**
 * Class SchemaValidatorToAssoc use this validator to define a set of mini objects as a field
 * 	validator value will mark valid keys in the assoc array value
 * eg.:
 * 	static::$_schema = array(
 * 		(...)
 * 		'meta' => array(
 * 			'toAssoc' => array('name', 'content'),
 * 			(...)
 * 		),
 * 		(...)
 *
 * @package maui
 */
class SchemaValidatorToAssoc extends \SchemaValidatorToArray {

	public function apply($val, $Model=null) {
		if (!$this->validate($val)) {
			return null;
		}
		$val = (array) $val;
		$val = array_intersect_key($val, array_flip($this->_value));
		return $val;
	}

}
