<?php

namespace Heyday\CacheInclude\SilverStripe;

use InvalidArgumentException;

class TemplateParserBlockProvider
{
    /**
     * Allows the use of a <% cache 'ConfigName' %><% end_cache %> syntax in templates
     *
     * Also supports the optional specification of a key creator and a cache include instance
     *
     * <% cache 'ConfigName', 'KeyCreatorServiceName', 'CacheIncludeServiceName' %><% end_cache %>
     *
     * @param $res
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function cacheTemplate($res)
    {
        if (!isset($res['Arguments']) || !isset($res['Arguments'][0])) {
            throw new InvalidArgumentException('A config name must be passed into <% cache %>');
        }

        $keyCreator = isset($res['Arguments'][1]) ? $res['Arguments'][1]['text'] : "'CacheIncludeKeyCreator'";
        $cacheInclude = isset($res['Arguments'][2]) ? $res['Arguments'][2]['text'] : "'CacheInclude'";

        return <<<PHP
\$val .= \SilverStripe\Core\Injector\Injector::inst()->get({$cacheInclude})->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
        \$val = '';
        {$res['Template']['php']}        return \$val;
   },
   \SilverStripe\Core\Injector\Injector::inst()->get({$keyCreator})
);
PHP;
    }
    
    /**
     * Allows the use of a <% cache_include 'IncludeName' %> syntax in templates. Defaults to looking up a config
     * that matches the include name, but can be specified separately as config names must not contain special
     * characters whereras includes may (e.g. namespaces)
     *
     * Also supports the optional specification of a key creator and a cache include instance
     *
     * <% cache_include 'Include\Name', 'ConfigName', 'KeyCreatorServiceName', 'CacheIncludeServiceName' %><% end_cache %>
     *
     * @param $res
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function cacheIncludeTemplate($res)
    {
        if (!isset($res['Arguments']) || !isset($res['Arguments'][0])) {
            throw new InvalidArgumentException('A template name must be passed into <% cache_include %>');
        }

        $includeName = $res['Arguments'][0]['text'];
        $configName = isset($res['Arguments'][1]) ? $res['Arguments'][1]['text'] : $includeName;
        $keyCreator = isset($res['Arguments'][2]) ? $res['Arguments'][2]['text'] : "'CacheIncludeKeyCreator'";
        $cacheInclude = isset($res['Arguments'][3]) ? $res['Arguments'][3]['text'] : "'CacheInclude'";

        return <<<PHP
\$val .= \SilverStripe\Core\Injector\Injector::inst()->get({$cacheInclude})->process(
   {$configName},
   function () use (\$scope) {
       return \SilverStripe\View\SSViewer::execute_template({$res['Arguments'][0]['text']}, \$scope->getItem(), array(), \$scope);
   },
   \SilverStripe\Core\Injector\Injector::inst()->get({$keyCreator})
);
PHP;
    }
}
