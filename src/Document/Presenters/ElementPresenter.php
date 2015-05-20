<?php namespace Neomerx\JsonApi\Document\Presenters;

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

use \Neomerx\JsonApi\Document\Document;
use \Neomerx\JsonApi\Contracts\Schema\LinkObjectInterface;
use \Neomerx\JsonApi\Contracts\Schema\ResourceObjectInterface;
use \Neomerx\JsonApi\Contracts\Schema\PaginationLinksInterface;
use \Neomerx\JsonApi\Contracts\Document\DocumentLinksInterface;

/**
 * This is an auxiliary class for Document that help presenting elements.
 *
 * @package Neomerx\JsonApi
 */
class ElementPresenter
{
    /**
     * @param array                   $target
     * @param ResourceObjectInterface $parent
     * @param LinkObjectInterface     $current
     * @param mixed                   $url
     *
     * @return void
     */
    public function setRelationshipTo(
        array &$target,
        ResourceObjectInterface $parent,
        LinkObjectInterface $current,
        $url
    ) {
        $parentId     = $parent->getId();
        $parentType   = $parent->getType();
        $name         = $current->getName();
        $parentExists = isset($target[$parentType][$parentId]);

        assert('$parentExists === true');
        assert('isset($target[$parentType][$parentId][\''.Document::KEYWORD_RELATIONSHIPS.'\'][$name]) === false');

        if ($parentExists === true) {
            $target[$parentType][$parentId][Document::KEYWORD_RELATIONSHIPS][$name] = $url;
        }
    }

    /**
     * @param array                   $target
     * @param ResourceObjectInterface $parent
     * @param LinkObjectInterface     $link
     * @param ResourceObjectInterface $resource
     *
     * @return void
     */
    public function addRelationshipTo(
        array &$target,
        ResourceObjectInterface $parent,
        LinkObjectInterface $link,
        ResourceObjectInterface $resource
    ) {
        $parentId     = $parent->getId();
        $parentType   = $parent->getType();
        $parentExists = isset($target[$parentType][$parentId]);

        // parent might be already added to included to it won't be in 'target' buffer
        if ($parentExists === true) {
            $name = $link->getName();
            $alreadyGotLinkages = isset($target[$parentType][$parentId][Document::KEYWORD_RELATIONSHIPS][$name]);
            if ($alreadyGotLinkages === false) {
                // ... add the first one
                $target[$parentType][$parentId][Document::KEYWORD_RELATIONSHIPS][$name] =
                    $this->getLinkRepresentation($parent, $link, $resource);
            } else {
                // ... or add another linkage
                $target[$parentType][$parentId][Document::KEYWORD_RELATIONSHIPS][$name][Document::KEYWORD_LINKAGE][] =
                    $this->getLinkageRepresentation($resource);
            }
        }
    }

    /**
     * @param DocumentLinksInterface $links
     *
     * @return array
     */
    public function getDocumentLinksRepresentation(DocumentLinksInterface $links)
    {
        $representation = array_merge([
            Document::KEYWORD_SELF  => $links->getSelfUrl(),
        ], $this->getPaginationLinksRepresentation($links));

        return array_filter($representation, function ($value) {
            return $value !== null;
        });
    }

    /**
     * Correct empty or single relationships.
     *
     * @param array $resource
     *
     * @return array
     */
    public function correctRelationships(array $resource)
    {
        if (empty($resource[Document::KEYWORD_RELATIONSHIPS]) === false) {
            foreach ($resource[Document::KEYWORD_RELATIONSHIPS] as &$relation) {
                if (isset($relation[Document::KEYWORD_LINKAGE]) === true &&
                    empty($relation[Document::KEYWORD_LINKAGE]) === false &&
                    count($relation[Document::KEYWORD_LINKAGE]) === 1
                ) {
                    $tmp = $relation[Document::KEYWORD_LINKAGE][0];
                    unset($relation[Document::KEYWORD_LINKAGE][0]);
                    $relation[Document::KEYWORD_LINKAGE] = $tmp;
                }
            }
        } else {
            unset($resource[Document::KEYWORD_RELATIONSHIPS]);
        }

        return $resource;
    }

    /**
     * Convert resource object for 'data' section to array.
     *
     * @param ResourceObjectInterface $resource
     *
     * @return array
     */
    public function convertDataResourceToArray(ResourceObjectInterface $resource)
    {
        return $this->convertResourceToArray($resource, $resource->isShowSelf(), $resource->isShowMeta());
    }

    /**
     * Convert resource object for 'included' section to array.
     *
     * @param ResourceObjectInterface $resource
     *
     * @return array
     */
    public function convertIncludedResourceToArray(ResourceObjectInterface $resource)
    {
        return $this
            ->convertResourceToArray($resource, $resource->isShowSelfInIncluded(), $resource->isShowMetaInIncluded());
    }

