<?php

class SchemaValidatorKeysRequiredTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::validate
	 */
	function testValidate($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeysRequired($keys);
		$this->assertEquals($isValid, $Validator->validate($data));
	}

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::filter
	 */
	function testFilter($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeysRequired($keys);
		$this->assertEquals($filteredData, $Validator->filter($data));
	}

	function dataProvider() {
		return array(
			array(array('a'=>1,'b'=>2), array('a'=>1,'b'=>2), true),
			array(array('a'=>1,'b'=>2, 'c'=>3), array('a'=>1,'b'=>2, 'c'=>3), true),
			array(array('b'=>2, 'c'=>3), null, false),
		);
	}

}
