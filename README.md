#SilverStripe Cache Include

[![Build Status](https://magnum.travis-ci.com/heyday/silverstripe-cacheinclude.png?token=PUaVGqRbNa3xySvbQ4qD&branch=master)](https://magnum.travis-ci.com/heyday/silverstripe-cacheinclude)

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

For a SilverStripe `2.4` compatible version, see the `2.0` branch.

##License


##Installation

	$ composer require silverstripe-cacheinclude:3.0.0

##How to use

### Enabling

To be able to use `CacheInclude` from your templates, and to be able to have caches cleared from DataObject writes. Add the `CacheIncludeExtension` like so:

1. Create a config file `mysite/_config/caching.yml`
2. Enable the extension:

		---
		After: 'silverstripe-cacheinclude/*'
		---
		DataObject:
		  extensions:
			- CacheIncludeExtension

### Configuration

`CacheInclude` uses the SilverStripe `Injector` system for DI. You can configure and override classes that `CacheInclude` uses.

#### Yaml config

To set up `CacheInclude` to be configured from a yml file:

`mysite/_config/caching.yml`

```yml
---
After: 'silverstripe-cacheinclude/*'
---
DataObject:
  extensions:
    - CacheIncludeExtension

Injector:
  CacheIncludeConfig:
    class: Heyday\CacheInclude\Configs\YamlConfig
    constructor:
      0: '../mysite/cacheinclude_config.yml'
      1: '%$CacheCache'
```

#### Example yml config

`mysite/cacheinclude_config.yml`

```yml
MyPageTypeInclude:
    context: page
    contains:
        - MyPageType
```

With the previous config, the template cache `MyPageTypeInclude` will be refreshed whenever a page of `MyPageType` is written.

#### Available contexts

* `no`
	* No differences in url or environment will create a new cache key
* `page`
	* Differences in URLSegment will cause a different cache key
* `url-params`
	* `getURLParams` is used as part of the cache key
* `full`
	* `requestVars` is used to create the cache key
* `controller`
	* the controller class is used as the cache key

#### Alternate cache key modifiers

* versions
	* If set to 5 you will get 5 different caches of the same page. This is used for pages with random content per render
* member
	* If the member affects the content of the template cached then use the member id as part of the cache

#### Putting it all together

```yml
MainMenu:
  context: page
  contains:
    - Page
SomeTemplate:
  context: full
  versions: 20
  member: true
  contains:
    - MyDataObject
    - MyPageType
```

### Usage in templates

Replace <% include %> calls with `$CacheInclude('TemplateName')` and ensure there is a config key for `TemplateName`

##Contributing

###Unit Testing

	$ composer install --prefer-dist --dev
	$ phpunit

###Code guidelines

This project follows the standards defined in:

* [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
* [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
* [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

Run the following before contributing:

	$ php-cs-fixer fix .
