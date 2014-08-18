<?php

namespace Maui;

class Schema {

	// always store objects separately and just refer them
	const REF_ALWAYS = 'refAlways';
	// store inline, move to reference if reused
	const REF_AUTO = 'refMatch';
	// never refer, jsut use the data in this schema
	const REF_NEVER = 'refNever';

	const REL_NONE = 'relNone';
	const REL_HASONE = 'relHasOne';
	const REL_CANHAVEONE = 'relCanHaveOne';
	const REL_CANHAVESOME = 'relCanHaveSome';
	const REL_HASSOME = 'relHasSome';

	protected $_schema = array();

	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

}
