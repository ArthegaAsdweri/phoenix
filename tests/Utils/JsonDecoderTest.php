<?php

namespace PhoenixPhp\Utils;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhoenixPhp\Utils\JsonDecoder
 */
class JsonDecoderTest extends TestCase
{

    //---- TESTS

    /**
     * @covers ::decode
     */
    public function testDecode_ValidString_ReturnsArray(): void
    {
        $decoder = new JsonDecoder();
        $jsonArray = $decoder->decode('{"key":"value"}');
        $this->assertEquals(['key' => 'value'], $jsonArray);
    }

    /**
     * @covers ::decode
     * @covers ::setError
     * @covers ::getError
     */
    public function testDecode_InvalidJson_ReturnsNull(): void
    {
        $decoder = new JsonDecoder();
        $jsonArray = $decoder->decode('Pikachu');
        $this->assertNull($jsonArray);
        $this->assertEquals('syntax error', $decoder->getError());
    }

}