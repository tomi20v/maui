<?php

class CollectionTest extends \PHPUnit_Framework_TestCase {

	public $VideoCollection;

	public function setUp() {
		$this->VideoCollection = new\VideoCollection(
			json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/VideoCollection.001.json'), true)
		);
	}

	public function testAdd() {
		//echop($this->VideoCollection);
		foreach ($this->VideoCollection as $EachVideo) {
			$this->assertTrue($EachVideo instanceof \Video);
		}

	}

}
