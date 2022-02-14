<?php

namespace PhoenixPhp\Core;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Core\Parser
 */
class ParserTest extends TestCase
{

    //---- CONSTANTS

    const TEMPLATE_FILE = '_Specimen/Template.html';
    const SUBTEMPLATE_FILE = '_Specimen/Subtemplate.html';


    //---- TESTS

    /**
     * @covers ::__construct
     */
    public function testConstruct_InvalidFileName_ThrowsException()
    {
        $this->expectException('PhoenixPhp\Core\Exception');
        $result = new Parser('Pikachu');
    }

    /**
     * @covers ::__construct
     * @covers ::setFileName
     * @covers ::setParsed
     * @covers ::setOriginal
     */
    public function testConstruct_ValidFileName_ReturnsObject()
    {
        $result = new Parser(self::TEMPLATE_FILE);
        $this->assertInstanceOf('\PhoenixPhp\Core\Parser', $result);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct_InvalidSubTemplate_ThrowsException()
    {
        $this->expectException('\PhoenixPhp\Core\Exception');
        $result = new Parser(self::SUBTEMPLATE_FILE, 'Pikachu');
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct_ValidSubTemplate_ReturnsObject()
    {
        $result = new Parser(self::SUBTEMPLATE_FILE, 'SUBTEMPLATE');
        $this->assertInstanceOf('PhoenixPhp\Core\Parser', $result);
    }

    /**
     * @covers ::parse
     * @covers ::getFileName
     */
    public function testParse_InvalidPlaceholder_ThrowsException()
    {
        $this->expectException('PhoenixPhp\Core\Exception');
        $tpl = new Parser(self::TEMPLATE_FILE);
        $tpl->parse('Pikachu', 'Pikachu');
    }

    /**
     * @covers ::parse
     */
    public function testGetTemplate_ResettingParser_EqualsOriginal()
    {
        $testString = 'Pikachu';
        $tpl = new Parser(self::TEMPLATE_FILE);
        $tpl->parse('CONTENT', $testString);
        $tpl->retrieveTemplate();
        $tpl2 = new Parser(self::TEMPLATE_FILE);
        $this->assertEquals($tpl, $tpl2);
    }

    /**
     * @covers ::parse
     * @covers ::retrieveTemplate
     * @covers ::getOriginal
     * @covers ::getParsed
     */
    public function testParse_ValidPlaceholder_FillsContent()
    {
        $testString = 'Pikachu';
        $tpl = new Parser(self::TEMPLATE_FILE);
        $tpl->parse('CONTENT', $testString);
        $result = $tpl->retrieveTemplate();
        $this->assertStringContainsString($testString, $result);
    }

}