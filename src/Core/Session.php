<?php

namespace PhoenixPhp\Core;

/**
 * handles access of session data and session manipulation
 */
class Session
{

    //---- MEMBERS

    private static self $instance;


    //---- CONSTRUCTOR

    private function __construct()
    {
        session_name('phoenix');
        session_start();
        $sessionID = session_id();
        setcookie('phoenix', $sessionID, 0, '/');
    }

    /**
     * returns the instance
     * @return self
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    //---- GENERAL METHODS

    /**
     * stores values inside the php session
     * @param string $namespace session namespace
     * @param string $key session key
     * @param string|int|bool $value session value
     */
    public function put(string $namespace, string $key, string|int|bool $value): void
    {
        $_SESSION[$namespace][$key] = $value;
    }

    /**
     * reads values from the php session
     * @param string $namespace session namespace
     * @param string $key session key
     * @return string|int|bool|null session value
     */
    public function retrieve(string $namespace, string $key): string|int|bool|null
    {
        if (isset($_SESSION[$namespace][$key])) {
            return $_SESSION[$namespace][$key];
        }
        return null;
    }

    /**
     * returns the whole session
     * @return array
     */
    public function retrieveSession(): array
    {
        return $_SESSION;
    }

    /**
     * resets the session
     */
    public function reset(): void
    {
        session_reset();
    }

}