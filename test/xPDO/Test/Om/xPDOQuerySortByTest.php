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

use xPDO\Om\xPDOExpression;
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
     *
     * @before
     */
    public function setUpFixtures() {
        parent::setUpFixtures();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();
            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Item');

            $colors = array('red','green','yellow','blue');

            $r = 0;
            for ($i=1;$i<40;$i++) {
                $item = $this->xpdo->newObject('xPDO\\Test\\Sample\\Item');
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
     *
     * @after
     */
    public function tearDownFixtures() {
    	$this->xpdo->getManager();
        $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Item');
        parent::tearDownFixtures();
    }

    /**
     * Test sortby
     * @dataProvider providerSortBy
     */
    public function testSortBy($sort,$dir,$nameOfFirst) {
        try {
            $criteria = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Item');
            $criteria->sortby($sort,$dir);
            $result = $this->xpdo->getCollection('xPDO\\Test\\Sample\\Item',$criteria);
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
            $criteria = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Item');
            $criteria->groupby("{$sort},id,color");
            $criteria->sortby($this->xpdo->escape($sort),$dir);
            $criteria->sortby($this->xpdo->escape('id'),'ASC');
            $criteria->sortby($this->xpdo->escape('color'),'ASC');
            $result = $this->xpdo->getCollection('xPDO\\Test\\Sample\\Item',$criteria);
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
            $criteria = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Item');
            $criteria->sortby($this->xpdo->escape($sort),$dir);
            $criteria->limit($limit,$start);
            $result = $this->xpdo->getCollection('xPDO\\Test\\Sample\\Item',$criteria);
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

    /**
     * An xPDOExpression passed to sortby() must be inlined verbatim in the
     * ORDER BY clause — not identifier-quoted, not dropped, no TypeError.
     *
     * Before the fix, sortby() passes $column directly to isValidClause() which
     * calls rtrim() on it; PHP 8.1 emits a TypeError when rtrim() receives an
     * object. Even if that were suppressed the object would be stored as-is and
     * the driver construct() would fail to cast it to a string.
     */
    public function testSortByExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Item');
        $query->sortby(new xPDOExpression('FIELD(status, 1, 2)'), 'ASC');
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('FIELD(status, 1, 2) ASC', $sql,
            'An xPDOExpression in sortby() must be inlined verbatim in ORDER BY. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression passed to groupby() must be inlined verbatim in the
     * GROUP BY clause — not identifier-quoted, not dropped, no TypeError.
     *
     * Before the fix, groupby() stores $column directly in the query array.
     * When the driver construct() runs, it does `$sql .= $groupby['column']`
     * which performs implicit string concatenation on the xPDOExpression object.
     * xPDOExpression does not implement __toString(), so PHP 8.1 emits a fatal
     * TypeError: "Object of class xPDO\Om\xPDOExpression could not be converted
     * to string".
     *
     * After the fix, groupby() must call getExpression() before storing, so the
     * driver construct() receives the raw SQL string and inlines it verbatim.
     */
    public function testGroupByExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Item');
        $query->groupby(new xPDOExpression('DATE(created_at)'));
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('DATE(created_at)', $sql,
            'An xPDOExpression in groupby() must be inlined verbatim in GROUP BY. SQL was: ' . $sql);
    }
}
