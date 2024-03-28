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
                //@codeCoverageIgnoreStart
                $logger = new Logger();
                $logger->critical(
                    'invalid request: json_decode error: ' . $decoder->getError(),
                    [$stream]
                );
                //@codeCoverageIgnoreEnd
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
     *
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
     * returns all request parameters
     *
     * @param bool $withController true: also returns the framework relevant parameters, false: does not
     * @return ?array $array all parameters or null if none exist
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
     * retrieves a parameter
     *
     * @param string $parameter key (name) of the parameter     *
     * @return array|bool|string|int|null $parameter value of the parameter or null if not it does not exist
     */
    public function retrieveParameter(string $parameter): array|bool|string|int|null
    {
        if (isset($this->request[$parameter]) && $this->request[$parameter] !== '') {
            return $this->request[$parameter];
        }
        return null;
    }

    /**
     * saves additional parameters inside the object
     *
     * @param array $parameters array of additional parameters
     */
    public function putParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->putParameter($key, $value);
        }
    }

    /**
     * saves one additional parameter inside the object
     *
     * @param string $key
     * @param bool|string|int $value
     */
    public function putParameter(string $key, bool|string|int $value): void
    {
        $request = $this->getRequest();
        $request[$key] = $value;
        $this->setRequest($request);
    }

    /**
     * removes all parameters from the object
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