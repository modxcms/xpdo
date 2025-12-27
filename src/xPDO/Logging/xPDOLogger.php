<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use xPDO\Cache\xPDOCacheManager;
use xPDO\xPDO;

/**
 * Minimal PSR-3 logger that preserves the legacy xPDO line format.
 *
 * Context interpolation replaces {placeholders} using stringified values:
 * scalars are cast, DateTimeInterface uses DATE_ATOM, and arrays/objects
 * fall back to print_r().
 */
class xPDOLogger extends AbstractLogger
{
    /**
     * @var xPDO
     */
    protected $xpdo;
    /**
     * @var string|array|null
     */
    protected $target = null;
    /**
     * @var array
     */
    protected $targetOptions = array();

    public function __construct(xPDO $xpdo, array $options = array())
    {
        $this->xpdo = $xpdo;
        if (array_key_exists('target', $options)) {
            $this->target = $options['target'];
        }
        if (array_key_exists('target_options', $options) && is_array($options['target_options'])) {
            $this->targetOptions = $options['target_options'];
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param mixed  $message
     * @param array  $context
     * @return void
     */
    public function log($level, $message, array $context = array()): void
    {
        $legacyLevel = $this->mapPsrLevel($level);
        if (!$this->shouldLog($legacyLevel)) {
            return;
        }

        $def = isset($context['def']) ? $context['def'] : '';
        $file = isset($context['file']) ? $context['file'] : '';
        $line = isset($context['line']) ? $context['line'] : '';
        list($file, $line) = $this->resolveLogLocation($file, $line);

        $messageText = $this->formatMessage($message, $context);

        $target = $this->resolveTarget($context);
        $targetOptions = $this->targetOptions;
        if (is_array($target)) {
            $targetOptions = array();
            if (isset($target['options'])) {
                $targetOptions = &$target['options'];
            }
            $target = isset($target['target']) ? $target['target'] : 'ECHO';
        }

        $levelText = $this->getLegacyLevelLabel($legacyLevel);
        $defText = !empty($def) ? " in {$def}" : '';
        $fileText = !empty($file) ? " @ {$file}" : '';
        $lineText = !empty($line) ? " : {$line}" : '';

        if ($target === 'HTML') {
            $content = '<h5>[' . date('Y-m-d H:i:s') . '] (' . $levelText . $defText . $fileText . $lineText . ')</h5><pre>' . $messageText . '</pre>' . "\n";
        } else {
            $content = '[' . date('Y-m-d H:i:s') . '] (' . $levelText . $defText . $fileText . $lineText . ') ' . $messageText . "\n";
        }

        if ($this->writeToTarget($target, $targetOptions, $content, $levelText, $messageText, $defText, $fileText, $lineText)) {
            return;
        }

        echo $content;
    }

    protected function shouldLog($legacyLevel): bool
    {
        if ($this->xpdo->getDebug() === true) {
            return true;
        }
        if ($legacyLevel === xPDO::LOG_LEVEL_FATAL) {
            return true;
        }
        return $legacyLevel <= $this->xpdo->getLogLevel();
    }

    protected function resolveTarget(array $context)
    {
        if (array_key_exists('target', $context)) {
            return $context['target'];
        }
        if ($this->target !== null) {
            return $this->target;
        }
        return $this->xpdo->getLogTarget();
    }

    protected function writeToTarget($target, & $targetOptions, $content, $levelText, $messageText, $defText, $fileText, $lineText): bool
    {
        if ($target === 'FILE' && $this->xpdo->getCacheManager()) {
            $filename = isset($targetOptions['filename']) ? $targetOptions['filename'] : 'error.log';
            $filepath = isset($targetOptions['filepath']) ? $targetOptions['filepath'] : $this->xpdo->getCachePath() . xPDOCacheManager::LOG_DIR;
            $this->xpdo->cacheManager->writeFile($filepath . $filename, $content, 'a');
            return true;
        }

        if (
            $target === 'ARRAY' &&
            isset($targetOptions['var']) &&
            (is_array($targetOptions['var']) || $targetOptions['var'] instanceof \ArrayAccess)
        ) {
            $targetOptions['var'][] = $content;
            return true;
        }

        if (
            $target === 'ARRAY_EXTENDED' &&
            isset($targetOptions['var']) &&
            (is_array($targetOptions['var']) || $targetOptions['var'] instanceof \ArrayAccess)
        ) {
            $targetOptions['var'][] = array(
                'content' => $content,
                'level' => $levelText,
                'msg' => $messageText,
                'def' => $defText,
                'file' => $fileText,
                'line' => $lineText,
            );
            return true;
        }

        return false;
    }

    protected function formatMessage($message, array $context): string
    {
        $messageText = $this->stringifyValue($message);
        if (array_key_exists('xpdo_interpolate', $context) && $context['xpdo_interpolate'] === false) {
            return $messageText;
        }
        if (strpos($messageText, '{') === false) {
            return $messageText;
        }

        $replace = array();
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $this->stringifyValue($value);
        }

        return strtr($messageText, $replace);
    }

    protected function stringifyValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        return print_r($value, true);
    }

    protected function resolveLogLocation($file, $line): array
    {
        if (empty($file)) {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            } elseif (version_compare(phpversion(), '5.3.6', '>=')) {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            } else {
                $backtrace = debug_backtrace();
            }
            if ($backtrace && isset($backtrace[2])) {
                $file = $backtrace[2]['file'];
                $line = $backtrace[2]['line'];
            }
        }
        if (empty($file) && isset($_SERVER['SCRIPT_NAME'])) {
            $file = $_SERVER['SCRIPT_NAME'];
        }
        return array($file, $line);
    }

    protected function mapPsrLevel($level): int
    {
        $level = strtolower((string) $level);
        switch ($level) {
            case LogLevel::DEBUG:
                return xPDO::LOG_LEVEL_DEBUG;
            case LogLevel::INFO:
                return xPDO::LOG_LEVEL_INFO;
            case LogLevel::NOTICE:
                return xPDO::LOG_LEVEL_INFO;
            case LogLevel::WARNING:
                return xPDO::LOG_LEVEL_WARN;
            case LogLevel::ERROR:
                return xPDO::LOG_LEVEL_ERROR;
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                return xPDO::LOG_LEVEL_FATAL;
            default:
                return xPDO::LOG_LEVEL_INFO;
        }
    }

    protected function getLegacyLevelLabel($legacyLevel): string
    {
        switch ($legacyLevel) {
            case xPDO::LOG_LEVEL_DEBUG:
                return 'DEBUG';
            case xPDO::LOG_LEVEL_INFO:
                return 'INFO';
            case xPDO::LOG_LEVEL_WARN:
                return 'WARN';
            case xPDO::LOG_LEVEL_ERROR:
                return 'ERROR';
            default:
                return 'FATAL';
        }
    }
}
