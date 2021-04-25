<?php
/*
 * The file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Transport;


use xPDO\Test\Sample\xPDOSample;
use xPDO\TestCase;
use xPDO\Transport\xPDOObjectVehicle;
use xPDO\Transport\xPDOTransport;

class xPDOVehicleTest extends TestCase
{
    public function testPutDoesNotAddPackageForNamespacedClass()
    {
        $transport = new xPDOTransport($this->xpdo, uniqid('transport-'), self::$properties['xpdo_test_path'] . 'fs/transport/');

        $object = $this->xpdo->newObject(xPDOSample::class);

        $vehicle = new xPDOObjectVehicle();
        $vehicle->put($transport, $object);

        $this->assertEmpty($vehicle->payload['package']);
    }
}