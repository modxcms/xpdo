<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Om;

use xPDO\Om\xPDOObject;
use xPDO\TestCase;
use xPDO\xPDO;

/**
 * Tests related to basic xPDOObject methods when using single-table inheritance features
 *
 * @package xPDO\Test\Om
 */
class xPDOObjectSingleTableInheritanceTest extends TestCase
{
    /**
     * Setup dummy data for each test.
     */
    public function setUp()
    {
        parent::setUp();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();
            $this->xpdo->addPackage('xPDO\\Test\\Sample\\STI', self::$properties['xpdo_test_path'] . 'model/');

            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\STI\\baseClass');
            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\STI\\relClassOne');
            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\STI\\relClassMany');

            /* add some various base and derivative objects */
            $object = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\baseClass');
            $object->set('field1', 1);
            $object->set('field2', 'a string');

            $relatedObject = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassOne');
            $relatedObject->fromArray(array(
                'field1' => 123,
                'field2' => 'alphanumeric',
            ));
            $object->addOne($relatedObject);

            $relatedObjects[0] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[0]->fromArray(array(
                'field1' => 'some text',
                'field2' => true,
            ));
            $relatedObjects[1] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[1]->fromArray(array(
                'field1' => 'some different text',
                'field2' => false,
            ));

            $object->addMany($relatedObjects);
            $object->save();

            /* add some various base and derivative objects */
            $object = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\derivedClass');
            $object->set('field1', 2);
            $object->set('field2', 'a derived string');

            $relatedObject = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassOne');
            $relatedObject->fromArray(array(
                'field1' => 321,
                'field2' => 'numericalpha',
            ));
            $object->addOne($relatedObject);

            $relatedObjects[0] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[0]->fromArray(array(
                'field1' => 'some derived text',
                'field2' => true,
            ));
            $relatedObjects[1] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[1]->fromArray(array(
                'field1' => 'some different derived text',
                'field2' => false,
            ));

            $object->addMany($relatedObjects);
            $object->save();

            /* add some various base and derivative objects */
            $object = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\derivedClass2');
            $object->set('field1', 3);
            $object->set('field2', 'another derived string');

            $relatedObject = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassOne');
            $relatedObject->fromArray(array(
                'field1' => 789,
                'field2' => 'derived numericalpha',
            ));
            $object->addOne($relatedObject, 'relOne');

            $relatedObjects[0] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[0]->fromArray(array(
                'field1' => 'some double derived text',
                'field2' => true,
            ));
            $relatedObjects[1] = $this->xpdo->newObject('xPDO\\Test\\Sample\\STI\\relClassMany');
            $relatedObjects[1]->fromArray(array(
                'field1' => 'some different double derived text',
                'field2' => false,
            ));

            $object->addMany($relatedObjects, 'relMany');
            $object->save();
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Remove dummy data prior to each test.
     */
    public function tearDown()
    {
        try {
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\STI\\baseClass');
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\STI\\relClassOne');
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\STI\\relClassMany');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        parent::tearDown();
    }

    /**
     * Test getting the proper derived instance from getObject.
     */
    public function testGetDerivedObject()
    {
        try {
            $baseObject = $this->xpdo->getObject('xPDO\\Test\\Sample\\STI\\baseClass', array('field1' => 1));
            $derivedObject = $this->xpdo->getObject('xPDO\\Test\\Sample\\STI\\baseClass', array('field1' => 2));
            $derivedObject2 = $this->xpdo->getObject('xPDO\\Test\\Sample\\STI\\baseClass', array('field1' => 3));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($baseObject instanceof \xPDO\Test\Sample\STI\baseClass && $baseObject->get('class_key') == 'xPDO\\Test\\Sample\\STI\\baseClass', "Error getting base object of the appropriate class.");
        $this->assertTrue($derivedObject instanceof \xPDO\Test\Sample\STI\derivedClass && $derivedObject->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass', "Error getting derived object of the appropriate class.");
        $this->assertTrue($derivedObject2 instanceof \xPDO\Test\Sample\STI\derivedClass2 && $derivedObject2->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass2', "Error getting derived object of the appropriate class.");
    }

    /**
     * Test getting only the requested derived instance from getObject.
     *
     * @dataProvider providerGetSpecificDerivedObject
     *
     * @param string $expectedClass A valid xPDO table class name derived from a base table class.
     * @param array $criteria
     */
    public function testGetSpecificDerivedObject($expectedClass, $criteria)
    {
        $result = false;
        $realClass = '(none)';
        try {
            $object = $this->xpdo->getObject("{$expectedClass}", $criteria);
            if ($object) {
                $result = ($object instanceof $expectedClass && $object->get('class_key') == $expectedClass);
                if ($result !== true) $realClass = $object->_class;
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result === true, "Error getting a derived class instance for {$expectedClass}; got {$realClass}.");
    }

    /**
     * Data provider for testGetSpecificDerivedObject.
     */
    public function providerGetSpecificDerivedObject()
    {
        return array(
            array('xPDO\\Test\\Sample\\STI\\derivedClass', array('field1' => 2)),
            array('xPDO\\Test\\Sample\\STI\\derivedClass2', array('field1' => 3))
        );
    }

    /**
     * Test getting the proper derived instances from getCollection.
     */
    public function testGetDerivedCollection()
    {
        try {
            $collection = $this->xpdo->getCollection('xPDO\\Test\\Sample\\STI\\baseClass');
            /** @var xPDOObject $object */
            foreach ($collection as $object) {
                $result = false;
                switch ($object->get('field1')) {
                    case 1:
                        $expectedClass = 'baseClass';
                        $result = ($object instanceof \xPDO\Test\Sample\STI\baseClass && $object->get('class_key') == 'xPDO\\Test\\Sample\\STI\\baseClass');
                        break;
                    case 2:
                        $expectedClass = 'derivedClass';
                        $result = ($object instanceof \xPDO\Test\Sample\STI\derivedClass && $object->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass');
                        break;
                    case 3:
                        $expectedClass = 'derivedClass2';
                        $result = ($object instanceof \xPDO\Test\Sample\STI\derivedClass2 && $object->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass2');
                        break;
                }
                $this->assertTrue($result, "Error getting derived object of the appropriate class ({$expectedClass}) in collection.");
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Test getting only the requested derived instances from getCollection.
     *
     * @dataProvider providerGetSpecificDerivedCollection
     *
     * @param string $expectedClass A valid xPDO table class name derived from a base table class.
     */
    public function testGetSpecificDerivedCollection($expectedClass)
    {
        try {
            $collection = $this->xpdo->getCollection("{$expectedClass}");
            /** @var xPDOObject $object */
            foreach ($collection as $object) {
                $result = ($object instanceof $expectedClass && $object->get('class_key') == $expectedClass);
                $this->assertTrue($result, "Error only getting derived objects of the specified derived class in collection.");
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Data provider for testGetSpecificDerivedCollection.
     */
    public function providerGetSpecificDerivedCollection()
    {
        return array(
            array('xPDO\\Test\\Sample\\STI\\derivedClass'),
            array('xPDO\\Test\\Sample\\STI\\derivedClass2')
        );
    }

    /**
     * Test getting the proper derived instance from getObjectGraph.
     */
    public function testGetDerivedObjectGraph()
    {
        try {
            $baseObject = $this->xpdo->getObjectGraph('xPDO\\Test\\Sample\\STI\\baseClass', '{"relOne":{},"relMany":{}}', array('field1' => 1));
            $derivedObject = $this->xpdo->getObjectGraph('xPDO\\Test\\Sample\\STI\\baseClass', '{"relOne":{},"relMany":{}}', array('field1' => 2));
            $derivedObject2 = $this->xpdo->getObjectGraph('xPDO\\Test\\Sample\\STI\\baseClass', '{"relOne":{},"relMany":{}}', array('field1' => 3));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($baseObject instanceof \xPDO\Test\Sample\STI\baseClass && $baseObject->get('class_key') == 'xPDO\\Test\\Sample\\STI\\baseClass', "Error getting base object of the appropriate class from graph.");
        $this->assertTrue($derivedObject instanceof \xPDO\Test\Sample\STI\derivedClass && $derivedObject->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass', "Error getting derived object of the appropriate class from graph.");
        $this->assertTrue($derivedObject2 instanceof \xPDO\Test\Sample\STI\derivedClass2 && $derivedObject2->get('class_key') == 'xPDO\\Test\\Sample\\STI\\derivedClass2', "Error getting derived object of the appropriate class from graph.");
    }

    /**
     * Test getting the proper derived instance from getCollection.
     */
    public function testGetDerivedCollectionGraph()
    {
        try {
            $collection = $this->xpdo->getCollectionGraph('xPDO\\Test\\Sample\\STI\\baseClass', '{"relOne":{},"relMany":{}}');
            /** @var xPDOObject $object */
            foreach ($collection as $object) {
                $result = false;
                switch ($object->get('field1')) {
                    case 1:
                        $expectedClass = 'xPDO\\Test\\Sample\\STI\\baseClass';
                        $result = ($object instanceof $expectedClass && $object->get('class_key') == $expectedClass);
                        break;
                    case 2:
                        $expectedClass = 'xPDO\\Test\\Sample\\STI\\derivedClass';
                        $result = ($object instanceof $expectedClass && $object->get('class_key') == $expectedClass);
                        break;
                    case 3:
                        $expectedClass = 'xPDO\\Test\\Sample\\STI\\derivedClass2';
                        $result = ($object instanceof $expectedClass && $object->get('class_key') == $expectedClass);
                        break;
                }
                $this->assertTrue($result, "Error getting derived object of the appropriate class ({$expectedClass}) in collection graph.");
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Test saving instances of a base and various derived classes.
     *
     * @param string $expectedClass The expected class of the instance.
     * @param array $criteria The criteria for selecting the instance.
     * @param array $update The changes to make to the instance data to test the save.
     *
     * @dataProvider providerSaveDerivedObject
     */
    public function testSaveDerivedObject($expectedClass, $criteria, $update)
    {
        $result = false;
        try {
            $object = $this->xpdo->getObject("xPDO\\Test\\Sample\\STI\\baseClass", $criteria);
            if ($object) {
                while (list($key, $value) = each($update)) {
                    $object->set($key, $value);
                }
                $result = $object->save();
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result === true, "Error saving class instance for expected class {$expectedClass}.");
    }

    /**
     * Data provider for testSaveDerivedObject.
     */
    public function providerSaveDerivedObject()
    {
        return array(
            array('xPDO\\Test\\Sample\\STI\\baseClass', array('field1' => 1), array('field2' => 'updated base class string')),
            array('xPDO\\Test\\Sample\\STI\\derivedClass', array('field1' => 2), array('field2' => 'updated derived class string')),
            array(
                'xPDO\\Test\\Sample\\STI\\derivedClass2',
                array('field1' => 3),
                array('field2' => 'updated derived class 2 string', 'field3' => 'updated derived field content')
            )
        );
    }
}
