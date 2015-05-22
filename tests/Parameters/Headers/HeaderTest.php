<?php namespace Neomerx\Tests\JsonApi\Parameters\Headers;

/**
 * Copyright 2015 info@neomerx.com (www.neomerx.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Neomerx\JsonApi\Parameters\Headers\MediaType;
use \Neomerx\Tests\JsonApi\BaseTestCase;
use \Neomerx\JsonApi\Parameters\Headers\Header;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\HeaderInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;

/**
 * @package Neomerx\Tests\JsonApi
 */
class HeaderTest extends BaseTestCase
{
    /**
     * Test parse header.
     */
    public function testParseHeaderNameAndQualityAndParameters()
    {
        $input = ' Accept: foo/bar.baz;media=param;q=0.5;ext="ext1,ext2", type/*, */*';
        $this->checkSorting([
            'type/*',
            '*/*',
            'foo/bar.baz',
        ], $header = Header::parse($input));

        $this->assertEquals('Accept', $header->getName());

        $this->assertEquals('type', $header->getSortedMediaTypes()[0]->getType());
        $this->assertEquals('*', $header->getSortedMediaTypes()[0]->getSubType());
        $this->assertEquals('type/*', $header->getSortedMediaTypes()[0]->getMediaType());
        $this->assertEquals(1, $header->getSortedMediaTypes()[0]->getQuality());
        $this->assertEquals(null, $header->getSortedMediaTypes()[0]->getMediaParameters());

        $this->assertEquals('*', $header->getSortedMediaTypes()[1]->getType());
        $this->assertEquals('*', $header->getSortedMediaTypes()[1]->getSubType());
        $this->assertEquals('*/*', $header->getSortedMediaTypes()[1]->getMediaType());
        $this->assertEquals(1, $header->getSortedMediaTypes()[1]->getQuality());
        $this->assertEquals(null, $header->getSortedMediaTypes()[1]->getMediaParameters());

        $this->assertEquals('foo', $header->getSortedMediaTypes()[2]->getType());
        $this->assertEquals('bar.baz', $header->getSortedMediaTypes()[2]->getSubType());
        $this->assertEquals('foo/bar.baz', $header->getSortedMediaTypes()[2]->getMediaType());
        $this->assertEquals(0.5, $header->getSortedMediaTypes()[2]->getQuality());
        $this->assertEquals(['media' => 'param'], $header->getSortedMediaTypes()[2]->getMediaParameters());
        $this->assertEquals(['ext' => 'ext1,ext2'], $header->getSortedMediaTypes()[2]->getExtensionParameters());
    }

    /**
     * Test rfc2616 #3.9 (3 meaningful digits for quality)
     */
    public function testParserHeaderRfc2616P3p9Part1()
    {
        $input = 'Accept: type1/*;q=0.5001, type2/*;q=0.5009';
        $header = Header::parse($input);

        $params = [
            $header->getSortedMediaTypes()[0]->getMediaType() => $header->getSortedMediaTypes()[0]->getQuality(),
            $header->getSortedMediaTypes()[1]->getMediaType() => $header->getSortedMediaTypes()[1]->getQuality(),
        ];

        $this->assertCount(2, array_intersect(['type1/*' => 0.5, 'type2/*' => 0.5], $params));
    }

    /**
     * Test rfc2616 #3.9 (3 meaningful digits for quality)
     */
    public function testParserHeaderRfc2616P3p9Part2()
    {
        $input = 'Accept: type1/*;q=0.501, type2/*;q=0.509';
        $header = Header::parse($input);

        $params = [
            $header->getSortedMediaTypes()[0]->getMediaType() => $header->getSortedMediaTypes()[0]->getQuality(),
            $header->getSortedMediaTypes()[1]->getMediaType() => $header->getSortedMediaTypes()[1]->getQuality(),
        ];

        $this->assertCount(2, array_intersect(['type1/*' => 0.501, 'type2/*' => 0.509], $params));
    }

    /**
     * Test sample from RFC.
     */
    public function testParseHeaderRfcSample1()
    {
        $input = 'Accept: audio/*; q=0.2, audio/basic';
        $this->checkSorting([
            'audio/basic',
            'audio/*',
        ], $header = Header::parse($input));
    }

