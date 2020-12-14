<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Om;


use xPDO\Test\Sample\xPDOSample;
use xPDO\TestCase;
use xPDO\xPDO;

class xPDOQueryConditionsTest extends TestCase
{
    /**
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();

        try {
            $this->xpdo->getManager();

            $this->xpdo->manager->createObjectContainer('xPDO\Test\Sample\xPDOSample');

            $sample = $this->xpdo->newObject('xPDO\Test\Sample\xPDOSample', [
                'parent' => 0,
                'unique_varchar' => uniqid('prefix_'),
                'varchar' => 'varchar',
                'text' => 'text',
                'timestamp' => 'CURRENT_TIMESTAMP',
                'unix_timestamp' => strtotime('2018-03-24 00:00:00'),
                'date_time' => '2018-03-24 00:00:00',
                'date' => '2018-03-24',
                'enum' => 'T',
                'password' => 'password',
                'integer' => 1999,
                'float' => 3.14159,
                'boolean' => true,
            ])->save();
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * @after
     */
    public function tearDownFixtures()
    {
        parent::tearDownFixtures();

        try {
            $this->xpdo->manager->removeObjectContainer('xPDO\Test\Sample\xPDOSample');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Test selection of the object by various conditions.
     *
     * @dataProvider providerSelectConditions
     *
     * @param array $condition The condition to use to select the expected object.
     */
    public function testSelectConditions($condition)
    {
        $selected = $this->xpdo->getObject('xPDO\Test\Sample\xPDOSample', $condition);

        $this->assertTrue($selected instanceof xPDOSample, 'Query failed using condition: ' . print_r($condition, true));
    }

    public function providerSelectConditions()
    {
        return [
            [
                ['parent' => 0]
            ],
            [
                ['float' => 3.14159]
            ],
            [
                ['integer' => 1999]
            ],
            [
                ['enum' => 'T']
            ],
            [
                ['date_time' => '2018-03-24 00:00:00']
            ]
        ];
    }
}
