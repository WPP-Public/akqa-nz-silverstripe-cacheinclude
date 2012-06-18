#Heyday Cache Include

HTML Caching can be added to your SilverStripe project by replacing <% include X %> calls with $CacheInclude(X) calls.

##License


##Installation

To install drop the `heyday-cacheinclude` directory into your SilverStripe root and run `/dev/build?flush=1`.

##How to use



##Configuration



##Clearing Cache



##Unit Testing

If you have `phpunit` installed you can run `heyday-cacheinclude`'s unit tests to see if everything is functioning correctly.

###Running the unit tests

From the command line:
    
    ./sake dev/tests/module/heyday-cacheinclude


From your browser:

    http://localhost/dev/tests/module/heyday-cacheinclude

##Contributing

###Code guidelines

This project follows the standards defined in:

* [PSR-1](https://github.com/pmjones/fig-standards/blob/psr-1-style-guide/proposed/PSR-1-basic.md)
* [PSR-2](https://github.com/pmjones/fig-standards/blob/psr-1-style-guide/proposed/PSR-2-advanced.md)
