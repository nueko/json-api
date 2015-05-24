<?php namespace Neomerx\Tests\JsonApi\Parameters;

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

use \Mockery;
use \Neomerx\Tests\JsonApi\BaseTestCase;
use \Neomerx\JsonApi\Parameters\ParametersFactory;
use \Neomerx\JsonApi\Contracts\Parameters\SortParameterInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\HeaderInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\AcceptHeaderInterface;

/**
 * @package Neomerx\Tests\JsonApi
 */
class FactoryTest extends BaseTestCase
{
    /**
     * @var ParametersFactoryInterface
     */
    private $factory;

    /**
     * Set up.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->factory  = new ParametersFactory();
    }

    /**
     * Test create media type.
     */
    public function testCreateMediaType()
    {
        $this->assertNotNull($type = $this->factory->createMediaType(
            $mediaType = 'media',
            $mediaSubType = 'type.abc',
            $parameters = ['someKey' => 'ext1,ext2']
        ));

        $this->assertEquals("$mediaType/$mediaSubType", $type->getMediaType());
        $this->assertEquals($parameters, $type->getParameters());
    }

    /**
     * Test create parameters.
     */
    public function testCreateParameters()
    {
        /** @var HeaderInterface $contentTypeHeader */
        $contentTypeHeader = Mockery::mock(HeaderInterface::class);
        /** @var AcceptHeaderInterface $acceptHeader */
        $acceptHeader = Mockery::mock(AcceptHeaderInterface::class);
        /** @var SortParameterInterface $sortParam */
        $sortParam = Mockery::mock(SortParameterInterface::class);
        $this->assertNotNull($parameters = $this->factory->createParameters(
            $contentTypeHeader,
            $acceptHeader,
            $includePaths = ['p1', 'p2'],
            $fieldSets = ['s1' => ['value11', 'value12']],
            $sortParameters = [$sortParam],
            $pagingParameters = ['first' => 5, 'page' => 30],
            $filteringParameters = ['some' => 'filter']
        ));

        $this->assertSame($contentTypeHeader, $parameters->getContentTypeHeader());
        $this->assertSame($acceptHeader, $parameters->getAcceptHeader());
        $this->assertEquals($includePaths, $parameters->getIncludePaths());
        $this->assertEquals($fieldSets, $parameters->getFieldSets());
        $this->assertEquals($sortParameters, $parameters->getSortParameters());
        $this->assertEquals($pagingParameters, $parameters->getPaginationParameters());
        $this->assertEquals($filteringParameters, $parameters->getFilteringParameters());
    }

    /**
     * Test create encoding parameters.
     */
    public function testCreateEncodingParameters()
    {
        $this->assertNotNull($parameters = $this->factory->createEncodingParameters(
            $includePaths = ['p1', 'p2'],
            $fieldSets    = ['s1' => ['value11', 'value12']]
        ));

        $this->assertEquals($includePaths, $parameters->getIncludePaths());
        $this->assertEquals($fieldSets, $parameters->getFieldSets());
    }

    /**
     * Test create parameters parser.
     */
    public function testCreateParametersParser()
    {
        $this->assertNotNull($this->factory->createParametersParser());
    }

    /**
     * Test create sort parameter.
     */
    public function testCreateSortParameter()
    {
        $this->assertNotNull($parameter = $this->factory->createSortParam(
            $sortField   = 'field1',
            $isAscending = true
        ));

        $this->assertEquals($sortField, $parameter->getField());
        $this->assertEquals($isAscending, $parameter->isAscending());
        $this->assertEquals(!$isAscending, $parameter->isDescending());
    }

    /**
     * Test create supported extensions.
     */
    public function testCreateSupportedExtensions()
    {
        $this->assertNotNull($supported = $this->factory->createSupportedExtensions(
            $extensions = 'ext1,ext2'
        ));

        $this->assertEquals($extensions, $supported->getExtensions());
    }
}
