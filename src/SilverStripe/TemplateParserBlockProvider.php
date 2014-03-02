<?php

namespace Heyday\CacheInclude\SilverStripe;

use InvalidArgumentException;

class TemplateParserBlockProvider
{
    /**
     * @var \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
     */
    protected static $keyCreator;
    /**
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected static $cacheInclude;

    /**
     * Allows the use of a <% cache 'ConfigName' %><% end_cache %> syntax in templates
     * @param $res
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function cacheTemplate(&$res)
    {
        if (!isset($res['Arguments']) || !isset($res['Arguments'][0])) {
            throw new InvalidArgumentException('A config name must be passed into <% cache %>');
        }

        return <<<PHP
\$val .= \Heyday\CacheInclude\SilverStripe\TemplateParserBlockProvider::getCacheInclude()->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
        \$val = '';
        {$res['Template']['php']}        return \$val;
   },
   \Heyday\CacheInclude\SilverStripe\TemplateParserBlockProvider::getKeyCreator()
);
PHP;
    }

    /**
     * @return \Heyday\CacheInclude\CacheInclude
     */
    public static function getCacheInclude()
    {
        if (self::$cacheInclude === null) {
            self::$cacheInclude = \Injector::inst()->create('CacheInclude');
        }

        return self::$cacheInclude;
    }

    /**
     * @return \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
     */
    public static function getKeyCreator()
    {
        if (self::$keyCreator === null) {
            self::$keyCreator = \Injector::inst()->create('CacheIncludeKeyCreator', \Controller::curr());
        }

        return self::$keyCreator;
    }
} 