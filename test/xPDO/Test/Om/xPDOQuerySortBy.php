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
 * Tests related to sortby statements.
 *
 * @package xPDO\Test\Om
 */
class xPDOQuerySortByTest extends TestCase {
    /**
     * Setup dummy data for each test.
     */
    public function setUp() {
        parent::setUp();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();
            $this->xpdo->manager->createObjectContainer('Item');

            $colors = array('red','green','yellow','blue');

            $r = 0;
            for ($i=1;$i<40;$i++) {
                $item = $this->xpdo->newObject('Item');
                $idx = str_pad($i,2,'0',STR_PAD_LEFT);
                $item->set('name','item-'.$idx);
                $r++;
                if ($r > 3) $r = 0;
                $item->set('color',$colors[$r]);
                $item->save();
            }

        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Clean up data when through.
     */
    public function tearDown() {
    	$this->xpdo->getManager();
        $this->xpdo->manager->removeObjectContainer('Item');
        parent::tearDown();
    }

    /**
     * Test sortby
     * @dataProvider providerSortBy
     */
    public function testSortBy($sort,$dir,$nameOfFirst) {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->sortby($sort,$dir);
            $result = $this->xpdo->getCollection('Item',$criteria);
            if (is_array($result) && !empty($result)) {
                foreach ($result as $r) {
                    /** @var xPDOObject $result */
                    $result = $r;
                    break;
                }
                $name = $result->get('name');
                $this->assertEquals($nameOfFirst,$name,'xPDOQuery: SortBy did not return expected result, returned `'.$name.'` instead.');
            } else {
                throw new \Exception('xPDOQuery: SortBy test getCollection call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false,$e->getMessage());
        }
    }
    /**
     * Data provider for testLimit
     * @see testLimit
     */
    public function providerSortBy() {
        return array(
            array('name','ASC','item-01'),
            array('name','DESC','item-39'),
            array('color,name','ASC','item-03'),
        );
    }

    /**
     * Test sortby with groupby statement
     * @dataProvider providerSortByWithGroupBy
     */
    public function testSortByWithGroupBy($sort,$dir,$nameOfFirst) {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->groupby("{$sort},id,color");
            $criteria->sortby($this->xpdo->escape($sort),$dir);
            $criteria->sortby($this->xpdo->escape('id'),'ASC');
            $criteria->sortby($this->xpdo->escape('color'),'ASC');
            $result = $this->xpdo->getCollection('Item',$criteria);
            if (is_array($result) && !empty($result)) {
                $match = null;
                foreach ($result as $r) {
                    /** @var xPDOObject $match */
                    $match = $r;
                    break;
                }
                $name = $match->get('name');
                $this->assertEquals($nameOfFirst,$name,'xPDOQuery: SortBy did not return expected result, returned `'.$name.'` instead.');
            } else {
                throw new \Exception('xPDOQuery: SortBy test with groupby call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false,$e->getMessage());
        }
    }
    /**
     * Data provider for testSortByWithGroupBy
     * @see testSortByWithGroupBy
     */
    public function providerSortByWithGroupBy() {
        return array(
            array('name','ASC','item-01'),
            array('name','DESC','item-39'),
        );
    }


    /**
     * Test sortby with limit statement
     * @dataProvider providerSortByWithLimit
     */
    public function testSortByWithLimit($sort,$dir,$limit,$start,$nameOfFirst) {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->sortby($this->xpdo->escape($sort),$dir);
            $criteria->limit($limit,$start);
            $result = $this->xpdo->getCollection('Item',$criteria);
            if (is_array($result) && !empty($result)) {
                foreach ($result as $r) {
                    /** @var xPDOObject $result */
                    $result = $r;
                    break;
                }
                $name = $result->get('name');
                $this->assertEquals($nameOfFirst,$name,'xPDOQuery: SortBy did not return expected result `'.$nameOfFirst.'`, returned `'.$name.'` instead: '.$criteria->toSql());
            } else {
                throw new \Exception('xPDOQuery: SortBy test with limit call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false,$e->getMessage());
        }
    }
    /**
     * Data provider for testSortByWithGroupBy
     * @see testSortByWithLimit
     */
    public function providerSortByWithLimit() {
        return array(
            array('name','ASC',4,0,'item-01'),
            array('name','DESC',4,0,'item-39'),
        );
    }
}
