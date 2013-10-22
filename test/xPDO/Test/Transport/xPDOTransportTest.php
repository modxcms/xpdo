<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Transport;

use xPDO\TestCase;
use xPDO\Transport\xPDOTransport;

/**
 * Tests related to xPDOTransport
 *
 * @package xPDO\Test\Transport
 */
class xPDOTransportTest extends TestCase {
    /**
     * Test xPDOTransport::satisfies()
     *
     * @param string $version
     * @param string $constraint
     * @param bool $expected
     * @dataProvider providerSatisfies
     */
    public function testSatisfies($version, $constraint, $expected) {
        $this->assertEquals($expected, xPDOTransport::satisfies($version, $constraint));
    }
    public function providerSatisfies() {
        return array(
            array('1.0.0', '~1.0', true),
            array('1.0.0', '~1.1', false),
            array('1.0.0', '>=0.9,<2.0', true),
            array('3.2.1', '3.*', true),
            array('3.2.1', '3.1.*', false),
            array('3.2.1', '3.2.*', true),
            array('3.2.1', '3.2.1', true),
            array('3.2.1', '3.2.2', false),
        );
    }

    /**
     * Test xPDOTransport::nextSignificantRelease()
     *
     * @param string $version
     * @param string $expected
     * @dataProvider providerNextSignificantRelease
     */
    public function testNextSignificantRelease($version, $expected) {
        $this->assertEquals($expected, xPDOTransport::nextSignificantRelease($version));
    }
    public function providerNextSignificantRelease() {
        return array(
            array('1.2.3', '1.3'),
            array('1.0', '2.0'),
            array('2.10.11-rc-12', '2.11'),
            array('0.10-rc1', '1.0'),
            array('0.10.1-pl', '0.11'),
        );
    }
}
