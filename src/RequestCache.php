<?php

namespace Heyday\CacheInclude;

use Controller;
use DataModel;
use Director;
use RequestFilter;
use Requirements;
use SecurityToken;
use Session;
use SS_HTTPRequest;
use SS_HTTPResponse;

class RequestCache implements RequestFilter
{
    /**
     *
     */
    const REPLACED_TOKEN_PREFIX = '!!ReplacedToken.';
    /**
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var array
     */
    protected $excludes;
    /**
     * @var array
     */
    protected $cachableResponseCodes = array(
        200
    );
    /**
     * @var array
     */
    protected $tokens;

    /**
     * @param \Heyday\CacheInclude\CacheInclude $cache
     * @param string                            $name
     * @param array                             $excludes
     * @param array                             $tokens
     */
    public function __construct(
        CacheInclude $cache,
        $name = 'Global',
        $excludes = array('/admin', '/dev'),
        $tokens = array()
    )
    {
        $this->cache = $cache;
        $this->name = $name;
        if (is_array($excludes)) {
            $this->excludes = $excludes;
        }
        $this->setTokens($tokens);
    }

    /**
     * @param \Heyday\CacheInclude\CacheInclude $cache
     */
    public function setCache(CacheInclude $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param mixed $excludes
     */
    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;
    }

    /**
     * @return mixed
     */
    public function getExcludes()
    {
        return $this->excludes;
    }

    /**
     * @param $tokens
     */
    public function setTokens($tokens)
    {
        if (is_array($tokens)) {
            foreach ($tokens as $token) {
                if ($token instanceof SecurityToken) {
                    $this->tokens[] = $token;
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function hasTokens()
    {
        return count($this->tokens) > 0;
    }

    /**
     * @param array $cachableResponseCodes
     */
    public function setCacheableResponseCodes($cachableResponseCodes)
    {
        if (is_array($cachableResponseCodes)) {
            $this->cachableResponseCodes = $cachableResponseCodes;
        }
    }

    /**
     * If this url allows caching and there is a cached response then send it
     * @param  SS_HTTPRequest $request
     * @param  Session        $session
     * @param  DataModel      $model
     * @return bool|void
     */
    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
        if (!$this->isExcluded($request)) {
            \Versioned::choose_site_stage();
            if ($request->getURL() == '') {
                $request = clone $request;
                $request->setUrl('home');
            }
            $response = $this->cache->get(
                $this->name,
                \Injector::inst()->create(
                    'CacheIncludeKeyCreator',
                    $this->getController($request)
                )
            );
            if ($response instanceof SS_HTTPResponse) {
                // replace in body
                if ($this->hasTokens()) {
                    $body = $response->getBody();
                    foreach ($this->tokens as $token) {
                        $name = self::REPLACED_TOKEN_PREFIX . $token->getName();
                        if (strpos($body, $name) !== false) {
                            $body = str_replace($name, $token->getValue(), $body);
                        }
                    }
                    $response->setBody($body);
                }
                $session->save();
                $response->output();
                exit;
            }
        }

        return true;
    }

    /**
     * If this request allows caching then cache it
     * @param  SS_HTTPRequest  $request
     * @param  SS_HTTPResponse $response
     * @param  DataModel       $model
     * @return bool
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
        if ($response instanceof SS_HTTPResponse && !$this->isExcluded($request, $response)) {
            $response = clone $response;
            if ($this->hasTokens()) {
                $body = $response->getBody();
                foreach ($this->tokens as $token) {
                    $val = $token->getValue();
                    if (strpos($body, $val) !== false) {
                        $body = str_replace($val, self::REPLACED_TOKEN_PREFIX . $token->getName(), $body);
                    }
                }
                $response->setBody($body);
            }
            if (Director::is_ajax()) {
                Requirements::include_in_response($response);
            }
            $this->cache->set(
                $this->name,
                $response,
                \Injector::inst()->create(
                    'CacheIncludeKeyCreator',
                    $this->getController($request)
                )
            );
        }

        return true;
    }

    /**
     * @param  SS_HTTPRequest $request
     * @return Controller
     */
    protected function getController(SS_HTTPRequest $request)
    {
        $controller = new Controller();
        $controller->setRequest($request);
        $controller->setURLParams($request->allParams());

        return $controller;
    }

    /**
     * @param  SS_HTTPRequest  $request
     * @param  SS_HTTPResponse $response
     * @return bool
     */
    protected function isExcluded(SS_HTTPRequest $request, SS_HTTPResponse $response = null)
    {
        if ($response instanceof SS_HTTPResponse) {
            // Don't cache non-200 responses
            if (!in_array($response->getStatusCode(), $this->cachableResponseCodes)) {
                return true;
            }
        }
        if ($request->httpMethod() !== 'GET') {
            return true;
        } else {
            $url = '/' . ltrim($request->getURL(), '/');
            foreach ($this->excludes as $exclude) {
                if (strpos($url, $exclude) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
