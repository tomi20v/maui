<?php

class_exists('\Video') or die('class Video not found');
class_exists('\User') or die('class User not found');
class_exists('\Staff') or die('class Staff not found');

class ModelTest extends \maui\TestCase {

	/**
	 * I will contain the default video fixture
	 * @var \Video
	 */
	public $Video;

	/**
	 * @var \ReflectionProperty
	 */
	public $propData;

	/**
	 * @var \ReflectionProperty
	 */
	public $propOriginalData;

	public function setup() {
		parent::setup();
		$this->Video = new \Video(
			json_decode(
				file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/Video.001.json'),
				true
			),
			true
		);
		$this->propData = new \ReflectionProperty('Model', '_data');
		$this->propData->setAccessible(true);
		$this->propOriginalData = new \ReflectionProperty('Model', '_originalData');
		$this->propOriginalData->setAccessible(true);
	}

	/**
	 * @covers Model::__construct
	 */
	public function test__construct() {
		$data = array(
			'title' => 't',
		);
		$Video = new \Video($data);
		$this->assertEquals(array(), $this->propOriginalData->getValue($Video));
		$this->assertEquals($data, $this->propData->getValue($Video));
		$Video = new \Video($data, true);
		$this->assertEquals($data, $this->propOriginalData->getValue($Video));
		$this->assertEquals(array(), $this->propData->getValue($Video));
		$id = new \MongoId('000000000000000000000001');
		$data = array(
			'_id' => $id,
		);
		$Video = new \Video($id);
		$this->assertEquals(array(), $this->propOriginalData->getValue($Video));
		$this->assertEquals($data, $this->propData->getValue($Video));
		$Video = new \Video($id, true);
		$this->assertEquals($data, $this->propOriginalData->getValue($Video));
		$this->assertEquals(array(), $this->propData->getValue($Video));
		$Video = new \Video('000000000000000000000001');
		$this->assertEquals(array(), $this->propOriginalData->getValue($Video));
		$this->assertEquals($data, $this->propData->getValue($Video));
		$Video = new \Video('000000000000000000000001', true);
		$this->assertEquals($data, $this->propOriginalData->getValue($Video));
		$this->assertEquals(array(), $this->propData->getValue($Video));
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
		$this->assertArrayHasKey('User', $_pool);
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
		$this->markTestIncomplete();
	}

	/**
	 * @covers Model::loadAsSaved
	 * @expectedException Exception
	 */
	public function testLoadAsSaved() {
		$Video = \Video::loadAsSaved(array('_id'=>'000000000000000000000002'));
		$this->assertEquals('Video', get_class($Video));
		$Video = \Video::loadAsSaved(array('_id'=>new MongoId('000000000000000000000003')));
		$this->assertEquals('VideoEpisode', get_class($Video));
		$Video = \Video::loadAsSaved(array('_id'=>new MongoId('000000000000000000000004')));
	}

	public function testEnsureLoaded() {
		$Video = new \Video(array('_id' => new \MongoId('000000000000000000000001')));
		$this->assertNull($Video->title);
		$Video->ensureLoaded();
		$this->assertNotNull($Video->title);
	}

}
