<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Debugger
 */
class DebuggerTest extends TestCase
{

    //---- TESTS

    /**
     * @runInSeparateProcess
     * @covers ::startExecutionTimer
     * @covers ::getExecutionTimer
     * @covers ::setExecutionTimer
     */
    public function testStartExecutionTimer_WithoutParameter_SetsTheTimer()
    {
        Debugger::startExecutionTimer();
        $timer = Debugger::getExecutionTimer();
        $this->assertNotNull($timer);
    }

    /**
     * @covers ::retrieveExecutionTime
     * @covers ::calcRunTime
     */
    public function testRetrieveExecutionTime_ReturnsRunTime()
    {
        $executionTime = Debugger::retrieveExecutionTime();
        $this->assertNotNull($executionTime);
    }

    /**
     * @covers ::putDebugMessage
     * @covers ::retrieveDebugMessages
     * @covers ::getDebugMessages
     * @covers ::setDebugMessages
     */
    public function testPutDebugMessage_WithMessage_ReturnsArray()
    {
        $message = 'Test';
        Debugger::putDebugMessage($message);
        $messages = Debugger::retrieveDebugMessages();
        $lastMessage = $messages[count($messages) - 1];
        $this->assertEquals($message, $lastMessage);
    }

    /**
     * @covers ::putDebugQuery
     * @covers ::retrieveDebugQueries
     * @covers ::removeLastQuery
     * @covers ::setDebugQueries
     * @covers ::getDebugQueries
     */
    public function testPutDebugQuery_WithQuery_ReturnsArray()
    {
        $query = 'Test';
        Debugger::putDebugQuery($query);
        $queries = Debugger::retrieveDebugQueries();
        $lastMessage = $queries[count($queries) - 1];
        $this->assertEquals($query, $lastMessage['content']);
        Debugger::removeLastQuery();
        $queries = Debugger::retrieveDebugQueries();
        $lastMessage = $queries[count($queries) - 1];
        $this->assertNotEquals($query, $lastMessage['content']);
    }


}