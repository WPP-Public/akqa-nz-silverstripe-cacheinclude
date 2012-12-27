<?php

namespace Heyday\CacheInclude\Processors;

use Heyday\CacheInclude\ProcessorInterface;

class Processor implements ProcessorInterface
{
    protected $context;

    public function setContext(\ViewableData $context)
    {
        $this->context = $context;

        return $this;
    }

    public function __invoke($name)
    {
        if (!$this->context instanceof \ViewableData) {
            throw new \InvalidArgumentException('Context must be instance of ViewableData');
        }
        if ($this->context->hasMethod($name)) {
            $result = $this->context->$name();

            return $result instanceof \ViewableData ? $result->forTemplate() : $result;
        }

        return $this->context->renderWith($name);
    }
}