    /**
     * @param string $url
     * @param string $subUrl
     *
     * @return string
     */
    public function concatUrls($url, $subUrl)
    {
        $urlEndsWithSlash   = (substr($url, -1) === '/');
        $subStartsWithSlash = (substr($subUrl, 0, 1) === '/');
        if ($urlEndsWithSlash === false && $subStartsWithSlash === false) {
            return $url . '/' . $subUrl;
        } elseif (($urlEndsWithSlash xor $subStartsWithSlash) === true) {
            return $url . $subUrl;
        } else {
            return rtrim($url, '/') . $subUrl;
        }
    }

    /**
     * @param ResourceObjectInterface $resource
     *
     * @return array<string,string>
     */
    private function getLinkageRepresentation(ResourceObjectInterface $resource)
    {
        $representation = [
            Document::KEYWORD_TYPE => $resource->getType(),
            Document::KEYWORD_ID   => $resource->getId(),
        ];
        if ($resource->isShowMetaInLinkage() === true) {
            $representation[Document::KEYWORD_META] = $resource->getMeta();
        }
        return $representation;
    }

    /**
     * @param ResourceObjectInterface $parent
     * @param LinkObjectInterface     $link
     * @param ResourceObjectInterface $resource
     *
     * @return array
     */
    private function getLinkRepresentation(
        ResourceObjectInterface $parent,
        LinkObjectInterface $link,
        ResourceObjectInterface $resource
    ) {
        assert(
            '$link->getName() !== \''.Document::KEYWORD_SELF.'\'',
            '"self" is a reserved keyword and cannot be used as a related resource link name'
        );

        $selfUrl = $parent->getSelfUrl();

        $representation = [];
        if ($link->isShowSelf() === true) {
            $representation[Document::KEYWORD_LINKS][Document::KEYWORD_SELF] =
                $this->concatUrls($selfUrl, $link->getSelfSubUrl());
        }

        if ($link->isShowRelated() === true) {
            $representation[Document::KEYWORD_LINKS][Document::KEYWORD_RELATED] =
                $this->concatUrls($selfUrl, $link->getRelatedSubUrl());
        }

        if ($link->isShowLinkage() === true) {
            $representation[Document::KEYWORD_LINKAGE][] = $this->getLinkageRepresentation($resource);
        }

        if ($link->isShowMeta() === true) {
            $representation[Document::KEYWORD_META] = $resource->getMeta();
        }

        if ($link->isShowPagination() === true) {
            if (empty( $representation[Document::KEYWORD_LINKS]) === true) {
                $representation[Document::KEYWORD_LINKS] =
                    $this->getPaginationLinksRepresentation($link->getPagination());
            } else {
                $representation[Document::KEYWORD_LINKS] +=
                    $this->getPaginationLinksRepresentation($link->getPagination());
            }
        }

        assert(
            '$link->isShowSelf() || $link->isShowRelated() || $link->isShowLinkage() || $link->isShowMeta()',
            'Specification requires at least one of them to be shown'
        );

        return $representation;
    }

    /**
     * @param PaginationLinksInterface $links
     *
     * @return array
     */
    private function getPaginationLinksRepresentation(PaginationLinksInterface $links)
    {
        return array_filter([
            Document::KEYWORD_FIRST => $links->getFirstUrl(),
            Document::KEYWORD_LAST  => $links->getLastUrl(),
            Document::KEYWORD_PREV  => $links->getPrevUrl(),
            Document::KEYWORD_NEXT  => $links->getNextUrl(),
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * Convert resource object to array.
     *
     * @param ResourceObjectInterface $resource
     * @param bool                    $isShowSelf
     * @param bool                    $isShowMeta
     *
     * @return array
     */
    private function convertResourceToArray(ResourceObjectInterface $resource, $isShowSelf, $isShowMeta)
    {
        assert('is_bool($isShowSelf) && is_bool($isShowMeta)');

        $representation = [
            Document::KEYWORD_TYPE => $resource->getType(),
            Document::KEYWORD_ID   => $resource->getId(),
        ];

        $attributes = $resource->getAttributes();
        assert(
            'isset($attributes[\''.Document::KEYWORD_TYPE.'\']) === false && '.
            'isset($attributes[\''.Document::KEYWORD_ID.'\']) === false',
            '"type" and "id" are reserved keywords and cannot be used as resource object attributes'
        );
        if (empty($attributes) === false) {
            $representation[Document::KEYWORD_ATTRIBUTES] = $attributes;
        }

        // reserve placeholder for relationships, otherwise it would be added after
        // links and meta which is not visually beautiful
        $representation[Document::KEYWORD_RELATIONSHIPS] = null;

        if ($isShowSelf === true) {
            $representation[Document::KEYWORD_LINKS][Document::KEYWORD_SELF] = $resource->getSelfUrl();
        }

        if ($isShowMeta === true) {
            $representation[Document::KEYWORD_META] = $resource->getMeta();
        }

        return $representation;
    }
}
