<?php

class ModelManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider compareProvider
	 */
	function testCompare($m1, $m2, $expected) {
		$this->assertEquals($expected, \Maui\ModelManager::compare($m1, $m2));
	}

	function compareProvider() {
		return array(
			array('asd', 'asd', true),
			array('asd', 'bsd', false),
			array('asd', array('_id'=>'asd'), true),
			array('asd', array('_id'=>'bsd'), false),
			array(array('_id'=>'asd'), 'asd', true),
			array(array('_id'=>'bsd'), 'asd', false),
		);
	}

}
