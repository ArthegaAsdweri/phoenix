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
     * @covers ::getCalledPage
     * @covers ::getArgument
     */
    public function testConstruct_RenderTestPage_ReturnsPage(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters([
            'requestPage' => 'test'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
    }

    /**
     * @runInSeparateProcess
     * @covers ::__construct
     * @covers ::setDebugMode
     * @covers ::isDebugMode
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
     * @runInSeparateProcess
     * @covers ::utilizeConfig
     * @covers ::utilizeRequest
     */
    public function testConstruct_WithLocalConfig_ReturnsPage(): void
    {
        file_put_contents('tests/config/config.yml', '
Config:
  TEST: test
  
Aliases:
  test:  
    testalias: testalias
    actions:
      default:
        - alias
');
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'test'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
        $this->assertEquals('test', PHPHP_TEST);
        unlink('tests/config/config.yml');
    }

    /**
     * @covers ::__construct
     * @covers ::retrieveAliasForPage
     * @covers ::callPage
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

    /**
     * @covers ::__construct
     * @covers ::retrieveAliasForPage
     * @covers ::retrieveAliasForAction
     * @covers ::getAliases
     * @covers ::setAliases
     * @covers ::callPage
     */
    public function testConstruct_RenderValidAliasAction_ReturnsPage(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'testalias',
            'requestAction' => 'alias'
        ]);
        $controller = new Controller();
        $controller->setAliases(['test' => ['testalias' => 'testalias', 'actions' => ['default' => ['alias']]]]);
        $pageContent = $controller->render();
        $this->assertStringContainsString("WRAPPER\r\nDefault.html\r\nWRAPPER", $pageContent);
    }
    
    /**
     * @covers ::__construct
     * @covers ::retrieveAliasForAction
     * @covers ::callPage
     * @covers ::setStatusCode404
     * @covers ::checkFile
     */
    public function testConstruct_RenderInvalidAliasAction_Returns404Page(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'testalias',
            'requestAction' => 'Pikachu'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("404 - not found", $pageContent);
    }
    
    /**
     * @covers ::__construct
     * @covers ::retrieveAliasForPage
     */
    public function testConstruct_RenderInvalidAliasPage_Returns404Page(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->putParameters([
            'requestPage' => 'Pikachu',
            'requestAction' => 'test'
        ]);
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("404 - not found", $pageContent);
    }
    
    /**
     * @covers ::__construct
     * @covers ::utilizeRequest
     * @covers ::callPage
     */
    public function testUtilizeRequest_WithoutParameters_Returns404Page(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $controller = new Controller();
        $pageContent = $controller->render();
        $this->assertStringContainsString("404 - not found", $pageContent);
    }

    
    /**
     * @covers ::__construct
     * @covers ::utilizeRequest
     * @covers ::callAjax
     * @covers ::retrieveDir
     * @covers ::setAjaxCall
     * @covers ::isAjaxCall
     * @covers ::render
     */
    public function testConstruct_RenderValidAjax_ReturnsOK(): void
    {
        $_SERVER['HTTP_AJAX'] = true;
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters([
            'requestPage' => 'test',
            'requestAction' => 'valid',
            'testKey' => 'test'
        ]);
        $controller = new Controller();
        $controller->setAjaxCall(true);
        $pageContent = $controller->render();
        $this->assertEquals('{"content":"AjaxContent"}', $pageContent);
    }
    /**
     * @covers ::callAjax
     * @covers ::setStatusCode400
     */
    public function testConstruct_RenderInvalidAjax_Returns400(): void
    {
        $_SERVER['HTTP_AJAX'] = true;
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters([
            'requestPage' => 'pikachu'
        ]);
        $controller = new Controller();
        $controller->setAjaxCall(true);
        $pageContent = $controller->render();
        $this->assertEquals('{"content":"Bad Request"}', $pageContent);
    }    
}