<?php
/*
 *  Copyright notice
 *
 *  (c) 2014 Daniel Corn <info@cundd.net>, cundd
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author COD
 * Created 06.12.13 12:34
 */

namespace Cundd\Rest\Cache;

use Cundd\Rest\DataProvider\Utility;
use Cundd\Rest\Http\Header;
use Cundd\Rest\Http\RestRequestInterface;
use Cundd\Rest\ResponseFactory;
use Cundd\Rest\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The class caches responses of requests
 */
class Cache implements CacheInterface
{
    /**
     * Concrete cache instance
     *
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    private $cacheInstance;

    /**
     * Cache life time
     *
     * @var integer
     */
    private $cacheLifeTime;

    /**
     * Life time defined in the expires header
     *
     * @var integer
     */
    private $expiresHeaderLifeTime;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * Cache constructor
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Returns the cached value for the given request or NULL if it is not defined
     *
     * @param RestRequestInterface $request
     * @return ResponseInterface|null
     */
    public function getCachedValueForRequest(RestRequestInterface $request)
    {
        $cacheLifeTime = $this->getCacheLifeTime();

        /*
         * Use caching if the cache life time configuration is not -1, an API
         * path is given and the request is a read request
         */
        $useCaching = ($cacheLifeTime !== -1) && $request->getPath();
        if (!$useCaching) {
            return null;
        }

        $cacheInstance = $this->getCacheInstance();
        $responseData = $cacheInstance->get($this->getCacheKeyForRequest($request));
        if (!$responseData) {
            return null;
        }

        if (!$request->isRead()) {
            $this->clearCache($request);

            return null;
        }

        /** TODO: Send 304 status if appropriate */
        $response = $this->responseFactory->createResponse($responseData['content'], intval($responseData['status']));

        return $response
            ->withHeader(Header::CONTENT_TYPE, $responseData[Header::CONTENT_TYPE])
            ->withHeader(Header::LAST_MODIFIED, $responseData[Header::LAST_MODIFIED])
            ->withHeader(Header::EXPIRES, $this->getHttpDate(time() + $this->getExpiresHeaderLifeTime()))
            ->withHeader(Header::CUNDD_REST_CACHED, 'true');
    }

    /**
     * Sets the cache value for the given request
     *
     * @param RestRequestInterface $request
     * @param ResponseInterface    $response
     */
    public function setCachedValueForRequest(RestRequestInterface $request, ResponseInterface $response)
    {
        /** @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend $cacheInstance */
        $cacheInstance = null;

        // Don't cache write requests
        if ($request->isWrite()) {
            return;
        }

        $cacheLifeTime = $this->getCacheLifeTime();

        /*
         * Use caching if the cache life time configuration is not -1, an API
         * path is given and the request is a read request
         */
        $useCaching = ($cacheLifeTime !== -1) && $request->getPath();
        if (!$useCaching) {
            return;
        }

        $cacheInstance = $this->getCacheInstance();
        $cacheInstance->set(
            $this->getCacheKeyForRequest($request),
            array(
                'content'             => (string)$response->getBody(),
                'status'              => $response->getStatusCode(),
                Header::CONTENT_TYPE  => $response->getHeader(Header::CONTENT_TYPE),
                Header::LAST_MODIFIED => $this->getHttpDate(time()),
            ),
            $this->getTags($request),
            $cacheLifeTime
        );
    }

    /**
     * Returns the cache key for the given request
     *
     * @param RestRequestInterface $request
     * @return string
     */
    public function getCacheKeyForRequest(RestRequestInterface $request)
    {
        $cacheKey = sha1($request->getUri() . '_' . $request->getFormat() . '_' . $request->getMethod());
        $params = $request->getQueryParams();
        if ($request->getMethod() === 'GET' && count($params)) {
            $cacheKey = sha1($cacheKey . serialize($params));
        }

        return $cacheKey;
    }

    /**
     * Sets the cache life time
     *
     * @param int $cacheLifeTime
     * @return $this
     */
    public function setCacheLifeTime($cacheLifeTime)
    {
        $this->cacheLifeTime = $cacheLifeTime;

        return $this;
    }

    /**
     * Returns the cache life time
     *
     * @return int
     */
    public function getCacheLifeTime()
    {
        return $this->cacheLifeTime;
    }

    /**
     * Sets the life time defined in the expires header
     *
     * @param int $expiresHeaderLifeTime
     * @return $this
     */
    public function setExpiresHeaderLifeTime($expiresHeaderLifeTime)
    {
        $this->expiresHeaderLifeTime = $expiresHeaderLifeTime;

        return $this;
    }

    /**
     * Returns the life time defined in the expires header
     *
     * @return int
     */
    public function getExpiresHeaderLifeTime()
    {
        return $this->expiresHeaderLifeTime;
    }

    /**
     * Returns a date in the format for a HTTP header
     *
     * @param $date
     * @return string
     */
    private function getHttpDate($date)
    {
        return gmdate('D, d M Y H:i:s \G\M\T', $date);
    }

    /**
     * Returns the cache instance
     *
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface|\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    private function getCacheInstance()
    {
        if (!$this->cacheInstance) {
            /** @var CacheManager $cacheManager */
            $cacheManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
            $this->cacheInstance = $cacheManager->getCache('cundd_rest_cache');
        }

        return $this->cacheInstance;
    }

    /**
     * Sets the concrete Cache instance
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend $cacheInstance
     * @internal
     */
    public function setCacheInstance($cacheInstance)
    {
        $this->cacheInstance = $cacheInstance;
    }

    /**
     * Clears the cache for the current request
     *
     * @param RestRequestInterface $request
     */
    private function clearCache(RestRequestInterface $request)
    {
        $allTags = $this->getTags($request);
        $firstTag = $allTags[0];
        $this->getCacheInstance()->flushByTag($firstTag);
    }

    /**
     * Returns the tags for the current request
     *
     * @param RestRequestInterface $request
     * @return array [string]
     */
    private function getTags(RestRequestInterface $request)
    {
        $currentPath = $request->getPath();
        list($vendor, $extension, $model) = Utility::getClassNamePartsForResourceType($currentPath);

        return array(
            $vendor . '_' . $extension . '_' . $model,
            $extension . '_' . $model,
            $currentPath,
        );
    }
}
