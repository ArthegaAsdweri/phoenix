<?php

namespace PhoenixPhp\Utils;

use PhoenixPhp\Core\Logger;

class JsonDecoder
{

    //---- MEMBERS

    private ?string $error = null;


    //---- GENERAL METHODS

    /**
     * decodes the json string
     * @param string $json json string
     * @return array|null json array or null on error
     */
    public function decode(string $json): ?array
    {
        $jsonArray = json_decode($json, true);

        $error = match (json_last_error()) {
            JSON_ERROR_NONE => null,
            JSON_ERROR_SYNTAX => 'syntax error',
            //@codeCoverageIgnoreStart
            default => 'unknown'
            //@codeCoverageIgnoreStop
        };

        if($error !== null) {
            $this->setError($error);
            return null;
        }
        return $jsonArray;
    }


    //---- SETTERS AND GETTERS

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }
}