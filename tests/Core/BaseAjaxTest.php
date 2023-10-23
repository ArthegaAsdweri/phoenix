<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\BaseAjax
 */
class BaseAjaxTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::render
     * @covers ::validateParameters
     */
    public function testRender_ValidCall_ReturnsContent(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $_SERVER['HTTP_AJAX'] = true;
        $call = new \PhoenixPhp\Pages\Test\ValidAjaxAction();
        $content = $call->render();
        $this->assertEquals('AjaxContent', $content);
    }

    /**
     * @covers ::render
     * @covers ::validateParameters
     */
    public function testRender_InvalidCall_ReturnsSUCCESS(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters(['key' => 'value']);
        $_SERVER['HTTP_AJAX'] = true;
        $call = new \PhoenixPhp\Pages\Test\InvalidAjaxAction();
        $content = $call->render();
        $this->assertEquals('SUCCESS', $content);
    }

    /**
     * @runInSeparateProcess
     * @covers ::render
     * @covers ::validateParameters
     */
    public function testRender_ValidationMissing_ReturnsOK(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters(['key' => 'value']);
        $_SERVER['HTTP_AJAX'] = true;
        putenv('DEVELOPER="true"');
        $call = new \PhoenixPhp\Pages\Test\MissingValidationAjaxAction();
        $content = $call->render();
        $this->assertEquals('OK', $content);
    }

}