<?php

namespace Heyday\CacheInclude\Processors;

use InvalidArgumentException;
use SilverStripe\View\ViewableData;

class ViewableDataProcessor implements ProcessorInterface
{
    /**
     * @var ViewableData
     */
    protected $context;

    /**
     * @param ViewableData $context
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
            if ($result instanceof ViewableData && method_exists($result, 'forTemplate')) {
                return $result->forTemplate();
            }

            return $result;
        }

        return $this->context->renderWith($name);
    }
}
