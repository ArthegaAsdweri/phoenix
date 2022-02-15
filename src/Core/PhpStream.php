<?php

namespace PhoenixPhp\Core;

class PhpStream implements IStream
{

    //---- GENERAL METHODS

    /**
     * returns the json stream
     * @return string|null
     */
    public function retrieveContent(): ?string
    {
        $jsonPost = file_get_contents('php://input');
        if ($jsonPost !== '') {
            return $jsonPost;
        }
        return null;
    }

}