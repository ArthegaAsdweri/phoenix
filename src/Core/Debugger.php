<?php

namespace PhoenixPhp\Core;

/**
 * handles debugging information
 */
class Debugger
{

    //---- MEMBERS

    private static array $debugMessages = [];
    private static array $debugQueries = [];
    private static ?float $executionTimer = null;


    //---- GENERAL METHODS

    /**
     * starts the execution timer to calculate the script run time
     * @param float|null $time starting time
     */
    public static function startExecutionTimer(?float $time = null): void
    {
        if (self::getExecutionTimer() === null) {
            if ($time === null) {
                $time = microtime(true);
            }
            self::setExecutionTimer($time);
        }
    }

    /**
     * returns the execution time
     * @return float
     */
    public static function retrieveExecutionTime(): float
    {
        $now = microtime(true);
        return self::calcRunTime($now);
    }

    /**
     * calculates the runtime of the script
     * @param float $now end time
     * @return float
     */
    private static function calcRunTime(float $now): float
    {
        $start = self::getExecutionTimer();
        $diff = $now - $start;
        return $diff * 1000;
    }

    /**
     * adds a debug message
     * @param string $message message to be logged
     */
    public static function putDebugMessage(string $message): void
    {
        $messages = self::getDebugMessages();
        $messages[] = trim($message);
        self::setDebugMessages($messages);
    }

    /**
     * returns all debug messages
     * @return array array of debug messages
     */
    public static function retrieveDebugMessages(): array
    {
        return self::getDebugMessages();
    }

    /**
     * adds a query to the list of debug queries
     * @param string $query query to be logged
     * @param bool $toggle true: query can be toggled on and off, false: query cannot be toggled
     */
    public static function putDebugQuery(string $query, bool $toggle = false): void
    {
        $queries = self::getDebugQueries();
        $time = intval(self::retrieveExecutionTime());
        $queries[] = ['toggle' => $toggle, 'content' => trim($query), 'time' => $time];
        self::setDebugQueries($queries);
    }

    /**
     * returns the array of debug queries
     * @return array array of debug queries
     */
    public static function retrieveDebugQueries(): array
    {
        return self::getDebugQueries();
    }

    /**
     * removes the last debug query
     */
    public static function removeLastQuery(): void
    {
        $queries = self::getDebugQueries();
        $count = count($queries) - 1;
        unset($queries[$count]);
        $newQueries = array_values($queries);
        self::setDebugQueries($newQueries);
    }


    //---- SETTERS AND GETTERS

    /**
     * @return array
     */
    public static function getDebugMessages(): array
    {
        return self::$debugMessages;
    }

    /**
     * @param array $debugMessages
     */
    public static function setDebugMessages(array $debugMessages): void
    {
        self::$debugMessages = $debugMessages;
    }

    /**
     * @return array
     */
    public static function getDebugQueries(): array
    {
        return self::$debugQueries;
    }

    /**
     * @param array $debugQueries
     */
    public static function setDebugQueries(array $debugQueries): void
    {
        self::$debugQueries = $debugQueries;
    }

    /**
     * @return float|null
     */
    public static function getExecutionTimer(): ?float
    {
        return self::$executionTimer;
    }

    /**
     * @param float|null $executionTimer
     */
    public static function setExecutionTimer(?float $executionTimer): void
    {
        self::$executionTimer = $executionTimer;
    }

}