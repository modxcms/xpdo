<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Legacy;

use xPDO\xPDO;

/**
 * Tests related to cleaning up the test environment
 *
 * @package xPDO\Test
 */
class TearDownTest extends TestCase {
    /**
     * Ensure source container is not overwritten
     * By default, if the connection fails, it should just error out.
     * Should be an error set since we gave bogus info.
     */
    public function testDoNotOverwriteSourceContainer() {
        $xpdo = self::getInstance(true);
        $result = false;
        try {
            $xpdo->getManager();
            $driver = self::$properties['xpdo_driver'];
            $result = $xpdo->manager->createSourceContainer(
                self::$properties[$driver . '_string_dsn_test'],
                self::$properties[$driver . '_string_username'],
                self::$properties[$driver . '_string_password']
            );
        } catch (\Exception $e) {
            $xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $this->assertTrue($result == false, "Error testing overwriting source container with createSourceContainer() method");
    }

    /**
     * Verify drop database works.
     */
    public function testRemoveSourceContainer() {
        $xpdo = self::getInstance(true);
        $success = false;
        if ($xpdo) {
            $driver = self::$properties['xpdo_driver'];
            $dsn = self::$properties[$driver . '_string_dsn_test'];
            $dsn = xPDO::parseDSN($dsn);
            $success = $xpdo->getManager()->removeSourceContainer($dsn);
        }
        $this->assertTrue($success, "Test container exists and could not be removed for initialization via xPDOManager->removeSourceContainer()");
    }
}
