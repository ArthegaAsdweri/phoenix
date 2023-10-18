<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\BaseWrapper
 */
class BaseWrapperTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::generateTemplate
     * @covers ::render
     * @covers ::renderPage
     * @covers ::setPageContent
     * @covers ::getPageContent
     */
    public function testRender_ValidPath_RendersPage(): void
    {
        $pageWrapper = new \PhoenixPhp\PageWrapper\PageWrapper();
        $page = new \PhoenixPhp\Pages\Test\DefaultAction();
        $page->setCalledPage('test');
        $page->setCalledAction('default');
        $pageWrapper->renderPage($page);
        $contents = $pageWrapper->render();
        $this->assertEquals(file_get_contents('tests/_Specimen/Page.html'), $contents);
    }

    /**
     * @covers ::render
     */
    public function testRender_WithComponent_RendersPageWithComponent(): void
    {
        $assertHandler = AssetHandler::getInstance();
        $assertHandler->setCssFiles([]);
        $assertHandler->setJsFiles([]);
        $assertHandler->setInlineJs('');
        $assertHandler->setExternalJsFiles([]);
        $pageWrapper = new \PhoenixPhp\PageWrapper\PageWrapper();
        $page = new \PhoenixPhp\Pages\Test\VueGlobalMixinAction();
        $page->setCalledPage('test');
        $page->setCalledAction('vue-component');
        $pageWrapper->renderPage($page);
        $contents = $pageWrapper->render();
        $this->assertEquals(file_get_contents('tests/_Specimen/PageComponent.html'), $contents);
    }

    /**
     * @covers ::render
     */
    public function testRender_WithMainMixin_RendersPageWithComponent(): void
    {
        $assertHandler = AssetHandler::getInstance();
        $assertHandler->setCssFiles([]);
        $assertHandler->setJsFiles([]);
        $assertHandler->setInlineJs('');
        $assertHandler->setExternalJsFiles([]);
        $pageWrapper = new \PhoenixPhp\PageWrapper\PageWrapper();
        $page = new \PhoenixPhp\Pages\Test\VueMainMixinAction();
        $page->setCalledPage('test');
        $page->setCalledAction('vue-component');
        $pageWrapper->renderPage($page);
        $contents = $pageWrapper->render();
        $this->assertEquals(file_get_contents('tests/_Specimen/PageMainMixin.html'), $contents);
    }

    /**
     * @runInSeparateProcess
     * @covers ::render
     */
    public function testRender_WithAllIncludes_RendersPageWithAllIncludes(): void
    {
        define('PHPHP_VUETIFY', true);
        define('PHPHP_VUETIFY_THEME', 'dark');
        define('PHPHP_AXIOS', true);
        define('PHPHP_LANGUAGE', 'de-DE');
        define('PHPHP_GOOGLE', ['ANALYTICS' => true, 'ANALYTICS_ID' => '123456789']);
        define('PHPHP_DOMAIN', 'test.de');
        define('PHPHP_COOKIEBOT_ID', '123456789');
        define('PHPHP_META', ['AUTHOR' => 'Author', 'COPYRIGHT' => 'Copyright', 'PUBLISHER' => 'Publisher']);
        define('PHPHP_VERSION', '2');
        $assertHandler = AssetHandler::getInstance();
        $assertHandler->setCssFiles([]);
        $assertHandler->setJsFiles([]);
        $assertHandler->setInlineJs('');
        $assertHandler->setExternalJsFiles([]);
        $pageWrapper = new \PhoenixPhp\PageWrapper\PageWrapper();
        $page = new \PhoenixPhp\Pages\Test\VueMainMixinAction();
        $page->setCalledPage('test');
        $page->setCalledAction('vue-component');
        $pageWrapper->renderPage($page);
        $pageWrapper->setAdditionalMeta('<meta type="daniel"/>');
        $pageWrapper->setAdditionalBodyScripts('<script src="https://www.test.com"></script>');
        $contents = $pageWrapper->render();
        $this->assertEquals(file_get_contents('tests/_Specimen/PageAllIncludes.html'), $contents);
    }
}