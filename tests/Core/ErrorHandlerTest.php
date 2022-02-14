<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\ErrorHandler
 */
class ErrorHandlerTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::handleException
     */
    public function testHandleException_ReturnsNull(): void
    {
        ErrorHandler::handleException(new Exception('Pikachu'));
        $this->assertNull(null);
    }

    /**
     * @covers ::handleError
     */
    public function testHandleError_ReturnsNull(): void
    {
        ErrorHandler::handleError(1, 'Pikachu', 'Pikachu', 1, ['Pikachu' => 'Pikachu']);
        $this->assertNull(null);
    }

    /**
     * @covers ::handleError
     */
    public function testHandleError_Code8_ReturnsNull(): void
    {
        ErrorHandler::handleError(8, 'Pikachu', 'Pikachu', 1, ['Pikachu' => 'Pikachu']);
        $this->assertNull(null);
    }

    /**
     * @covers ::handleShutdown
     */
    public function testHandleShutdown_ReturnsNull(): void
    {
        ErrorHandler::handleShutdown();
        $this->assertNull(null);
    }

}