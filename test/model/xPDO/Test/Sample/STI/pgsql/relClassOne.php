<?php

namespace xPDO\Test\Sample\STI\pgsql;

class relClassOne extends \xPDO\Test\Sample\STI\relClassOne
{
    public static $metaMap = array(
        'package' => 'xPDO\\Test\\Sample\\STI',
        'version' => '3.0',
        'table' => 'sti_related_one',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => array(
            'field1' => null,
            'field2' => null,
        ),
        'fieldMeta' => array(
            'field1' => array(
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => false,
            ),
            'field2' => array(
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
            ),
        ),
        'aggregates' => array(
            'relParent' => array(
                'class' => 'xPDO\\Test\\Sample\\STI\\baseClass',
                'local' => 'id',
                'foreign' => 'fkey',
                'cardinality' => 'one',
                'owner' => 'local',
            ),
        ),
    );
}
