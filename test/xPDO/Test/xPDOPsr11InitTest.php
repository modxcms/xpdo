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

use PHPUnit\Framework\TestCase;
use xPDO\xPDO;
use xPDO\xPDOContainer;
use xPDO\xPDOException;

/**
 * Tests for PSR-11 container initialization contract (issue #269).
 *
 * These tests verify that passing a ContainerInterface to xPDO makes the
 * required 'config' entry contract explicit rather than silently falling back
 * to default values when it is missing.
 *
 * @package xPDO\Test
 */
class xPDOPsr11InitTest extends TestCase
{
    /**
     * Passing a container without a 'config' entry must throw xPDOException.
     *
     * Before the fix, xPDO would silently fall back to an empty config array
     * when the container did not provide a 'config' entry, hiding the broken
     * API contract from the caller.
     */
    public function testContainerWithoutConfigEntryThrowsException(): void
    {
        $container = new xPDOContainer();
        // Deliberately do NOT add a 'config' entry

        $this->expectException(xPDOException::class);
        $this->expectExceptionMessage('config');

        new xPDO(null, '', '', $container);
    }

    /**
     * Passing a container WITH a valid 'config' entry must initialize normally.
     *
     * This is the positive-path contract test: a container that provides the
     * required 'config' entry must result in a fully initialised xPDO instance
     * without throwing.
     */
    public function testContainerWithConfigEntryInitializesSuccessfully(): void
    {
        $properties = include __DIR__ . '/../../properties.inc.php';
        $driver = getenv('TEST_DRIVER') ?: 'sqlite';
        $config = $properties["{$driver}_array_options"];

        $container = new xPDOContainer();
        $container->add('config', $config);

        $xpdo = new xPDO(null, '', '', $container);

        $this->assertInstanceOf(xPDO::class, $xpdo);
        $this->assertSame($container, $xpdo->services);
    }
}
