<?php

class_exists('\Video') or die('class Video not found');
class_exists('\User') or die('class User not found');
class_exists('\Staff') or die('class Staff not found');

class ModelTest extends \PHPUnit_Framework_TestCase {

	/**
	 * I will contain the default video fixture
	 * @var \Video
	 */
	public $Video;

	public function setup() {
		$this->Video = new \Video(
			json_decode(
				file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/Video.001.json'),
				true
			),
			true
		);
	}

	/**
	 * @covers Model::__init
	 */
	public function testInit() {

		$SchemaManager = \SchemaManager::instance();
		$pool = new ReflectionProperty($SchemaManager, '_pool');
		$pool->setAccessible(true);
		$this->assertCount(2, $pool->getValue($SchemaManager));
		\User::__init();
		$_pool = $pool->getValue($SchemaManager);
		$this->assertCount(3, $_pool);
		$this->assertArrayHasKey('\\User', $_pool);
	}

	/**
	 * @covers Model::hasField
	 */
	public function testHasField() {
		$this->assertTrue($this->Video->hasField('length'));
		$this->assertFalse($this->Video->hasField('asd'));
	}

	/**
	 * @covers Model::originalField
	 */
	public function testFieldField1() {
	}

	/**
	 * @covers Model::originalField
	 * @covers Model::field
	 * @expectedException Exception
	 */
	public function testOriginalFieldAndField1() {
		$originalData = new ReflectionProperty($this->Video, '_originalData');
		$originalData->setAccessible(true);
		$this->assertEquals(
			$originalData->getValue($this->Video),
			$this->Video->originalField()
		);
		$this->assertEquals(54, $this->Video->field('length'));
		$this->Video->length = 24;
		$this->assertEquals(24, $this->Video->field('length'));
		$this->assertEquals(54, $this->Video->originalField('length'));

		// should throw
		$this->Video->originalField('asd');
	}

	/**
	 * @covers Model::field
	 */
	public function testField2() {
		$User = $this->Video->field('user');
		$this->assertEquals(true, $User instanceof \User);
		$this->assertEquals('ArcheAdmin', $User->field('name'));

		$this->assertEquals(54, $this->Video->length);
		$this->Video->field('length', 25);
		$this->assertEquals(25, $this->Video->length);
		$this->assertEquals(54, $this->Video->originalField('length'));

		$User2 = new \User(array('name' => 'OtherAdmin'), true);
		$this->Video->field('user', $User2);
		$User2x = $this->Video->user;
		$this->assertSame($User2, $User2x);
	}

	/**
	 * @covers Model::_relative
	 */
	public function test_relative() {

	}

}
