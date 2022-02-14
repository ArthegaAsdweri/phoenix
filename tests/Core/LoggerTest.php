<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Logger
 */
class LoggerTest extends TestCase
{

    //---- MEMBERS

    private Logger $logger;


    //---- PRERUN

    protected function setUp(): void
    {
        $this->logger = new Logger();
    }


    //---- TESTS

    /**
     * @covers ::emergency
     */
    public function testEmergency_ReturnsTrue()
    {
        $this->logger->emergency('Logger: Emergency-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::alert
     */
    public function testAlert_ReturnsTrue()
    {
        $this->logger->alert('Logger: Alert-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::critical
     */
    public function testCritical_ReturnsTrue()
    {
        $this->logger->critical('Logger: Critical-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::error
     */
    public function testError_ReturnsTrue()
    {
        $this->logger->error('Logger: Error-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::warning
     */
    public function testWarning_ReturnsTrue()
    {
        $this->logger->warning('Logger: Warning-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::notice
     */
    public function testNotice_ReturnsTrue()
    {
        $this->logger->notice('Logger: Notice-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::info
     */
    public function testInfo_ReturnsTrue()
    {
        $this->logger->info('Logger: Info-Test');
        $this->assertNull(null);
    }

    /**
     * @covers ::debug
     * @covers ::trace
     * @covers ::log
     */
    public function testDebug_WithContext_ReturnsTrue()
    {
        $this->logger->debug('Logger: Debug-Test', ['test' => 'value']);
        $this->assertNull(null);
    }

}