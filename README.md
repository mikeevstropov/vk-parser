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
  - `logger` _(null|Psr\Log\LoggerInterface)_ - logger instance or null as default
  
  Returns:
  - `Mikeevstropov\VkParser\VideoParser`

- **getSourceList**
  
  _Getting the source list_
  
  Arguments:
  - `ownerId` _(string)_ - the owner ID of the video is required
  - `logger` _(string)_ - ID of the video is required
  - `userSession` _(null|GuzzleHttp\Cookie\CookieJar)_ - user session or null as default
  
  Returns:
  - `array` - contain a keys "static", "embed" and "stream"
  - `false` - video does not exist, private (adult also) or blocked by law
  - `null` - source of the video is not supported
  
## ExtendedVideoParser Interface

- **__constructor**
  
  _ExtendedVideoParser constructor_
  
  Arguments:
  - `client` _(GuzzleHttp\ClientInterface)_ - client instance required
  - `logger` _(null|Psr\Log\LoggerInterface)_ - logger instance or null as default
  - `cache` _(null|Psr\SimpleCache\CacheInterface)_ - cache instance or null as default
  
  Returns:
  - `Mikeevstropov\VkParser\ExtendedVideoParser`

- **getSourceList**
  
  _Getting the source list_
  
  Arguments:
  - `ownerId` _(string)_ - owner ID of the video is required
  - `logger` _(string)_ - ID of the video is required
  - `userSession` _(null|GuzzleHttp\Cookie\CookieJar)_ - user session or null as default
  - `cache` _(bool)_ - use the cache, is true as default
  - `cacheTtl` _(null|int)_ - number of seconds or null as default
  
  Returns:
  - `array` - contain a keys "static", "embed" and "stream"
  - `false` - video does not exist, private (adult also) or blocked by law
  - `null` - source of the video is not supported

## Development

Clone

```bash
$ git clone https://github.com/mikeevstropov/vk-parser.git
```

Go to project

```bash
$ cd vk-parser
```

Install dependencies

```bash
$ composer install
```

Set permissions

```bash
$ sudo chmod 777 ./var -v -R
```

Configure testing environment in `phpunit.xml`. Make sure the environment
variables "userLogin", "userPassword" and "applicationId" is not empty.

```xml
<phpunit>
    <php>
        <env name="logLevel" value="DEBUG"/>
        <env name="logFile" value="var/logs/parser.test.log"/>
        <env name="memcachedConnection" value="memcached://localhost"/>
        <env name="userLogin" value=""/>
        <env name="userPassword" value=""/>
        <env name="applicationId" value=""/>
    </php>
</phpunit>
```

Increase composer timeout. Since composer by default set it to 300 seconds.

```bash
$ composer config --global process-timeout 900
```

Run the tests

```bash
$ composer test
```