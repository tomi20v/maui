<?php

namespace Maui;

class Schema {

	protected $_schema = array();

	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

}
