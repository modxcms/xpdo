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
            $result[] = $this->xpdo->manager->createObjectContainer('Person');
            $result[] = $this->xpdo->manager->createObjectContainer('Phone');
            $result[] = $this->xpdo->manager->createObjectContainer('PersonPhone');
            $result[] = $this->xpdo->manager->createObjectContainer('BloodType');
            $result[] = $this->xpdo->manager->createObjectContainer('Item');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $result = !array_search(false, $result, true);
        $this->assertTrue($result, 'Error creating tables.');
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
        $criteria = $this->xpdo->newQuery('Person');
        $success = is_object($criteria) && $criteria instanceof xPDOQuery;
        $this->assertTrue($success);
    }

    /**
     * Tests xPDO::getAncestry and make sure it returns an array of the correct
     * data.
     *
     * @dataProvider providerGetAncestry
     */
    public function testGetAncestry($class, array $correct = array(), $includeSelf = true)
    {
        $anc = $this->xpdo->getAncestry($class, $includeSelf);
        $diff = array_diff($correct, $anc);
        $diff2 = array_diff($anc, $correct);
        $success = is_array($anc) && empty($diff) && empty($diff2);
        $this->assertTrue($success);
    }

    /**
     * Data provider for testGetAncestry
     */
    public function providerGetAncestry()
    {
        return array(array('Person', array('Person', 'xPDOSimpleObject', 'xPDOObject')), array('Person', array('xPDOSimpleObject', 'xPDOObject'), false),);
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
        $derv = $this->xpdo->getDescendants($class);
        $diff = array_diff($correct, $derv);
        $diff2 = array_diff($derv, $correct);
        $success = is_array($derv) && empty($diff) && empty($diff2);
        $this->assertTrue($success);
    }

    /**
     * Data provider for testGetDescendants
     */
    public function providerGetDescendants()
    {
        return array(array('xPDOSimpleObject', array(0 => 'Person', 1 => 'Phone', 2 => 'xPDOSample', 3 => 'Item',)), array('xPDOObject', array(0 => 'xPDOSimpleObject', 1 => 'PersonPhone', 2 => 'BloodType', 3 => 'Person', 4 => 'Phone', 5 => 'xPDOSample', 6 => 'Item',)),);
    }

    /**
     * Test xPDO->getSelectColumns.
     *
     * $className, $tableAlias= '', $columnPrefix= '', $columns= array (), $exclude= false
     */
    public function testGetSelectColumns()
    {
        $fields = array('id', 'first_name', 'last_name', 'middle_name', 'date_modified', 'dob', 'gender', 'blood_type', 'username', 'password', 'security_level');
        $correct = implode(', ', array_map(array($this->xpdo, 'escape'), $fields));
        $columns = $this->xpdo->getSelectColumns('Person');
        $this->assertEquals($columns, $correct);

        $correct = implode(', ', array_map(array($this, 'prefixWithEscapedPersonAlias'), $fields));
        $columns = $this->xpdo->getSelectColumns('Person', 'Person');
        $this->assertEquals($columns, $correct);

        $correct = implode(', ', array_map(array($this, 'postfixWithEscapedTestAlias'), $fields));
        $columns = $this->xpdo->getSelectColumns('Person', 'Person', 'test_');
        $this->assertEquals($columns, $correct);

        $includeColumns = array('dob', 'last_name', 'id');
        $correct = implode(', ', array_map(array($this->xpdo, 'escape'), $includeColumns));
        $columns = $this->xpdo->getSelectColumns('Person', '', '', $includeColumns);
        $this->assertEquals($columns, $correct);

        $excludeColumns = array('first_name', 'middle_name', 'dob', 'gender', 'security_level', 'blood_type');
        $correct = implode(', ', array_map(array($this->xpdo, 'escape'), array('id', 'last_name', 'date_modified', 'username', 'password')));
        $columns = $this->xpdo->getSelectColumns('Person', '', '', $excludeColumns, true);
        $this->assertEquals($columns, $correct);
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
        return array(array('Person', 'sample'),);
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
        return array(array('Person', array('engine' => 'MyISAM')),);
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
        return array(array('Person', array('id' => null, 'first_name' => '', 'last_name' => '', 'middle_name' => '', 'date_modified' => 'CURRENT_TIMESTAMP', 'dob' => '', 'gender' => '', 'blood_type' => null, 'username' => '', 'password' => '', 'security_level' => 1,)), array('xPDOSample', array('id' => null, 'parent' => 0, 'unique_varchar' => null, 'varchar' => null, 'text' => null, 'timestamp' => 'CURRENT_TIMESTAMP', 'unix_timestamp' => 0, 'date_time' => null, 'date' => null, 'enum' => null, 'password' => null, 'integer' => null, 'float' => 1.01230, 'boolean' => null,)),);
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
        return array(array('Person'),);
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
        return array(array('Person', 'id'), array('Phone', 'id'), array('PersonPhone', array('person' => 'person', 'phone' => 'phone')),);
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
        return array(array('Person', 'integer'), array('Phone', 'integer'), array('PersonPhone', array('person' => 'integer', 'phone' => 'integer')),);
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
        return array(array('Person', array('BloodType' => array('class' => 'BloodType', 'local' => 'blood_type', 'foreign' => 'type', 'cardinality' => 'one', 'owner' => 'foreign',),)), array('Phone', array()), array('PersonPhone', array('Person' => array('class' => 'Person', 'local' => 'person', 'foreign' => 'id', 'cardinality' => 'one', 'owner' => 'foreign',),)),);
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
        return array(array('Person', array('PersonPhone' => array('class' => 'PersonPhone', 'local' => 'id', 'foreign' => 'person', 'cardinality' => 'many', 'owner' => 'local',),)), array('Phone', array('PersonPhone' => array('class' => 'PersonPhone', 'local' => 'id', 'foreign' => 'phone', 'cardinality' => 'many', 'owner' => 'local',),)), array('PersonPhone', array('Phone' => array('class' => 'Phone', 'local' => 'phone', 'foreign' => 'id', 'cardinality' => 'one', 'owner' => 'foreign',),)),);
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
        return array(array('Person', 10, array('BloodType' => array(), 'PersonPhone' => array('Phone' => array()))), array('Person', 1, array('BloodType' => array(), 'PersonPhone' => array())), array('Person', 0, array()), array('Person', 1000, array('BloodType' => array(), 'PersonPhone' => array('Phone' => array()))),);
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
        return array(array('SELECT * FROM a WHERE a.a=?', array("$1.00"), "SELECT * FROM a WHERE a.a='$1.00'"), array('SELECT * FROM a WHERE a.a=:a', array(':a' => "$1.00"), "SELECT * FROM a WHERE a.a='$1.00'"),);
    }

    /**
     * Test xPDO->call()
     */
    public function testCall()
    {
        $results = array();
        try {
            $results[] = ($this->xpdo->call('Item', 'callTest') === 'Item_' . $this->xpdo->getOption('dbtype'));
            $results[] = ($this->xpdo->call('xPDOSample', 'callTest') === 'xPDOSample');
            $results[] = ($this->xpdo->call('TransientDerivative', 'callTest', array(), true) === 'TransientDerivative');
            $results[] = ($this->xpdo->call('Transient', 'callTest', array(), true) === 'Transient');
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
            $result[] = $this->xpdo->manager->removeObjectContainer('Person');
            $result[] = $this->xpdo->manager->removeObjectContainer('Phone');
            $result[] = $this->xpdo->manager->removeObjectContainer('PersonPhone');
            $result[] = $this->xpdo->manager->removeObjectContainer('BloodType');
            $result[] = $this->xpdo->manager->removeObjectContainer('Item');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $result = !array_search(false, $result, true);
        $this->assertTrue($result, 'Error dropping tables.');
    }
}
