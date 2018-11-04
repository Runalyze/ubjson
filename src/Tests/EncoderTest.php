<?php

/*
 * This file is part of the Runalyze/Ubjson.
 * (c) RUNALYZE <mail@runalyze.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Runalyze\Ubjson\Tests;

use PHPUnit\Framework\TestCase;
use Runalyze\Ubjson\Encoder;

class EncoderTest extends TestCase
{
    /** @var Encoder */
    protected $Encoder;

    public function setUp()
    {
        $this->Encoder = new Encoder();
    }

    /**
     * Expected *.ubj are not from the ubjson repo as that contains draft8, not draft12
     *
     * @see https://github.com/ubjson/universal-binary-json-java/tree/master/src/test/resources/org/ubjson
     */
    public function testMediaContentExample()
    {
        $expected = file_get_contents(__DIR__.'/resources/MediaContent.draft12.ubj');
        $json = json_decode(file_get_contents(__DIR__.'/resources/MediaContent.json'), true);
        $result = $this->Encoder->encode($json);

        file_put_contents(__DIR__.'/resources/MediaContent.draft12.ubj', $result);

        $this->assertEquals($expected, $result);
    }
}
