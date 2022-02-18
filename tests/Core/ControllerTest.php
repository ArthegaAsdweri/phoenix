<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Controller
 */
class ControllerTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::__construct
     * @covers ::utilizeConfig
     * @covers ::utilizeRequest
     * @covers ::setUpSession
     * @covers ::retrieveDir
     * @covers ::checkFile
     * @covers ::callPage
     * @covers ::render
     * @covers ::setCalledPage
     * @covers ::setPage
     * @covers ::getPage
     * @covers ::setAction
     * @covers ::getAction
     * @covers ::setArgument
     */
    public function testConstruct_RenderTestPage_ReturnsPage(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'test'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
    }

    /**
     * @covers ::__construct
     * @covers ::setDebugMode
     */
    public function testConstruct_WithDebugMode_ReturnsPage(): void
    {
        $session = Session::getInstance();
        $session->put('PHOENIX', 'DEBUGGER_ACTIVE', true);
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'test'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
    }

    /**
     * @covers ::__construct
     * @covers ::retrieveAliasForPage
     */
    public function testConstruct_RenderValidAliasPage_ReturnsPage(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'Pikachu',
            'requestAction' => 'test'
        ]);
        $controller = new Controller();
        $controller->setAliases(['test' => ['Pikachu']]);
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
    }

    /*
    public function testConstruct_RenderAjax_ReturnsOK(): void
    {
        $request = Request::getInstance();
        $request->putParameters([
            'requestPage' => 'ajax',
            'requestAction' => 'cart',
            'requestArgument' => 'product_add',
            'product' => 1,
            'size' => 1
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertEquals('{"content":"{\"price\":6.9,\"amount\":1}"}', $pageContent);
    }
    */

    /**
     * Funktioniert plötzlich nicht mehr - "Standard input code (Zeile 166) Zend OPcache can't be temporary enabled (it may be only disabled till the end of request)"
     * Erstmal raus die Tests - Mit Docker vielleicht kein Problem mehr - vermutlich ist die config des vmx Containers wieder konfliktiv
     *
     * @runInSeparateProcess
     *
     * public function testConstruct_RenderInvalidPage_ReturnsPageNotFound() : void {
     * $request = Request::getInstance();
     * $request->putParameters([
     * 'requestPage'   => 'Pikachu',
     * 'requestAction' => 'Pikachu'
     * ]);
     * $controller  = new Controller();
     * $pageContent = $controller->render();
     * $this->assertStringContainsString('404', $pageContent);
     * }
     */

    /**
     * Funktioniert plötzlich nicht mehr - "Standard input code (Zeile 166) Zend OPcache can't be temporary enabled (it may be only disabled till the end of request)"
     * Erstmal raus die Tests - Mit Docker vielleicht kein Problem mehr - vermutlich ist die config des vmx Containers wieder konfliktiv
     *
     * @runInSeparateProcess
     *
     * public function testConstruct_RenderInvalidAjax_ReturnsSUCCESS() : void {
     * $request = Request::getInstance();
     * $request->putParameters([
     * 'requestPage'     => 'ajax',
     * 'requestAction'   => 'cart',
     * 'requestArgument' => 'Pikachu'
     * ]);
     * $controller  = new Controller();
     * $pageContent = $controller->render();
     * $this->assertEquals('SUCCESS', $pageContent);
     * }
     */

}