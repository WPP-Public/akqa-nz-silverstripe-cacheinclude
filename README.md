#SilverStripe Cache Include

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

##License


##Installation

To install drop the `silverstripe-cacheinclude` directory into your SilverStripe root and run `/dev/build?flush=1`.

##How to use

###Templates

    $CacheInclude(TemplateName)

##Configuration

`CacheInclude` uses a dependancy injection container (Pimple) for configuration and DI. The following options are available with the follow defaults:


* 'cachecache.class'                  ('\CacheCache\Cache')
* 'cachecache.options.namespace'      ('cacheinclude')
* 'cachecache.options.default_ttl'    (null)
* 'cachecache.options.ttl_variation'  (0)
* 'cachecache_backend.class'          ('\CacheCache\Backends\File')
* 'cacheinclude.class'                ('\Heyday\CacheInclude\CacheInclude')
* 'cacheinclude.options.enabled'      (true)
* 'cacheinclude.options.force_expire' (false)
* 'cacheinclude_config.class'         ('\Heyday\CacheInclude\Configs\ArrayConfig')
* 'cacheinclude_config.config'        (array())
* 'cacheinclude_key_creator.class'    ('\Heyday\CacheInclude\KeyCreators\KeyCreator')
* 'cacheinclude_processor.class'      ('\Heyday\CacheInclude\Processors\Processor')


`mysite/_config.php`

```php
use Heyday\CacheInclude;
CacheInclude\Container::addShared(
    'cacheinclude_config',
    function ($c) {
        return new CacheInclude\Configs\YamlConfig(__DIR__ . '/cache_config.yml');
    }
);
```



##Clearing Cache


##Contributing

##Unit Testing

If you have `phpunit` installed you can run `silverstripe-cacheinclude`'s unit tests to see if everything is functioning correctly.

###Running the unit tests

From the command line:
    
    phpunit


From your browser:

    http://localhost/dev/tests/module/heyday-cacheinclude

###Code guidelines

This project follows the standards defined in:

* [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
* [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
* [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
