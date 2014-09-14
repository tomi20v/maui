<?php

class SchemaValidatorKeysTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::validate
	 */
	function testValidate($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeys($keys);
		$this->assertEquals($isValid, $Validator->validate($data));
	}

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::filter
	 */
	function testFilter($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeys($keys);
		$this->assertEquals($filteredData, $Validator->filter($data));
	}

	function dataProvider() {
		return array(
			array(array('a'=>1,'b'=>2), array('a'=>1,'b'=>2), true),
			array(array('a'=>1), array('a'=>1), true),
			array(array('a'=>1,'b'=>2, 'c'=>3), array('a'=>1,'b'=>2), false),
			array(1, null, false),
			array(array('x'=>9), array(), false),
			array(array('a'=>1, 'x'=>9), array('a'=>1), false),
		);
	}

}
