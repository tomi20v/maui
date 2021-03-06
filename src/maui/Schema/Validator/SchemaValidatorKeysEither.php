<?php

namespace maui;

class SchemaValidatorKeysEither extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			$alreadyHasOne = false;
			foreach ($val as $eachKey=>$eachVal) {
				if (in_array($eachKey, $this->_getValue($Model))) {
					if ($alreadyHasOne) {
						return false;
					}
					$alreadyHasOne = true;
				}
			}
			return true;
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'can contain keys any but only one of (' . implode(', ', $this->_getValue($Model)) . ')' . $this->_getSawValueString(func_num_args(), @implode(', ', @array_keys($val)), $Model);
	}

	public function apply(&$val, $Model=null) {
		return is_array($val) ? true : null;
	}

	public function filter($val, $Model=null) {
		if (!is_array($val)) {
			return null;
		}
		$fromKey = null;
		foreach (array_reverse($this->_getValue($Model)) as $eachPossibleKey) {
			if (!isset($val[$eachPossibleKey])) {
				continue;
			}
			if (isset($fromKey)) {
				unset($val[$fromKey]);
			}
			$fromKey = $eachPossibleKey;
		}
		return $val;
	}

}
