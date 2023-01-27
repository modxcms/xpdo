<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Sample;


interface Secure
{
    /**
     * Indicates if the object is secure.
     *
     * @return bool
     */
    public function isSecure();
}
