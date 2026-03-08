<?php
declare(strict_types=1);

namespace xPDO\Test\Logging;

use xPDO\TestCase;

/**
 * This is required because xPDO::_log() has a code path that calls exit() when fatal logging is enabled.
 * To test this, we spawn a subprocess that calls xPDO::_log() and verify that it exits with status 0.
 */
class xPDOLoggingFatalTest extends TestCase
{
    /**
     * @dataProvider providerFatalLogOutputs
     */
    public function testLogFatalExitsWithOutput($debug, $expectBacktrace)
    {
        if (!function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is required to run fatal log subprocess.');
        }

        $msg = 'fatal log message';
        $def = 'DefiningStruct';
        $file = 'example.php';
        $line = '123';

        $result = $this->runFatalLogSubprocess((bool)$debug, $msg, $def, $file, $line);
        $stdout = $result['stdout'];

        $this->assertSame(0, $result['exitCode'], 'Fatal log subprocess should exit with status 0.');
        $this->assertSame('', $result['stderr'], 'Fatal log subprocess should not write to STDERR.');

        if ($expectBacktrace) {
            $pattern = '/\\A\\['
                . '\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}'
                . '\\] \\('
                . preg_quote('FATAL' . $def . $file . $line, '/')
                . '\\) '
                . preg_quote($msg, '/')
                . "\\r?\\n<pre>\\r?\\n/s";

            $this->assertMatchesRegularExpression($pattern, $stdout);
            $this->assertMatchesRegularExpression('/<pre>\\r?\\nArray\\r?\\n\\(/', $stdout);
            $this->assertStringContainsString('</pre>', $stdout);
        } else {
            $pattern = '/\\A\\['
                . '\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}'
                . '\\] \\('
                . preg_quote('FATAL' . $def . $file . $line, '/')
                . '\\) '
                . preg_quote($msg, '/')
                . "\\r?\\n\\z/";

            $this->assertMatchesRegularExpression($pattern, $stdout);
        }
    }

    public function providerFatalLogOutputs()
    {
        return array(
            'debug off' => array(false, false),
            'debug on' => array(true, true),
        );
    }

    private function runFatalLogSubprocess($debug, $msg, $def, $file, $line)
    {
        $projectRoot = dirname(__DIR__, 4);
        $bootstrapPath = $projectRoot . '/src/bootstrap.php';
        $propertiesPath = $projectRoot . '/test/properties.inc.php';
        $scriptPath = tempnam(sys_get_temp_dir(), 'xpdo-fatal-');

        if ($scriptPath === false) {
            $this->fail('Unable to create temporary script for fatal log test.');
        }

        $script = sprintf(
            "<?php\n" .
            "declare(strict_types=1);\n\n" .
            "require %s;\n\n" .
            "\$properties = include %s;\n" .
            "\$driver = getenv('TEST_DRIVER') ?: 'sqlite';\n" .
            "\$optionsKey = \$driver . '_array_options';\n" .
            "\$options = isset(\$properties[\$optionsKey]) ? \$properties[\$optionsKey] : array();\n\n" .
            "\$xpdo = \\xPDO\\xPDO::getInstance(null, \$options, true);\n" .
            "\$xpdo->setDebug(%s);\n" .
            "\$xpdo->log(\\xPDO\\xPDO::LOG_LEVEL_FATAL, %s, 'ECHO', %s, %s, %s);\n",
            var_export($bootstrapPath, true),
            var_export($propertiesPath, true),
            var_export((bool)$debug, true),
            var_export($msg, true),
            var_export($def, true),
            var_export($file, true),
            var_export($line, true)
        );

        try {
            if (file_put_contents($scriptPath, $script) === false) {
                $this->fail('Unable to write temporary script for fatal log test.');
            }

            $driver = getenv('TEST_DRIVER') ?: 'sqlite';
            putenv("TEST_DRIVER={$driver}");

            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath);
            $descriptorSpec = array(
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            );

            $process = proc_open($command, $descriptorSpec, $pipes, $projectRoot);
            if (!is_resource($process)) {
                $this->fail('Unable to start fatal log subprocess.');
            }

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_string($scriptPath) && file_exists($scriptPath)) {
                @unlink($scriptPath);
            }
        }

        return array(
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        );
    }
}
