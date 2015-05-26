<?php namespace Neomerx\JsonApi\Parameters;

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

use \Neomerx\JsonApi\Contracts\Parameters\EncodingParametersInterface;

/**
 * @package Neomerx\JsonApi
 */
class EncodingParameters implements EncodingParametersInterface
{
    /**
     * @var string[]|null
     */
    private $includePaths;

    /**
     * @var array
     */
    private $pathIndexes;

    /**
     * @var array|null
     */
    private $fieldSets;

    /**
     * @var array|null
     */
    private $matchCache;

    /**
     * @param string[]|null $includePaths
     * @param array|null    $fieldSets
     */
    public function __construct($includePaths = null, array $fieldSets = null)
    {
        $this->fieldSets    = $fieldSets;
        $this->includePaths = $includePaths;

        if ($this->includePaths !== null) {
            assert('is_array($this->includePaths)');
            $this->pathIndexes = array_flip(array_values($this->includePaths));
            $this->matchCache  = [];
        }
    }

    /**
     * @inheritdoc
     */
    public function getIncludePaths()
    {
        return $this->includePaths;
    }

    /**
     * @inheritdoc
     */
    public function isPathIncluded($path)
    {
        return $this->pathIndexes === null || isset($this->pathIndexes[$path]) === true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldSets()
    {
        return $this->fieldSets;
    }

    /**
     * @inheritdoc
     */
    public function getFieldSet($type)
    {
        assert('is_string($type)');
        if ($this->fieldSets === null) {
            return null;
        } else {
            return (isset($this->fieldSets[$type]) === true ? $this->fieldSets[$type] : []);
        }
    }

    /**
     * @inheritdoc
     */
    public function hasMatchWithIncludedPaths($path)
    {
        $hasMatch = false;

        if ($this->includePaths !== null) {
            if (array_key_exists($path, $this->matchCache) === true) {
                $hasMatch = $this->matchCache[$path];
            } else {
                foreach ($this->includePaths as $targetPath) {
                    if (strpos($targetPath, $path) === 0) {
                        $hasMatch = true;
                        break;
                    }
                }
                $this->matchCache[$path] = $hasMatch;
            }
        }

        return $hasMatch;
    }
}
