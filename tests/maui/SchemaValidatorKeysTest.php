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
	 * @covers SchemaValidatorKeys::apply
	 */
	function testApply($data, $filteredData, $isValid) {
		$keys = array('a', 'b');
		$Validator = new \SchemaValidatorKeys($keys);
		$this->assertEquals($filteredData, $Validator->apply($data));
	}

	function dataProvider() {
		return array(
			array(array('a'=>1,'b'=>2), array('a'=>1,'b'=>2), true),
		);
	}

}
