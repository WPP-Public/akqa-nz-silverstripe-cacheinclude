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
     * @param \Heyday\CacheInclude\CacheInclude $cache
     * @param string                           $name
     * @param array                            $excludes
     */
    public function __construct(
        CacheInclude $cache,
        $name = 'Global',
        $excludes = array('/admin', '/dev')
    ) {
        $this->cache = $cache;
        $this->name = $name;
        if (is_array($excludes)) {
            $this->excludes = $excludes;
        }
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
     * If this url allows caching and there is a cached response then send it
     * @param SS_HTTPRequest $request
     * @param Session        $session
     * @param DataModel      $model
     * @return bool|void
     */
    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
        if ($request->httpMethod() === 'GET' && !$this->isExcluded($request)) {
            \Versioned::choose_site_stage();
            $response = $this->cache->get($this->name, $this->getController($request));
            if ($response instanceof SS_HTTPResponse) {
                $response->output();
                exit;
            }
        }
        return true;
    }
    /**
     * If this request allows caching then cache it
     * @param SS_HTTPRequest  $request
     * @param SS_HTTPResponse $response
     * @param DataModel       $model
     * @return bool
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
        if ($response instanceof SS_HTTPResponse && !$this->isExcluded($request)) {
            if (strpos($response->getBody(), SecurityToken::getSecurityID()) === false) {
                $response = clone $response;
                if (Director::is_ajax()) {
                    Requirements::include_in_response($response);
                }
                $this->cache->set(
                    $this->name,
                    $response,
                    $this->getController($request)
                );
            }
        }
        return true;
    }
    /**
     * @param SS_HTTPRequest $request
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
     * @param SS_HTTPRequest $request
     * @return bool
     */
    protected function isExcluded(SS_HTTPRequest $request)
    {
        $url = '/' . ltrim($request->getURL(), '/');
        foreach ($this->excludes as $exclude) {
            if (strpos($url, $exclude) === 0) {
                return true;
            }
        }
        return false;
    }
}