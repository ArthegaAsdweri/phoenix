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
     * @covers ::retrievePageArray
     * @covers ::render
     * @covers ::generateTemplate
     * @covers ::registerCss
     * @covers ::registerJs
     * @covers ::registerExternalJs
     * @covers ::registerInlineJs
     */
    public function testRender_ValidPage_ReturnsPage(): void
    {
        $page = new \PhoenixPhp\Pages\Test\DefaultAction();
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
     * @covers ::generateTemplate
     */
    public function testRender_MissingTemplate_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $page = new \PhoenixPhp\Pages\Test\MissingTemplateAction();
        $content = $page->render();
        $this->assertStringContainsString('Default.html', $content);
    }

    /**
     * @covers ::registerVueComponent
     * @covers ::setVueComponents
     * @covers ::getVueComponents
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
     * @covers ::registerVueComponent
     */
    public function testRegisterMainMixin_existing_ReturnsComponent(): void
    {
        $page = new \PhoenixPhp\Pages\Test\VueMainMixinAction();
        $content = $page->render();
        $this->assertStringContainsString('VueMainMixin.html', $content);
    }

    /**
     * @covers ::callModule
     * @covers ::renderModule
     */
    public function testCallModule_withComponents_ReturnsModuleContent(): void
    {
        $page = new \PhoenixPhp\Pages\Test\ModuleAction();
        $moduleContent = $page->render();
        $this->assertStringContainsString('testModule', $moduleContent);
    }

    /**
     * @covers ::callGlobalModule
     */
    public function testCallGlobalModule_ExistingModule_ReturnsNothingBecauseNoGlobalModuleYet(): void
    {
        $page = new \PhoenixPhp\Pages\Test\GlobalModuleAction();
        $moduleContent = $page->render();
        $this->assertStringContainsString('testModule', $moduleContent);
    }

    /**
     * @covers ::returnJsonForHtml
     */
    public function testReturnJsonForHtml_withArray_returnsEncodedArray(): void
    {
        $page = new \PhoenixPhp\Pages\Test\JsonAction();
        $jsonContent = $page->render();
        $this->assertEquals('{&quot;key&quot;:&quot;value&quot;}', $jsonContent);
    }


    /**
     * @covers ::getPage
     * @covers ::setPage
     * @covers ::getCalledPage
     * @covers ::setCalledPage
     * @covers ::getCalledAction
     * @covers ::setCalledAction
     * @covers ::getCalledArgument
     * @covers ::setCalledArgument
     * @covers ::getTemplatePath
     * @covers ::setTemplatePath
     * @covers ::getSubTemplatePath
     * @covers ::setSubTemplatePath
     * @covers ::isDebugMode
     * @covers ::setDebugMode
     * @covers ::getTitle
     * @covers ::setTitle
     * @covers ::getDescription
     * @covers ::setDescription
     * @covers ::getAdditionalMeta
     * @covers ::setAdditionalMeta
     * @covers ::getAdditionalBodyScripts
     * @covers ::setAdditionalBodyScripts
     */
    public function testSettersAndGetters_UsingValidValues_SetsDataCorrectly(): void
    {
        $page = new \PhoenixPhp\Pages\Test\DefaultAction();
        $page->setPage('setPage');
        $this->assertEquals('setPage', $page->getPage());
        $page->setCalledPage('setCalledPage');
        $this->assertEquals('setCalledPage', $page->getCalledPage());
        $page->setCalledAction('setCalledAction');
        $this->assertEquals('setCalledAction', $page->getCalledAction());
        $page->setCalledArgument('setCalledArgument');
        $this->assertEquals('setCalledArgument', $page->getCalledArgument());
        $page->setTemplatePath('setTemplatePath');
        $this->assertEquals('setTemplatePath', $page->getTemplatePath());
        $page->setSubTemplatePath('setSubTemplatePath');
        $this->assertEquals('setSubTemplatePath', $page->getSubTemplatePath());
        $page->setDebugMode(true);
        $this->assertEquals(true, $page->isDebugMode());
        $page->setTitle('setTitle');
        $this->assertEquals('setTitle', $page->getTitle());
        $page->setDescription('setDescription');
        $this->assertEquals('setDescription', $page->getDescription());
        $page->setAdditionalMeta('setAdditionalMeta');
        $this->assertEquals('setAdditionalMeta', $page->getAdditionalMeta());
        $page->setAdditionalBodyScripts('setAdditionalBodyScripts');
        $this->assertEquals('setAdditionalBodyScripts', $page->getAdditionalBodyScripts());
    }


}