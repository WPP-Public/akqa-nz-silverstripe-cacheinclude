#SilverStripe Cache Include

[![Build Status](https://magnum.travis-ci.com/heyday/silverstripe-cacheinclude.png?token=PUaVGqRbNa3xySvbQ4qD&branch=master)](https://magnum.travis-ci.com/heyday/silverstripe-cacheinclude)

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

##License


##Installation

	$ composer require silverstripe-cacheinclude:2.0.0

##How to use

### Enabling

Enable in controller or relevant data objects or pages.

```php
class Page_Controller extends ContentController
{
    public static $extensions = array(
        'CacheIncludeExtension'
    );
}
```

### Templates

    $CacheInclude(TemplateName)

## Configuration

`CacheInclude` uses [Pimple](http://pimple.sensiolabs.org/) for configuration and dependency injection. The following options are available with the follow defaults:


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

YAML config

```php
use Heyday\CacheInclude;
CacheInclude\Container::addShared(
    'cacheinclude_config',
    function ($c) {
        return new CacheInclude\Configs\YamlConfig(__DIR__ . '/cache_config.yml');
    }
);
```

YAML config with caching of YAML

```php
use Heyday\CacheInclude;
CacheInclude\Container::addShared(
    'cacheinclude_config',
    function ($c) {
        return new CacheInclude\Configs\YamlConfig(__DIR__ . '/cache_config.yml', $c['cachecache']);
    }
);
```

Array config

```php
use Heyday\CacheInclude;
CacheInclude\Container::addShared(
    'cacheinclude_config',
    function ($c) {
        return new CacheInclude\Configs\ArrayConfig(
        	array(
        		'TemplateName' => array(
        			'context' => 'page'
        		)
        	)
        );
    }
);
```

Clear cache mechanism

```php
use Heyday\CacheInclude;
CacheInclude\Container::extendConfig(
	array(
		'cacheinclude.options.force_expire' => isset($_GET['flush']) && $_GET['flush'] && Permission::check('ADMIN')
	)
);
```

Don't invoke the cache in development mode

```php
use Heyday\CacheInclude;
if(Director::isDev()){
    CacheInclude\Container::extendConfig(
        array(
            'cacheinclude.options.enabled' => false
        )
    );
}
```

##Contributing

###Unit Testing

	$ composer install --dev
	$ phpunit

###Code guidelines

This project follows the standards defined in:

* [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
* [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
* [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

Run the following before contributing:

	$ php-cs-fixer fix .
