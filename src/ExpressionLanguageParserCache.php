<?php

namespace Heyday\CacheInclude;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

class ExpressionLanguageParserCache implements ParserCacheInterface
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct()
    {
        $this->cache = Injector::inst()->get(CacheInterface::class . '.CacheInclude');
    }

    /**
     * Saves an expression in the cache.
     *
     * @param string $key The cache key
     * @param ParsedExpression $expression A ParsedExpression instance to store in the cache
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function save($key, ParsedExpression $expression)
    {
        $this->cache->set($key, $expression);
    }

    /**
     * Fetches an expression from the cache.
     *
     * @param string $key The cache key
     *
     * @return ParsedExpression|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function fetch($key)
    {
        return $this->cache->get($key) ?: null;
    }
}
