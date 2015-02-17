<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../src/bootstrap.php';

use xPDO\xPDO;

$properties = array();
require __DIR__ . '/../test/properties.inc.php';

array_shift($argv);
$command = array_shift($argv);

$arg = function($idx = 1) use ($argv) {
    $current = 1;
    foreach ($argv as $arg) {
        if (preg_match('{^-}', $arg)) continue;
        if ($current === $idx) return $arg;
        $current++;
    }
    return null;
};

$opt = function($find) use ($argv) {
    $value = false;
    $findPrefix = strlen($find) === 1 ? '-' : '--';
    $re = '{^' . $findPrefix . '(' . $find . ')=?(.*)?}';
    $matches = array();
    foreach ($argv as $opt) {
        if (preg_match($re, $opt, $matches)) {
            $value = true;
            if ($matches[2] !== '') {
                $value = $matches[2];
            }
            break;
        }
    }
    return $value;
};

$platforms = array('mysql', 'sqlite', 'sqlsrv');

switch ($command) {
    case 'parse-schema':
        $platform = $arg(1);
        if ($platform === null || !in_array(strtolower($platform), $platforms)) {
            echo "fatal: no valid platform specified" . PHP_EOL;
            exit(128);
        }
        $platform = strtolower($platform);
        $schema = $arg(2);
        if ($schema === null || !is_readable($schema)) {
            echo "fatal: no valid schema provided" . PHP_EOL;
            exit(128);
        }
        $path = $arg(3);

        $compile = $opt('compile') || $opt('c');
        $update = $opt('update');
        $regen = $opt('regen');

        $update = $update === false ? 0 : (int)$update;
        $regen = $regen === false ? 0 : (int)$regen;

        $xpdo = xPDO::getInstance('generator', $properties["{$platform}_array_options"]);
        $xpdo->setLogLevel(xPDO::LOG_LEVEL_INFO);
        $xpdo->setLogTarget(PHP_SAPI === 'cli' ? 'ECHO' : 'HTML');

        $generator = $xpdo->getManager()->getGenerator();
        $generator->parseSchema(
            $schema,
            $path,
            array(
                'compile' => $compile,
                'update' => $update,
                'regenerate' => $regen,
            )
        );
        exit(0);
        break;
    case 'write-schema':
        echo "write-schema command not yet implemented" . PHP_EOL;
        break;
    default:
        echo "unknown command {$command}" . PHP_EOL;
        break;
}

echo <<<'EOF'
Example usage:
  xpdo parse-schema [[--compile|-c]|--update=[0-2]|--regen=[0-2]] PLATFORM SCHEMA_FILE PATH
  xpdo write-schema [?] PLATFORM SCHEMA_FILE PATH

EOF;
exit(0);
