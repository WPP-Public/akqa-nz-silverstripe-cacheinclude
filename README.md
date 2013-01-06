#SilverStripe Cache Include

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

##License


##Installation

To install drop the `silverstripe-cacheinclude` directory into your SilverStripe root and run `/dev/build?flush=1`.

##How to use

###Templates

    $CacheInclude(TemplateName)

##Configuration

`CacheInclude` uses a dependancy injection container (Pimple) for configuration and DI. The following options are available:

* 


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
