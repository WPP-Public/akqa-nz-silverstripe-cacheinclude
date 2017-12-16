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
\$val .= \SilverStripe\Core\Injector\Injector::inst()->get($cacheInclude)->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
        \$val = '';
        {$res['Template']['php']}        return \$val;
   },
   \SilverStripe\Core\Injector\Injector::inst()->get($keyCreator)
);
PHP;
    }
    
    /**
     * Allows the use of a <% cache_include 'ConfigName' %> syntax in templates
     *
     * Also supports the optional specification of a key creator and a cache include instance
     *
     * <% cache_include 'ConfigName', 'KeyCreatorServiceName', 'CacheIncludeServiceName' %><% end_cache %>
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

        $keyCreator = isset($res['Arguments'][1]) ? $res['Arguments'][1]['text'] : "'CacheIncludeKeyCreator'";
        $cacheInclude = isset($res['Arguments'][2]) ? $res['Arguments'][2]['text'] : "'CacheInclude'";

        //[["type" => "Includes", 'Page_Environment'], 'Page_Environment']
        // todo: this is for Includes, how will it handle namespaced templates?

        return <<<PHP
\$val .= \SilverStripe\Core\Injector\Injector::inst()->get($cacheInclude)->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
       return SilverStripe\View\SSViewer::execute_template([["type" => "Includes", {$res['Arguments'][0]['text']}], {$res['Arguments'][0]['text']}], \$scope->getItem(), array(), \$scope);
   },
   \SilverStripe\Core\Injector\Injector::inst()->get($keyCreator)
);
PHP;
    }
}
