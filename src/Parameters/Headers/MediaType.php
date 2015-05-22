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

use \InvalidArgumentException;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;

/**
 * @package Neomerx\JsonApi
 */
class MediaType implements MediaTypeInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $subType;

    /**
     * @var string
     */
    private $mediaType;

    /**
     * @var array<string,string>|null
     */
    private $mediaParameters;

    /**
     * @var float [0..1]
     */
    private $quality;

    /**
     * @var array<string,string>|null
     */
    private $extensionParameters;

    /**
     * @param string                    $type
     * @param string                    $subType
     * @param array<string,string>|null $mediaParameters
     * @param float                     $quality
     * @param array<string,string>|null $extensionParameters
     */
    public function __construct($type, $subType, $mediaParameters = null, $quality = 1.0, $extensionParameters = null)
    {
        $type = trim($type);
        if (empty($type) === true) {
            throw new InvalidArgumentException('type');
        }

        $subType = trim($subType);
        if (empty($subType) === true) {
            throw new InvalidArgumentException('subType');
        }

        if ($mediaParameters !== null && is_array($mediaParameters) === false) {
            throw new InvalidArgumentException('mediaParameters');
        }

        // rfc2616: 3 digits are meaningful (#3.9 Quality Values)
        $quality = floor((float)$quality * 1000) / 1000;
        if ($quality < 0 || $quality > 1) {
            throw new InvalidArgumentException('quality');
        }

        if ($extensionParameters !== null && is_array($extensionParameters) === false) {
            throw new InvalidArgumentException('extensionParameters');
        }

        $this->type                = $type;
        $this->subType             = $subType;
        $this->mediaType           = $type . '/' . $subType;
        $this->mediaParameters     = $mediaParameters;
        $this->quality             = $quality;
        $this->extensionParameters = $extensionParameters;
    }


    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @inheritdoc
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * @inheritdoc
     */
    public function getMediaParameters()
    {
        return $this->mediaParameters;
    }

    /**
     * @inheritdoc
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionParameters()
    {
        return $this->extensionParameters;
    }
}
