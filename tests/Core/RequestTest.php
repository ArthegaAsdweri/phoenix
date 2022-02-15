<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Request
 */
class RequestTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::__construct
     * @covers ::getInstance
     * @covers ::setRequest
     */
    public function testGetInstance_ReturnsObject(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $this->assertInstanceOf('\PhoenixPhp\Core\Request', $request);
    }

    /**
     * @covers ::retrieveParameters
     * @depends testGetInstance_ReturnsObject
     */
    public function testRetrieveParameters_WithoutRequest_ReturnsNull(): void
    {
        $request = Request::getInstance();
        $request->setRequest([]);
        $result = $request->retrieveParameters();
        $this->assertNull($result);
    }

    /**
     * @covers ::putParameters
     * @covers ::retrieveParameters
     * @covers ::getRequest
     * @depends testRetrieveParameters_WithoutRequest_ReturnsNull
     */
    public function testPutParameters_ValidArray_UpdatesInstance(): void
    {
        $request = Request::getInstance();
        $request->putParameters(['key' => 'value']);
        $result = $request->retrieveParameters();
        $this->assertEquals(['key' => 'value'], $result);
    }

    /**
     * @covers ::putParameter
     * @covers ::retrieveParameter
     * @depends testPutParameters_ValidArray_UpdatesInstance
     */
    public function testRetrieveParameter_InvalidKey_ReturnsNull(): void
    {
        $request = Request::getInstance();
        $request->putParameters(['key' => 'value']);
        $result = $request->retrieveParameter('Pikachu');
        $this->assertNull($result);
    }

    /**
     * @covers ::retrieveParameter
     * @depends testRetrieveParameter_InvalidKey_ReturnsNull
     */
    public function testRetrieveParameter_ValidKey_ReturnsKey(): void
    {
        $request = Request::getInstance();
        $request->putParameter('testkey', 'testvalue');
        $result = $request->retrieveParameter('testkey');
        $this->assertEquals('testvalue', $result);
    }

    /**
     * @covers ::deleteAllParameters
     * @depends testRetrieveParameter_ValidKey_ReturnsKey
     */
    public function testDeleteAllParameters_ReturnsEmptyArray(): void
    {
        $request = Request::getInstance();
        $this->assertEquals(['testkey' => 'testvalue', 'key' => 'value'], $request->getRequest());
        $request->deleteAllParameters();
        $this->assertEquals([], $request->getRequest());
    }

}