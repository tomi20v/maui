<?php

class SchemaValidatorKeyValuesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeyValues::validate
	 */
	function testValidate($data, $filteredData, $isValid) {
		$keys = array('a', array(1, 2));
		$Validator = new \SchemaValidatorKeyValues($keys);
		$this->assertEquals($isValid, $Validator->validate($data));
	}

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeyValues::filter
	 */
	function testFilter($data, $filteredData, $isValid) {
		$keys = array('a', array(1, 2));
		$Validator = new \SchemaValidatorKeyValues($keys);
		$this->assertEquals($filteredData, $Validator->filter($data));
	}

	function dataProvider() {
		return array(
			array(array('a'=>1,'b'=>2), array('a'=>1,'b'=>2), true),
			array(array('a'=>2,'b'=>2), array('a'=>2,'b'=>2), true),
			array(array('a'=>3,'b'=>2), array('b'=>2), false),
			array(array('b'=>2), array('b'=>2), true),
		);
	}

}
