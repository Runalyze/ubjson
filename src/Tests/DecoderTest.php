<?php

/*
 * This file is part of the Runalyze/Ubjson.
 * (c) RUNALYZE <mail@runalyze.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Runalyze\Ubjson\Tests;

use PHPUnit\Framework\TestCase;
use Runalyze\Ubjson\Decoder;

class DecoderTest extends TestCase
{
    public function testEmptyString()
    {
        $decoder = new Decoder('');

        $this->assertNull($decoder->decode());
    }

    /**
     * Expected *.ubj are not from the ubjson repo as that contains draft8, not draft12
     *
     * @see https://github.com/ubjson/universal-binary-json-java/tree/master/src/test/resources/org/ubjson
     */
    public function testMediaContentExample()
    {
        $expected = json_decode(file_get_contents(__DIR__.'/resources/MediaContent.json'), true);
        $decoder = new Decoder(file_get_contents(__DIR__.'/resources/MediaContent.draft12.ubj'));
        $result = $decoder->decode();

        $this->assertEquals($expected, $result, '', 0.001);
    }
}
