<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test;

use xPDO\Cache\xPDOCacheManager;
use xPDO\Om\xPDODriver;
use xPDO\Om\xPDOManager;
use xPDO\Om\xPDOQuery;
use xPDO\Test\Sample\BloodType;
use xPDO\Test\Sample\Item;
use xPDO\Test\Sample\Person;
use xPDO\Test\Sample\xPDOSample;
use xPDO\TestCase;
use xPDO\xPDO;

/**
 * Tests related to basic xPDO methods
 *
 * @package xPDO\Test
 */
class xPDOTest extends TestCase
{
    /**
     * Verify xPDO::connect works.
     */
    public function testConnect()
    {
        $connect = $this->xpdo->connect();
        $this->assertTrue($connect, 'xPDO could not connect via xpdo->connect().');
    }

    /**
     * Test table creation.
     */
    public function testCreateObjectContainer()
    {
        $result = false;
        try {
            $this->xpdo->getManager();
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Phone');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\PersonPhone');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\BloodType');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Item');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $result = !array_search(false, $result, true);
        $this->assertTrue($result, 'Error creating tables.');
    }

    /**
     * Test table engine override.
     */
    public function testOverrideTableType() {
        $result = false;
        try {
            $this->xpdo->getManager();
            $oldType = $this->xpdo->getOption(xPDO::OPT_OVERRIDE_TABLE_TYPE);

            $this->xpdo->setOption(xPDO::OPT_OVERRIDE_TABLE_TYPE, 'INNODB');

            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Person');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');

            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Phone');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Phone');

            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\PersonPhone');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\PersonPhone');

            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\BloodType');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\BloodType');

            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Item');
            $result[] = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Item');

            $this->xpdo->setOption(xPDO::OPT_OVERRIDE_TABLE_TYPE, $oldType);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $result = !array_search(false, $result, true);
        $this->assertTrue($result, 'Error creating tables with table type override.');
    }

    /**
     * Tests xPDO::escape
     */
    public function testEscape()
    {
        $correct = 'test';
        $correct = trim($correct, $this->xpdo->_escapeCharOpen . $this->xpdo->_escapeCharClose);
        $correct = $this->xpdo->_escapeCharOpen . $correct . $this->xpdo->_escapeCharClose;

        $eco = $this->xpdo->_escapeCharOpen;
        $ecc = $this->xpdo->_escapeCharClose;
        $this->assertEquals($correct, $this->xpdo->escape('test'), 'xpdo->escape() did not correctly escape.');
        $this->assertEquals($correct, $this->xpdo->escape($eco . 'test'), 'xpdo->escape() did not strip the beginning escape character before escaping.');
        $this->assertEquals($correct, $this->xpdo->escape($eco . 'test' . $ecc), 'xpdo->escape() did not strip the beginning and end escape character before escaping.');
        $this->assertEquals($correct, $this->xpdo->escape('test' . $ecc), 'xpdo->escape() did not strip the end escape character before escaping.');
    }

    /**
     * Test xPDO::escSplit
     */
    public function testEscSplit()
    {
        $str = '1,2,3';
        $result = xPDO::escSplit(',', $str, $this->xpdo->_escapeCharOpen);
        $this->assertTrue(is_array($result), 'xPDO::escSplit did not return an array.');
        $this->assertEquals(3, count($result), 'xPDO::escSplit did not return the correct number of indices.');
    }

    /**
     * Test xPDO::fromJSON
     */
    public function testFromJson()
    {
        $json = '{"key":"value","nested":{"foo":"123","another":"test"}}';
        $result = $this->xpdo->fromJSON($json);
        $this->assertTrue(is_array($result), 'xpdo->fromJSON() did not return an array.');
    }

    /**
     * Test xPDO::toJSON
     */
    public function testToJson()
    {
        $array = array('key' => 'value', 'nested' => array('foo' => '123', 'another' => 'test',),);
        $result = $this->xpdo->toJSON($array);
        $this->assertTrue(is_string($result), 'xpdo->fromJSON() did not return an array.');
    }

    /**
     * Test xPDO::getManager
     */
    public function testGetManager()
    {
        $manager = $this->xpdo->getManager();
        $success = is_object($manager) && $manager instanceof xPDOManager;
        $this->assertTrue($success, 'xpdo->getManager did not return an xPDOManager instance.');
    }

    /**
     * Test xPDO::getDriver
     */
    public function testGetDriver()
    {
        $driver = $this->xpdo->getDriver();
        $success = is_object($driver) && $driver instanceof xPDODriver;
        $this->assertTrue($success, 'xpdo->getDriver did not return an xPDODriver instance.');
    }

    /**
     * Test xPDO::getCacheManager
     */
    public function testGetCacheManager()
    {
        $cacheManager = $this->xpdo->getCacheManager();
        $success = is_object($cacheManager) && $cacheManager instanceof xPDOCacheManager;
        $this->assertTrue($success, 'xpdo->getCacheManager did not return an xPDOCacheManager instance.');
    }

    /**
     * Test xPDO::getCachePath
     */
    public function testGetCachePath()
    {
        $cachePath = $this->xpdo->getCachePath();
        $this->assertEquals($cachePath, self::$properties['xpdo_test_path'] . 'cache/', 'xpdo->getCachePath() did not return the correct cache path.');
    }

    /**
     * Verify xPDO::newQuery returns a xPDOQuery object
     */
    public function testNewQuery()
    {
        $criteria = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $success = is_object($criteria) && $criteria instanceof xPDOQuery;
        $this->assertTrue($success);
    }

    /**
     * Verify xPDO::getCriteriaType returns "xPDOQuery"
     */
    public function testGetCriteriaType()
    {
        $criteria = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $criteriaType = $this->xpdo->getCriteriaType($criteria);
        $success = $criteriaType === 'xPDOQuery';
        $this->assertTrue($success, 'Unexpected criteriaType ' . $criteriaType);
    }

    /**
     * Tests xPDO::getAncestry and make sure it returns an array of the correct
     * data.
     *
     * @dataProvider providerGetAncestry
     */
    public function testGetAncestry($class, array $correct = array(), $includeSelf = true)
    {
        $this->assertEquals($correct, $this->xpdo->getAncestry($class, $includeSelf));
    }

    /**
     * Data provider for testGetAncestry
     */
    public function providerGetAncestry()
    {
        return array(
            array(
                'xPDO\\Test\\Sample\\Person',
                array('xPDO\\Test\\Sample\\Person', 'xPDO\\Om\\xPDOSimpleObject', 'xPDO\\Om\\xPDOObject')
            ),
            array(
                'xPDO\\Test\\Sample\\Person',
                array('xPDO\\Om\\xPDOSimpleObject', 'xPDO\\Om\\xPDOObject'),
                false
            ),
        );
    }

    /**
     * Tests xPDO::getDescendants and make sure it returns an array of the correct data.
     *
     * @dataProvider providerGetDescendants
     *
     * @param string $class
     * @param array $correct
     */
    public function testGetDescendants($class, array $correct = array())
    {
        $this->assertEquals($correct, $this->xpdo->getDescendants($class));
    }

    /**
     * Data provider for testGetDescendants
     */
    public function providerGetDescendants()
    {
        return array(
            array(
                'xPDO\\Om\\xPDOSimpleObject',
                array(
                    0 => 'xPDO\\Test\\Sample\\Person',
                    1 => 'xPDO\\Test\\Sample\\Phone',
                    2 => 'xPDO\\Test\\Sample\\xPDOSample',
                    3 => 'xPDO\\Test\\Sample\\Item',
                    4 => 'xPDO\\Test\\Sample\\SecureObject',
                    5 => 'xPDO\\Test\\Sample\\SecureItem'
                )
            ),
            array(
                'xPDO\\Om\\xPDOObject',
                array(
                    0 => 'xPDO\\Om\\xPDOSimpleObject',
                    1 => 'xPDO\\Test\\Sample\\PersonPhone',
                    2 => 'xPDO\\Test\\Sample\\BloodType',
                    3 => 'xPDO\\Test\\Sample\\Person',
                    4 => 'xPDO\\Test\\Sample\\Phone',
                    5 => 'xPDO\\Test\\Sample\\xPDOSample',
                    6 => 'xPDO\\Test\\Sample\\Item',
                    7 => 'xPDO\\Test\\Sample\\SecureObject',
                    8 => 'xPDO\\Test\\Sample\\SecureItem'
                )
            ),
        );
    }

    /**
     * Test xPDO->getSelectColumns.
     *
     * $className, $tableAlias= '', $columnPrefix= '', $columns= array (), $exclude= false
     */
    public function testGetSelectColumns()
    {
        $fields = array(
            'id',
            'first_name',
            'last_name',
            'middle_name',
            'date_modified',
            'dob',
            'gender',
            'blood_type',
            'username',
            'password',
            'security_level'
        );
        $correct = implode(', ', array_map(array($this->xpdo, 'escape'), $fields));
        $columns = $this->xpdo->getSelectColumns('xPDO\\Test\\Sample\\Person');
        $this->assertEquals($correct, $columns);

        $correct = implode(', ', array_map(array($this, 'prefixWithEscapedPersonAlias'), $fields));
        $columns = $this->xpdo->getSelectColumns('xPDO\\Test\\Sample\\Person', 'Person');
        $this->assertEquals($correct, $columns);

        $correct = implode(', ', array_map(array($this, 'postfixWithEscapedTestAlias'), $fields));
        $columns = $this->xpdo->getSelectColumns('xPDO\\Test\\Sample\\Person', 'Person', 'test_');
        $this->assertEquals($correct, $columns);

        $includeColumns = array('dob', 'last_name', 'id');
        $correct = implode(', ', array_map(array($this->xpdo, 'escape'), $includeColumns));
        $columns = $this->xpdo->getSelectColumns('xPDO\\Test\\Sample\\Person', '', '', $includeColumns);
        $this->assertEquals($correct, $columns);

        $excludeColumns = array('first_name', 'middle_name', 'dob', 'gender', 'security_level', 'blood_type');
        $correct = implode(', ', array_map(array(
            $this->xpdo,
            'escape'
        ), array(
            'id',
            'last_name',
            'date_modified',
            'username',
            'password'
        )));
        $columns = $this->xpdo->getSelectColumns('xPDO\\Test\\Sample\\Person', '', '', $excludeColumns, true);
        $this->assertEquals($correct, $columns);
    }

    private function prefixWithEscapedPersonAlias($string)
    {
        return $this->xpdo->escape('Person') . '.' . $this->xpdo->escape($string);
    }

    private function postfixWithEscapedTestAlias($string)
    {
        return $this->prefixWithEscapedPersonAlias($string) . ' AS ' . $this->xpdo->escape('test_' . $string);
    }

    /**
     * Test xPDO->getPackage.
     *
     * @dataProvider providerGetPackage
     *
     * @param string $class The class to test.
     * @param string $correctPackage The correct table package name that should be returned.
     */
    public function testGetPackage($class, $correctPackage)
    {
        $package = $this->xpdo->getPackage($class);
        $this->assertEquals($correctPackage, $package);
    }

    /**
     * Data provider for testGetPackage
     *
     * @see testGetPackage
     */
    public function providerGetPackage()
    {
        return array(array('xPDO\\Test\\Sample\\Person', 'xPDO\\Test\\Sample'),);
    }

    /**
     * Test xPDO->getTableMeta
     *
     * @dataProvider providerGetTableMeta
     *
     * @param string $class The class to test.
     * @param array /null $correctMeta The correct table meta that should be returned.
     */
    public function testGetTableMeta($class, $correctMeta = null)
    {
        $tableMeta = $this->xpdo->getTableMeta($class);
        if (self::$properties['xpdo_driver'] !== 'mysql') {
            $correctMeta = null;
        }
        $this->assertEquals($correctMeta, $tableMeta);
    }

    /**
     * Data provider for testGetTableMeta
     *
     * @see testGetTableMeta
     */
    public function providerGetTableMeta()
    {
        return array(array('xPDO\\Test\\Sample\\Person', array('engine' => 'InnoDB')),);
    }

    /**
     * Test xPDO->getFields
     *
     * @dataProvider providerGetFields
     *
     * @param string $class The name of the class to test.
     * @param array $correctFields An array of fields that should result.
     */
    public function testGetFields($class, array $correctFields = array())
    {
        $fields = $this->xpdo->getFields($class);
        $this->assertEquals($correctFields, $fields);
    }

    /**
     * Data provider for testGetFields
     *
     * @see testGetFields
     */
    public function providerGetFields()
    {
        return array(
            array(
                'xPDO\\Test\\Sample\\Person',
                array(
                    'id' => null,
                    'first_name' => '',
                    'last_name' => '',
                    'middle_name' => '',
                    'date_modified' => 'CURRENT_TIMESTAMP',
                    'dob' => '',
                    'gender' => '',
                    'blood_type' => null,
                    'username' => '',
                    'password' => '',
                    'security_level' => 1,
                )
            ),
            array(
                'xPDO\\Test\\Sample\\xPDOSample',
                array(
                    'id' => null,
                    'parent' => 0,
                    'unique_varchar' => null,
                    'varchar' => null,
                    'text' => null,
                    'timestamp' => 'CURRENT_TIMESTAMP',
                    'unix_timestamp' => 0,
                    'date_time' => null,
                    'date' => null,
                    'enum' => null,
                    'password' => null,
                    'integer' => null,
                    'float' => 1.01230,
                    'boolean' => null,
                )
            ),
        );
    }

    /**
     * Test xPDO->getFieldMeta
     *
     * @dataProvider providerGetFieldMeta
     *
     * @param string $class The class to test.
     */
    public function testGetFieldMeta($class)
    {
        $tableMeta = $this->xpdo->getFieldMeta($class);
        $this->assertTrue(is_array($tableMeta));
    }

    /**
     * Data provider for testGetFieldMeta
     *
     * @see testGetTableMeta
     */
    public function providerGetFieldMeta()
    {
        return array(array('xPDO\\Test\\Sample\\Person'),);
    }

    /**
     * Test xPDO->getPK
     *
     * @dataProvider providerGetPK
     *
     * @param string $class The class name to check.
     * @param string $correctPk The PK that should result.
     */
    public function testGetPK($class, $correctPk)
    {
        $pk = $this->xpdo->getPK($class);
        $this->assertEquals($correctPk, $pk);
    }

    /**
     * Data provider for testGetPK
     *
     * @see testGetPK
     */
    public function providerGetPK()
    {
        return array(
            array('xPDO\\Test\\Sample\\Person', 'id'),
            array('xPDO\\Test\\Sample\\Phone', 'id'),
            array('xPDO\\Test\\Sample\\PersonPhone', array('person' => 'person', 'phone' => 'phone')),
        );
    }

    /**
     * Tests xPDO->getPKType
     *
     * @dataProvider providerGetPKType
     *
     * @param string $class
     * @param string $correctType
     */
    public function testGetPKType($class, $correctType = 'integer')
    {
        $type = $this->xpdo->getPKType($class);
        $this->assertEquals($correctType, $type);
    }

    /**
     * Data provider for testGetPKType
     *
     * @see testGetPKType
     */
    public function providerGetPKType()
    {
        return array(
            array('xPDO\\Test\\Sample\\Person', 'integer'),
            array('xPDO\\Test\\Sample\\Phone', 'integer'),
            array('xPDO\\Test\\Sample\\PersonPhone', array('person' => 'integer', 'phone' => 'integer')),
        );
    }

    /**
     * Test xPDO->getAggregates
     *
     * @dataProvider providerGetAggregates
     *
     * @param string $class
     * @param array $correctAggs
     */
    public function testGetAggregates($class, $correctAggs)
    {
        $aggs = $this->xpdo->getAggregates($class);
        $this->assertEquals($correctAggs, $aggs);
    }

    /**
     * Data provider for testGetAggregates
     *
     * @see testGetAggregates
     */
    public function providerGetAggregates()
    {
        return array(
            array(
                'xPDO\\Test\\Sample\\Person',
                array(
                    'BloodType' => array(
                        'class' => 'xPDO\\Test\\Sample\\BloodType',
                        'local' => 'blood_type',
                        'foreign' => 'type',
                        'cardinality' => 'one',
                        'owner' => 'foreign',
                    ),
                )
            ),
            array('xPDO\\Test\\Sample\\Phone', array()),
            array(
                'xPDO\\Test\\Sample\\PersonPhone',
                array(
                    'Person' => array(
                        'class' => 'xPDO\\Test\\Sample\\Person',
                        'local' => 'person',
                        'foreign' => 'id',
                        'cardinality' => 'one',
                        'owner' => 'foreign',
                    ),
                )
            ),
        );
    }

    /**
     * Test xPDO->getComposites
     *
     * @dataProvider providerGetComposites
     *
     * @param string $class
     * @param array $correctComps
     */
    public function testGetComposites($class, $correctComps)
    {
        $comps = $this->xpdo->getComposites($class);
        $this->assertEquals($correctComps, $comps);
    }

    /**
     * Data provider for testGetComposites
     *
     * @see testGetComposites
     */
    public function providerGetComposites()
    {
        return array(
            array(
                'xPDO\\Test\\Sample\\Person',
                array(
                    'PersonPhone' => array(
                        'class' => 'xPDO\\Test\\Sample\\PersonPhone',
                        'local' => 'id',
                        'foreign' => 'person',
                        'cardinality' => 'many',
                        'owner' => 'local',
                    ),
                )
            ),
            array(
                'xPDO\\Test\\Sample\\Phone',
                array(
                    'PersonPhone' => array(
                        'class' => 'xPDO\\Test\\Sample\\PersonPhone',
                        'local' => 'id',
                        'foreign' => 'phone',
                        'cardinality' => 'many',
                        'owner' => 'local',
                    ),
                )
            ),
            array(
                'xPDO\\Test\\Sample\\PersonPhone',
                array(
                    'Phone' => array(
                        'class' => 'xPDO\\Test\\Sample\\Phone',
                        'local' => 'phone',
                        'foreign' => 'id',
                        'cardinality' => 'one',
                        'owner' => 'foreign',
                    ),
                )
            ),
        );
    }

    /**
     * Test xPDO->getGraph()
     *
     * @dataProvider providerGetGraph
     *
     * @param string $class The class to get a graph for.
     * @param int $depth The depth to get the graph to.
     * @param array $expected The expected graph array.
     */
    public function testGetGraph($class, $depth, $expected)
    {
        $actual = $this->xpdo->getGraph($class, $depth);
        $this->assertEquals($expected, $actual);
    }

    public function providerGetGraph()
    {
        return array(
            array('xPDO\\Test\\Sample\\Person', 10, array('BloodType' => array(), 'PersonPhone' => array('Phone' => array()))),
            array('xPDO\\Test\\Sample\\Person', 1, array('BloodType' => array(), 'PersonPhone' => array())),
            array('xPDO\\Test\\Sample\\Person', 0, array()),
            array('xPDO\\Test\\Sample\\Person', 1000, array('BloodType' => array(), 'PersonPhone' => array('Phone' => array()))),
        );
    }

    /**
     * Test xPDO->parseBindings()
     *
     * @dataProvider providerParseBindings
     *
     * @param $sql
     * @param $bindings
     * @param $expected
     */
    public function testParseBindings($sql, $bindings, $expected)
    {
        $this->assertEquals($expected, $this->xpdo->parseBindings($sql, $bindings));
    }

    public function providerParseBindings()
    {
        return array(
            array('SELECT * FROM a WHERE a.a=?', array("$1.00"), "SELECT * FROM a WHERE a.a='$1.00'"),
            array('SELECT * FROM a WHERE a.a=? AND a.b=? AND a.c = ?', array('$1.00', '$2.50', '$5.00'), "SELECT * FROM a WHERE a.a='$1.00' AND a.b='$2.50' AND a.c = '$5.00'"),
            array('SELECT * FROM a WHERE a.a=:a', array(':a' => '$1.00'), "SELECT * FROM a WHERE a.a='$1.00'"),
            array('SELECT * FROM a WHERE a.a=:a AND a.b=:b AND a.c = :c', array(':a' => '$1.00', ':b' => '$2.50', ':c' => '$5.00'), "SELECT * FROM a WHERE a.a='$1.00' AND a.b='$2.50' AND a.c = '$5.00'"),
        );
    }

    /**
     * Test xPDO->call()
     */
    public function testCall()
    {
        $results = array();
        try {
            $results[] = ($this->xpdo->call('xPDO\\Test\\Sample\\Item', 'callTest') === 'xPDO\\Test\\Sample\\' . $this->xpdo->getOption('dbtype') . '\\Item');
            $results[] = ($this->xpdo->call('xPDO\\Test\\Sample\\xPDOSample', 'callTest') === 'xPDO\\Test\\Sample\\xPDOSample');
            $results[] = ($this->xpdo->call('xPDO\\Test\\Sample\\TransientDerivative', 'callTest', array(), true) === 'xPDO\\Test\\Sample\\TransientDerivative');
            $results[] = ($this->xpdo->call('xPDO\\Test\\Sample\\Transient', 'callTest', array(), true) === 'xPDO\\Test\\Sample\\Transient');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue(!array_search(false, $results, true), 'Error using call()');
    }

    /**
     * Test table destruction.
     */
    public function testRemoveObjectContainer()
    {
        $result = false;
        try {
            $this->xpdo->getManager();
            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Person');
            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Phone');
            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\PersonPhone');
            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\BloodType');
            $result[] = $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Item');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $result = !array_search(false, $result, true);
        $this->assertTrue($result, 'Error dropping tables.');
    }

    /**
     * Test xPDO->getAlias()
     *
     * @param string $class
     * @param string $expectedAlias
     *
     * @dataProvider providerGetAlias
     */
    public function testGetAlias($class, $expectedAlias)
    {
        $this->assertEquals($expectedAlias, $this->xpdo->getAlias($class));
    }

    public function providerGetAlias()
    {
        return [
            [Item::class, 'Item'],
            [Person::class, 'Person'],
            [BloodType::class, 'BloodType'],
            [xPDOSample::class, 'xPDOSample'],
        ];
    }
}
