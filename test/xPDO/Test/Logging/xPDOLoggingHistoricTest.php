<?php
declare(strict_types=1);

namespace xPDO\Test\Logging;

use xPDO\Cache\xPDOCacheManager;
use xPDO\TestCase;
use xPDO\xPDO;

/**
 * These tests confirm that when we switch to a psr/log logger, the xPDO logging behavior is unchanged.
 * The tests were developed against the 3.x branch prior to any modifications being made to logging.
 */
class xPDOLoggingHistoricTest extends TestCase
{
    /** @var xPDOLoggingHistoricTestCacheManager */
    private $cacheManagerSpy;

    /**
     * Configure a cacheManager spy so tests can assert on FILE target behavior.
     *
     * @before
     */
    public function setUpCacheManagerSpy()
    {
        $this->cacheManagerSpy = new xPDOLoggingHistoricTestCacheManager($this->xpdo);
        $this->xpdo->cacheManager = $this->cacheManagerSpy;
    }

    /**
     * @after
     */
    public function tearDownCacheManagerSpy()
    {
        $this->cacheManagerSpy = null;
    }

    private function assertPlainLogLineMatches($output, $levelText, $msg, $def = '', $file = '', $line = '')
    {
        $defPart = ($def !== '') ? " in {$def}" : '';
        $filePart = ($file !== '') ? " @ {$file}" : '';
        $linePart = ($line !== '') ? " : {$line}" : '';

        $pattern = '/\\A\\['
            . '\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}'
            . '\\] \\('
            . preg_quote($levelText . $defPart . $filePart . $linePart, '/')
            . '\\) '
            . preg_quote($msg, '/')
            . "\\n\\z/";

        $this->assertMatchesRegularExpression($pattern, $output);
    }

    private function assertHtmlLogLineMatches($output, $levelText, $msg, $def = '', $file = '', $line = '')
    {
        $defPart = ($def !== '') ? " in {$def}" : '';
        $filePart = ($file !== '') ? " @ {$file}" : '';
        $linePart = ($line !== '') ? " : {$line}" : '';

        $pattern = '/\\A<h5>\\['
            . '\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}'
            . '\\] \\('
            . preg_quote($levelText . $defPart . $filePart . $linePart, '/')
            . '\\)<\\/h5><pre>'
            . preg_quote($msg, '/')
            . '<\\/pre>'
            . "\\n\\z/";

        $this->assertMatchesRegularExpression($pattern, $output);
    }

    private function captureLogOutput($level, $msg, $target, $def, $file, $line)
    {
        ob_start();
        $this->xpdo->log($level, $msg, $target, $def, $file, $line);
        return ob_get_clean();
    }

