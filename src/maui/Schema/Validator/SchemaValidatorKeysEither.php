<?php

namespace maui;

class SchemaValidatorKeysEither extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_array($val)) {
			$alreadyHasOne = false;
			foreach ($val as $eachKey=>$eachVal) {
				if (in_array($eachKey, $this->_value)) {
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

	public function getError($val, $Model=null) {
		return 'values can contain either but only one of (' . implode(', ', $this->_value[]) . '), but saw: ' . implode(', ', array_keys($val));
	}

	public function apply(&$val, $Model=null) {
		return is_array($val) ? true : null;
	}

	public function filter($val, $Model=null) {
		if (!is_array($val)) {
			return null;
		}
		$fromKey = null;
		foreach (array_reverse($this->_value) as $eachPossibleKey) {
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
