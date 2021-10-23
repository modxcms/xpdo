<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Implements a minimal PSR-3 compatible logger if none are provided.
 *
 * @package xPDO
 */
class xPDOLogger implements LoggerInterface
{
    /**
     * @var \xPDO\Cache\xPDOCacheManager
     */
    protected $cacheManager;
    /**
     * @var array|string
     */
    protected $target;
    /**
     * @var int|string
     */
    protected $level;

    /**
     *
     * Valid target values include:
     * <ul>
     * <li>'ECHO': Returns output to the STDOUT / echo.</li>
     * <li>'HTML': Returns output to the STDOUT / echo with HTML formatting.</li>
     * <li>'FILE': Sends output to a log file.</li>
     * <li>array ["target" => "FILE", "options" => ["filename" => "error.log", "filepath" => "/path/to/dir"]]</li>
     * <li>array ["target" => "ARRAY", "options" => ["var" => &$arrayByRef]]</li>
     * <li>array ["target" => "ARRAY_EXTENDED", "options" => ["var" => &$arrayByRef]]</li>
     * </ul>
     *
     * @param \xPDOCacheManager $cacheManager
     * @param string|array $target String values:
     * @param int|string $level Either an xPDO::LOG_LEVEL_* constant or a LogLevel::* constant
     */
    public function __construct(\xPDO\Cache\xPDOCacheManager $cacheManager, $target = 'ECHO', $level = LogLevel::ERROR)
    {
        $this->cacheManager = $cacheManager;
        $this->target = $target;
        $this->level = is_int($level) ? $this->translateLevel($level) : $level;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = []): void
    {
        // Convert xPDO log constants (which resolves to integers 0-4 inclusive) to LogLevel constants
        if (is_int($level)) {
            $level = $this->translateLevel($level);
        }

        // Only process log levels when the provided severity exceeds the configured minimum
        if (!$this->isHigherThanMinimum($level)) {
            return;
        }

        // Handle target options for FILE and ARRAY/ARRAY_EXTENDED
        $targetOptions = array();
        $target = $this->target;
        if (is_array($target)) {
            if (isset($target['options'])) {
                $targetOptions =& $target['options'];
            }
            $target = isset($target['target']) ? $target['target'] : 'ECHO';
        }

        // Automatically identify the file and line if not set
        if (empty($context['file'])) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            if ($backtrace && isset($backtrace[1])) {
                $context['file'] = $backtrace[1]['file'];
                $context['line'] = $backtrace[1]['line'];
            }
        }

        if (empty($context['file']) && isset($_SERVER['SCRIPT_NAME'])) {
            $context['file'] = $_SERVER['SCRIPT_NAME'];
        }

        $def = strtoupper($level);

        if (!empty($context['def'])) {
            $def .= " in {$context['def']}";
            unset($context['def']);
        }
        if (!empty($context['file'])) {
            $def .= " @ {$context['file']}";
            unset($context['file']);
        }
        if (!empty($context['line'])) {
            $def .= " : {$context['line']}";
            unset($context['line']);
        }

        // If an emergency was triggered, end immediately.
        if ($level === LogLevel::EMERGENCY) {
            while (ob_get_level() && @ob_end_flush()) {}
            exit ('[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $def . ') ' . $message . "\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n" . ($this->getDebug() === true ? '<pre>' . "\n" . print_r(debug_backtrace(), true) . "\n" . '</pre>' : ''));
        }

        // Process into format: [timestamp] (SEVERITY) msg {context}
        $content = ($target === 'HTML')
            ? '<h5>[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $def . ')</h5><pre>' . $message . "\n" . json_encode($context, JSON_PRETTY_PRINT)  . '</pre>' . "\n"
            : '[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $def . ') ' . $message . ' ' . json_encode($context) . "\n";

        if ($target === 'FILE') {
            $filename = isset($targetOptions['filename']) ? $targetOptions['filename'] : 'error.log';
            $filepath = isset($targetOptions['filepath']) ? $targetOptions['filepath'] : $this->cacheManager->getCachePath() . Cache\xPDOCacheManager::LOG_DIR;
            $this->cacheManager->writeFile($filepath . $filename, $content, 'a');
        }
        elseif ($target === 'ARRAY' && isset($targetOptions['var']) && is_array($targetOptions['var'])) {
            $targetOptions['var'][] = $content;
        }
        elseif ($target === 'ARRAY_EXTENDED' && isset($targetOptions['var']) && is_array($targetOptions['var'])) {
            $targetOptions['var'][] = [
                'content' => $content,
                'level' => strtoupper($level),
                'msg' => $message,
                'def' => $def,
            ] + $context;
        }
        else {
            echo $content;
        }
    }

    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    private function translateLevel(int $level)
    {
        switch ($level) {
            case xPDO::LOG_LEVEL_FATAL:
                return LogLevel::EMERGENCY;
            case xPDO::LOG_LEVEL_ERROR:
                return LogLevel::ERROR;
            case xPDO::LOG_LEVEL_WARN:
                return LogLevel::WARNING;
            case xPDO::LOG_LEVEL_INFO:
                return LogLevel::INFO;
            case xPDO::LOG_LEVEL_DEBUG:
                return LogLevel::DEBUG;
        }

        return $level;
    }

    private function isHigherThanMinimum($level): bool
    {
        switch ($this->level) {
            case LogLevel::EMERGENCY:
                return $level === LogLevel::EMERGENCY;
            case LogLevel::ALERT:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT], true);
            case LogLevel::CRITICAL:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL], true);
            case LogLevel::ERROR:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR], true);
            case LogLevel::WARNING:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING], true);
            case LogLevel::NOTICE:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE], true);
            case LogLevel::INFO:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO], true);
            case LogLevel::DEBUG:
                return in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG], true);
            default:
                // If the level is unrecognised, always log everything.
                return true;
        }
    }

    public function getLogTarget()
    {
        return $this->target;
    }

    public function setLogTarget($target)
    {
        $this->target = $target;
    }

    public function getLogLevel()
    {
        return $this->target;
    }

    public function setLogLevel($level)
    {
        $this->level = is_int($level) ? $this->translateLevel($level) : $level;
    }
}
