# SilverStripe Cache Include

[![Build Status](https://travis-ci.org/heyday/silverstripe-cacheinclude.svg?branch=master)](https://travis-ci.org/heyday/silverstripe-cacheinclude)

Template caching based on urls not DB queries.

## Features

* Cache keys are built from information available in request object (means no DB calls)
* Invalidation hooks for when DataObject's are modified
* Uses `doctrine/cache` library, providing many cache backends
* Uses Symfony Expression language for fine-grained invalidation control
* Support for `<% cache_include 'TemplateName' %>` syntax in templates
* Support for `<% cache %><% end_cache %>` syntax in templates
* A full request cache that includes the ability to substitute security tokens
* Highly customisable
* CacheInclude Manager for easy cache management https://github.com/heyday/silverstripe-cacheinclude-manager/

For a SilverStripe `2.4` compatible version, see the `2.0.4` tag.

## Installation

```bash
$ composer require heyday/silverstripe-cacheinclude:~4.0
```

## How to use

### Enabling

To  be able to invalidate caches from DataObject writes, add the `InvalidationExtension`:

1. Create a config file `mysite/_config/caching.yml`
2. Add the following to the yml file

```yml
---
After: 'silverstripe-cacheinclude/*'
---
DataObject:
  extensions:
    - Heyday\CacheInclude\SilverStripe\InvalidationExtension
```

### Template Usage

Cache a section of a template:

```
<% cache 'SomeCacheBlock' %>
<% loop ExpensiveSet %><% end_loop %>
<% end_cache %>
```

Cache an included template:

```
<% cache_include 'SomeTemplateName' %>
```

### Cache block config

For each cache block that is used, you need a corresponding config provided to `CacheInclude`.

The following is an example of a config for `SomeCacheBlock` and `AnotherCacheBlock`:

`mysite/_config/caching.yml`

```yml
---
After: 'silverstripe-cacheinclude/*'
---
Injector:
  CacheIncludeConfig:
    class: Heyday\CacheInclude\Configs\ArrayConfig
    properties:
      Config:
        SomeCacheBlock:
          context: full
          contains:
            - MyDataObject
        AnotherCacheBlock:
          context: no
          expires: +1 hour
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

#### `expires`

Possible values:

* (string)
  * A string to pass into strtotime e.g. '+1 hour'
* (int)
  * A number of seconds

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

## Full request caching

CacheInclude comes with a `RequestCache` service that can be added to cache full request objects for use in high load
sites.

### Enabling

To enable the full request cache the `RequestCache` service needs to be added to the `RequestProcessor` as a filter.

```yml
Injector:
  RequestProcessor:
    class: RequestProcessor
    properties:
      filters:
        - '%$RequestCache'
```

Full request caching increases performance substantially but it isn't without a cost. It can be hard to configure, as there
are numerous cases where you don't want to either cache a request or alternatively serve a cached request.

To help in this there is quite a bit you can do out of the box to configure the way that caching is handled.

The following gives some demonstration of how to configure things and what you can do:

```yml
Injector:
  RequestProcessor:
    class: RequestProcessor
    properties:
      filters:
        - '%$RequestCache'

  CacheIncludeConfig:
    class: Heyday\CacheInclude\Configs\ArrayConfig
    properties:
      Config:
        Global:
          context: full
          contains:
            - MyDataObject
          expires: +1 hour

  RequestCache:
    class: Heyday\CacheInclude\RequestCache
    constructor:
      0: '%$CacheInclude'
      1: '%$CacheIncludeExpressionLanguage'
      2: Global
    properties:
      # Expression language rules:
      # Add here any rules that should cause a request to not have a cache saved
      SaveExcludeRules:
        - 'request.getUrl() matches "{^admin|dev|cache-manager}"'

      # Add here any rules that must pass in order for a request to have a cache saved
      SaveIncludeRules:
        - "request.httpMethod() == 'GET'"
        - "response.getStatusCode() == 200"

      # Add here any rules that should cause a request to not have a cache served
      FetchExcludeRules:
        - 'request.getUrl() matches "{^admin|dev|cache-manager}"'

      # Add here any rules that must pass in order for a request to have a cache served
      FetchIncludeRules:
        - "request.httpMethod() == 'GET'"
```

As you can see above there are some variables made accessible to you in the expression language.

The following is made available in the "Save" rules:

* `request`
* `response`
* `member`
* `session`

The following is made available in the "Fetch" rules:

* `request`
* `member`
* `session`

Additional variables can be provided through the injector system.
 
```yml
Injector:
  RequestCache:
    properties:
      ExtraExpressionVars:
        'hello': 'Something'
```

### Extra performance

For even more performance from global caching, a new `main.php` file is provided which ensures database connections aren't made when a cache is available for the current request.

If using apache, replace instances of `framework/main.php` with `silverstripe-cacheinclude/main.php` in your `.htaccess` file.

## Customisation

Because of the heavy usage of dependency injection and the SilverStripe `Injector` component, most parts
of `CacheInclude` can be completely customised by replacing the standard classes with ones of your own.

### Key Creators

`CacheInclude` comes built in with one key creator `Heyday\CacheInclude\KeyCreators\ControllerBased`.
This key creator makes keys based on the config supplied in yaml, the current request and the environment.

You can create your own key creators by extending the `KeyCreatorInterface` and specifying the creator's service name from the template.

```
<% cache 'SomeBlock', 'MyKeyCreator' %>
Some content
<% end_cache %>
```

```php
class MyKeyCreator implements \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
{
    public function getKey($name, $config)
    {
        return [
           'key',
           'parts'
        ];
    }
}
```

## License

SilverStripe CacheInclude is released under the [MIT license](http://heyday.mit-license.org/)

## Contributing

### Unit Testing

```bash
$ composer install --prefer-dist --dev
$ phpunit
```

### Code guidelines

This project follows the standards defined in:

* [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
* [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
* [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

Run the following before contributing:

```bash
$ php-cs-fixer fix .
```
