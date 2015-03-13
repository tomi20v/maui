<?php

namespace maui;

/**
 * @todo maybe I should strip the maui namespace from generated _type variables
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

	public function getError($val=null, $Model=null) {
		return 'oops';
	}

	/**
	 * I apply $val if not empty. If empty, will set automaticly. Will not overwrite _type if it is set and is a subclass
	 * 		of $val
	 * @param string|null $val
	 * @param \Model|null $Model
	 * @return bool|mixed|null
	 */
	public function apply(&$val, $Model=null) {

		if (empty($val)) {
			if (is_object($Model)) {
				$val = get_class($Model);
			}
			else {
				// @todo I think this should just be an error...?
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
		$val = $Model->Data()->getField($key);
		$this->apply($val, $Model);
		return $Model->Data()->setField($key, $val);
	}

}
