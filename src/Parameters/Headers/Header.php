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
use \Neomerx\JsonApi\Contracts\Parameters\Headers\HeaderInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;

/**
 * @package Neomerx\JsonApi
 */
class Header implements HeaderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var MediaTypeInterface[]
     */
    private $mediaTypes;

    /**
     * @param string $name
     * @param MediaTypeInterface[] $unsortedMediaTypes
     */
    public function __construct($name, $unsortedMediaTypes)
    {
        $name = trim($name);
        if (empty($name) === true) {
            throw new InvalidArgumentException('name');
        }

        if (is_array($unsortedMediaTypes) ===  false) {
            throw new InvalidArgumentException('unsortedMediaTypes');
        }

        usort($unsortedMediaTypes, $this->getMediaTypeCompareClosure());

        $this->name       = $name;
        $this->mediaTypes = $unsortedMediaTypes;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getSortedMediaTypes()
    {
        return $this->mediaTypes;
    }

    /**
     * Get best media type match for available media types.
     *
     * @param MediaTypeInterface[] $availableMediaTypes
     *
     * @return MediaTypeInterface|null
     */
    public function getBestMatch($availableMediaTypes)
    {
        if (is_array($availableMediaTypes) === false) {
            throw new InvalidArgumentException('availableMediaTypes');
        }

        foreach ($this->getSortedMediaTypes() as $headerMediaType) {
            foreach ($availableMediaTypes as $availableMediaType) {
                if ($this->mediaTypeMatchTo($availableMediaType, $headerMediaType) === true) {
                    return $availableMediaType;
                }
            }
        }

        return null;
    }

    /**
     * @param MediaTypeInterface $specificType
     * @param MediaTypeInterface $mightBeTemplateType
     *
     * @return bool
     */
    private function mediaTypeMatchTo(MediaTypeInterface $specificType, MediaTypeInterface $mightBeTemplateType)
    {
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
    private function isTypeMatches(MediaTypeInterface $specificType, MediaTypeInterface $mightBeTemplateType)
    {
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
    private function isSubTypeMatches(MediaTypeInterface $specificType, MediaTypeInterface $mightBeTemplateType)
    {
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
    private function isMediaParametersMatch(MediaTypeInterface $specificType, MediaTypeInterface $mightBeTemplateType)
    {
        if ($specificType->getMediaParameters() === null && $mightBeTemplateType->getMediaParameters() === null) {
            return true;
        } elseif ($specificType->getMediaParameters() !== null && $mightBeTemplateType->getMediaParameters() !== null) {
            $count     = count($specificType->getMediaParameters());
            $intersect = array_intersect(
                $specificType->getMediaParameters(),
                $mightBeTemplateType->getMediaParameters()
            );

            return ($count === count($intersect));
        }

        return false;
    }

    /**
     * @return Closure
     */
    private function getMediaTypeCompareClosure()
    {
        return function (MediaTypeInterface $lhs, MediaTypeInterface $rhs) {
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

            return $this->compareParameters($lhs->getMediaParameters(), $rhs->getMediaParameters());
        };
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

    /**
     * Parse header.
     *
     * @param string $header
     *
     * @return HeaderInterface
     */
    public static function parse($header)
    {
        $input  = explode(':', $header, 2);

        if (isset($input[1]) === false) {
            throw new InvalidArgumentException('header');
        }

        $mediaTypes = [];
        $name       = $input[0];
        $ranges     = preg_split("/,(?=([^\"]*\"[^\"]*\")*[^\"]*$)/", $input[1]);
        foreach ($ranges as $range) {
            $fields = explode(';', $range);

            if (strpos($fields[0], '/') === false) {
                throw new InvalidArgumentException('header');
            }

            list($type, $subType) = explode('/', $fields[0], 2);
            list($mediaParameters, $quality, $extensionParameters) = self::parseQualityAndParameters($fields);

            $mediaTypes[] = new MediaType($type, $subType, $mediaParameters, $quality, $extensionParameters);
        }

        return new self($name, $mediaTypes);
    }

    /**
     * @param string $fields
     *
     * @return array
     */
    private static function parseQualityAndParameters($fields)
    {
        $quality             = 1;
        $qParamFound         = false;
        $mediaParameters     = null;
        $extensionParameters = null;

        for ($idx = 1; $idx < count($fields); ++$idx) {
            if (strpos($fields[$idx], '=') === false) {
                throw new InvalidArgumentException('header');
            }

            list($key, $value) = explode('=', $fields[$idx], 2);

            $key   = trim($key);
            $value = trim($value, ' "');

            // 'q' param separates media parameters from extension parameters

            if ($key === 'q' && $qParamFound === false) {
                $quality     = (float)$value;
                $qParamFound = true;
                continue;
            }

            $qParamFound === false ? $mediaParameters[$key] = $value : $extensionParameters[$key] = $value;
        }

        return [$mediaParameters, $quality, $extensionParameters];
    }
}
