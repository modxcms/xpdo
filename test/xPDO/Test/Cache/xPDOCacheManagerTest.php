<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Cache;

use xPDO\TestCase;

/**
 * Tests related to basic xPDOCacheManager methods
 *
 * @package xPDO\Test\Cache
 */
class xPDOCacheManagerTest extends TestCase
{
    /**
     * Test writing a directory tree structure to the filesystem.
     *
     * @dataProvider providerWriteTree
     *
     * @param string $path
     * @param array $options
     */
    public function testWriteTree($path, $options)
    {
        $path = self::$properties['xpdo_test_path'] . "fs/{$path}";
        $result = $this->xpdo->getCacheManager()->writeTree($path, $options);
        $this->assertTrue($result, 'Error writing directory tree to filesystem');
    }

    public function providerWriteTree()
    {
        return array(
            array('test', array()),
            array('test/', array()),
            array('test/dirA', array()),
            array('test/dirB/', array()),
            array('test/dirA/subdirA', array()),
            array('test/dirA/subdirB/', array()),
        );
    }

    /**
     * Test copying a directory and it's contents on the filesystem.
     *
     * @dataProvider providerCopyTree
     *
     * @param string $source
     * @param string $target
     * @param array $options
     * @param array $expected
     */
    public function testCopyTree($source, $target, $options, $expected)
    {
        $source = self::$properties['xpdo_test_path'] . "fs/{$source}";
        $target = self::$properties['xpdo_test_path'] . "fs/{$target}";
        while (list($idx, $path) = each($expected)) $expected[$idx] = self::$properties['xpdo_test_path'] . 'fs/' . $path;
        $result = $this->xpdo->getCacheManager()->copyTree($source, $target, $options);
        $this->assertEquals($expected, $result, 'Error copying directory tree on filesystem');
    }

    public function providerCopyTree()
    {
        return array(
            array(
                'test/dirA',
                'copy/',
                array(),
                array(
                    "copy/subdirA",
                    "copy/subdirB",
                    "copy",
                )
            ),
        );
    }

    /**
     * Test deleting a directory from the filesystem.
     *
     * @dataProvider providerDeleteTree
     *
     * @param string $path
     * @param array $options
     * @param array $expected
     */
    public function testDeleteTree($path, $options, $expected)
    {
        $path = self::$properties['xpdo_test_path'] . "fs/{$path}";
        while (list($idx, $dir) = each($expected)) $expected[$idx] = self::$properties['xpdo_test_path'] . 'fs/' . $dir;
        $result = $this->xpdo->getCacheManager()->deleteTree($path, $options);
        $this->assertEquals($expected, $result, 'Error deleting directory tree from filesystem');
    }

    public function providerDeleteTree()
    {
        return array(
            array(
                'test/dirA',
                array('deleteTop' => false, 'skipDirs' => false, 'extensions' => array()),
                array(
                    "test/dirA/subdirA/",
                    "test/dirA/subdirB/",
                )
            ),
            array(
                'test/dirB/',
                array('deleteTop' => false, 'skipDirs' => false, 'extensions' => array()),
                array()
            ),
            array(
                'copy/',
                array('deleteTop' => true, 'skipDirs' => false, 'extensions' => array()),
                array(
                    "copy/subdirA/",
                    "copy/subdirB/",
                    "copy/",
                )
            ),
            array(
                'test/dirA',
                array('deleteTop' => true, 'skipDirs' => false, 'extensions' => array()),
                array(
                    "test/dirA/",
                )
            ),
            array(
                'test/dirB',
                array('deleteTop' => true, 'skipDirs' => false, 'extensions' => array()),
                array(
                    "test/dirB/",
                )
            ),
            array(
                'test/',
                array('deleteTop' => true, 'skipDirs' => false, 'extensions' => array()),
                array(
                    "test/",
                )
            ),
        );
    }
}
