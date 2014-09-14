<?php

class SchemaValidatorKeysEitherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::validate
	 */
	function testValidate($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeysEither($keys);
		$this->assertEquals($isValid, $Validator->validate($data));
	}

	/**
	 * @dataProvider dataProvider
	 * @covers SchemaValidatorKeys::filter
	 */
	function testFilter($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeysEither($keys);
		$this->assertEquals($filteredData, $Validator->filter($data));
	}

	function dataProvider() {
		return array(
			array(array('a'=>1, 'c'=>3), array('a'=>1,'c'=>3), true),
			array(array('a'=>1, 'b'=>2, 'c'=>3), array('a'=>1, 'c'=>3), false),
			array(array('b'=>2, 'a'=>1, 'c'=>3), array('a'=>1, 'c'=>3), false),
			array(array('c'=>3), array('c'=>3), true),
		);
	}

}
