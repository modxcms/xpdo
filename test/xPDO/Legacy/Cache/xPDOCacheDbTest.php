<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Legacy\Cache;

use xPDO\Legacy\TestCase;
use xPDO\xPDO;

/**
 * Tests related to xPDO cache_db options
 *
 * @package xPDO\Legacy\Cache
 */
class xPDOCacheDbTest extends TestCase
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
            $phone->fromArray(array('type' => 'work', 'number' => '555-111-1111',));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array('person' => 1, 'phone' => 1, 'is_primary' => true,), '', true, true);
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
            $phone->fromArray(array('type' => 'work', 'number' => '555-222-2222',));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array('person' => 2, 'phone' => 2, 'is_primary' => true,), '', true, true);
            $personPhone->save();

            $phone = $this->xpdo->newObject('Phone');
            $phone->fromArray(array('type' => 'home', 'number' => '555-555-5555',));
            $phone->save();

            $personPhone = $this->xpdo->newObject('PersonPhone');
            $personPhone->fromArray(array('person' => 2, 'phone' => 3, 'is_primary' => false,), '', true, true);
            $personPhone->save();
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Remove dummy data prior to each test.
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
     * Ensure cache entries for class do not remain when removing an object.
     */
    public function testRemoveObject()
    {
        $this->xpdo->setOption(xPDO::OPT_CACHE_DB, true);

        /** @var Person $person */
        $person = $this->xpdo->getObject('Person', 1);
        $person->remove();

        $people = $this->xpdo->getCollection('Person');
        $count = count($people);

        $this->assertEquals(0, $this->xpdo->getCount('Person', 1), "Object still exists after remove");
        $this->assertEquals(1, $count, "Object still exists in cache after removal");
    }
}
