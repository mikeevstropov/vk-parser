<?php

namespace Mikeevstropov\VkParser;

use GuzzleHttp\Cookie\CookieJar;

interface VideoParserInterface
{
    /**
     * Get the source list that contain a keys "static", "embed" and "stream"
     * where is the "embed" is a link of the player page that can be used in
     * iframe tag, also "static" is contain the list of the video levels by the
     * keys "240", "360", "480", "720" and "1080". If the level is not available
     * it will not be listed. The "stream" section is intend for a link to the
     * HLS stream.
     *
     * If the video does not exist, private (adult also) or blocked by law the
     * method will return "false", "null" will be returns if the source of the
     * video is not supported.
     *
     * Use the library mikeevstropov/vk-api to get the user session if you want
     * access to adult videos.
     *
     * @param string         $ownerId     Owner ID
     * @param string         $id          Video ID
     * @param null|CookieJar $userSession User session
     *
     * @return false|null|array
     */
    function getSourceList(
        $ownerId,
        $id,
        CookieJar $userSession = null
    );
}