<?php

namespace Mikeevstropov\VkParser;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Mikeevstropov\VkApi\Api;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\MemcachedCache;
use Webmozart\Assert\Assert;

class ExtendedVideoParserTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var MemcachedCache
     */
    protected $cache;

    protected $userLogin;
    protected $userPassword;
    protected $applicationId;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->logger = $this->createLogger(
            getenv('logFile'),
            getenv('logLevel')
        );

        $this->client = $this->createClient(
            $this->logger
        );

        $this->api = new Api(
            $this->client,
            $this->logger
        );

        $this->cache = $this->createCache(
            getenv('memcachedConnection')
        );

        $this->userLogin = getenv('userLogin');
        $this->userPassword = getenv('userPassword');
        $this->applicationId = getenv('applicationId');
    }

    protected function createLogger(
        $logFile,
        $logLevel
    ) {
        Assert::stringNotEmpty(
            $logFile,
            'To create "logger" instance, method is require an argument "logFile" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $logLevel,
            'To create "logger" instance, method is require an argument "logLevel" as not empty string, %s given.'
        );

        if (is_file($logFile) && !unlink($logFile))
            throw new \LogicException(sprintf(
                'To create "logger" instance, method was unable to remove existed log file "%s".',
                $logFile
            ));

        $streamHandler = new StreamHandler(
            $logFile,
            $logLevel
        );

        return new Logger('test', [$streamHandler]);
    }

    protected function createClient(
        LoggerInterface $logger
    ) {
        $clientStackHandler = HandlerStack::create();

        $clientStackHandler->push(
            Middleware::log(
                $logger,
                new MessageFormatter('Client {code} - {request}'),
                'DEBUG'
            )
        );

        return new Client([
            'handler' => $clientStackHandler
        ]);
    }

    protected function createCache(
        $connection
    ) {
        Assert::stringNotEmpty(
            $connection,
            'To create "cache" instance, method is require an argument "connection" as not empty string, %s given.'
        );

        $cacheClient = MemcachedCache::createConnection(
            $connection
        );

        $cache = new MemcachedCache($cacheClient);

        $cache->clear();

        return $cache;
    }

    public function testCanCreate()
    {
        new ExtendedVideoParser(
            $this->client
        );

        new ExtendedVideoParser(
            $this->client,
            $this->logger
        );

        new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );
    }

    public function testCacheTtl()
    {
        $this->cache->clear();

        $ttl = 2;

        $key = 'parser.video.test_ttl';

        $value = 'value';

        $this->cache->set(
            $key,
            $value,
            $ttl
        );

        sleep(1);

        Assert::true(
            $this->cache->has($key)
        );

        sleep(2);

        Assert::false(
            $this->cache->has($key)
        );
    }

    public function testCanGetSourceListCacheKey()
    {
        $ownerId = '-44897489';
        $id = '9898416';

        $expectedKey = 'parser.video.source_list.'. md5($ownerId . $id);

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::same(
            $expectedKey,
            $cacheKey
        );
    }

    public function testCanSetSourceListCacheAsNull()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = null;

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $setSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'setSourceListCache'
        );

        $setSourceListCache->setAccessible(true);

        $setSourceListCache->invoke(
            $parser,
            $sourceList,
            $ownerId,
            $id,
            null
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanSetSourceListCacheAsFalse()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = false;

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $setSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'setSourceListCache'
        );

        $setSourceListCache->setAccessible(true);

        $setSourceListCache->invoke(
            $parser,
            $sourceList,
            $ownerId,
            $id,
            null
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanSetSourceListCacheAsArray()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = [
            'static' => [
                '240' => 'http://source.com/source.mp4'
            ],
            'embed'  => 'http://source.com/embed',
            'stream' => 'http://source.com/source.m3u8'
        ];

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $setSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'setSourceListCache'
        );

        $setSourceListCache->setAccessible(true);

        $setSourceListCache->invoke(
            $parser,
            $sourceList,
            $ownerId,
            $id,
            null
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanSetSourceListCacheWithTtl()
    {
        $this->cache->clear();

        $cacheTtl = 3;

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = [
            'static' => [
                '240' => 'http://source.com/source.mp4'
            ],
            'embed'  => 'http://source.com/embed',
            'stream' => 'http://source.com/source.m3u8'
        ];

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $setSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'setSourceListCache'
        );

        $setSourceListCache->setAccessible(true);

        $setSourceListCache->invoke(
            $parser,
            $sourceList,
            $ownerId,
            $id,
            $cacheTtl
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        sleep(1);

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );

        sleep(3);

        Assert::false(
            $this->cache->has($cacheKey)
        );
    }

    public function testCanGetSourceListCacheAsNull()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = null;

        $sourceListJson = json_encode($sourceList);

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        $this->cache->set(
            $cacheKey,
            $sourceListJson
        );

        $getSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCache'
        );

        $getSourceListCache->setAccessible(true);

        $sourceListCache = $getSourceListCache->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceListCache
        );

        Assert::keyExists(
            $sourceListCache,
            'data'
        );

        $cachedSourceList = $sourceListCache['data'];

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListCacheAsFalse()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = false;

        $sourceListJson = json_encode($sourceList);

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        $this->cache->set(
            $cacheKey,
            $sourceListJson
        );

        $getSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCache'
        );

        $getSourceListCache->setAccessible(true);

        $sourceListCache = $getSourceListCache->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceListCache
        );

        Assert::keyExists(
            $sourceListCache,
            'data'
        );

        $cachedSourceList = $sourceListCache['data'];

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListCacheAsArray()
    {
        $this->cache->clear();

        $ownerId = '-44897489';
        $id = '9898416';

        $sourceList = [
            'static' => [
                '240' => 'http://source.com/source.mp4'
            ],
            'embed'  => 'http://source.com/embed',
            'stream' => 'http://source.com/source.m3u8'
        ];

        $sourceListJson = json_encode($sourceList);

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        $this->cache->set(
            $cacheKey,
            $sourceListJson
        );

        $getSourceListCache = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCache'
        );

        $getSourceListCache->setAccessible(true);

        $sourceListCache = $getSourceListCache->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceListCache
        );

        Assert::keyExists(
            $sourceListCache,
            'data'
        );

        $cachedSourceList = $sourceListCache['data'];

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListStaticWithoutCache()
    {
        $ownerId = '112397758';
        $id = '169205476';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::notEmpty(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList['static'],
            '240'
        );

        Assert::keyExists(
            $sourceList['static'],
            '360'
        );

        Assert::keyExists(
            $sourceList['static'],
            '480'
        );

        Assert::keyExists(
            $sourceList['static'],
            '720'
        );

        Assert::stringNotEmpty(
            $sourceList['static']['240']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['360']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['480']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['720']
        );
    }

    public function testCanGetSourceListStaticWithCache()
    {
        $this->cache->clear();

        $ownerId = '112397758';
        $id = '169205476';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            null
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::notEmpty(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList['static'],
            '240'
        );

        Assert::keyExists(
            $sourceList['static'],
            '360'
        );

        Assert::keyExists(
            $sourceList['static'],
            '480'
        );

        Assert::keyExists(
            $sourceList['static'],
            '720'
        );

        Assert::stringNotEmpty(
            $sourceList['static']['240']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['360']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['480']
        );

        Assert::stringNotEmpty(
            $sourceList['static']['720']
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListEmbedWithoutCache()
    {
        $ownerId = '43735050';
        $id = '171042960';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::stringNotEmpty(
            $sourceList['embed']
        );
    }

    public function testCanGetSourceListEmbedWithCache()
    {
        $this->cache->clear();

        $ownerId = '43735050';
        $id = '171042960';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            null
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::stringNotEmpty(
            $sourceList['embed']
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListStreamWithoutCache()
    {
        $ownerId = '89958296';
        $id = '456239048';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::stringNotEmpty(
            $sourceList['stream']
        );
    }

    public function testCanGetSourceListStreamWithCache()
    {
        $this->cache->clear();

        $ownerId = '89958296';
        $id = '456239048';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            null
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        Assert::stringNotEmpty(
            $sourceList['stream']
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );
    }

    public function testCanGetSourceListWithCacheByTtl()
    {
        $this->cache->clear();

        $cacheTtl = 3;

        $ownerId = '89958296';
        $id = '456239048';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            $cacheTtl
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        $getSourceListCacheKey = new \ReflectionMethod(
            ExtendedVideoParser::class,
            'getSourceListCacheKey'
        );

        $getSourceListCacheKey->setAccessible(true);

        $cacheKey = $getSourceListCacheKey->invoke(
            $parser,
            $ownerId,
            $id
        );

        Assert::stringNotEmpty(
            $cacheKey
        );

        sleep(1);

        Assert::true(
            $this->cache->has($cacheKey)
        );

        $cachedSourceListJson = $this->cache->get($cacheKey);

        Assert::stringNotEmpty(
            $cachedSourceListJson
        );

        $cachedSourceList = json_decode(
            $cachedSourceListJson,
            true
        );

        Assert::same(
            $sourceList,
            $cachedSourceList
        );

        sleep(3);

        Assert::false(
            $this->cache->has($cacheKey)
        );
    }

    public function testCanGetSourceListAfterCache()
    {
        $this->cache->clear();

        $ownerId = '43735050';
        $id = '171042960';

        $parser = new ExtendedVideoParser(
            $this->client,
            $this->logger,
            $this->cache
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            null
        );

        Assert::isArray(
            $sourceList
        );

        Assert::keyExists(
            $sourceList,
            'static'
        );

        Assert::keyExists(
            $sourceList,
            'embed'
        );

        Assert::keyExists(
            $sourceList,
            'stream'
        );

        $cachedSourceList = $parser->getSourceList(
            $ownerId,
            $id,
            null,
            true,
            null
        );

        Assert::isArray(
            $cachedSourceList
        );

        Assert::keyExists(
            $cachedSourceList,
            'static'
        );

        Assert::keyExists(
            $cachedSourceList,
            'embed'
        );

        Assert::keyExists(
            $cachedSourceList,
            'stream'
        );
    }
}