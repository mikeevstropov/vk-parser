<?php

namespace Mikeevstropov\VkParser;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Mikeevstropov\VkApi\Api;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

class VideoParserTest extends TestCase
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

    public function testCanCreate()
    {
        new VideoParser(
            $this->client
        );

        new VideoParser(
            $this->client,
            $this->logger
        );
    }


    public function testIsPrivate()
    {
        $string = '<html><head><title>Ошибка</title></head><body><div class="message_page_title">Ошибка</div></body></html>';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $isPrivate = new \ReflectionMethod(
            VideoParser::class,
            'isPrivate'
        );

        $isPrivate->setAccessible(true);

        $privacy = $isPrivate->invoke(
            $parser,
            $string
        );

        Assert::true(
            $privacy
        );
    }

    public function testIsNotPrivate()
    {
        $string = '<html><head><title>Видеозаписи</title></head><body><div class="page"></div></body></html>';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $isPrivate = new \ReflectionMethod(
            VideoParser::class,
            'isPrivate'
        );

        $isPrivate->setAccessible(true);

        $privacy = $isPrivate->invoke(
            $parser,
            $string
        );

        Assert::false(
            $privacy
        );
    }

    public function testCanGetStaticSource()
    {
        $string = '"url240":"https:\/\/cs640405.userapi.com\/6\/u264634741\/videos\/a0d1816dd7.240.mp4?extra=Zt1hykR4RvJ",'
            .'"url360":"https:\/\/cs640405.userapi.com\/6\/u264634741\/videos\/a0d1816dd7.360.mp4?extra=Zt1hykR4RvJ",'
            .'"url480":"https:\/\/cs640405.userapi.com\/6\/u264634741\/videos\/a0d1816dd7.480.mp4?extra=Zt1hykR4RvJ",'
            .'"url720":"https:\/\/cs640405.userapi.com\/6\/u264634741\/videos\/a0d1816dd7.720.mp4?extra=Zt1hykR4RvJ",'
            .'"url1080":"https:\/\/cs640405.userapi.com\/6\/u264634741\/videos\/a0d1816dd7.1080.mp4?extra=Zt1hykR4RvJ",';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getStaticSource = new \ReflectionMethod(
            VideoParser::class,
            'getStaticSource'
        );

        $getStaticSource->setAccessible(true);

        $source = $getStaticSource->invoke(
            $parser,
            $string
        );

        Assert::isArray(
            $source
        );

        Assert::keyExists(
            $source,
            '240'
        );

        Assert::keyExists(
            $source,
            '360'
        );

        Assert::keyExists(
            $source,
            '480'
        );

        Assert::keyExists(
            $source,
            '720'
        );

        Assert::keyExists(
            $source,
            '1080'
        );

        Assert::stringNotEmpty(
            $source['240']
        );

        Assert::stringNotEmpty(
            $source['360']
        );

        Assert::stringNotEmpty(
            $source['360']
        );

        Assert::stringNotEmpty(
            $source['720']
        );

        Assert::stringNotEmpty(
            $source['1080']
        );
    }

    public function testCannotGetStaticSource()
    {
        $string = 'bar,"foo":"bar","baz":"foo",';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getStaticSource = new \ReflectionMethod(
            VideoParser::class,
            'getStaticSource'
        );

        $getStaticSource->setAccessible(true);

        $source = $getStaticSource->invoke(
            $parser,
            $string
        );

        Assert::isArray(
            $source
        );

        Assert::isEmpty(
            $source
        );
    }

    public function testCanGetEmbedSource()
    {
        $string = 'ajax.preload(ght:100%;outline:0;\">\n      <iframe class=\"video_yt_player\" '
            .'type=\"text\/html\" width=\"100%\" height=\"100%\" src=\"https:\/\/'
            .'www.youtube.com\/embed\/OKui_hD4s08?enablejsapi=1&autoplay=0&start='
            .'0&autohide=1&wmode=opaque&showinfo=0&origin=https:\/\/vk.com&rel=0&'
            .'iv_load_policy=3\" frameborder=\"0\" allowfullscreen><\/iframe>\n  '
            .'  <\/div></script>';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getEmbedSource = new \ReflectionMethod(
            VideoParser::class,
            'getEmbedSource'
        );

        $getEmbedSource->setAccessible(true);

        $source = $getEmbedSource->invoke(
            $parser,
            $string
        );

        Assert::stringNotEmpty(
            $source
        );
    }

    public function testCannotGetEmbedSource()
    {
        $string = 'ajax.preloadght:100%;outline:0;\">\n      <iframe class=\"video_yt_player\" '
            .'type=\"text\/html\" width=\"100%\" height=\"100%\" frameborder=\"0\" '
            .'allowfullscreen><\/iframe>\n   <\/div></script>';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getEmbedSource = new \ReflectionMethod(
            VideoParser::class,
            'getEmbedSource'
        );

        $getEmbedSource->setAccessible(true);

        $source = $getEmbedSource->invoke(
            $parser,
            $string
        );

        Assert::null(
            $source
        );
    }

    public function testCanGetStreamSource()
    {
        $string = 'ajax.preload({"hls":"https:\/\/vk.com\/video_hls.php?path=8'
            .'Zvyy3iIjV-T2Qg&c_extra=a8zeQ2Pi4XZnCsgxXZs36t45b28Poht-utF3gsqdj'
            .'sF1axX4jKgTImbpOJc3Rmd8S5nVb0mps=6","hls_raw":"#EXTM});</script>';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getStreamSource = new \ReflectionMethod(
            VideoParser::class,
            'getStreamSource'
        );

        $getStreamSource->setAccessible(true);

        $source = $getStreamSource->invoke(
            $parser,
            $string
        );

        Assert::stringNotEmpty(
            $source
        );
    }

    public function testCannotGetStreamSource()
    {
        $string = 'ajax.preload({"com\/video_hls.php?path=8'
            .'%2Fu5nVb0tmps=6","hls_raw":"#EXTM});video_hls';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $getStreamSource = new \ReflectionMethod(
            VideoParser::class,
            'getStreamSource'
        );

        $getStreamSource->setAccessible(true);

        $source = $getStreamSource->invoke(
            $parser,
            $string
        );

        Assert::null(
            $source
        );
    }

    public function testCanGetSourceListStatic()
    {
        $ownerId = '112397758';
        $id = '169205476';

        $parser = new VideoParser(
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

    public function testCanGetSourceListEmbedYoutube()
    {
        $ownerId = '43735050';
        $id = '171042960';

        $parser = new VideoParser(
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

    public function testCanGetSourceListEmbedVimeo()
    {
        $ownerId = '4400660';
        $id = '456239084';

        $parser = new VideoParser(
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

    public function testCanGetSourceListEmbedCoub()
    {
        $ownerId = '-46172262';
        $id = '456245609';

        $parser = new VideoParser(
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

    public function testCanGetSourceListEmbedRutube()
    {
        $ownerId = '-23459697';
        $id = '166536379';

        $parser = new VideoParser(
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

    public function testCanGetSourceListStream()
    {
        $ownerId = '89958296';
        $id = '456239048';

        $parser = new VideoParser(
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

    public function testCannotGetSourceListPrivate()
    {
        $ownerId = '159710844';
        $id = '171523269';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id
        );

        Assert::false(
            $sourceList
        );
    }

    public function testCanGetSourceListAdult()
    {
        $ownerId = '-137384131';
        $id = '456239318';

        $userSession = $this->api->getUserSession(
            $this->userLogin,
            $this->userPassword,
            $this->applicationId
        );

        Assert::isInstanceOf(
            $userSession,
            CookieJar::class
        );

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id,
            $userSession
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
    }

    public function testCannotGetSourceListAdult()
    {
        $ownerId = '-137384131';
        $id = '456239318';

        $parser = new VideoParser(
            $this->client,
            $this->logger
        );

        $sourceList = $parser->getSourceList(
            $ownerId,
            $id
        );

        Assert::false(
            $sourceList
        );
    }
}