    public function testLogEchoWritesOnlyStdout()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'ECHO',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'echo-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, 'INFO', $msg, $def, $file, $line);
        $this->assertSame([], $sink, 'ECHO target must not append to ARRAY/ARRAY_EXTENDED sinks.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ECHO target must not write to FILE.');
    }

    public function testLogHtmlWritesOnlyStdout()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'HTML',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'html-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertHtmlLogLineMatches($output, 'INFO', $msg, $def, $file, $line);
        $this->assertSame([], $sink, 'HTML target must not append to ARRAY/ARRAY_EXTENDED sinks.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'HTML target must not write to FILE.');
    }

    public function testLogUnknownTargetDefaultsToPlainStdout()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'UNKNOWN',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'unknown-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, 'INFO', $msg, $def, $file, $line);
        $this->assertSame([], $sink, 'Unknown target must not append to ARRAY/ARRAY_EXTENDED sinks.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'Unknown target must not write to FILE.');
    }

    public function testLogFileWritesOnlyFileWithDefaults()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'FILE',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'file-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertSame('', $output, 'FILE target must not write to STDOUT.');
        $this->assertSame([], $sink, 'FILE target must not append to ARRAY/ARRAY_EXTENDED sinks.');

        $this->assertCount(1, $this->cacheManagerSpy->writeCalls);
        $call = $this->cacheManagerSpy->writeCalls[0];

        $expectedFilename = $this->xpdo->getCachePath() . xPDOCacheManager::LOG_DIR . 'error.log';
        $this->assertSame($expectedFilename, $call['filename']);
        $this->assertSame('a', $call['mode']);
        $this->assertSame([], $call['options']);
        $this->assertPlainLogLineMatches($call['content'], 'INFO', $msg, $def, $file, $line);
    }

    public function testLogFileWritesOnlyFileWithCustomOptions()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'FILE',
            'options' => array(
                'filename' => 'custom.log',
                'filepath' => '/custom/path/',
                'var' => &$sink,
            ),
        );

        $msg = 'file-target custom options message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertSame('', $output, 'FILE target must not write to STDOUT.');
        $this->assertSame([], $sink, 'FILE target must not append to ARRAY/ARRAY_EXTENDED sinks.');

        $this->assertCount(1, $this->cacheManagerSpy->writeCalls);
        $call = $this->cacheManagerSpy->writeCalls[0];

        $this->assertSame('/custom/path/custom.log', $call['filename']);
        $this->assertSame('a', $call['mode']);
        $this->assertSame([], $call['options']);
        $this->assertPlainLogLineMatches($call['content'], 'INFO', $msg, $def, $file, $line);
    }

    public function testLogArrayAppendsOnlyToArraySink()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'array-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_WARN, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertSame('', $output, 'ARRAY target must not write to STDOUT.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ARRAY target must not write to FILE.');

        $this->assertCount(1, $sink);
        $this->assertIsString($sink[0]);
        $this->assertPlainLogLineMatches($sink[0], 'WARN', $msg, $def, $file, $line);
    }

    public function testLogArrayWithoutVarFallsBackToStdout()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $target = array(
            'target' => 'ARRAY',
            'options' => array(),
        );

        $msg = 'array-target no var message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, 'INFO', $msg, $def, $file, $line);
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ARRAY target with invalid options must not write to FILE.');
    }

    public function testLogArrayExtendedAppendsOnlyToArraySinkWithStructuredData()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $target = array(
            'target' => 'ARRAY_EXTENDED',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'array-extended-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertSame('', $output, 'ARRAY_EXTENDED target must not write to STDOUT.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ARRAY_EXTENDED target must not write to FILE.');

        $this->assertCount(1, $sink);
        $this->assertIsArray($sink[0]);
        $this->assertSame(
            array('content', 'level', 'msg', 'def', 'file', 'line'),
            array_keys($sink[0])
        );

        $this->assertPlainLogLineMatches($sink[0]['content'], 'ERROR', $msg, $def, $file, $line);
        $this->assertSame('ERROR', $sink[0]['level']);
        $this->assertSame($msg, $sink[0]['msg']);
        $this->assertSame(" in {$def}", $sink[0]['def']);
        $this->assertSame(" @ {$file}", $sink[0]['file']);
        $this->assertSame(" : {$line}", $sink[0]['line']);
    }

    public function testLogArrayExtendedWithoutVarFallsBackToStdout()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $target = array(
            'target' => 'ARRAY_EXTENDED',
            'options' => array(),
        );

        $msg = 'array-extended-target no var message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, $target, $def, $file, $line);
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, 'INFO', $msg, $def, $file, $line);
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ARRAY_EXTENDED target with invalid options must not write to FILE.');
    }

    public function testLogUsesInstanceLogTargetWhenTargetParamEmpty()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $sink = [];
        $this->xpdo->setLogTarget(array(
            'target' => 'ARRAY',
            'options' => array(
                'var' => &$sink,
            ),
        ));

        $msg = 'instance-target message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        ob_start();
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, '', $def, $file, $line);
        $output = ob_get_clean();

        $this->assertSame('', $output, 'Instance ARRAY target must not write to STDOUT.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'Instance ARRAY target must not write to FILE.');
        $this->assertCount(1, $sink);
        $this->assertPlainLogLineMatches($sink[0], 'INFO', $msg, $def, $file, $line);
    }

    /**
     * @dataProvider providerDebugAndLogLevelFiltering
     */
    public function testLogRespectsDebugAndLogLevel($debug, $logLevel, $level, $shouldLog, $expectedLevelText)
    {
        $this->xpdo->setDebug($debug);
        $this->xpdo->setLogLevel($logLevel);

        $sink = [];
        $target = array(
            'target' => 'ECHO',
            'options' => array(
                'var' => &$sink,
            ),
        );

        $msg = 'log filtering message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        $output = $this->captureLogOutput($level, $msg, $target, $def, $file, $line);

        if ($shouldLog) {
            $this->assertPlainLogLineMatches($output, $expectedLevelText, $msg, $def, $file, $line);
        } else {
            $this->assertSame('', $output, 'Filtered message must not write to STDOUT.');
        }

        $this->assertSame([], $sink, 'ECHO target must not append to ARRAY/ARRAY_EXTENDED sinks.');
        $this->assertSame([], $this->cacheManagerSpy->writeCalls, 'ECHO target must not write to FILE.');
    }

    public function providerDebugAndLogLevelFiltering()
    {
        return array(
            'debug overrides log level' => array(true, xPDO::LOG_LEVEL_WARN, xPDO::LOG_LEVEL_DEBUG, true, 'DEBUG'),
            'level at log level' => array(false, xPDO::LOG_LEVEL_WARN, xPDO::LOG_LEVEL_WARN, true, 'WARN'),
            'level above log level' => array(false, xPDO::LOG_LEVEL_WARN, xPDO::LOG_LEVEL_DEBUG, false, 'DEBUG'),
        );
    }

    /**
     * Verify backtrace file/line resolution when file/line params are omitted.
     */
    public function testLogResolvesFileAndLineFromBacktraceWhenNotProvided()
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $msg = 'backtrace resolution message';
        $def = 'DefiningStruct';

        $line = __LINE__ + 2;
        ob_start();
        $this->getLog($msg, $def);
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, 'INFO', $msg, $def, __FILE__, (string)$line);
    }

    /**
     * Verify _getLogLevel mapping via _log output.
     *
     * @dataProvider providerLogLevels
     */
    public function testLogLevelTextMapping($level, $expectedText)
    {
        $this->xpdo->setDebug(false);
        $this->xpdo->setLogLevel(xPDO::LOG_LEVEL_DEBUG);

        $msg = 'level-mapping message';

        ob_start();
        $this->xpdo->log($level, $msg, 'ECHO', 'DefiningStruct', 'example.php', '123');
        $output = ob_get_clean();

        $this->assertPlainLogLineMatches($output, $expectedText, $msg, 'DefiningStruct', 'example.php', '123');
    }

    public function providerLogLevels()
    {
        return array(
            array(xPDO::LOG_LEVEL_DEBUG, 'DEBUG'),
            array(xPDO::LOG_LEVEL_INFO, 'INFO'),
            array(xPDO::LOG_LEVEL_WARN, 'WARN'),
            array(xPDO::LOG_LEVEL_ERROR, 'ERROR'),
        );
    }

    /**
     * @param string $msg
     * @param string $def
     * @return void
     */
    private function getLog(string $msg, string $def): void
    {
        $this->xpdo->log(xPDO::LOG_LEVEL_INFO, $msg, 'ECHO', $def);
    }
}

class xPDOLoggingHistoricTestCacheManager extends xPDOCacheManager
{
    /** @var array<int, array{filename: string, content: string, mode: string, options: array}> */
    public $writeCalls = [];

    public function writeFile($filename, $content, $mode = 'wb', $options = array())
    {
        $this->writeCalls[] = array(
            'filename' => $filename,
            'content' => $content,
            'mode' => $mode,
            'options' => $options,
        );

        return true;
    }
}
