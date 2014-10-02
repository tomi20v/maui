<?php

class ModelManagerTester1 extends \Model {
	protected static $_schema = array('title');
}
class ModelManagerTester2 extends \Model {
	protected static $_schema = array('title');
}
class ModelManagerTesterCompare extends \Model {
	protected static $_schema = array('title', 'parent'=>array('class'=>'ModelManagerTesterCompare'));
}

/**
 * Class ModelManagerTest
 */
class ModelManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers ModelManager::isInited
	 */
	function testIsInited() {
		$classname = 'ModelManagerTester1';
		$this->assertEquals(false, \maui\ModelManager::isInited($classname));
		$this->assertTrue(class_exists($classname));
		$this->assertEquals(false, \maui\ModelManager::isInited($classname));
		new \ModelManagerTester1();
		$this->assertEquals(true, \maui\ModelManager::isInited($classname));
	}

	/**
	 * @covers ModelManager::registerInited
	 */
	function testRegisterInited() {

		$managerClassname = 'maui\ModelManager';
		$classname = 'ModelManagerTester2';

		$P = new \ReflectionProperty($managerClassname, '_initedClasses');
		$P->setAccessible(true);
		$Q = new \ReflectionProperty($managerClassname, '_modelPool');
		$Q->setAccessible(true);
//		$C = new \ReflectionClass('maui\ModelManager');
//		$p = $C->getStaticPropertyValue('_initedClasses');

		$p = $P->getValue($managerClassname);
		$q = $Q->getValue($managerClassname);
		$this->assertArrayNotHasKey($classname, $p);
		$this->assertArrayNotHasKey($classname, $q);

		\maui\ModelManager::registerInited($classname);

		$p = $P->getValue($managerClassname);
		$q = $Q->getValue($managerClassname);

		$this->assertContains($classname, $p);
		$this->assertTrue(is_array($q[$classname]));

	}

	/**
	 * @dataProvider compareProvider
	 * @covers ModelManager::compare
	 */
	function testCompare($m1, $m2, $expected) {
		$this->assertEquals($expected, \maui\ModelManager::compare($m1, $m2));
	}

	function compareProvider() {
		return array(

			array('asd', 'asd', true),
			array('asd', 'bsd', false),
			array('asd', array('_id'=>'asd'), true),
			array('asd', array('_id'=>'bsd'), false),
			array(array('_id'=>'asd'), 'asd', true),
			array(array('_id'=>'bsd'), 'asd', false),

			array('123456789012345678901234', new ModelManagerTesterCompare('123456789012345678901234'), true),
			array('123456789012345678901235', new ModelManagerTesterCompare('123456789012345678901234'), false),
			array(array('_id'=>'123456789012345678901234'), new ModelManagerTesterCompare('123456789012345678901234'), true),
			array(array('_id'=>'123456789012345678901235'), new ModelManagerTesterCompare('123456789012345678901234'), false),
			array(array('title'=>'123456789012345678901234'), new ModelManagerTesterCompare('123456789012345678901234'), false),
			array(array('title'=>'asd'), new ModelManagerTesterCompare(array('title'=>'asd')), true),
			array(array('title'=>'bsd'), new ModelManagerTesterCompare(array('title'=>'asd')), false),

			array(new ModelManagerTesterCompare('123456789012345678901234'), '123456789012345678901234', true),
			array(new ModelManagerTesterCompare('123456789012345678901234'), '123456789012345678901235', false),
			array(new ModelManagerTesterCompare('123456789012345678901234'), array('_id'=>'123456789012345678901234'), true),
			array(new ModelManagerTesterCompare('123456789012345678901234'), array('_id'=>'123456789012345678901235'), false),
			array(new ModelManagerTesterCompare('123456789012345678901234'), array('title'=>'123456789012345678901234'), false),
			array(new ModelManagerTesterCompare(array('title'=>'asd')), array('title'=>'asd'), true),
			array(new ModelManagerTesterCompare(array('title'=>'asd')), array('title'=>'bsd'), false),

			array(new ModelManagerTesterCompare(array('title'=>'asd', 'parent'=>new ModelManagerTesterCompare())), array('title'=>'asd', 'parent'=>array()), true),
			array(new ModelManagerTesterCompare(array('title'=>'asd', 'parent'=>null)), array('title'=>'asd', 'parent'=>array()), false),
			array(new ModelManagerTesterCompare(array('title'=>'asd', 'parent'=>new ModelManagerTesterCompare())), array('title'=>'bsd', 'parent'=>array()), false),
			array(new ModelManagerTesterCompare(array('title'=>'asd', 'parent'=>new ModelManagerTesterCompare())), array('title'=>'asd', 'parent'=>null), false),
			array(new ModelManagerTesterCompare(array('title'=>'asd', 'parent'=>new ModelManagerTesterCompare())), array('title'=>'asd'), false),

			array(new stdClass(), 'asd', null),
			array('asd', new stdClass(), null),
			array(new stdClass(), array('title'=>'asd'), null),
			array(array('title'=>'asd'), new stdClass(), null),

		);
	}

}
