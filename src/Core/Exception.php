<?php

namespace PhoenixPhp\Core;

/**
 * general exception handler
 */
class Exception extends \Exception implements IException
{

    //---- MEMBERS

    protected $message;
    private $string;
    protected $code;
    protected string $file;
    protected int $line;
    private $trace;


    //---- CONSTRUCTOR

    /**
     * creates an exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct($message, $code);
    }


    //---- GENERAL METHODS

    /**
     * returns the exception as readable string
     *
     * @return string $message
     */
    public function __toString(): string
    {
        return PHP_EOL . get_class() . ': "' . $this->getMessage() . '"' .
            PHP_EOL . 'source: "' . $this->getFile() . '" (line ' . $this->getLine() . ')' .
            PHP_EOL . PHP_EOL . $this->getTraceAsString();
    }
}