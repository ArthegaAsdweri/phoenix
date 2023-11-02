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
        $call = new \PhoenixPhp\Pages\Test\ValidAjax();
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
        $call = new \PhoenixPhp\Pages\Test\InvalidAjax();
        $content = $call->render();
        $this->assertEquals('SUCCESS', $content);
    }

    /**
     * @covers ::render
     * @covers ::validateParameters
     */
    public function testRender_ValidationMissing_ReturnsOK(): void
    {
        $stream = new TestStream();
        $request = Request::getInstance($stream);
        $request->deleteAllParameters();
        $request->putParameters(['key' => 'value']);
        putenv('DEVELOPER="true"');
        $call = new \PhoenixPhp\Pages\Test\MissingValidationAjax();
        $content = $call->render();
        $this->assertEquals('OK', $content);
    }

}