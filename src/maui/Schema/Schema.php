<?php

namespace Maui;

class Schema {

	// always store objects separately and just refer them
	const REF_REFERENCE = 'reference';
	// store inline, move to reference if reused
	const REF_AUTO = 'auto';
	// never refer, have the data inline
	const REF_INLINE = 'inline';

	/**
	 * relation constants
	 * @todo think it over, maybe the canhavesome and canhaveone shall be changed to
	 *		hassome and hasone including the possibility of 0 and using with min and max
	 */
	// object has no relation like this. pretty useless
	const REL_NONE = 'none';
	// has exactly one
	const REL_HASONE = 'hasOne';
	// has 0 or 1
	const REL_CANHAVEONE = 'canHaveOne';
	// has 0, 1, or more
	const REL_CANHAVESOME = 'canHaveSome';
	// has 1 or more
	const REL_HASSOME = 'hasSome';

	protected $_schema = array();

	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

}
