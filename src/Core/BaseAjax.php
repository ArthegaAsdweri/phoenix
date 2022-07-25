<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * Diese Klasse ist die abstrakte Grundklasse für alle Ajax-Requests, die angefragt werden
 */
abstract class BaseAjax
{

    //---- ALLGEMEINE FUNKTIONEN

    abstract public function run(): ?string;


    /**
     * Diese Methode durchläuft alle übergebenen Post-Parameter und ruft die Prüfmethode auf
     *
     * @return bool
     */
    private function validateParameters(): bool
    {
        $request = Request::getInstance();
        $parameters = $request->retrieveParameters();
        $valid = true;

        if ($parameters !== null) {
            foreach ($parameters as $parameter => $value) {
                if ($parameter === 'phoenix') {
                    continue;
                }
                $camelParam = StringConversion::toCamelCase($parameter);
                $methodName = 'validate' . ucfirst($camelParam);

                if (getenv('DEVELOPER')) {
                    if (!method_exists($this, $methodName)) {
                        $logger = new Logger();
                        $logger->debug(
                            get_called_class(
                            ) . ': Die Methode "' . $methodName . '()" für "' . $parameter . '" existiert nicht.'
                        );
                        break;
                    }
                }
                $valid = $this->$methodName($value);
                if ($valid === false) {
                    $logger = new Logger();
                    $logger->debug(
                        $methodName . '(): Der Parameter "' . $value . '" für "' . $parameter . '" ist ungültig.'
                    );
                    break;
                }
            }
        }

        if ($valid === false) {
            return false;
        }
        return true;
    }

    /**
     * Diese Methode gibt die Rückgabe der Ajax-Anfrage aus
     *
     * @return string    'OK', wenn alles glatt lief, 'SUCCESS' bei Fehler
     */
    final public function render(): string
    {
        if ($this->validateParameters() === true) {
            $runResult = $this->run();
            if ($runResult === null) {
                return 'OK';
            } else {
                return $runResult;
            }
        }
        return 'SUCCESS';
    }
}