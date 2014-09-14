<?php

class CollectionTest extends \PHPUnit_Framework_TestCase {

	public $VideoCollection;

	public $DataProp;

	public function setUp() {
		$this->VideoCollection = new\VideoCollection(
			json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/VideoCollection.001.json'), true)
		);
		$this->DataProp = new \ReflectionProperty($this->VideoCollection, '_data');
		$this->DataProp->setAccessible(true);
	}

	/**
	 * this test could be broken up to individually test these methods...
	 * @covers Collection::current
	 * @covers Collection::key
	 * @covers Collection::next
	 * @covers Collection::rewind
	 * @covers Collection::valid
	 */
	public function testForeach() {
		$i = 0;
		foreach ($this->VideoCollection as $eachKey=>$EachVideo) {
			$this->assertTrue($EachVideo instanceof \Video);
			$this->assertNotEmpty($EachVideo->title);
			$i++;
		}
		$this->assertEquals(2, $i);
	}

	/**
	 * @covers Collection::count
	 */
	public function testCount() {
		$this->assertEquals(2, count($this->VideoCollection));
		$Video = new \Video(array('title'=>'asd'));
		$this->VideoCollection->add($Video);
		$this->assertEquals(3, count($this->VideoCollection));
	}

	/**
	 * @covers Collection::add
	 */
	public function testAdd() {
		$this->assertEquals(2, count($this->DataProp->getValue($this->VideoCollection)));
		$Video = new \Video(array('title'=>'asd'));
		$this->VideoCollection->add($Video);
		$_p = $this->DataProp->getValue($this->VideoCollection);
		$this->assertEquals(3, count($_p));
		$this->assertTrue($_p[2] instanceof \Video);
		$v = array('title'=>'bsd');
		$this->VideoCollection->add($v);
		$_p = $this->DataProp->getValue($this->VideoCollection);
		$this->assertEquals(4, count($_p));
		$this->assertTrue(is_array($_p[3]));
	}

	/**
	 * @covers Collection::contains
	 */
	public function testContains() {
		$data = reset($this->DataProp->getValue($this->VideoCollection));
		$this->assertTrue($this->VideoCollection->contains($data));
		$data2 = $data;
		$data2['subtitle'] = 'asd';
		$this->assertFalse($this->VideoCollection->contains($data2));
		$Video = new \Video($data);
		$this->assertTrue($this->VideoCollection->contains($Video));
		$Video2 = new \Video($data2);
		$this->assertFalse($this->VideoCollection->contains($Video2));
	}

}
