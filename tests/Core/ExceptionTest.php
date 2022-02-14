<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Exception
 */
class ExceptionTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::__construct
     * @covers ::__toString
     */
    public function testConstruct_ValidParameters_ThrowsException(): void
    {
        $exception = new Exception('Pikachu');
        $checkString = $exception . '..';
        $this->assertStringStartsWith(PHP_EOL.'PhoenixPhp\Core\Exception:', $checkString);
    }

}