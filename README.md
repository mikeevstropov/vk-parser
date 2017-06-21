# mikeevstropov/vk-parser

The parser of the social network [vk.com](https://vk.com/dev/manuals)

## Installation

Add dependency [mikeevstropov/vk-parser](https://packagist.org/packages/mikeevstropov/vk-parser)

```bash
$ composer require mikeevstropov/vk-parser
```

## Usage

Sorry, but examples will not be provided for this library.

## VideoParser Interface

- **__constructor**
  
  _VideoParser constructor_
  
  Arguments:
  - `client` _(GuzzleHttp\ClientInterface)_ - client instance required
  - `logger` _(Psr\Log\LoggerInterface)_ - logger instance or null as default
  
  Returns:
  - `Mikeevstropov\VkParser\VideoParser`

- **getSourceList**
  
  _Getting the source list_
  
  Arguments:
  - `ownerId` _(string)_ - the owner ID of the video is required
  - `logger` _(string)_ - ID of the video is required
  - `userSession` _(GuzzleHttp\Cookie\CookieJar)_ - user session or null
  
  Returns:
  - `array` - contain a keys "static", "embed" and "stream"
  - `false` - video is private (adult also) or blocked by law
  - `null` - source of the video is not supported
  
## ExtendedVideoParser Interface

- **__constructor**
  
  _ExtendedVideoParser constructor_
  
  Arguments:
  - `client` _(GuzzleHttp\ClientInterface)_ - client instance required
  - `logger` _(Psr\Log\LoggerInterface)_ - logger instance or null as default
  - `cache` _(Psr\SimpleCache\CacheInterface)_ - cache instance or null as default
  
  Returns:
  - `Mikeevstropov\VkParser\ExtendedVideoParser`

- **getSourceList**
  
  _Getting the source list_
  
  Arguments:
  - `ownerId` _(string)_ - the owner ID of the video is required
  - `logger` _(string)_ - ID of the video is required
  - `userSession` _(GuzzleHttp\Cookie\CookieJar)_ - user session or null
  - `cache` _(bool)_ - use the cache, is true as default
  - `cacheTtl` _(int)_ - number of seconds or null as default
  
  Returns:
  - `array` - contain a keys "static", "embed" and "stream"
  - `false` - video is private (adult also) or blocked by law
  - `null` - source of the video is not supported