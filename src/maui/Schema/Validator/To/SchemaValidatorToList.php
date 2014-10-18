<?php

namespace maui;

class SchemaValidatorToList extends \SchemaValidatorToArray {

	public function apply(&$val, $Model=null) {
		parent::apply($val, $Model);
		$val = array_merge($val);
		return true;
	}

}
