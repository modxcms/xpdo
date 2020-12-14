<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Legacy\Om;

use xPDO\Om\xPDOObject;
use xPDO\Legacy\TestCase;
use xPDO\xPDO;

/**
 * Tests related to basic xPDOObject methods
 *
 * @package xPDO\Legacy\Om
 */
class xPDOObjectTest extends TestCase
{
    /**
     * Setup dummy data for each test.
     *
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();

            $this->xpdo->manager->createObjectContainer('Phone');
            $this->xpdo->manager->createObjectContainer('Person');
            $this->xpdo->manager->createObjectContainer('PersonPhone');
            $this->xpdo->manager->createObjectContainer('BloodType');

            $bloodTypes = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
            foreach ($bloodTypes as $bloodType) {
                $bt = $this->xpdo->newObject('BloodType');
                $bt->set('type', $bloodType);
                $bt->set('description', '');
                if (!$bt->save()) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_FATAL, 'Could not add blood type: ' . $bloodType);
                }
            }

            $bloodTypeABPlus = $this->xpdo->getObject('BloodType', 'AB+');
            if (empty($bloodTypeABPlus)) $this->xpdo->log(xPDO::LOG_LEVEL_FATAL, 'Could not load blood type.');

            /* add some people */
            $person = $this->xpdo->newObject('Person');
            $person->set('first_name', 'Johnathon');
            $person->set('last_name', 'Doe');
            $person->set('middle_name', 'Harry');
            $person->set('dob', '1950-03-14');
            $person->set('gender', 'M');
            $person->set('password', 'ohb0ybuddy');
            $person->set('username', 'john.doe@gmail.com');
            $person->set('security_level', 3);
            $person->set('blood_type', $bloodTypeABPlus->get('type'));
            $person->save();

