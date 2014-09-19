<?php

class SchemaValidatorKeysValuesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider filterDataProvider
	 * @covers SchemaValidatorKeysValues::validate
	 */
	function testValidate($data, $filteredData, $isValid) {
		$keys = array('a', array(1, 2));
		$Validator = new \SchemaValidatorKeysValues($keys);
		$this->assertEquals($isValid, $Validator->validate($data));
	}

	/**
	 * @dataProvider filterDataProvider
	 * @covers SchemaValidatorKeysValues::filter
	 */
	function testFilter($data, $filteredData, $isValid) {
		$keys = array('a', array(1, 2));
		$Validator = new \SchemaValidatorKeysValues($keys);
		$this->assertEquals($filteredData, $Validator->filter($data));
	}

	function filterDataProvider() {
		return array(
			array('a', null, false),
			array(array('a'=>1,'b'=>2), array('a'=>1,'b'=>2), true),
			array(array('a'=>2,'b'=>2), array('a'=>2,'b'=>2), true),
			array(array('a'=>3,'b'=>2), array('b'=>2), false),
			array(array('b'=>2), array('b'=>2), true),
		);
	}

	/**
	 * @covers SchemaValidatorKeysValues::apply
	 */
	function testApply() {
		$keys = array('a', array(1, 2));
		$Validator = new \SchemaValidatorKeysValues($keys);
		$v = array();
		$vv = array();
		$this->assertTrue($Validator->apply($v));
		$this->assertEquals($v, $vv);
		$this->assertTrue($Validator->apply($v));
		$this->assertEquals($v, $vv);
		$v = 'a';
		$this->assertNull($Validator->apply($v));
	}

}
