<?php

namespace Mikeevstropov\VkParser;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

class ExtendedVideoParser extends VideoParser
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger = null,
        CacheInterface $cache = null
    ) {
        parent::__construct($client, $logger);

        $this->cache = $cache;
    }

    /**
     * Get the cache key of the source list
     *
     * @param string $ownerId Owner ID
     * @param string $id      Video ID
     *
     * @return string
     */
    protected function getSourceListCacheKey(
        $ownerId,
        $id
    ) {
        Assert::stringNotEmpty(
            $ownerId,
            'To get the cache key of the source list, video parser is require an argument "ownerId" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $id,
            'To get the cache key of the source list, video parser is require an argument "id" as not empty string, %s given.'
        );

        return 'parser.video.source_list.'. md5($ownerId . $id);
    }

    /**
     * To cache the source list
     *
     * @param null|false|array $sourceList Source list of the video
     * @param string           $ownerId    Owner ID
     * @param string           $id         Video ID
     * @param null|int         $cacheTtl   Cache TTL
     *
     * @return true
     */
    protected function setSourceListCache(
        $sourceList,
        $ownerId,
        $id,
        $cacheTtl = null
    ) {
        if (
            !is_array($sourceList)
            && !is_null($sourceList)
            && $sourceList !== false
        ) {
            throw new \InvalidArgumentException(sprintf(
                'To cache the source list, video parser is require an argument "sourceList" as null, false or an array, %s given.'
            ));
        }

        Assert::stringNotEmpty(
            $ownerId,
            'To cache the source list, video parser is require an argument "ownerId" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $id,
            'To cache the source list, video parser is require an argument "id" as not empty string, %s given.'
        );

        Assert::nullOrGreaterThan(
            $cacheTtl,
            0,
            'To cache the source list, video parser is require an argument "cacheTtl" as null or a positive integer, %s given.'
        );

        Assert::implementsInterface(
            $this->cache,
            CacheInterface::class,
            'To cache the source list, video parser is require injection "cache" as instance of interface %2$s, %s given.'
        );

        $cacheKey = $this->getSourceListCacheKey(
            $ownerId,
            $id
        );

        $this->logger->debug(sprintf(
            'To cache the source list, video parser will use the cache key is "%s".',
            $cacheKey
        ));

        Assert::stringNotEmpty(
            $cacheKey,
            'To cache the source list, video parser is require the cache key as not empty string, %s given.'
        );

        $sourceListJson = json_encode($sourceList);

        Assert::stringNotEmpty(
            $sourceListJson,
            'To cache the source list, video parser was unable to convert the list to JSON, %s given.'
        );

        $this->cache->set(
            $cacheKey,
            $sourceListJson,
            $cacheTtl
        );

        $this->logger->debug(sprintf(
            $cacheTtl
                ? 'To cache the source list, video parser saved the list in JSON to the cache that expired after %s.'
                : 'To cache the source list, video parser saved the list in JSON to the cache that never expire',
            date('F j, Y, g:i a', time() + $cacheTtl)
        ));

        return true;
    }

    /**
     * Get the source list from cache
     *
     * @param string $ownerId Owner ID
     * @param string $id      Video ID
     *
     * @return false|array
     */
    protected function getSourceListCache(
        $ownerId,
        $id
    ) {
        Assert::stringNotEmpty(
            $ownerId,
            'To get the source list from cache, video parser is require an argument "ownerId" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $id,
            'To get the source list from cache, video parser is require an argument "id" as not empty string, %s given.'
        );

        Assert::implementsInterface(
            $this->cache,
            CacheInterface::class,
            'To get the source list from cache, video parser is require injection "cache" as instance of interface %2$s, %s given.'
        );

        $cacheKey = $this->getSourceListCacheKey(
            $ownerId,
            $id
        );

        $this->logger->debug(sprintf(
            'To get the source list from cache, video parser will use the cache key is "%s".',
            $cacheKey
        ));

        Assert::stringNotEmpty(
            $cacheKey,
            'To get the source list from cache, video parser is require the cache key as not empty string, %s given.'
        );

        if (!$this->cache->has($cacheKey)) {

            $this->logger->debug(
                'To get the source list from cache, video parser was unable to find a stored list.'
            );

            return false;
        }

        $sourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $sourceListJson,
            'To get the source list from cache, video parser is require a stored JSON as not empty string, %s given.'
        );

        $sourceList = json_decode(
            $sourceListJson,
            true
        );

        if (
            !is_array($sourceList)
            && !is_null($sourceList)
            && $sourceList !== false
        ) {
            throw new \InvalidArgumentException(sprintf(
                'To get the source list from cache, video parser is require a converted source list from JSON as null, false or an array, %s given.'
            ));
        }

        $this->logger->debug(sprintf(
            'To get the source list from cache, video parser was received a stored list that type of "%s".',
            gettype($sourceList)
        ));

        $this->logger->debug(sprintf(
            'To get the source list from cache, video parser will return the source list in a section "data" of an array.'
        ));

        return [
            'data' => $sourceList
        ];
    }

    /**
     * Get the source list
     *
     * @param string         $ownerId     Owner ID
     * @param string         $id          Video ID
     * @param null|CookieJar $userSession User session
     * @param bool           $cache       Use the cache
     * @param null|int       $cacheTtl    Cache TTL
     *
     * @return false|null|array
     */
    public function getSourceList(
        $ownerId,
        $id,
        CookieJar $userSession = null,
        $cache = true,
        $cacheTtl = null
    ) {
        Assert::stringNotEmpty(
            $ownerId,
            'To get the source list, video parser is require an argument "ownerId" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $id,
            'To get the source list, video parser is require an argument "id" as not empty string, %s given.'
        );

        Assert::boolean(
            $cache,
            'To get the source list, video parser is require an argument "cache" as a boolean, %s given.'
        );

        Assert::nullOrGreaterThan(
            $cacheTtl,
            0,
            'To get the source list, video parser is require an argument "cacheTtl" as null or a positive integer, %s given.'
        );

        if ($cache && $this->cache) {

            $this->logger->debug(
                'To get the source list, video parser will use the cache.'
            );

            $sourceListCache = $this->getSourceListCache(
                $ownerId,
                $id
            );

            if (isset($sourceListCache['data'])) {

                $sourceList = $sourceListCache['data'];

                return $sourceList;

            } else {

                $this->logger->debug(
                    'To get the source list, video parser was unable to find a stored list.'
                );
            }

        } else {

            $this->logger->debug(
                'To get the source list, video parser will not use the cache.'
            );
        }

        $sourceList = parent::getSourceList(
            $ownerId,
            $id,
            $userSession
        );

        if ($cache && $this->cache) {

            $this->logger->debug(
                'To get the source list, video parser will save received source list to the cache.'
            );

            $this->setSourceListCache(
                $sourceList,
                $ownerId,
                $id,
                $cacheTtl
            );
        }

        return $sourceList;
    }
}