<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\JsonDecoder;

/**
 * wraps _GET, _POST and _REQUEST parameters for access and manipulation
 */
class Request
{

    //---- MEMBERS

    private static self $instance;
    private array $request;


    //---- CONSTRUCTOR

    private function __construct(IStream $stream)
    {
        $request = $_REQUEST;
        if ($stream->retrieveContent() !== null) {
            $decoder = new JsonDecoder();
            $jsonArray = $decoder->decode($stream->retrieveContent());

            if ($decoder->getError() !== null) {
                $logger = new Logger();
                $logger->critical(
                    'invalid request: json_decode error: ' . $decoder->getError(),
                    [$stream]
                );
            }

            if ($jsonArray) {
                foreach ($jsonArray as $key => $val) {
                    if ($val === '') {
                        $val = null;
                    }
                    $request[$key] = $val;
                }
            }
        }
        $this->setRequest($request);
    }

    /**
     * returns the classInstance
     * @param IStream|null $stream
     * @return self
     */
    public static function getInstance(IStream $stream = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($stream);
        }
        return self::$instance;
    }


    //---- GENERAL METHODS

    /**
     * Diese Methode holt alle Request-Parameter aus dem Request-Array (außer den controller-relevanten)
     *
     * @param bool $withController true, wenn die Controller-relevanten Parameter gewünscht sind, false wenn nicht
     *
     * @return ?array $array          Alle Request-Parameter oder null, wenn keine vorhanden sind
     */
    public function retrieveParameters(bool $withController = false): ?array
    {
        $array = $this->getRequest();
        if ($withController === false) {
            unset($array['requestPage']);
            unset($array['requestAction']);
            unset($array['requestArgument']);
        }
        if (count($array) === 0) {
            return null;
        }
        return $array;
    }

    /**
     * Diese Methode holt einen Parameter aus dem Request-Array
     *
     * @param string $parameter Der übergebene Post-Wert des Parameters
     *
     * @return mixed $parameter   Der Wert des Parameters oder null, wenn er nicht vorhanden ist
     */
    public function retrieveParameter(string $parameter)
    {
        if (isset($this->request[$parameter]) && $this->request[$parameter] !== '') {
            return $this->request[$parameter];
        }
        return null;
    }

    /**
     * Diese Methode speichert zusätzliche Parameter in das Request-Objekt
     *
     * @param array $parameters Ein Array von zusätzlichen Parametern
     */
    public function putParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->putParameter($key, $value);
        }
    }

    /**
     * Diese Methode speichert einen zusätzlichen Parameter in das Request-Objekt
     *
     * @param string $key Der Key
     * @param $value
     */
    public function putParameter(string $key, $value): void
    {
        $request = $this->getRequest();
        $request[$key] = $value;
        $this->setRequest($request);
    }

    /**
     * Diese Methode löscht alle Parameter aus dem Request-Objekt, sodass es erneut befüllt werden kann
     */
    public function deleteAllParameters(): void
    {
        $this->setRequest([]);
    }


    //---- SETTERS AND GETTERS

    /**
     * @return array
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request): void
    {
        $this->request = $request;
    }

}