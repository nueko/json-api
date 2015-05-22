<?php namespace Neomerx\JsonApi\Parameters\Headers;

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

use \Closure;
use \InvalidArgumentException;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\AcceptHeaderInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\AcceptMediaTypeInterface;

/**
 * @package Neomerx\JsonApi
 */
class AcceptHeader extends Header implements AcceptHeaderInterface
{
    /**
     * @param string $name
     * @param AcceptMediaTypeInterface[] $unsortedMediaTypes
     */
    public function __construct($name, $unsortedMediaTypes)
    {

        if (is_array($unsortedMediaTypes) ===  false) {
            throw new InvalidArgumentException('unsortedMediaTypes');
        }

        usort($unsortedMediaTypes, $this->getMediaTypeCompareClosure());

        parent::__construct($name, $unsortedMediaTypes);
    }

    /**
     * Get best media type match for available media types.
     *
     * @param MediaTypeInterface[] $availableMediaTypes
     *
     * @return AcceptMediaTypeInterface|null
     */
    public function getBestMatch($availableMediaTypes)
    {
        if (is_array($availableMediaTypes) === false) {
            throw new InvalidArgumentException('availableMediaTypes');
        }

        foreach ($this->getMediaTypes() as $headerMediaType) {
            /** @var AcceptMediaTypeInterface $headerMediaType */
            foreach ($availableMediaTypes as $availableMediaType) {
                if ($this->mediaTypeMatchTo($availableMediaType, $headerMediaType) === true) {
                    return $availableMediaType;
                }
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     *
     * @return AcceptHeaderInterface
     */
    public static function parse($name, $header)
    {
        return parent::parse($name, $header);
    }

    /**
     * @inheritdoc
     *
     * @return AcceptMediaTypeInterface
     */
    protected static function parseMediaType($position, $mediaType)
    {
        return AcceptMediaType::parse($position, $mediaType);
    }

    /**
     * @return Closure
     */
    private function getMediaTypeCompareClosure()
    {
        return function (AcceptMediaTypeInterface $lhs, AcceptMediaTypeInterface $rhs) {
            $qualityCompare = $this->compareQuality($lhs->getQuality(), $rhs->getQuality());
            if ($qualityCompare !== 0) {
                return $qualityCompare;
            }

            $typeCompare = $this->compareStrings($lhs->getType(), $rhs->getType());
            if ($typeCompare !== 0) {
                return $typeCompare;
            }

            $subTypeCompare = $this->compareStrings($lhs->getSubType(), $rhs->getSubType());
            if ($subTypeCompare !== 0) {
                return $subTypeCompare;
            }

            $parametersCompare = $this->compareParameters($lhs->getParameters(), $rhs->getParameters());
            if ($parametersCompare !== 0) {
                return $parametersCompare;
            }

            return ($lhs->getPosition() - $rhs->getPosition());
        };
    }

    /**
     * @param MediaTypeInterface $specificType
     * @param MediaTypeInterface $mightBeTemplateType
     *
     * @return bool
     */
    private function mediaTypeMatchTo(
        MediaTypeInterface $specificType,
        MediaTypeInterface $mightBeTemplateType
    ) {
        return
            $this->isTypeMatches($specificType, $mightBeTemplateType) &&
            $this->isSubTypeMatches($specificType, $mightBeTemplateType) &&
            $this->isMediaParametersMatch($specificType, $mightBeTemplateType);
    }

    /**
     * @param MediaTypeInterface $specificType
     * @param MediaTypeInterface $mightBeTemplateType
     *
     * @return bool
     */
    private function isTypeMatches(
        MediaTypeInterface $specificType,
        MediaTypeInterface $mightBeTemplateType
    ) {
        return
            $specificType->getType() === $mightBeTemplateType->getType() ||
            $mightBeTemplateType->getType() === '*';
    }

    /**
     * @param MediaTypeInterface $specificType
     * @param MediaTypeInterface $mightBeTemplateType
     *
     * @return bool
     */
    private function isSubTypeMatches(
        MediaTypeInterface $specificType,
        MediaTypeInterface $mightBeTemplateType
    ) {
        return
            $specificType->getSubType() === $mightBeTemplateType->getSubType() ||
            $mightBeTemplateType->getSubType() === '*';
    }

    /**
     * @param MediaTypeInterface $specificType
     * @param MediaTypeInterface $mightBeTemplateType
     *
     * @return bool
     */
    private function isMediaParametersMatch(
        MediaTypeInterface $specificType,
        MediaTypeInterface $mightBeTemplateType
    ) {
        if ($specificType->getParameters() === null && $mightBeTemplateType->getParameters() === null) {
            return true;
        } elseif ($specificType->getParameters() !== null && $mightBeTemplateType->getParameters() !== null) {
            $count     = count($specificType->getParameters());
            $intersect = array_intersect(
                $specificType->getParameters(),
                $mightBeTemplateType->getParameters()
            );

            return ($count === count($intersect));
        }

        return false;
    }

    /**
     * @param float $lhs
     * @param float $rhs
     *
     * @return int
     */
    private function compareQuality($lhs, $rhs)
    {
        $qualityDiff = $lhs - $rhs;

        // rfc2616: 3 digits are meaningful (#3.9 Quality Values)
        if (abs($qualityDiff) < 0.001) {
            return 0;
        } else {
            return $lhs > $rhs ? -1 : 1;
        }
    }

    /**
     * @param string $lhs
     * @param string $rhs
     *
     * @return int
     */
    private function compareStrings($lhs, $rhs)
    {
        return ($rhs !== '*' ? 1 : 0) - ($lhs !== '*' ? 1 : 0);
    }

    /**
     * @param array|null $lhs
     * @param array|null $rhs
     *
     * @return int
     */
    private function compareParameters($lhs, $rhs)
    {
        return (empty($lhs) !== false ? 1 : 0) - (empty($rhs) !== false ? 1 : 0);
    }
}
