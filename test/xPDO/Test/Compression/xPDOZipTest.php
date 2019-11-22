<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Compression;

use xPDO\Compression\xPDOZip;
use xPDO\TestCase;
use xPDO\xPDO;

/**
 * Tests related to xPDOZip methods
 *
 * @package xPDO\Test\Compression
 */
class xPDOZipTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $xpdo = self::getInstance();

        $zipPath = self::$properties['xpdo_test_path'] . "fs/zip/";
        $xpdo->getCacheManager()->writeTree($zipPath);
        $xpdo->getCacheManager()->writeTree("{$zipPath}1/");
        $xpdo->getCacheManager()->writeTree("{$zipPath}1/a/");
        $xpdo->getCacheManager()->writeTree("{$zipPath}1/b/");
        $xpdo->getCacheManager()->writeTree("{$zipPath}1/c/");
        $xpdo->getCacheManager()->writeTree("{$zipPath}2/");
        $xpdo->getCacheManager()->writeFile("{$zipPath}2/a", "### placeholder file ###");
        $xpdo->getCacheManager()->writeFile("{$zipPath}2/b", "### placeholder file ###");
        $xpdo->getCacheManager()->writeFile("{$zipPath}2/c", "### placeholder file ###");
        $xpdo->getCacheManager()->writeTree("{$zipPath}3/");

        $unzipPath = self::$properties['xpdo_test_path'] . "fs/unzip";
        $xpdo->getCacheManager()->writeTree($unzipPath);
    }

    public static function tearDownAfterClass()
    {
        $xpdo = self::getInstance();
        $paths = array(
            self::$properties['xpdo_test_path'] . "fs/zip/",
            self::$properties['xpdo_test_path'] . "fs/unzip/"
        );
        foreach ($paths as $path) {
            $xpdo->getCacheManager()->deleteTree($path, array(
                'deleteTop' => true,
                'skipDirs' => false,
                'extensions' => array()
            ));
        }
        $files = array(
            self::$properties['xpdo_test_path'] . "fs/test-1.zip",
            self::$properties['xpdo_test_path'] . "fs/test-2.zip",
            self::$properties['xpdo_test_path'] . "fs/test-3.zip"
        );
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }

    /**
     * Test creating and packing files/dirs into a ZipArchive
     *
     * @dataProvider providerPackArchive
     *
     * @param $source
     * @param $archive
     * @param $options
     * @param $packOptions
     * @param $expected
     */
    public function testPackArchive($source, $archive, $options, $packOptions, $expected)
    {
        $result = false;
        $sourcePath = self::$properties['xpdo_test_path'] . "fs/zip/{$source}";
        $archivePath = self::$properties['xpdo_test_path'] . "fs/{$archive}";
        try {
            $zip = new xPDOZip($this->xpdo, $archivePath, $options);
            $result = $zip->pack($sourcePath, $packOptions);
            foreach ($result as $idx => $entry) $result[$idx] = str_replace($sourcePath, $source, $entry);
            $this->xpdo->log(xPDO::LOG_LEVEL_INFO, "Pack results for {$archive}: " . print_r($result, true));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertEquals($expected, $result, "Error packing xPDOZip archive {$archive} from {$source}");
        //        $this->assertTrue(file_exists($archivePath), "Error creating xPDOZip archive at {$archive}");
    }

    public function providerPackArchive()
    {
        return array(
            array(
                '1/',
                'test-1.zip',
                array('create' => true, 'overwrite' => true),
                array('zip_target' => '1/'),
                array(
                    '1/' => 'Successfully added directory 1/ from 1/',
                    '1/a/' => 'Successfully added directory 1/a/ from 1/a/',
                    '1/b/' => 'Successfully added directory 1/b/ from 1/b/',
                    '1/c/' => 'Successfully added directory 1/c/ from 1/c/'
                )
            ),
            array(
                '2/',
                'test-2.zip',
                array('create' => true, 'overwrite' => true),
                array('zip_target' => '2/'),
                array(
                    '2/' => 'Successfully added directory 2/ from 2/',
                    '2/a' => 'Successfully packed 2/a from 2/a',
                    '2/b' => 'Successfully packed 2/b from 2/b',
                    '2/c' => 'Successfully packed 2/c from 2/c'
                )
            ),
        );
    }

    /**
     * Test unpacking files/dirs from a ZipArchive
     *
     * @dataProvider providerUnpackArchive
     * @depends      testPackArchive
     *
     * @param $target
     * @param $archive
     * @param $options
     * @param $unpackOptions
     * @param $expected
     */
    public function testUnpackArchive($target, $archive, $options, $unpackOptions, $expected)
    {
        $result = false;
        $targetPath = self::$properties['xpdo_test_path'] . "fs/unzip/{$target}";
        $archivePath = self::$properties['xpdo_test_path'] . "fs/{$archive}";
        try {
            $archive = new xPDOZip($this->xpdo, $archivePath, $options);
            $result = $archive->unpack($targetPath, $unpackOptions);
            $this->xpdo->log(xPDO::LOG_LEVEL_INFO, "Unpack results for {$archivePath}: " . print_r($result, true));
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertEquals($expected, $result, "Error unpacking xPDOZip archive {$archivePath} to target {$targetPath}");
    }

    public function providerUnpackArchive()
    {
        return array(
            array('', 'test-1.zip', array(), array(), true),
            array('', 'test-2.zip', array(), array(), true),
        );
    }
}
