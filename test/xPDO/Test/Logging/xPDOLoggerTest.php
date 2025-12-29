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

use ArrayObject;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use xPDO\Logging\xPDOLogger;
use xPDO\TestCase;
use xPDO\xPDO;

class xPDOLoggerTest extends TestCase
{
    public function testLegacyArrayTargetFormat()
    {
        $this->xpdo->logger = new xPDOLogger($this->xpdo);
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

    public function testLegacyArrayAccessTargetsCaptureLogs()
    {
        $this->xpdo->logger = new xPDOLogger($this->xpdo);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $output = new ArrayObject();
        $target = array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => $output,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'ArrayAccess', $target, 'UnitTest', 'array-access.php', 111);

        $this->assertCount(1, $output);
        $pattern = '/^\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\] \\(INFO in UnitTest @ array-access\\.php : 111\\) ArrayAccess\\n$/';
        $this->assertMatchesRegularExpression($pattern, $output[0]);

        $extendedOutput = new ArrayObject();
        $extendedTarget = array(
            'target' => 'ARRAY_EXTENDED',
            'options' => array(
                'var' => $extendedOutput,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Extended', $extendedTarget, 'UnitTest', 'array-access.php', 222);

        $this->assertCount(1, $extendedOutput);
        $entry = $extendedOutput[0];
        $this->assertSame('ERROR', $entry['level']);
        $this->assertSame('Extended', $entry['msg']);
        $this->assertSame(' in UnitTest', $entry['def']);
        $this->assertSame(' @ array-access.php', $entry['file']);
        $this->assertSame(' : 222', $entry['line']);
        $this->assertArrayHasKey('content', $entry);
    }

    public function testLegacyFileTargetWritesToCache()
    {
        $this->xpdo->logger = new xPDOLogger($this->xpdo);
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

    public function testContainerInjectionSetsLoggerByLoggerInterfaceId()
    {
        $logger = new SpyLogger();
        $driver = self::$properties['xpdo_driver'];
        $config = self::$properties["{$driver}_array_options"];

        $container = new \xPDO\xPDOContainer();
        $container->add('config', $config);
        $container->add(LoggerInterface::class, $logger);

        $xpdo = xPDO::getInstance(uniqid('logger-container', true), $container, true);
        $xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $this->assertSame($logger, $xpdo->getLogger());
        $this->assertSame($logger, $xpdo->logger);
        $this->assertTrue($xpdo->services->has(LoggerInterface::class));
        $this->assertSame($logger, $xpdo->services->get(LoggerInterface::class));
    }

    public function testGetLoggerAndSetLoggerKeepServicesInSync()
    {
        $logger = new SpyLogger();
        $this->xpdo->setLogger($logger);

        $this->assertSame($logger, $this->xpdo->getLogger());
        $this->assertTrue($this->xpdo->services->has(LoggerInterface::class));
        $this->assertSame($logger, $this->xpdo->services->get(LoggerInterface::class));
    }

    public function testLegacyEchoAndHtmlTargetsWithXpdoLogger()
    {
        $this->xpdo->logger = new xPDOLogger($this->xpdo);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $this->xpdo->setLogTarget('ECHO');
        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Echo message', '', 'UnitTest', __FILE__, 456);
        $echoOutput = ob_get_clean();

        $echoPattern = '/^\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\] \\(INFO in UnitTest @ '
            . preg_quote(__FILE__, '/')
            . ' : 456\\) Echo message\\n$/';
        $this->assertMatchesRegularExpression($echoPattern, $echoOutput);

        $this->xpdo->setLogTarget('HTML');
        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Html message', '', 'UnitTest', __FILE__, 567);
        $htmlOutput = ob_get_clean();

        $htmlPattern = '/^<h5>\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\] \\(INFO in UnitTest @ '
            . preg_quote(__FILE__, '/')
            . ' : 567\\)<\\/h5><pre>Html message<\\/pre>\\n$/';
        $this->assertMatchesRegularExpression($htmlPattern, $htmlOutput);
    }

    public function testMonologLoggerReceivesMessages()
    {
        if (!class_exists(Logger::class) || !class_exists(TestHandler::class)) {
            $this->markTestSkipped('Monolog is not installed.');
        }

        $handler = new TestHandler();
        $logger = new Logger('xpdo');
        $logger->pushHandler($handler);

        $this->xpdo->logger = $logger;
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $output = array();
        $target = array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => &$output,
            ),
        );

        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Monolog works', $target, 'UnitTest', __FILE__, 678);

        $this->assertCount(0, $output);
        if (method_exists($handler, 'getRecords')) {
            $records = $handler->getRecords();
        } else {
            $ref = new \ReflectionProperty($handler, 'records');
            $ref->setAccessible(true);
            $records = $ref->getValue($handler);
        }
        $this->assertNotEmpty($records);
        $record = $records[0];
        $message = is_array($record) ? $record['message'] : $record->message;
        $this->assertSame('Monolog works', $message);
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
