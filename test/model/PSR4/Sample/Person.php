<?php
namespace xPDO\Test\Sample;

use xPDO\xPDO;

/**
 * Class Person
 *
 * @property string $first_name
 * @property string $last_name
 * @property string $middle_name
 * @property string $date_modified
 * @property string $dob
 * @property string $gender
 * @property string $blood_type
 * @property string $username
 * @property password $password
 * @property integer $security_level
 *
 * @property \xPDO\Test\Sample\PersonPhone[] $PersonPhone
 *
 * @package xPDO\Test\Sample
 */
class Person extends \xPDO\Om\xPDOSimpleObject
{
}
