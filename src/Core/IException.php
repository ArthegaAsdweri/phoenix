<?php

namespace PhoenixPhp\Core;

/**
 * Interface for general exceptions
 */
interface IException
{
    //---- CONSTRUCTOR

    public function __construct(string $message, int $code = 0);


    //---- GENERAL METHODS

    public function getMessage();

    public function getCode();

    public function getFile();

    public function getLine();

    public function getTrace();

    public function getTraceAsString();

    public function __toString(): string;
}