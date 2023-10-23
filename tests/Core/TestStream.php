<?php

namespace PhoenixPhp\Core;

class TestStream implements IStream
{

    //---- GENERAL METHODS

    /**
     * returns the json stream
     * @return string|null
     */
    public function retrieveContent(): ?string
    {
        return file_get_contents('tests/_Specimen/TestStream.json');
    }

}