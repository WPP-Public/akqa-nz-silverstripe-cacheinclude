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

```yml
---
After: 'silverstripe-cacheinclude/*'
---
DataObject:
	extensions:
		- Heyday\CacheInclude\SilverStripe\InvalidationExtension
```

### Template Usage

```
<% cache 'blockName' %>
Template cache to go here
<% loop ExpensiveSet %><% end_loop %>
<% end_cache %>
```

For each cache block name, you will need a config entry in a Yaml file:

### Yaml config

The following config will tell `CacheInclude` to look for a config file in `../mysite/cacheinclude_config.yml`

`mysite/_config/caching.yml`

```yml
---
After: 'silverstripe-cacheinclude/*'
---
Injector:
  CacheIncludeConfig:
    class: Heyday\CacheInclude\Configs\YamlConfig
    constructor:
      0: '../mysite/cacheinclude_config.yml'
      1: '%$DoctrineCache'
```

#### Example Yaml config

`mysite/cacheinclude_config.yml`

```yml
SomeTemplate:
  context: full
  versions: 20
  member: true
  contains:
    - MyDataObject
    - MyPageType
```

### Configuration options

Key creation options:

#### `context`

Context is a method to tell the key creator what information about the request to include in the created key.

Possible values:

* `no`
	* Key created is independent of the request
* `page`
	* Key is created based on url, but not including GET variables
* `full`
	* Key is created based on url, including GET variables

#### `member`

Possible values:

* `true`
	* Will create a new cache per logged in member
* `any`
	* Will create a new cache members as a group (and another key when a person is not logged in)

#### `versions`

Possible values:

* (int)
	* Set this to an integer to make the specified number of versions of the cache
	
This is useful for when a cache block contains random content, but you still want caching.

e.g. set to 20 to get 20 (potentially) different version of a cache block.

Cache invalidation options

#### `contains`

* (array)
	* An array of class names that if a record saved matches the cache will invalidate

#### `invalidation_rules`

* (array)
	* An array of rules written in the available expression language. If a rule is matched the cache will invalidate

The Expression Language is provided by Symfony, but also has the following available:

##### Variables

- `item`
- `action`

##### Functions

- `list()`
- `instanceof()`

Theses can be used to do the following:

```
  invalidation_rules:
    - "instanceof(item, 'CreativeProfile') and item.ID in list('CreativeProfile').sort('Created DESC').limit(4).getIDList()"
```

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
