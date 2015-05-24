<?php namespace Neomerx\JsonApi\Contracts\Encoder;

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

use \Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use \Neomerx\JsonApi\Contracts\Document\DocumentLinksInterface;
use \Neomerx\JsonApi\Contracts\Parameters\EncodingParametersInterface;

/**
 * @package Neomerx\JsonApi
 */
interface EncoderInterface
{
    /**
     * Encode input as JSON API string.
     *
     * @param object|array                     $data     Data to encode.
     * @param DocumentLinksInterface|null      $links    Optional document links information (e.g. request URL, paging).
     * @param array|object|null                $meta     Optional document meta information.
     * @param EncodingParametersInterface|null $parameters Encoding parameters.
     *
     * @return string
     */
    public function encode(
        $data,
        DocumentLinksInterface $links = null,
        $meta = null,
        EncodingParametersInterface $parameters = null
    );
    /**
     * Encode input meta as JSON API string.
     *
     * @param array|object $meta Meta information.
     *
     * @return string
     */
    public function meta($meta);

    /**
     * Encode error as JSON API string.
     *
     * @param ErrorInterface $error
     *
     * @return string
     */
    public function error(ErrorInterface $error);

    /**
     * Encode errors as JSON API string.
     *
     * @param ErrorInterface[] $errors
     *
     * @return string
     */
    public function errors($errors);
}
