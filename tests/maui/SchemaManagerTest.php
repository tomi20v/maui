<?php

class SchemaManagerTest extends \PHPUnit_Framework_TestCase {

	public $VideoCollection;

	public $DataProp;

	public function setUp() {
		$this->VideoCollection = new \VideoCollection(
			json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/VideoCollection.001.json'), true)
		);
		$this->DataProp = new \ReflectionProperty($this->VideoCollection, '_data');
		$this->DataProp->setAccessible(true);
	}

	/**
	 * todo: add test for filterng relatives recursively
	 * @covers SchemaManager::_filterBySchema
	 * @covers SchemaManager::filterBySchema
	 */
	public function test_filterBySchema() {
		$data = array(
			'_type' => 'typeSample',
			'title' => 'titleSample',
			'invalid' => 'invalidSample',
		);
		$exp = array_intersect_key($data, array_flip(array('_type', 'title')));

		$res = \SchemaManager::filterBySchema($data, \SchemaManager::getSchema('VideoCollection'));
		$this->assertEquals($res, $exp);

		$res = \SchemaManager::filterBySchema($data, \SchemaManager::getSchema('VideoCollection'));
		$this->assertEquals($res, $exp);

	}

}
