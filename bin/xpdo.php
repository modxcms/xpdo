<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

$application = new \xPDO\Console\Application();
$application->loadCommands();
$application->run();