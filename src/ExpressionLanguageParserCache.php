<?php

namespace Heyday\CacheInclude;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

class ExpressionLanguageParserCache implements ParserCacheInterface
{
    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Saves an expression in the cache.
     *
     * @param string $key The cache key
     * @param ParsedExpression $expression A ParsedExpression instance to store in the cache
     */
    public function save($key, ParsedExpression $expression)
    {
        $this->cache->save($key, $expression);
    }

    /**
     * Fetches an expression from the cache.
     *
     * @param string $key The cache key
     *
     * @return ParsedExpression|null
     */
    public function fetch($key)
    {
        return $this->cache->fetch($key) ?: null;
    }
} 