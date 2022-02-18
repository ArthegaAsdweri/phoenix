<?php

namespace PhoenixPhp\Core;

/**
 * framework specific error handlers
 */
class ErrorHandler
{

    //---- GENERAL METHODS

    /**
     * @param object $exception
     */
    public static function handleException(object $exception): void
    {
        $trace = $exception->getMessage() . PHP_EOL;
        $trace .= 'Quelle: ' . $exception->getFile() . ' (Zeile ' . $exception->getLine() . ')';
        $trace .= PHP_EOL . $exception->getTraceAsString();
        $logger = new Logger();
        $logger->critical($trace);
    }

    /**
     * @param int $errno
     * @param string $errmsg
     * @param string $errfile
     * @param int $errline
     * @param array|null $context
     * @return true
     */
    public static function handleError(int $errno, string $errmsg, string $errfile, int $errline, ?array $context = null): bool
    {
        $trace = $errmsg . PHP_EOL;
        $trace .= 'source: ' . $errfile . ' (line ' . $errline . ')';
        $logger = new Logger();
        if ($errno === 8) {
            $logger->warning($trace);
        } else {
            $logger->error($trace);
        }
        return true;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            //@codeCoverageIgnoreStart
            $trace = $error['file'] . ' (line ' . $error['line'] . ') ' . $error['message'];
            $logger = new Logger();
            $logger->critical($trace);
            //@codeCoverageIgnoreEnd
        }
    }
}