    /**
     * Test sample from RFC.
     */
    public function testParseHeaderRfcSample2()
    {
        $input  = 'Accept: text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c';
        $header = Header::parse($input);

        $this->assertEquals('text/x-dvi', $header->getSortedMediaTypes()[2]->getMediaType());
        $this->assertEquals('text/plain', $header->getSortedMediaTypes()[3]->getMediaType());
    }

    /**
     * Test sample from RFC.
     */
    public function testParseHeaderRfcSample3()
    {
        $input = 'Accept: text/*, text/html, text/html;level=1, */*';
        $this->checkSorting([
            'text/html',
            'text/html',
            'text/*',
            '*/*',
        ], $header = Header::parse($input));

        $this->assertEquals(['level' => '1'], $header->getSortedMediaTypes()[0]->getMediaParameters());
        $this->assertEquals(null, $header->getSortedMediaTypes()[1]->getMediaParameters());
    }

    /**
     * Test sample from RFC.
     */
    public function testParseHeaderRfcSample4()
    {
        $input = 'Accept: text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5';
        $header = Header::parse($input);
        $this->checkSorting([
            'text/html',
            'text/html',
            '*/*',
            'text/html',
            'text/*',
        ], $header);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader1()
    {
        Header::parse(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader2()
    {
        Header::parse('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader3()
    {
        Header::parse('Accept');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader4()
    {
        Header::parse('Accept: ');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader5()
    {
        Header::parse('Accept: foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader6()
    {
        Header::parse('Accept: foo/bar; baz');
    }

    /**
     * Test invalid constructor parameters.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstructorParams1()
    {
        new Header(null, []);
    }

    /**
     * Test invalid constructor parameters.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstructorParams2()
    {
        new Header('name', null);
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch1()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: type1/subtype2');

        $best = $header->getBestMatch($availableTypes);
        $this->assertEquals('type1/subtype2', $best->getMediaType());
        $this->assertEquals(null, $best->getMediaParameters());
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch2()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: type1/subtype2;q=0.4, type1/subtype2;ext="ext1,ext3";q=0.8');

        $best = $header->getBestMatch($availableTypes);
        $this->assertEquals('type1/subtype2', $best->getMediaType());
        $this->assertEquals(['ext' => 'ext1,ext3'], $best->getMediaParameters());
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch3()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: type1/*;ext="ext1,ext3"');

        $best = $header->getBestMatch($availableTypes);
        $this->assertEquals('type1/subtype2', $best->getMediaType());
        $this->assertEquals(['ext' => 'ext1,ext3'], $best->getMediaParameters());
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch4()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: */*;ext="ext1,ext3"');

        $best = $header->getBestMatch($availableTypes);
        $this->assertEquals('type1/subtype2', $best->getMediaType());
        $this->assertEquals(['ext' => 'ext1,ext3'], $best->getMediaParameters());
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch6()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: type2/*');

        $best = $header->getBestMatch($availableTypes);
        $this->assertEquals('type2/subtype1', $best->getMediaType());
        $this->assertEquals(null, $best->getMediaParameters());
    }

    /**
     * Test get best match.
     */
    public function testGetBestMatch7()
    {
        $availableTypes = [
            new MediaType('type1', 'subtype1'),
            new MediaType('type1', 'subtype2'),
            new MediaType('type1', 'subtype2', ['ext' => 'ext1,ext3']),
            new MediaType('type2', 'subtype1'),
        ];

        $header = Header::parse('Accept: type2/*;ext="ext1,ext3"');

        $best = $header->getBestMatch($availableTypes);
        $this->assertNull($best);
    }

    /**
     * Test get best match.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testGetBestMatchInvalidParam()
    {
        $header = Header::parse('Accept: type2/*;ext="ext1,ext3"');
        $header->getBestMatch(null);
    }

    /**
     * @param string[]        $mediaTypes
     * @param HeaderInterface $header
     *
     * @return void
     */
    private function checkSorting($mediaTypes, HeaderInterface $header)
    {
        $this->assertEquals($count = count($mediaTypes), count($sorted = $header->getSortedMediaTypes()));

        for ($idx = 0; $idx < $count; ++$idx) {
            /** @var MediaTypeInterface $mediaType */
            $mediaType = $sorted[$idx];
            $this->assertEquals($mediaTypes[$idx], $mediaType->getMediaType());
        }
    }
}
