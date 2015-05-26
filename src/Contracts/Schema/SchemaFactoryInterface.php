<?php namespace Neomerx\JsonApi\Contracts\Schema;

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

/**
 * @package Neomerx\JsonApi
 */
interface SchemaFactoryInterface
{
    /**
     * Create schema provider container.
     *
     * @param array $providers
     *
     * @return ContainerInterface
     */
    public function createContainer(array $providers = []);

    /**
     * Create resource object.
     *
     * @param bool   $isInArray
     * @param string $type
     * @param string $idx
     * @param array  $attributes
     * @param mixed  $meta
     * @param string $selfUrl
     * @param bool   $isShowSelf
     * @param bool   $isShowMeta
     * @param bool   $isShowSelfInIncluded
     * @param bool   $isShowLinksInIncluded
     * @param bool   $isShowMetaInIncluded
     * @param bool   $isShowMetaInRlShips
     *
     * @return ResourceObjectInterface
     */
    public function createResourceObject(
        $isInArray,
        $type,
        $idx,
        array $attributes,
        $meta,
        $selfUrl,
        $isShowSelf,
        $isShowMeta,
        $isShowSelfInIncluded,
        $isShowLinksInIncluded,
        $isShowMetaInIncluded,
        $isShowMetaInRlShips
    );

    /**
     * Create relationship object.
     *
     * @param string                        $name
     * @param object|array|null             $data
     * @param LinkInterface                 $selfLink
     * @param LinkInterface                 $relatedLink
     * @param bool                          $isShowAsRef
     * @param bool                          $isShowSelf
     * @param bool                          $isShowRelated
     * @param bool                          $isShowData
     * @param bool                          $isShowMeta
     * @param bool                          $isShowPagination
     * @param PaginationLinksInterface|null $pagination
     *
     * @return RelationshipObjectInterface
     */
    public function createRelationshipObject(
        $name,
        $data,
        LinkInterface $selfLink,
        LinkInterface $relatedLink,
        $isShowAsRef,
        $isShowSelf,
        $isShowRelated,
        $isShowData,
        $isShowMeta,
        $isShowPagination,
        $pagination
    );

    /**
     * Create pagination links.
     *
     * @param string|null $firstUrl
     * @param string|null $lastUrl
     * @param string|null $prevUrl
     * @param string|null $nextUrl
     *
     * @return PaginationLinksInterface
     */
    public function createPaginationLinks($firstUrl = null, $lastUrl = null, $prevUrl = null, $nextUrl = null);

    /**
     * Create link.
     *
     * @param string            $subHref
     * @param array|object|null $meta
     *
     * @return LinkInterface
     */
    public function createLink($subHref, $meta = null);
}
