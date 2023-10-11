<?php

namespace PhoenixPhp\Core;

/**
 * This class collects errors for output to the users (for example forms)
 */
class ErrorCollector {

    //---- MEMBERS

    private static array $errorMessages = [];


    //---- GENERAL METHODS

    /**
     * This method adds an error message to the list of errors.
     *
     * @param string $key name of the key that triggered the message
     * @param string $message message that should be shown or logged for the user
     */
    public static function putErrorMessage(string $key, string $message = 'ist ungültig') : void {
        $messages       = self::getErrorMessages();
        $messages[$key] = trim($message);
        self::setErrorMessages($messages);
    }

    /**
     * This method returns all error messages.
     *
     * @return array array of error messages
     */
    public static function retrieveErrorMessages() : array {
        return self::getErrorMessages();
    }


    //---- SETTER AND GETTERS

    private static function setErrorMessages(array $val) : void {
        self::$errorMessages = $val;
    }

    public static function getErrorMessages() : array {
        return self::$errorMessages;
    }

}