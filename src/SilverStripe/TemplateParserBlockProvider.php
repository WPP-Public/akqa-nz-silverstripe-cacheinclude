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
    public static function cacheTemplate(&$res)
    {
        if (!isset($res['Arguments']) || !isset($res['Arguments'][0])) {
            throw new InvalidArgumentException('A config name must be passed into <% cache %>');
        }

        $keyCreator = isset($res['Arguments'][1]) ? $res['Arguments'][1]['text'] : "'CacheIncludeKeyCreator'";
        $cacheInclude = isset($res['Arguments'][2]) ? $res['Arguments'][2]['text'] : "'CacheInclude'";

        return <<<PHP
\$val .= \Injector::inst()->get($cacheInclude)->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
        \$val = '';
        {$res['Template']['php']}        return \$val;
   },
   \Injector::inst()->get($keyCreator)
);
PHP;
    }
}
