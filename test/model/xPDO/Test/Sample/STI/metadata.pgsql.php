<?php
$xpdo_meta_map = array (
    'version' => '3.0',
    'namespace' => 'xPDO\\Test\\Sample\\STI',
    'namespacePrefix' => '',
    'class_map' => 
    array (
        'xPDO\\Om\\xPDOSimpleObject' => 
        array (
            0 => 'xPDO\\Test\\Sample\\STI\\baseClass',
            1 => 'xPDO\\Test\\Sample\\STI\\relClassOne',
            2 => 'xPDO\\Test\\Sample\\STI\\relClassMany',
        ),
        'xPDO\\Test\\Sample\\STI\\baseClass' => 
        array (
            0 => 'xPDO\\Test\\Sample\\STI\\derivedClass',
        ),
        'xPDO\\Test\\Sample\\STI\\derivedClass' => 
        array (
            0 => 'xPDO\\Test\\Sample\\STI\\derivedClass2',
        ),
    ),
);