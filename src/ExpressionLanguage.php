<?php

namespace Heyday\CacheInclude;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

/**
 * Class ExpressionLanguage
 * @package Heyday\CacheInclude
 */
class ExpressionLanguage extends SymfonyExpressionLanguage implements \Serializable
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register(
            'list',
            function ($arg) {
                return sprintf('%s::get()', $arg);
            },
            function (array $variables, $value) {
                return \DataList::create($value);
            }
        );

        $this->register(
            'instanceof',
            function ($arg0, $arg1) {
                return sprintf('%s instanceof %s', $arg0, $arg1);
            },
            function (array $variables, $arg0, $arg1) {
                return $arg0 instanceof $arg1;
            }
        );

        $this->register(
            'key_exists',
            function ($arg0, $arg1) {
                return sprintf('array_key_exists(%s, %s)', $arg0, $arg1);
            },
            function (array $variables, $arg0, $arg1) {
                return array_key_exists($arg0, $arg1);
            }
        );
    }

    /**
     * @return null
     */
    public function serialize()
    {
        return null;
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->__construct();
    }
}
