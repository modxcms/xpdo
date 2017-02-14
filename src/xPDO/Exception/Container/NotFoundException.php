<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Exception\Container;


use xPDO\xPDOException;

class NotFoundException extends xPDOException implements \Interop\Container\Exception\NotFoundException {}
