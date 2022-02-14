<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Session
 */
class SessionTest extends TestCase
{

    //---- MEMBERS

    private Session $session;


    //---- PRERUN

    protected function setUp(): void
    {
        $session = Session::getInstance();
        $this->session = $session;
        $this->session->reset();
    }


    //---- TESTS

    /**
     * @covers ::__construct
     * @covers ::getInstance
     */
    public function testConstruct_ReturnsObject(): void
    {
        $session = Session::getInstance();
        $this->assertInstanceOf('\PhoenixPhp\Core\Session', $session);
    }

    /**
     * @covers ::put
     * @covers ::retrieve
     */
    public function testPutRetrieve_validSessionKey_EqualsGiven(): void
    {
        $given = 'value';
        $this->session->put('TEST', 'TESTKEY', $given);
        $result = $this->session->retrieve('TEST', 'TESTKEY');
        $this->assertEquals($given, $result);
    }

    /**
     * @covers ::retrieve
     */
    public function testPutRetrieve_InvalidSessionKey_ReturnsNull(): void
    {
        $result = $this->session->retrieve('TEST', 'Pikachu');
        $this->assertNull($result);
    }

    /**
     * @covers ::retrieveSession
     * @covers ::reset
     */
    public function testRetrieveSession_Returns_Session(): void
    {
        $this->session->put('TEST', 'test', 'value');
        $this->session->put('TEST', 'test2', 'value2');
        $this->assertEquals(
            [
                'TEST' => [
                    'test' => 'value',
                    'test2' => 'value2'
                ]
            ]
            , $this->session->retrieveSession());
        $this->session->reset();
        $this->assertEquals([], $this->session->retrieveSession());
    }

}