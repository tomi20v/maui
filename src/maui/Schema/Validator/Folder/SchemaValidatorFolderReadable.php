<?php

namespace maui;

class SchemaValidatorFolderReadable extends \SchemaValidator {

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			$fullPath = \Finder::joinPath(APP_ROOT, $this->_getValue($Model), $val);
			return @is_dir($fullPath) && @is_readable($fullPath);
		}
		return null;
	}

	public function getError($val, $Model=null) {
		return 'folder not readable: ' . $val;
	}

	public function apply(&$val, $Model=null) {
		return is_string($val);
	}

}
