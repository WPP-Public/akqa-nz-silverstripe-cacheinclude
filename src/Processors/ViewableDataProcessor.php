<?php

namespace Heyday\CacheInclude\Processors;

use InvalidArgumentException;
use ViewableData;

/**
 * Class Processor
 * @package Heyday\CacheInclude\Processors
 */
class ViewableDataProcessor implements ProcessorInterface
{
    /**
     * @var
     */
    protected $context;

    /**
     * @param  \ViewableData $context
     * @return $this
     */
    public function setContext(ViewableData $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param $name
     * @return string
     * @throws \InvalidArgumentException
     */
    public function __invoke($name)
    {
        if (!$this->context instanceof ViewableData) {
            throw new InvalidArgumentException('Context must be instance of ViewableData');
        }

        if ($this->context->hasMethod($name)) {
            $result = $this->context->$name();

            return $result instanceof ViewableData ? $result->forTemplate() : $result;
        }

        return $this->context->renderWith($name);
    }
}
