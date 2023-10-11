<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\BasePage
 */
class BasePageTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::setCalledPage
     * @covers ::retrievePageArray
     * @covers ::render
     * @covers ::generateTemplate
     */
    public function testRender_ValidPage_ReturnsPage(): void
    {
        $page = new \PhoenixPhp\Pages\Test\DefaultAction();
        $page->setCalledPage('test');
        $content = $page->render();
        $this->assertStringContainsString('Default.html', $content);
    }

    /**
     * @covers ::render
     */
    public function testRender_InvalidPage_ThrowsException(): void
    {
        $this->expectException('\Error');
        $page = new \PhoenixPhp\Pages\Test\InvalidAction();
        $page->render();
    }

    /**
     * @covers ::registerJs
     * @covers ::registerCss
     */
    public function testRender_IncludingSameFileTwice_ReturnsFalse(): void
    {
        $page = new \PhoenixPhp\Pages\Test\DoubleIncludeAction();
        $content = $page->render();
        $this->assertStringContainsString('DoubleInclude.html', $content);
    }

    /**
     * @covers ::registerCss
     */
    public function testRender_IncludingInvalidCss_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $page = new \PhoenixPhp\Pages\Test\InvalidCssAction();
        $page->render();
    }

    /**
     * @covers ::registerJs
     */
    public function testRender_IncludingInvalidJs_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $page = new \PhoenixPhp\Pages\Test\InvalidJsAction();
        $page->render();
    }

    /**
     * @covers ::generateTemplate
     */
    public function testRender_MissingTemplate_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $page = new \PhoenixPhp\Pages\Test\MissingTemplateAction();
        $page->setCalledPage('test');
        $content = $page->render();
        $this->assertStringContainsString('Default.html', $content);
    }

    /**
     * @covers ::registerVueComponent
     */
    public function testRegisterVueComponent_ExistingComponent_ReturnsComponent(): void
    {
        $page = new \PhoenixPhp\Pages\Test\VueComponentAction();
        $content = $page->render();
        $this->assertStringContainsString('VueComponent.html', $content);
    }

    /**
     * @covers ::registerVueComponent
     */
    public function testRegisterVueComponent_NonExistingComponent_ReturnsNull(): void
    {
        $page = new \PhoenixPhp\Pages\Test\VueMissingComponentAction();
        $content = $page->render();
        $this->assertStringContainsString('VueMissingComponent.html', $content);
    }

    /**
     * @covers ::registerVueComponent
     * @covers ::registerVueMixin
     */
    public function testRegisterVueMixin_localMixin_ReturnsComponent(): void
    {
        $page = new \PhoenixPhp\Pages\Test\VueMixinAction();
        $content = $page->render();
        $this->assertStringContainsString('VueMixin.html', $content);
    }

    /**
     * @covers ::registerMainMixin
     */
    public function testRegisterMainMixin_existing_ReturnsComponent(): void
    {
        $page = new \PhoenixPhp\Pages\Test\VueMainMixinAction();
        $content = $page->render();
        $this->assertStringContainsString('VueMainMixin.html', $content);
    }

}