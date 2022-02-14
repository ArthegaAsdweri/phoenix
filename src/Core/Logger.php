<?php

namespace PhoenixPhp\Core;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * responsible for logging after psr-3
 */
class Logger implements LoggerInterface
{

    //---- GENERAL METHODS

    /**
     * system offline - gather all available resources to bring it back online
     * @param string $message
     * @param array $contextt
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * website offline, database offline, sms have to be sent here
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * critical error - component offline
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * errors that need logging for later bughunt
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * logs that are not necessarily errors
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * notifications
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * interesting logs
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * detailed debug information
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * does the actual logging
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        if ($context !== []) {
            $message .= print_r($context, true);
        }

        $trace = $this->trace();
        $message .= $trace;

        file_put_contents(PHPHP_LOG_FILE, $message, FILE_APPEND);
    }

    /**
     * creates the trace
     * @return string $trace modified trace string
     */
    public function trace(): string
    {
        $trace = PHP_EOL;
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $entry) {
            $trace .= PHP_EOL;
            $trace .= (isset($entry['file'])) ? $entry['file'] : '';
            $trace .= (isset($entry['class'])) ? ' - ' . $entry['class'] : ' - ';
            $trace .= (isset($entry['type'])) ? $entry['type'] : '';
            $trace .= (isset($entry['function'])) ? $entry['function'] : '';
            $trace .= (isset($entry['file'])) ? ' (line ' . $entry['line'] . ')' : '';
        }
        return $trace;
    }

}