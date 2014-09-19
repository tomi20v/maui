<?php

class SchemaManagerTester1 extends maui\Model{
	protected static $_schema = array(
		't1',
	);
}

class SchemaManagerTester2 extends maui\Model{
	protected static $_schema = array(
		'@extends' => 'SchemaManagerTester1',
		't2',
	);
}

class SchemaManagerTest extends \PHPUnit_Framework_TestCase {

	public $VideoCollection;

	public $DataProp;

	const CLASSNAME = 'SchemaManager';

	public function setUp() {
		// import $this->Video
		ModelTest::setUp();
		$this->VideoCollection = new \VideoCollection(
			json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/VideoCollection.001.json'), true)
		);
		$this->DataProp = new \ReflectionProperty($this->VideoCollection, '_data');
		$this->DataProp->setAccessible(true);
	}

	/**
	 * @covers SchemaManager::_toContext
	 * @dataProvider _toContextDataProvider
	 */
	public function test_toContext($from, $expected) {
		$m = new ReflectionMethod(static::CLASSNAME, '_toContext');
		$m->setAccessible(true);

		$this->assertSame($expected, $m->invoke(static::CLASSNAME, $from));

	}

	public function _toContextDataProvider() {
		return array(
			array('a', 'a'),
			array('\\a', 'a'),
			array('a\\', 'a'),
			array('b\\a', 'a'),
			array('\\b\\a', 'a'),
		);
	}

	/**
	 * todo: add test for filterng relatives recursively
	 * @covers SchemaManager::filterBySchema
	 */
	public function testFilterBySchema() {

		$Video = new \VideoEpisode(array('title'=>'testTitle', 'season'=>'01'));
		$VideoSchema = \SchemaManager::getSchema('Video');

		$exp = array();

		$res = \Maui\SchemaManager::filterBySchema($Video, 'Video');
		$this->assertEquals($exp, $res);

	}

	/**
	 * @covers SchemaManager::filterBySchema
	 * @expectedException Exception
	 * @expectedExceptionMessage cannot use for data
	 */
	public function testFilterBySchema2() {

		\maui\SchemaManager::filterBySchema(null, null);

	}

	/**
	 * @covers SchemaManager::filterBySchema
	 * @expectedException Exception
	 * @expectedExceptionMessage not a schema
	 */
	public function testFilterBySchema3() {

		\maui\SchemaManager::filterBySchema(array(), null);

	}

	/**
	 * @covers SchemaManager::_filterBySchema
	 */
	public function test_filterBySchema() {

		$m = new ReflectionMethod('SchemaManager', '_filterBySchema');
		$m->setAccessible(true);

		$data = array(
			'_type' => 'typeSample',
			'title' => 'titleSample',
			'invalid' => 'invalidSample',
		);
		$exp = array_intersect_key($data, array_flip(array('_type', 'title')));

		$res = $m->invoke('SchemaManager', $data, \SchemaManager::getSchema('Video'));
		$this->assertEquals($res, $exp);

		$res = $m->invoke('SchemaManager', $data, \SchemaManager::getSchema('Video'));
		$this->assertEquals($res, $exp);

	}

	/**
	 * @covers SchemaManager::_fromArray
	 */
	public function test_fromArray() {

		$schema = array(
			'@extends' => 'SchemaManagerTester1',
			'rel' => array(
				'class' => 'Video',
			)
		);
		$m = new ReflectionMethod(static::CLASSNAME, '_fromArray');
		$m->setAccessible(true);

		$Schema = $m->invoke(static::CLASSNAME, $schema, 'Test1');
		$this->assertTrue($Schema instanceof \maui\Schema);
		$this->assertEquals(array('_id', '_type', 't1', 'rel'), $Schema->fields());

	}

	/**
	 * @covers SchemaManager::_fromArray
	 * @expectedException Exception
	 * @expectedExceptionMessage class to extend does not exist
	 */
	public function test_fromArray2() {

		$schema = array(
			'@extends' => 'InvalidClass',
		);
		$m = new ReflectionMethod(static::CLASSNAME, '_fromArray');
		$m->setAccessible(true);

		$m->invoke(static::CLASSNAME, $schema, 'Test1');
	}

	/**
	 * @covers SchemaManager::_fromArray
	 * @expectedException Exception
	 * @expectedExceptionMessage invalid schema entry:
	 */
	public function test_fromArray3() {

		$schema = array(
			'invalid' => new stdClass(),
		);
		$m = new ReflectionMethod(static::CLASSNAME, '_fromArray');
		$m->setAccessible(true);

		$m->invoke(static::CLASSNAME, $schema, 'Test1');
	}

	/**
	 * @covers SchemaManager::ensureHasId
	 */
	public function testEnsureHasId() {

		$schema = array(
			'_id',
			'_type',
		);

		$res = \maui\SchemaManager::ensureHasId(array());
		$this->assertTrue(is_array($res));
		$this->assertArrayHasKey('_id', $res);
		$this->assertArrayHasKey('_type', $res);

		$res = \maui\SchemaManager::ensureHasId($schema);
		$this->assertTrue(is_array($res));
		$this->assertArrayHasKey('_id', $res);
		$this->assertArrayHasKey('_type', $res);
	}

	/**
	 * @covers SchemaManager::ensureHasId
	 */
	public function testEnsureHasId2() {
	}

}
