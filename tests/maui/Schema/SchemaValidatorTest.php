<?php

class SchemaValidatorTester extends maui\SchemaValidator{
	public $_value;
};

class SchemaValidatorTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
	}

	/**
	 * @covers SchemaValidator::from
	 */
	public function testFrom() {
		$value = 2;
		/**
		 * @var SchemaValidatorTester $Validator
		 */
		$Validator = \maui\SchemaValidator::from('Tester', $value);
		$this->assertTrue($Validator instanceof SchemaValidatorTester);
		$this->assertEquals($Validator->_value, $value);
	}

	/**
	 * @covers SchemaValidator::from
	 * @expectedException Exception
	 * @expectedExceptionMessage
	 */
	public function testFrom2() {
		\maui\SchemaValidator::from(array(), 2);
	}

	/**
	 * @covers SchemaValidator::__construct
	 * @dataProvider __constructDataProvider
	 */
	public function test__construct($value, $Parent, $isMulti, $objIsMulti) {
		$Parent = new stdClass();
		$v = new \ReflectionProperty('SchemaValidator', '_value');
		$v->setAccessible(true);
		$p = new \ReflectionProperty('SchemaValidator', '_parent');
		$p->setAccessible(true);
		$i = new \ReflectionProperty('SchemaValidator', '_isMulti');
		$i->setAccessible(true);
		$value = 2;

		$Validator = new \maui\SchemaValidator($value, $Parent, $isMulti);
		$this->assertTrue($Validator instanceof \maui\SchemaValidator);
		$this->assertSame($value, $v->getValue($Validator));
		$this->assertSame($Parent, $p->getValue($Validator));
		$this->assertSame($objIsMulti, $i->getValue($Validator));

	}

	public function __constructDataProvider() {
		return array(
			array(2, null, null, false),
			array(2, new stdClass(), null, false),
			array(2, new stdClass(), true, true),
			array(2, new stdClass(), false, false),
		);
	}



}
