<?php

class_exists('\Video') or die('class Video not found');
class_exists('\User') or die('class User not found');
class_exists('\Staff') or die('class Staff not found');

class ModelTester1 extends \Model {
	static $_schema = array();
}
class ModelTester2 extends \Model {
	static $_schema = array('asd');
	public static function getCollectionClassname() {
		return 'NonexistingCollection';
	}
}
// tester classes to examine used DB collection
class VTester1 extends \Video {
	public static function getDbCollection() {
		return static::_getDbCollection();
	}
}
class VETester1 extends \VideoEpisode {
	public static function getDbCollection() {
		return static::_getDbCollection();
	}
}
class VPTester1 extends \VideoPilot {
	public static function getDbCollection() {
		return static::_getDbCollection();
	}
}


class ModelTest extends \maui\TestCase {

	public $videoData;

	/**
	 * I will contain the default video fixture
	 * @var \Video
	 */
	public $Video;

	/**
	 * I will contain the default video fixture but loaded into an VideoEpisode object
	 * @var \VideoEpisode
	 */
	public $VideoEpisode;

	/**
	 * I will contain the default video fixture but loaded into an VideoPilot object
	 * @var \VideoPilot
	 */
	public $VideoPilot;

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
		$videoData = json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/Video.001.json'), true);
		$this->videoData = $videoData;
		$this->Video = new \Video($videoData, true);
		$this->VideoEpisode = new \VideoEpisode($videoData, true);
		$this->VideoPilot = new \VideoPilot($videoData, true);
		$this->propData = new \ReflectionProperty('Model', '_data');
		$this->propData->setAccessible(true);
		$this->propOriginalData = new \ReflectionProperty('Model', '_originalData');
		$this->propOriginalData->setAccessible(true);
	}

	////////////////////////////////////////////////////////////////////////////////
	// basic & magic
	////////////////////////////////////////////////////////////////////////////////

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

	public function test__get() {
		$this->markTestIncomplete();
	}

	public function test__set() {
		$this->markTestIncomplete();
	}

	/**
	 * @covers Model::__init
	 * @expectedException \Exception
	 * @expectedExceptionMessage schema must not be empty, saw empty in
	 */
	public function testInit() {

		$SchemaManager = \SchemaManager::instance();
		$pool = new ReflectionProperty($SchemaManager, '_pool');
		$pool->setAccessible(true);
		$this->assertCount(4, $pool->getValue($SchemaManager));
		\User::__init();
		$_pool = $pool->getValue($SchemaManager);
		$this->assertCount(5, $_pool);
		$this->assertArrayHasKey('User', $_pool);

		\ModelTester1::__init();

	}

	/**
	 * @covers Model::getIterator
	 */
	public function testGetIterator() {
		$Iterator = $this->Video->getIterator();
		$this->assertTrue($Iterator instanceof \ArrayIterator);
	}

	/**
	 * @covers Model::_getSchema
	 */
	public function test_getSchema() {
		$method = new \ReflectionMethod($this->Video, '_getSchema');
		$method->setAccessible(true);
		$Schema = $method->invoke($this->Video);
		$this->assertTrue($Schema instanceof \Schema);
	}

	////////////////////////////////////////////////////////////////////////////////
	//	CRUD etc
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * @covers Model::_getDbCollection
	 */
	public function test_getDbCollection() {

		$this->assertTrue(is_subclass_of('VTester1', 'Video'));
		$DbCollection = \VTester1::getDbCollection();
		$this->assertTrue($DbCollection instanceof \MongoCollection);
		$this->assertEquals('VideoCollection', $DbCollection->getName());

		$this->assertTrue(is_subclass_of('VETester1', 'VideoEpisode'));
		$DbCollection = \VETester1::getDbCollection();
		$this->assertTrue($DbCollection instanceof \MongoCollection);
		$this->assertEquals('VideoCollection', $DbCollection->getName());

		$this->assertTrue(is_subclass_of('VPTester1', 'VideoPilot'));
		$DbCollection = \VPTester1::getDbCollection();
		$this->assertTrue($DbCollection instanceof \MongoCollection);
		$this->assertEquals('VideoCollection', $DbCollection->getName());

	}

	/**
	 * @covers Model::getCollectionClassname
	 */
	public function testGetCollectionClassname() {
		$this->assertEquals('VideoCollection', \Video::getCollectionClassname());
		// test if model doesn't have own collection class, it shall fallback to an existing collection class
		$this->assertFalse(class_exists('VideoPilotCollection'));
		$this->assertEquals('VideoCollection', \VideoPilot::getCollectionClassname());
		$this->assertEquals('VideoEpisodeCollection', \VideoEpisode::getCollectionClassname());

		$this->assertEquals('Collection', \ModelTester1::getCollectionClassname());

	}

	/**
	 * @covers Model::getCollection
	 */
	public function testGetCollection() {

		$VideoCollection = $this->Video->getCollection();
		$this->assertEquals('VideoCollection', get_class($VideoCollection));

		$VideoEpisodeCollection = $this->VideoEpisode->getCollection();
		$this->assertEquals('VideoEpisodeCollection', get_class($VideoEpisodeCollection));

		$VideoPilotCollection = $this->VideoPilot->getCollection();
		$this->assertEquals('VideoCollection', get_class($VideoPilotCollection));

		$M = new \ModelTester2();
		/**
		 * @var Collection $NonexistingCollection
		 */
		$NonexistingCollection = $M->getCollection();
		// if I ever define root Collection class, this will lose its namespace
		$this->assertEquals('maui\Collection', get_class($NonexistingCollection));
		$P = new \ReflectionProperty($NonexistingCollection, '_modelClassname');
		$P->setAccessible(true);
		$this->assertEquals('ModelTester2', $P->getValue($NonexistingCollection));

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
	 * @expectedExceptionMessage asd
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
	 * @expectedExceptionMessage class source cannot be loaded for
	 */
	public function testLoadAsSaved() {
		$Video = \Video::loadAsSaved(array('_id'=>'000000000000000000000002'));
		$this->assertEquals('Video', get_class($Video));
		$Video = \Video::loadAsSaved(array('_id'=>new MongoId('000000000000000000000003')));
		$this->assertEquals('VideoEpisode', get_class($Video));
		$Video = \Video::loadAsSaved(array('_id'=>new MongoId('000000000000000000000004')));
	}

	/**
	 * @covers Model::loadAs
	 * @expectedException Exception
	 * @expectedExceptionMessage class source cannot be loaded for NonexistingModel
	 */
	public function testLoadAs() {
		$Video = \Video::loadAs(array('_id'=>'000000000000000000000002'));
		$this->assertEquals('Video', get_class($Video));
		$Video = \Video::loadAs(array('_id'=>'000000000000000000000002'), 'Video');
		$this->assertEquals('Video', get_class($Video));
		$Video = \Video::loadAs(array('_id'=>'000000000000000000000002'), 'VideoEpisode');
		$this->assertEquals('VideoEpisode', get_class($Video));
		$Video = \Video::loadAs(array('_id'=>'000000000000000000000002'), 'ModelTesterA');
		$this->assertEquals('ModelTesterA', get_class($Video));
		$Video = \Video::loadAs(array('_id'=>'000000000000000000000002'), 'NonexistingModel');
	}

	/**
	 * @covers Model::ensureLoaded
	 */
	public function testEnsureLoaded() {
		$Video = new \Video(array('_id' => new \MongoId('000000000000000000000001')));
		$this->assertNull($Video->title);
		$Video->ensureLoaded();
		$this->assertNotNull($Video->title);
	}

	/**
	 * @covers Model::load
	 */
	public function testLoad() {
		$Video = new \Video();
		$this->assertFalse($Video->load());
		$this->assertFalse($Video->load(false));
		$this->assertSame($Video, $Video->load(true));
		$Video = new \Video(array('_id' => new \MongoId('000000000000000000000001')));
		$this->assertSame($Video, $Video->load());
		$this->assertNotNull($Video->title);
	}

	////////////////////////////////////////////////////////////////////////////////
	// setters getters, data related
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * @covers Model::match
	 * @dataProvider matchDataProvider
	 */
	public function testMatch($arg1, $arg2, $expected) {
		$this->assertEquals($expected, \Model::match($arg1, $arg2));
	}

	public function matchDataProvider() {
		return array(
			array(array('a'=>1, 'b'=>2, 'c'=>3), array('a'=>1, 'b'=>2), true),
			array(array('a'=>1, 'b'=>2, 'c'=>3), array('a'=>2, 'b'=>2), false),
			array(array('a'=>1, 'b'=>2), array('a'=>1, 'b'=>2), true),
			array(array('a'=>1, 'b'=>2), array('a'=>2, 'b'=>2), false),
			array(array('a'=>1, 'b'=>2), array('a'=>1, 'b'=>2, 'c'=>3), false),
			array(array('a'=>1, 'b'=>2), array('a'=>2, 'b'=>2, 'c'=>3), false),
			array(new \Video(array('title'=>'t','subtitle'=>'st')), new \Video(array('title'=>'t')), true),
			array(new \Video(array('title'=>'t','subtitle'=>'st')), new \Video(array('title'=>'t2')), false),
			array(new \Video(array('title'=>'t')), new \Video(array('title'=>'t')), true),
			array(new \Video(array('title'=>'t')), new \Video(array('title'=>'t2')), false),
			array(new \Video(array('title'=>'t')), new \Video(array('title'=>'t','subtitle'=>'st')), false),
			array(new \Video(array('title'=>'t')), new \Video(array('title'=>'t','subtitle'=>'st2')), false),
			array('title', array('a'=>1), null),
			array(array('a'=>1), 'title', null),
			array(
				array('a'=>1,'b'=>array('x'=>5,'y'=>6)),
				array('a'=>1),
				true
			),
			array(
				array('a'=>1,'b'=>array('x'=>5,'y'=>6)),
				array('a'=>1,'b'=>array('x'=>5)),
				true
			),
			array(
				array('a'=>1,'b'=>array('x'=>5,'y'=>6)),
				array('b'=>array('x'=>5,'y'=>6)),
				true
			),
			array(
				array('a'=>1,'b'=>array('x'=>5)),
				array('b'=>array('x'=>6)),
				false
			),
		);
	}

	////////////////////////////////////////////////////////////////////////////////
	// validation
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * @covers Model::validate
	 * @expectedException Exception
	 * @expectedExceptionMessage cannot validate non existing field def
	 */
	public function testValidate() {
		$this->Video = new \Video(0);
		$this->assertFalse($this->Video->validate());
		$P = new \ReflectionProperty($this->Video, '_isValidated');
		$P->setAccessible(true);
		$Q = new \ReflectionProperty($this->Video, '_validationErrors');
		$Q->setAccessible(true);
		$this->assertCount(16, $P->getValue($this->Video));

		$this->assertEquals(array('title','subtitle','length'), array_keys($Q->getValue($this->Video)));

		$V = new \Video(array('title'=>'ttttt', 'subtitle'=>'sssss'));
		$this->assertTrue($V->validate(false));
		$this->assertFalse($V->validate(true));

		$this->assertFalse($this->Video->validate(array('_id','title')));
		$this->assertTrue($this->Video->validate(array('description')));

		$this->assertFalse($this->Video->validate('title'));
		$this->assertTrue($this->Video->validate('description'));

		$this->assertTrue($this->Video->validate('asd'));

	}

	/**
	 * @covers Model::getErrors
	 * @expectedException Exception
	 * @expectedExceptionMessage cannot get error for non existing field def
	 */
	public function testGetErrors() {

		$Q = new \ReflectionProperty($this->Video, '_validationErrors');
		$Q->setAccessible(true);
		$errors = $this->Video->getErrors();
		$this->assertEquals($Q->getValue($this->Video), $errors);

		$Video = new \Video($this->videoData);
		$Video->title = null;
		$fields = array('_id', 'title');
		$field = array('title');
		$this->assertEquals($field, array_keys($Video->getErrors($fields)));

		$fields2 = array('title', 'description');
		$this->assertEquals($field, array_keys($Video->getErrors($fields2)));

		$this->assertEquals($field, array_keys($Video->getErrors('title')));

		$this->assertEquals(array(), array_keys($Video->getErrors('description')));

		$this->assertEquals(array(), array_keys($Video->getErrors('asd')));

	}

}
