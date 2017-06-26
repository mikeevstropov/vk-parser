<?php

namespace Mikeevstropov\VkParser;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use Mikeevstropov\UserAgent\UserAgent;
use Mikeevstropov\VkParser\DependencyInjection\Dummy;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;
use Stringy\Stringy as S;

class VideoParser implements VideoParserInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;
    /**
     * @var LoggerInterface|Dummy
     */
    protected $logger;

    /**
     * VideoParser constructor.
     *
     * @param ClientInterface      $client
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->logger = $logger ?: new Dummy();
    }

    /**
     * Check the availability of video
     *
     * @param string $string Body of the source page
     *
     * @return bool
     */
    protected function isAvailable(
        $string
    ) {
        Assert::stringNotEmpty(
            $string,
            'To check the availability of video, video parser is require an argument "string" as not empty string, %s given.'
        );

        return strpos($string, 'message_page_title') === false;
    }

    /**
     * Get a static source as an array with a keys by following levels:
     * "240", "360", "480", "720" and "1080"
     *
     * @param string $string Body of the source page
     *
     * @return array
     */
    protected function getStaticSource(
        $string
    ) {
        Assert::stringNotEmpty(
            $string,
            'To get the static source, video parser is require an argument "string" as not empty string, %s given.'
        );

        $stringS = S::create($string);

        $source = [];

        $source['240'] = $stringS
            ->between('url240"', ',')
            ->between('"', '"')
            ->__toString();

        $source['240'] = json_decode('"'. $source['240'] .'"') ?: false;

        $this->logger->debug(sprintf(
            $source['240']
                ? 'To get the static source, video parser was received a source link of 240p that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of 240p.',
            $source['240']
        ));

        $source['360'] = $stringS
            ->between('url360"', ',')
            ->between('"', '"')
            ->__toString();

        $source['360'] = json_decode('"'. $source['360'] .'"') ?: false;

        $this->logger->debug(sprintf(
            $source['360']
                ? 'To get the static source, video parser was received a source link of 360p that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of 360p.',
            $source['360']
        ));

        $source['480'] = $stringS
            ->between('url480"', ',')
            ->between('"', '"')
            ->__toString();

        $source['480'] = json_decode('"'. $source['480'] .'"') ?: false;

        $this->logger->debug(sprintf(
            $source['480']
                ? 'To get the static source, video parser was received a source link of 480p that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of 480p.',
            $source['480']
        ));

        $source['720'] = $stringS
            ->between('url720"', ',')
            ->between('"', '"')
            ->__toString();

        $source['720'] = json_decode('"'. $source['720'] .'"') ?: false;

        $this->logger->debug(sprintf(
            $source['720']
                ? 'To get the static source, video parser was received a source link of 720p that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of 720p.',
            $source['720']
        ));

        $source['1080'] = $stringS
            ->between('url1080"', ',')
            ->between('"', '"')
            ->__toString();

        $source['1080'] = json_decode('"'. $source['1080'] .'"') ?: false;

        $this->logger->debug(sprintf(
            $source['1080']
                ? 'To get the static source, video parser was received a source link of 1080p that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of 1080p.',
            $source['1080']
        ));

        $source = array_filter($source);

        $postLive = $stringS
            ->between('postlive_mp4"', ',')
            ->between('"', '"')
            ->__toString();

        $postLive = json_decode('"'. $postLive .'"') ?: false;

        $this->logger->debug(sprintf(
            $postLive
                ? 'To get the static source, video parser was received a source link of "post live" that contain is "%s".'
                : 'To get the static source, video parser could not received a source link of "post live".',
            $postLive
        ));

        if (!$source && $postLive) {

            preg_match('/\.(\d+)\.mp4/', $postLive, $qualityMatches);

            $postLiveQuality = isset($qualityMatches[1])
                && preg_match('/(240|360|480|720|1080)/', $qualityMatches[1])
                    ? $qualityMatches[1]
                    : null;

            if ($postLiveQuality) {

                $source[$postLiveQuality] = $postLive;

                $this->logger->debug(sprintf(
                    'To get the static source, video parser will set a source link of "post live" into "%s" section, because no other source received.',
                    $postLiveQuality
                ));

            }
        }

        return $source;
    }

    /**
     * Get a link of the embed source
     *
     * @param string $string Body of the source page
     *
     * @return null|string
     */
    protected function getEmbedSource(
        $string
    ) {
        Assert::stringNotEmpty(
            $string,
            'To get the embed source, video parser is require an argument "string" as not empty string, %s given.'
        );

        $stringS = S::create($string);

        $source = $stringS
            ->between('ajax.preload', '</script>')
            ->between('<iframe', '<\/iframe')
            ->between('src=\"', '\"')
            ->__toString();

        $source = json_decode('"'. $source .'"') ?: null;

        $this->logger->debug(sprintf(
            $source
                ? 'To get the embed source, video parser was received a source link is "%s".'
                : 'To get the embed source, video parser could not received a source link.',
            $source
        ));

        return $source;
    }

    /**
     * Get a stream source
     *
     * @param string $string Body of the source page
     *
     * @return null|string
     */
    protected function getStreamSource(
        $string
    ) {
        Assert::stringNotEmpty(
            $string,
            'To get the stream source, video parser is require an argument "string" as not empty string, %s given.'
        );

        $stringS = S::create($string);

        $source = $stringS
            ->between('ajax.preload', '</script>')
            ->between('"hls"', ',')
            ->between(':"', '"')
            ->__toString();

        $source = json_decode('"'. $source .'"') ?: null;

        $this->logger->debug(sprintf(
            $source
                ? 'To get the stream source, video parser was received a source link is "%s".'
                : 'To get the stream source, video parser could not received a source link.',
            $source
        ));

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceList(
        $ownerId,
        $id,
        CookieJar $userSession = null
    ) {
        Assert::stringNotEmpty(
            $ownerId,
            'To get the source list, video parser is require an argument "ownerId" as not empty string, %s given.'
        );

        Assert::stringNotEmpty(
            $id,
            'To get the source list, video parser is require an argument "id" as not empty string, %s given.'
        );

        $pageUrl = 'https://vk.com/video'. $ownerId .'_'. $id;

        $this->logger->debug(sprintf(
            'To get the source list, video parser will use the page "%s".',
            $pageUrl
        ));

        try {

            $response = $this->client->request('GET', $pageUrl, [
                'cookies' => $userSession,
                'headers' => [
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'accept-encoding' => 'gzip, deflate, br',
                    'accept-language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    'upgrade-insecure-requests' => '1',
                    'user-agent' => UserAgent::getDesktopRand()
                ]
            ]);

        } catch (ClientException $exception) {

            $this->logger->debug(
                'To get the source list, video parser was unable to get a request response, so will return "false".'
            );

            return false;
        }

        $responseHtml = $response->getBody()->__toString();

        $responseHtml = mb_convert_encoding($responseHtml, 'UTF-8');

        $this->logger->debug(sprintf(
            'To get the source list, video parser will use a response HTML that contain %d bytes.',
            strlen($responseHtml)
        ));

        if (!$this->isAvailable($responseHtml)) {

            $this->logger->debug(
                'To get the source list, video parser was unable to get access to the video, so will return "false".'
            );

            return false;
        }

        $sourceList = [];

        $sourceList['static'] = $this->getStaticSource($responseHtml);

        Assert::isArray(
            $sourceList['static'],
            'To get the source list, video parser is require a static source as an array, %s given.'
        );

        $this->logger->debug(sprintf(
            $sourceList['static']
                ? 'To get the source list, video parser was received a static source that contain %d levels.'
                : 'To get the source list, video parser was unable to receive a static source.',
            count($sourceList['static'])
        ));

        $sourceList['embed'] = $this->getEmbedSource($responseHtml);

        Assert::nullOrStringNotEmpty(
            $sourceList['embed'],
            'To get the source list, video parser is require an embed source as null or not empty string, %s given.'
        );

        $this->logger->debug(sprintf(
            $sourceList['embed']
                ? 'To get the source list, video parser was received an embed source is "%d".'
                : 'To get the source list, video parser was unable to receive an embed source.',
            $sourceList['embed']
        ));

        $sourceList['stream'] = $this->getStreamSource($responseHtml);

        Assert::nullOrStringNotEmpty(
            $sourceList['stream'],
            'To get the source list, video parser is require a stream source as null or not empty string, %s given.'
        );

        $this->logger->debug(sprintf(
            $sourceList['stream']
                ? 'To get the source list, video parser was received a stream source is "%d".'
                : 'To get the source list, video parser was unable to receive a stream source.',
            $sourceList['stream']
        ));

        if (
            !$sourceList['static']
            && !$sourceList['embed']
            && !$sourceList['stream']
        ) {
            $this->logger->debug(
                'To get the source list, video parser was unable to receive the source from response HTML, so will return "null".'
            );

            return null;
        }

        return $sourceList;
    }
}