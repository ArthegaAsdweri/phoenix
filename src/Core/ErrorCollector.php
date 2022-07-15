<?php

namespace PhoenixPhp\Core;

/**
 * Diese Klasse sammelt Fehler-Nachrichten durch Benutzereingaben (z.B. Formulare)
 */
class ErrorCollector {

    //---- MEMBER VARIABLEN

    private static array $errorMessages = [];


    //---- SETTER

    private static function setErrorMessages(array $val) : void {
        self::$errorMessages = $val;
    }


    //---- GETTER

    public static function getErrorMessages() : array {
        return self::$errorMessages;
    }


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Methode fügt eine Fehler-Nachricht zum Array aller Fehler-Nachrichten hinzu
     *
     * @param string $key        Der Schlüssel, der den Fehler erzeugt hat
     * @param string $message    Die Nachricht, die geloggt werden soll
     */
    public static function putErrorMessage(string $key, string $message = 'ist ungültig') : void {
        $messages       = self::getErrorMessages();
        $messages[$key] = trim($message);
        self::setErrorMessages($messages);
    }

    /**
     * Diese Methode gibt die Fehler-Nachrichten zurück
     *
     * @return array    das Array der Fehler-Nachrichten
     */
    public static function retrieveErrorMessages() : array {
        return self::getDebugMessages();
    }

}