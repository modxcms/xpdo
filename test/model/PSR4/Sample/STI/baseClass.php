<?php
namespace xPDO\Test\Sample\STI;

use xPDO\xPDO;

/**
 * Class baseClass
 *
 * @property integer $field1
 * @property string $field2
 * @property string $date_modified
 * @property integer $fkey
 * @property string $class_key
 *
 * @property \xPDO\Test\Sample\STI\relClassMany[] $relMany
 *
 * @package xPDO\Test\Sample\STI
 */
class baseClass extends \xPDO\Om\xPDOSimpleObject
{
}
