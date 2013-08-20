# Changelog

## 3.0.8

* Only cache 200 status codes in global request cache
* Bugfix make CacheIncludeSiteTreeExtension consistent with SilverStripe API

## 3.0.7

* Don't cache redirects in global request cache

## 3.0.6

* Only cache GET requests in global request cache

## 3.0.5

* Bugfix for when a YAML file is empty
* Support for global caching even with security tokens

## 3.0.4

* Added new opt-in global request cache feature.

## 3.0.3

* Add ssl part to key when the request is over ssl

## 3.0.2

* Add ajax part to key when the request is ajax

## 3.0.1

* Bugfix Ensure CacheInclude controller has an instance of CacheInclude

## 3.0.0

* Support for SilverStripe 3
* [BC BREAK] Removed backwards compatibility for SilverStripe 2
* Removed Pimple as DI container. Migrated to SilverStripe Injector