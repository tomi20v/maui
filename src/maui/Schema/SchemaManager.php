<?php

namespace Maui;

class SchemaManager extends \Schema {

	/**
	 * @var \Schema[string] I hold instances of Schema objects keyed by classname
	 */
	protected static $_pool = array();

	/**
	 * I return the schema for $classname
	 * @param string $className
	 * @return \Schema
	 * @throws \Exception
	 */
	public static function getSchema($classname) {
		if (!array_key_exists($classname, self::$_pool)) {
			throw new \Exception('schema not found');
		}
		return static::$_pool[$classname];
	}

	/**
	 * I register a schema for an object
	 * @param string $classname
	 * @param $schema
	 * @return Schema
	 */
	public static function registerSchema($classname, &$schema) {
		if (isset(self::$_pool[$classname])) {
			throw new \Exception('schema for ' . $classname . ' already registered');
		}
		$schema = static::from($schema);
		self::$_pool[$classname] = $schema;
		return $schema;
	}

	public static function from($schema) {
		if ($schema instanceof \Schmea) {
			return $schema;
		}
		elseif (is_array($schema)) {
			return static::_fromArray($schema);
		}
		else throw new \Exception('cannot create schema from unknown format');
	}

	protected static function _fromArray($schema) {
		$ret = new \Schema();
		foreach ($schema as $eachKey=>$eachVal) {
			// 'field' => 'Classname' reference
			if (is_string($eachKey) && \SchemaReference::isSchemaReference($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaReference::from($eachVal, $eachKey);
			}
			// 'field' (just value, without key)
			elseif (is_numeric($eachKey) && is_string($eachVal)) {
				$ret->_schema[$eachVal] = \SchemaAttr::from($eachVal, $eachVal);
			}
			elseif (is_string($eachKey) && \SchemaAttr::isSchemaAttr($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaAttr::from($eachVal, $eachKey);
			}
			else {
				print_r($eachKey);
				print_r($eachVal);
				die ('FU');
//				throw new \Exception();
			}
		}
		return $ret;
	}

}
