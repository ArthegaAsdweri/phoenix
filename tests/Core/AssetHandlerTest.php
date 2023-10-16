<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\AssetHandler
 */
class AssetHandlerTest extends TestCase
{

    //---- CONSTANTS

    const CSS_FILE = '_Specimen/Resources/Css/Test.css';
    const JS_FILE = '_Specimen/Resources/Js/Test.js';
    const JS_EXT_FILE = 'https://www.test.de';


    //---- TESTS

    /**
     * @runInSeparateProcess
     * @covers ::__construct
     * @covers ::getInstance
     */
    public function testConstruct_NoInstance_CreatesInstance(): void
    {
        $assetHandler = AssetHandler::getInstance();
        $this->assertInstanceOf('\PhoenixPhp\Core\AssetHandler', $assetHandler);
    }

    /**
     * @covers ::registerCss
     */
    public function testRegisterCss_IncludingInvalidCss_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $handler = AssetHandler::getInstance();
        $handler->registerCss('not-existent.css');
    }

    /**
     * @covers ::setCssFiles
     * @covers ::getCssFiles
     * @covers ::registerCss
     */
    public function testRegisterCss_IncludingSameFileTwice_Returns(): void
    {
        $handler = AssetHandler::getInstance();
        $handler->setCssFiles([]);
        $handler->registerCss(self::CSS_FILE);
        $handler->registerCss(self::CSS_FILE);
        $cssFiles = $handler->getCssFiles();
        $this->assertCount(1, $cssFiles);
        $this->assertEquals(self::CSS_FILE, $cssFiles[0]);
    }


    /**
     * @covers ::registerJs
     */
    public function testRegisterJs_IncludingInvalidJs_ThrowsException(): void
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $handler = AssetHandler::getInstance();
        $handler->registerJs('not-existent.js');
    }

    /**
     * @covers ::setJsFiles
     * @covers ::getJsFiles
     * @covers ::registerJs
     */
    public function testRegisterJs_IncludingSameFileTwice_Returns(): void
    {
        $handler = AssetHandler::getInstance();
        $handler->setJsFiles([]);
        $handler->registerJs(self::JS_FILE);
        $handler->registerJs(self::JS_FILE);
        $jsFiles = $handler->getJsFiles();
        $this->assertCount(1, $jsFiles);
        $this->assertEquals(self::JS_FILE, $jsFiles[0]);
    }

    /**
     * @covers ::registerExternalJs
     */
    public function testRegisterExternalJs_ValidString_ReturnsArray(): void
    {
        $handler = AssetHandler::getInstance();
        $handler->registerExternalJs(self::JS_EXT_FILE);
        $jsFiles = $handler->getExternalJsFiles();
        $this->assertEquals(self::JS_EXT_FILE, $jsFiles[0]);
    }

    /**
     * @covers ::setExternalJsFiles
     * @covers ::getExternalJsFiles
     * @covers ::registerExternalJs
     */
    public function testRegisterExternalJs_IncludingSameFileTwice_Returns(): void
    {
        $handler = AssetHandler::getInstance();
        $handler->setExternalJsFiles([]);
        $handler->registerExternalJs(self::JS_EXT_FILE);
        $handler->registerExternalJs(self::JS_EXT_FILE);
        $jsFiles = $handler->getExternalJsFiles();
        $this->assertCount(1, $jsFiles);
        $this->assertEquals(self::JS_EXT_FILE, $jsFiles[0]);
    }

    /**
     * @covers ::registerInlineJs
     * @covers ::getInlineJs
     * @covers ::setInlineJs
     */
    public function testRegisterInlineJs_ValidString_ReturnsCode(): void
    {
        $string = 'var test="test"';
        $handler = AssetHandler::getInstance();
        $handler->setInlineJs('');
        $handler->registerInlineJs($string);
        $inlineJs = $handler->getInlineJs();
        $this->assertEquals($string, $inlineJs);
    }

    /**
     * @covers ::renderCss
     * @covers ::retrieveDirectoryPath
     */
    public function testRenderCss_withComponentStyle_createsMergedFile(): void
    {
        //FIXME: compare file contents, result = css + component css
        $handler = AssetHandler::getInstance();
        $handler->registerCss(self::CSS_FILE);

        $component = new Component('_Specimen/test-component.vue');
        $component->setName('test-component');
        $fileName = $handler->renderCss([$component]);
        $this->assertNotEmpty($fileName);
    }

    /**
     * @covers ::renderJs
     */
    public function testRenderJs_createsFile(): void
    {
        //FIXME: compare file contents
        $handler = AssetHandler::getInstance();
        $handler->registerJs(self::JS_FILE);
        $fileName = $handler->renderJs();
        $this->assertNotEmpty($fileName);
    }

    /**
     * @covers ::renderExternalJs
     */
    public function testRenderExternalJs_withComponentStyle_createsMergedFile(): void
    {
        $handler = AssetHandler::getInstance();
        $handler->registerExternalJs(self::JS_EXT_FILE);
        $fileString = $handler->renderExternalJs();
        $this->assertEquals('<script src="'.self::JS_EXT_FILE.'"></script>', $fileString);
    }
}