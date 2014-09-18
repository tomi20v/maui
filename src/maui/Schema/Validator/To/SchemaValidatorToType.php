<?php

namespace maui;

/**
 * Class SchemaValidatorToType - I am to be used with the mandatory _type field so that
 * 		_type field contains the class of object which saves the data. However, if _type
 * 		already marks a subclass of current model, the _type is not changed (otherwise
 * 		the extra data would become invalid in the parent schema)
 *
 * @package maui
 */
class SchemaValidatorToType extends \SchemaValidatorTo {

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			return true;
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'oops';
	}

	// @TODO I should set the minimal sufficient type by schema. Eg. if an object doesn't use its specific fields then save as its parent
	public function apply(&$val, $Model=null) {

		// set only if object is not empty
		if (!empty($Model) && $Model->isEmpty('_type')) {
			// @todo this won't set the _type to empty just give a validation error...
			return null;
		}

		if (empty($val)) {
			if (is_object($Model)) {
				$val = get_class($Model);
			}
			else {
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); die('TBI');
			}
		}
		// if loaded data had type of a subclass, do not widen it to base class
		elseif (is_string($val) && ($Model instanceof \Model)) {
			if (class_exists($val) && is_subclass_of($Model, $val)) {
				$val = get_class($Model);
			}
		}

		$val = empty($val) ? null : $val;

		return true;

	}

	public function beforeSave($key, $Model) {
		$val = $Model->field($key);
		$res = $this->apply($val, $Model);
		return $Model->field($key, $val);
	}

}
