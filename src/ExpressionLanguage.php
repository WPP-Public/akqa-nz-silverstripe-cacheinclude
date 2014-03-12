<?php

namespace Heyday\CacheInclude;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

/**
 * Class ExpressionLanguage
 * @package Heyday\CacheInclude
 */
class ExpressionLanguage extends SymfonyExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register(
            'list',
            array($this, 'listCompiler'),
            array($this, 'listEvaluator')
        );

        $this->register(
            'instanceof',
            array($this, 'instanceofCompiler'),
            array($this, 'instanceofEvaluator')
        );
    }

    /**
     * @param $arg
     * @return string
     */
    public function listCompiler($arg)
    {
        return sprintf('%s::get()', $arg);
    }

    /**
     * @param array $variables
     * @param $value
     * @return static
     */
    public function listEvaluator(array $variables, $value)
    {
        return \DataList::create($value);
    }

    /**
     * @param $arg0
     * @param $arg1
     * @return string
     */
    public function instanceofCompiler($arg0, $arg1)
    {
        return sprintf('%s instanceof %s', $arg0, $arg1);
    }

    /**
     * @param array $variables
     * @param $arg0
     * @param $arg1
     * @return bool
     */
    public function instanceofEvaluator(array $variables, $arg0, $arg1)
    {
        return $arg0 instanceof $arg1;
    }
}