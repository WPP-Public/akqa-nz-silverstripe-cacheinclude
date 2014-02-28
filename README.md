# SilverStripe Cache Include

Template caching based on urls not DB queries.

For a SilverStripe `2.4` compatible version, see the `2.0.4` tag.

## License

SilverStripe CacheInclude is released under the [MIT license](http://heyday.mit-license.org/)

## Installation

	$ composer require silverstripe-cacheinclude

## How to use

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
	* Url not including GET variables
* `full`
	* Url including GET variables

#### Alternate cache key modifiers

* `versions`
	* Set this to any number, and that is how many versions of the cache you will get. This can be used when the content of your page changes each render (random)
* `member`
	* If set to true a new cache will be made per member logged in
	* If set to "any" there will be one cache for all logged in users

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



## Contributing

### Unit Testing

	$ composer install --prefer-dist --dev
	$ phpunit

### Code guidelines

This project follows the standards defined in:

* [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
* [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
* [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

Run the following before contributing:

	$ php-cs-fixer fix .
