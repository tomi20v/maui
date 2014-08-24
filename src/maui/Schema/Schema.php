<?php

namespace Maui;

class Schema implements \IteratorAggregate {

//	use \Maui\TraitIterable;

	protected $_schema = array();

	public function __get($key) {
		switch(true) {
			case array_key_exists($key, $this->_schema):
				return $this->_schema[$key];
			default:
				throw new \Exception(echon($key));
		}
	}

	public function getIterator() {
		return new \ArrayIterator($this->_schema);
	}

	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaObject);
	}

	public function attrs() {
		return array_keys($this->_schema);
	}

	/**
	 * @param $key
	 * @return \SchemaAttr
	 */
	public function attr($key) {
		return $this->_schema[$key];
	}

	/**
	 * @param $key
	 * @return \SchemaValidator[]
	 */
	public function validators($key) {
		return $this->_schema[$key]->validators();
	}


}
