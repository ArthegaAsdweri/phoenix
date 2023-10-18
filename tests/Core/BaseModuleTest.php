<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\BaseModule
 */
class BaseModuleTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::retrieveModuleArray
     * @covers ::generateTemplate
     * @covers ::render
     * @covers ::registerVueComponent
     * @covers ::getTemplatePath
     * @covers ::setTemplatePath
     * @covers ::getSubTemplatePath
     * @covers ::setSubTemplatePath
     * @covers ::setVueComponents
     * @covers ::getVueComponents
     */
    public function testModule_ReturnsContent(): void
    {
        $module = new \PhoenixPhp\Modules\Test\DefaultAction();
        $content = $module->render();
        $this->assertEquals('testModule testSubModule', $content['moduleContent']);
    }

    /**
     * @covers ::registerVueComponent
     */
    public function testRegisterVueComponent_withMissingComponent_ReturnsNothing(): void
    {
        $module = new \PhoenixPhp\Modules\Test\MissingComponentAction();
        $content = $module->render();
        $this->assertEquals('', '');
    }

    /**
     * @covers ::registerVueComponent
     */
    public function testRegisterVueComponent_withProjectComponent_ReturnsContent(): void
    {
        $module = new \PhoenixPhp\Modules\Test\ProjectComponentAction();
        $content = $module->render();
        $this->assertEquals('', '');
    }

    /**
     * @covers ::generateTemplate
     */
    public function testMissingTemplate_ReturnsContent(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $module = new \PhoenixPhp\Modules\Test\MissingTemplateAction();
        $content = $module->render();
        $this->assertEquals('', '');
    }

    /**
     * @covers ::returnJsonForHtml
     */
    public function testReturnJsonForHtml_withArray_returnsEncodedArray(): void
    {
        $module = new \PhoenixPhp\Modules\Test\JsonAction();
        $jsonContent = $module->render()['moduleContent'];
        $this->assertEquals('{&quot;key&quot;:&quot;value&quot;}', $jsonContent);
    }

}