            $phone = $this->xpdo->newObject('Phone');
            $phone->fromArray(array(
                'type' => 'work',
                'number' => '555-111-1111',
            ));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array(
                'person' => 1,
                'phone' => 1,
                'is_primary' => true,
            ), '', true, true);
            $personPhone->save();

            $person = $this->xpdo->newObject('Person');
            $person->set('first_name', 'Jane');
            $person->set('last_name', 'Heartstead');
            $person->set('middle_name', 'Cecilia');
            $person->set('dob', '1978-10-23');
            $person->set('gender', 'F');
            $person->set('password', 'n0w4yimdoingthat');
            $person->set('username', 'jane.heartstead@yahoo.com');
            $person->set('security_level', 1);
            $person->set('blood_type', $bloodTypeABPlus->get('type'));
            $person->save();

            $phone = $this->xpdo->newObject('Phone');
            $phone->fromArray(array(
                'type' => 'work',
                'number' => '555-222-2222',
            ));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array(
                'person' => 2,
                'phone' => 2,
                'is_primary' => true,
            ), '', true, true);
            $personPhone->save();

            $phone = $this->xpdo->newObject('Phone');
            $phone->fromArray(array(
                'type' => 'home',
                'number' => '555-555-5555',
            ));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array(
                'person' => 2,
                'phone' => 3,
                'is_primary' => false,
            ), '', true, true);
            $personPhone->save();
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Remove dummy data after each test.
     *
     * @after
     */
    public function tearDownFixtures()
    {
        try {
            $this->xpdo->manager->removeObjectContainer('Phone');
            $this->xpdo->manager->removeObjectContainer('Person');
            $this->xpdo->manager->removeObjectContainer('PersonPhone');
            $this->xpdo->manager->removeObjectContainer('BloodType');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        parent::tearDownFixtures();
    }

    /**
     * Test validating an object.
     *
     * @dataProvider providerValidate
     *
     * @param $class
     * @param $data
     * @param $options
     * @param $expected
     */
    public function testValidate($class, $data, $options, $expected)
    {
        try {
            /** @var xPDOObject $object */
            $object = $this->xpdo->newObject($class);
            $object->fromArray($data);
            $validated = $object->validate($options);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertEquals($expected, $validated, "Expected validation failed: " . print_r($object->_validator->getMessages(), true));
    }

    public function providerValidate()
    {
        return array(
            array(
                'Person',
                array(
                    'first_name' => 'My',
                    'middle_name' => 'Name',
                    'last_name' => 'Is',
                ),
                array(),
                false
            ),
            array(
                'Person',
                array(
                    'first_name' => 'My',
                    'middle_name' => 'Name',
                    'last_name' => 'Is',
                    'dob' => '2012-01-01',
                    'password' => 'foodisbeer'
                ),
                array(),
                true
            ),
        );
    }

    /**
     * Test the getCount method.
     *
     * @dataProvider providerGetCount
     * @param string $class
     * @param integer $expected
     */
    public function testGetCount($class, $expected) {
        $this->assertEquals($expected, $this->xpdo->getCount($class));
    }
    public function providerGetCount() {
        return array(
            array('Person', 2),
            array('Phone', 3),
            array('BloodType', 8),
        );
    }

    /**
     * Test saving an object.
     */
    public function testSaveObject()
    {
        $result = false;
        try {
            $person = $this->xpdo->newObject('Person');
            $person->set('first_name', 'Bob');
            $person->set('last_name', 'Bla');
            $person->set('middle_name', 'La');
            $person->set('dob', '1971-07-22');
            $person->set('password', 'b0bl4bl4');
            $person->set('username', 'boblabla');
            $person->set('security_level', 1);
            $person->set('gender', 'M');
            $result = $person->save();
            $this->xpdo->log(xPDO::LOG_LEVEL_INFO, "Object after save: " . print_r($person->toArray(), true));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result, "Error saving data.");
        $person->remove();
    }

    /**
     * Tests a cascading save
     */
    public function testCascadeSave()
    {
        $result = false;
        try {
            $person = $this->xpdo->newObject('Person');
            $person->set('first_name', 'Bob');
            $person->set('last_name', 'Bla');
            $person->set('middle_name', 'Lu');
            $person->set('dob', '1971-07-21');
            $person->set('gender', 'M');
            $person->set('password', 'b0blubl4!');
            $person->set('username', 'boblubla');
            $person->set('security_level', 1);

            $phone1 = $this->xpdo->newObject('Phone');
            $phone1->set('type', 'home');
            $phone1->set('number', '+1 555 555 5555');

            $phone2 = $this->xpdo->newObject('Phone');
            $phone2->set('type', 'work');
            $phone2->set('number', '+1 555 555 4444');

            $personPhone1 = $this->xpdo->newObject('PersonPhone');
            $personPhone1->addOne($phone1);
            $personPhone1->set('is_primary', false);

            $personPhone2 = $this->xpdo->newObject('PersonPhone');
            $personPhone2->addOne($phone2);
            $personPhone2->set('is_primary', true);

            $personPhone = array($personPhone1, $personPhone2);

            $person->addMany($personPhone);

            $result = $person->save();
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == true, "Error saving data.");
        $this->assertTrue(count($person->_relatedObjects['PersonPhone']) == 2, "Error saving related object data.");
        $person->remove();
    }

    /**
     * Test getting an object by the primary key.
     *
     * @depends testSaveObject
     */
    public function testGetObjectByPK()
    {
        $result = false;
        try {
            $person = $this->xpdo->getObject('Person', 1);
            $result = (is_object($person) && $person->getPrimaryKey() == 1);
            if ($person) $this->xpdo->log(xPDO::LOG_LEVEL_INFO, "Object after retrieval: " . print_r($person->toArray(), true));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result, "Error retrieving object by primary key");
    }

    /**
     * Test that an object can only be retrieved by primary key if type matches.
     *
     * @param string $class
     * @param mixed  $pkValue
     *
     * @depends testSaveObject
     * @dataProvider providerGetObjectByPKFailsOnTypeMismatch
     */
    public function testGetObjectByPKFailsOnTypeMismatch($class, $pkValue) {
        $result = null;
        try {
            $result = $this->xpdo->getObject($class, $pkValue);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertNull($result, "Object retrieved with invalid primary key type");
    }
    public function providerGetObjectByPKFailsOnTypeMismatch() {
        return array(
            array('Person', "stupid"),
            array('Phone', "crazy"),
            array('PersonPhone', "farfetched"),
            array('PersonPhone', 1),
            array('PersonPhone', "don't do it"),
            array('BloodType', 1),
        );
    }

    /**
     * Test that an object can only be retrieved by primary key if SQL injection detected.
     *
     * @param string $class
     * @param mixed  $pkValue
     *
     * @depends testSaveObject
     * @dataProvider providerGetObjectByPKFailsOnSQLInjection
     */
    public function testGetObjectByPKFailsOnSQLInjection($class, $pkValue) {
        $result = null;
        try {
            $result = $this->xpdo->getObject($class, $pkValue);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertNull($result, "Object retrieved with SQL injection detected");
    }
    public function providerGetObjectByPKFailsOnSQLInjection() {
        return array(
            array('Person', "1;DROP TABLE `person`"),
            array('Phone', "1 UNION SELECT * FROM `phone` WHERE id = 2"),
            array('PersonPhone', array("1=1;DROP TABLE `person`")),
            array('BloodType', "AB+ UNION SELECT * FROM `blood_type` WHERE `type` = 'A'"),
            array('BloodType', "AB+/**/UNION SELECT * FROM `blood_type` WHERE `type` = 'A'"),
        );
    }

    /**
     * Test using getObject by PK on multiple objects, including multiple PKs
     */
    public function testGetObjectsByPK()
    {
        try {
            $person = $this->xpdo->getObject('Person', 2);
            $phone = $this->xpdo->getObject('Phone', 2);
            $personPhone = $this->xpdo->getObject('PersonPhone', array(
                2,
                2,
            ));
            $expectedNull = $this->xpdo->getObject('Person', 0);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($person instanceof \Person, "Error retrieving Person object by primary key");
        $this->assertTrue($phone instanceof \Phone, "Error retrieving Phone object by primary key");
        $this->assertTrue($personPhone instanceof \PersonPhone, "Error retrieving PersonPhone object by primary key");
        $this->assertNull($expectedNull, "Got unexpected instance of Person object by invalid primary key");
    }

    /**
     * Test getObjectGraph by PK
     */
    public function testGetObjectGraphsByPK()
    {
        //array method
        try {
            $person = $this->xpdo->getObjectGraph('Person', array('PersonPhone' => array('Phone' => array())), 2);
            if ($person) {
                $personPhoneColl = $person->getMany('PersonPhone');
                if ($personPhoneColl) {
                    $phone = null;
                    foreach ($personPhoneColl as $personPhone) {
                        if ($personPhone->get('phone') == 2) {
                            $phone = $personPhone->getOne('Phone');
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($person instanceof \Person, "Error retrieving Person object by primary key via getObjectGraph");
        $this->assertTrue($personPhone instanceof \PersonPhone, "Error retrieving retreiving related PersonPhone collection via getObjectGraph");
        $this->assertTrue($phone instanceof \Phone, "Error retrieving related Phone object via getObjectGraph");
    }

    /**
     * Test getObjectGraph by PK with JSON graph
     */
    public function testGetObjectGraphsJSONByPK()
    {
        //JSON method
        try {
            $person = $this->xpdo->getObjectGraph('Person', '{"PersonPhone":{"Phone":{}}}', 2);
            if ($person) {
                $personPhoneColl = $person->getMany('PersonPhone');
                if ($personPhoneColl) {
                    $phone = null;
                    foreach ($personPhoneColl as $personPhone) {
                        if ($personPhone->get('phone') == 2) {
                            $phone = $personPhone->getOne('Phone');
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($person instanceof \Person, "Error retrieving Person object by primary key via getObjectGraph, JSON graph");
        $this->assertTrue($personPhone instanceof \PersonPhone, "Error retrieving retreiving related PersonPhone collection via getObjectGraph, JSON graph");
        $this->assertTrue($phone instanceof \Phone, "Error retrieving related Phone object via getObjectGraph, JSON graph");
    }

    /**
     * Test xPDO::getCollection
     */
    public function testGetCollection()
    {
        try {
            $people = $this->xpdo->getCollection('Person');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue(isset($people[1]) && $people[1] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(count($people) == 2, "Error retrieving all objects.");
    }

    /**
     * Test xPDO::getCollectionGraph
     */
    public function testGetCollectionGraph()
    {
        try {
            $people = $this->xpdo->getCollectionGraph('Person', array('PersonPhone' => array('Phone' => array())));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }

        $this->assertTrue($people[1] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2]->_relatedObjects['PersonPhone']['2-2'] instanceof \PersonPhone && $people[2]->_relatedObjects['PersonPhone']['2-3'] instanceof \PersonPhone, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2]->_relatedObjects['PersonPhone']['2-2']->_relatedObjects['Phone'] instanceof \Phone && $people[2]->_relatedObjects['PersonPhone']['2-3']->_relatedObjects['Phone'] instanceof \Phone, "Error retrieving all objects.");
        $this->assertTrue(count($people) == 2, "Error retrieving all objects.");
    }

    /**
     * Test xPDO::getCollectionGraph with JSON graph
     */
    public function testGetCollectionGraphJSON()
    {
        try {
            $people = $this->xpdo->getCollectionGraph('Person', '{"PersonPhone":{"Phone":{}}}');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }

        $this->assertTrue($people[1] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2] instanceof \Person, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2]->_relatedObjects['PersonPhone']['2-2'] instanceof \PersonPhone, "Error retrieving all objects.");
        $this->assertTrue(isset($people[2]) && $people[2]->_relatedObjects['PersonPhone']['2-2']->_relatedObjects['Phone'] instanceof \Phone, "Error retrieving all objects.");
        $this->assertTrue(count($people) == 2, "Error retrieving all objects.");
    }

    /**
     * Test getMany
     *
     * @dataProvider providerGetMany
     *
     * @param string $person The username of the \Person to use for the test data.
     * @param string $alias The relation alias to grab.
     * @param string $sortby A column to sort the related collection by.
     */
    public function testGetMany($person, $alias, $sortby)
    {
        $person = $this->xpdo->getObject('Person', array(
            'username' => $person,
        ));
        if ($person) {
            try {
                $fkMeta = $person->getFKDefinition($alias);
                $personPhones = $person->getMany($alias, $this->xpdo->newQuery($fkMeta['class'])->sortby($this->xpdo->escape($sortby)));
            } catch (\Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
            }
        }
        $this->assertTrue(!empty($personPhones) && count($personPhones) === 2, 'xPDOQuery: getMany failed from \Person to \PersonPhone.');
    }

    /**
     * Data provider for testGetMany
     */
    public function providerGetMany()
    {
        return array(
            array('jane.heartstead@yahoo.com', 'PersonPhone', 'is_primary'),
        );
    }

    /**
     * Test getOne
     *
     * @dataProvider providerGetOne
     *
     * @param string $username The username of the \Person to use for the test data.
     * @param string $alias The relation alias to grab.
     * @param string $class
     */
    public function testGetOne($username, $alias, $class)
    {
        $person = $this->xpdo->getObject('Person', array(
            'username' => $username,
        ));
        if ($person) {
            try {
                $one = $person->getOne($alias);
            } catch (\Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
            }
        }
        $this->assertTrue(!empty($one) && $one instanceof $class, 'xPDOQuery: getMany failed from \Person `' . $username . '` to ' . $alias . '.');
    }

    /**
     * Data provider for testGetOne
     */
    public function providerGetOne()
    {
        return array(
            array('jane.heartstead@yahoo.com', 'BloodType', 'BloodType'),
        );
    }

    /**
     * Test loading a graph of relations to an xPDOObject instance.
     */
    public function testGetGraph()
    {
        /** @var xPDOObject $object */
        $object = $this->xpdo->getObject('Person', 2);
        if ($object) {
            try {
                $object->getGraph(array('PersonPhone' => array('Phone' => array())));
            } catch (\Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
            }
        }
        $this->assertTrue(
            $object instanceof \Person &&
            $object->_relatedObjects['PersonPhone']['2-2'] instanceof \PersonPhone &&
            $object->_relatedObjects['PersonPhone']['2-2']->_relatedObjects['Phone'] instanceof \Phone &&
            $object->_relatedObjects['PersonPhone']['2-3'] instanceof \PersonPhone &&
            $object->_relatedObjects['PersonPhone']['2-3']->_relatedObjects['Phone'] instanceof \Phone,
            "Could not retrieve requested graph"
        );
    }

    /**
     * Test loading an iterator for
     */
    public function testGetIterator()
    {
        $children = array();
        /** @var xPDOObject $object */
        $object = $this->xpdo->getObject('Person', 2);
        if ($object) {
            try {
                $iterator = $object->getIterator('PersonPhone');
                foreach ($iterator as $child) {
                    $children[] = $child;
                }
            } catch (\Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
            }
        }
        $this->assertTrue($object instanceof \Person && $children[0] instanceof \PersonPhone && $children[1] instanceof \PersonPhone, "Could not retrieve requested iterator.");
    }

    /**
     * Test updating a collection.
     *
     * @dataProvider providerUpdateCollection
     *
     * @param string $class The class to update a collection of.
     * @param array $set An array of field/value pairs to update for the collection.
     * @param mixed $criteria A valid xPDOCriteria object or expression.
     * @param array $expected An array of expected values for the test.
     */
    public function testUpdateCollection($class, $set, $criteria, $expected)
    {
        $actualKeys = array_keys($set);
        $actualValues = array();
        $affected = $this->xpdo->updateCollection($class, $set, $criteria);
        $affectedCollection = $this->xpdo->getCollection($class, $criteria);
        foreach ($affectedCollection as $affectedObject) {
            $actualValues[] = $affectedObject->get($actualKeys);
        }
        $actual = array($affected, $actualValues);
        $this->assertEquals($expected, $actual, "Could not update collection as expected.");
    }

    /**
     * Data provider for testUpdateCollection
     */
    public function providerUpdateCollection()
    {
        return array(
            array('Person', array('dob' => '2011-08-09'), array('dob:<' => '1951-01-01'), array(1, array())),
            array('Person', array('security_level' => 5), array('security_level' => 3), array(1, array())),
            array(
                'Person',
                array('date_of_birth' => '2011-09-01'),
                null,
                array(2, array(array('date_of_birth' => '2011-09-01'), array('date_of_birth' => '2011-09-01')))
            ),
            array(
                'Person',
                array('date_of_birth' => null),
                array('security_level' => 3),
                array(1, array(array('date_of_birth' => null)))
            ),
        );
    }

    /**
     * Test removing an object
     */
    public function testRemoveObject()
    {
        $result = false;

        $person = $this->xpdo->newObject('Person');
        $person->set('first_name', 'Kurt');
        $person->set('last_name', 'Dirt');
        $person->set('middle_name', 'Remover');
        $person->set('dob', '1978-10-23');
        $person->set('gender', 'F');
        $person->set('password', 'fdsfdsfdsfds');
        $person->set('username', 'dirt@remover.com');
        $person->set('security_level', 1);
        $person->save();
        try {
            if ($person = $this->xpdo->getObject('Person', $person->get('id'))) {
                $result = $person->remove();
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == true, "Error removing data.");
    }

    /**
     * Test removing a dependent object
     */
    public function testRemoveDependentObject()
    {
        $result = false;
        $phone = $this->xpdo->newObject('Phone');
        $phone->set('type', 'work');
        $phone->set('number', '555-789-4563');
        $phone->set('is_primary', false);
        $phone->save();
        try {
            if ($phone = $this->xpdo->getObject('Phone', $phone->get('id'))) {
                $result = $phone->remove();
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == true, "Error removing data.");
    }

    /**
     * Test removing nested composite objects
     */
    public function testRemoveNestedComposites() {
        $result= false;

        $person= $this->xpdo->newObject('Person');
        $person->set('first_name', 'Kurt');
        $person->set('last_name', 'Dirt');
        $person->set('middle_name', 'Remover');
        $person->set('dob', '1978-10-23');
        $person->set('gender', 'F');
        $person->set('password', 'fdsfdsfdsfds');
        $person->set('username', 'dirt@remover.com');
        $person->set('security_level',1);

        $phone= $this->xpdo->newObject('Phone');
        $phone->set('type', 'home');
        $phone->set('number', '+1 555 555 5555');

        $personPhone= $this->xpdo->newObject('PersonPhone');
        $personPhone->addOne($phone);
        $personPhone->set('is_primary', false);

        $person->addMany($personPhone);
        $result= $person->save();

        try {
            if ($person= $this->xpdo->getObject('Person', $person->get('id'))) {
                $result= $person->remove();
                if ($result) {
                    $personPhone= $this->xpdo->getObject('PersonPhone', $personPhone->getPrimaryKey(), false);
                    $phone= $this->xpdo->getObject('Phone', $phone->getPrimaryKey());
                }
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == true, "Error removing parent object.");
        $this->assertTrue(empty($personPhone), "Intermediate object not removed.");
        $this->assertTrue(empty($phone), "Child object not removed.");
    }

    /**
     * Test removing circular composites
     */
    public function testRemoveCircularComposites()
    {
        $result = false;
        try {
            if ($personPhone = $this->xpdo->getObject('PersonPhone', array(2, 2))) {
                $result = $personPhone->remove();
                unset($personPhone);
                if ($result) {
                    if ($personPhone = $this->xpdo->getObject('PersonPhone', array(2, 2))) {
                        $this->assertTrue(false, "Parent object was not removed.");
                    }
                    if ($phone = $this->xpdo->getObject('Phone', 2)) {
                        $this->assertTrue(false, "Child object was not removed.");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == true, "Error removing objects with circular composite relationships.");
    }

    /**
     * Test removing a collection of objects
     */
    public function testRemoveCollection()
    {
        $result = false;

        $person = $this->xpdo->newObject('Person');
        $person->set('first_name', 'Ready');
        $person->set('last_name', 'Willing');
        $person->set('middle_name', 'Able');
        $person->set('dob', '1980-12-25');
        $person->set('gender', 'M');
        $person->set('password', 'blahblahblah');
        $person->set('username', 'ready@willingandable.com');
        $person->set('security_level', 1);
        $person->save();

        $person = $this->xpdo->newObject('Person');
        $person->set('first_name', 'Kurt');
        $person->set('last_name', 'Dirt');
        $person->set('middle_name', 'Remover');
        $person->set('dob', '1978-10-23');
        $person->set('gender', 'F');
        $person->set('password', 'fdsfdsfdsfds');
        $person->set('username', 'dirt@remover.com');
        $person->set('security_level', 2);
        $person->save();

        unset($person);
        try {
            $result = $this->xpdo->removeCollection('Person', array('last_name:IN' => array('Willing', 'Dirt')));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result === 2, "Error removing a collection of objects.");
    }
}
