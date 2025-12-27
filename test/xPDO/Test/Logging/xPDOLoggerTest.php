<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use xPDO\TestCase;
use xPDO\xPDO;

class xPDOLoggerTest extends TestCase
{
    public function testLegacyArrayTargetFormat()
    {
        $this->xpdo->logger = null;
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $output = array();
        $target = array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => &$output,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Hello', $target, 'UnitTest', __FILE__, 123);

        $this->assertCount(1, $output);
        $pattern = '/^\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\] \\(INFO in UnitTest @ '
            . preg_quote(__FILE__, '/')
            . ' : 123\\) Hello\\n$/';
        $this->assertMatchesRegularExpression($pattern, $output[0]);
    }

    public function testLegacyFileTargetWritesToCache()
    {
        $this->xpdo->logger = null;
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $cachePath = $this->xpdo->getCachePath();
        $filepath = $cachePath . 'logs/';
        $filename = 'xpdo_logger_test.log';
        $fullpath = $filepath . $filename;
        if (file_exists($fullpath)) {
            unlink($fullpath);
        }

        $target = array(
            'target' => 'FILE',
            'options' => array(
                'filename' => $filename,
                'filepath' => $filepath,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'File write', $target, 'UnitTest', __FILE__, 234);

        $this->assertFileExists($fullpath);
        $contents = file_get_contents($fullpath);
        $pattern = '/^\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\] \\(ERROR in UnitTest @ '
            . preg_quote(__FILE__, '/')
            . ' : 234\\) File write\\n$/';
        $this->assertMatchesRegularExpression($pattern, $contents);
        unlink($fullpath);
    }

    public function testInjectedLoggerIgnoresTargetAndMapsLevel()
    {
        $logger = new SpyLogger();
        $this->xpdo->logger = $logger;
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $output = array();
        $target = array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => &$output,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_WARN, 'Injected', $target, 'UnitTest', __FILE__, 345);

        $this->assertCount(0, $output);
        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::WARNING, $logger->records[0]['level']);
        $this->assertSame('Injected', $logger->records[0]['message']);
    }

    public function testInjectedLoggerMapsUnknownLevelToNotice()
    {
        $logger = new SpyLogger();
        $this->xpdo->logger = $logger;
        $this->xpdo->setDebug(true);

        $this->xpdo->log(999, 'Unknown');

        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::NOTICE, $logger->records[0]['level']);
    }

    public function testConstructorInjectionSetsLogger()
    {
        $logger = new SpyLogger();
        $driver = self::$properties['xpdo_driver'];
        $config = self::$properties["{$driver}_array_options"];
        $config['logger'] = $logger;

        $xpdo = xPDO::getInstance(uniqid('logger', true), $config, true);
        $xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $this->assertSame($logger, $xpdo->logger);
        $xpdo->log(xPDO::LOG_LEVEL_INFO, 'Injected instance');

        $this->assertCount(1, $logger->records);
        $this->assertSame('Injected instance', $logger->records[0]['message']);
    }
}

class SpyLogger extends AbstractLogger
{
    public $records = array();

    public function log($level, $message, array $context = array()): void
    {
        $this->records[] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
    }
}
