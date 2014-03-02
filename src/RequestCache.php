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

/**
 * Class RequestCache
 * @package Heyday\CacheInclude
 */
class RequestCache implements RequestFilter
{
    /**
     * The constant used when replacing tokens in an response
     */
    const REPLACED_TOKEN_PREFIX = '!!ReplacedToken.';
    /**
     * The instanceof CacheInclude to use for cache
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * The name that identifies which config to use
     * @var string
     */
    protected $name;
    /**
     * Rules that when true skips saving
     * @var array
     */
    protected $saveExcludeRules = array();
    /**
     * Rules that when false skip saving
     * @var array
     */
    protected $saveIncludeRules = array();
    /**
     * Rules that when true skip fetching
     * @var array
     */
    protected $fetchExcludeRules = array();
    /**
     * Rules that when false skip fetching
     * @var array
     */
    protected $fetchIncludeRules = array();
    /**
     * Tokens that should be searched for and replaced in cached output
     * @var array
     */
    protected $tokens;
    /**
     * The expression language used to evaluate rules
     * @var \Heyday\CacheInclude\ExpressionLanguage
     */
    protected $expressionLanguage;
    /**
     * Extra variables that are used when evaluating rules
     * @var array
     */
    protected $extraExpressionVars = array();

    /**
     * @param CacheInclude $cache
     * @param ExpressionLanguage $expressionLanguage
     * @param string $name
     * @param array $tokens
     */
    public function __construct(
        CacheInclude $cache,
        ExpressionLanguage $expressionLanguage,
        $name = 'Global',
        $tokens = array()
    )
    {
        $this->cache = $cache;
        $this->expressionLanguage = $expressionLanguage;
        $this->name = $name;
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
     * @param mixed $fetchExcludeRules
     */
    public function setFetchExcludeRules(array $fetchExcludeRules)
    {
        $this->fetchExcludeRules = $fetchExcludeRules;
    }

    /**
     * @param mixed $fetchIncludeRules
     */
    public function setFetchIncludeRules(array $fetchIncludeRules)
    {
        $this->fetchIncludeRules = $fetchIncludeRules;
    }

    /**
     * @param mixed $saveExcludeRules
     */
    public function setSaveExcludeRules(array $saveExcludeRules)
    {
        $this->saveExcludeRules = $saveExcludeRules;
    }

    /**
     * @param mixed $saveIncludeRules
     */
    public function setSaveIncludeRules(array $saveIncludeRules)
    {
        $this->saveIncludeRules = $saveIncludeRules;
    }

    /**
     * @param array $extraExpressionVars
     */
    public function setExtraExpressionVars($extraExpressionVars)
    {
        $this->extraExpressionVars = $extraExpressionVars;
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
        if ($this->allowFetch($request)) {
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
        if ($response instanceof SS_HTTPResponse && $this->allowSave($request, $response)) {
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
     * @param SS_HTTPRequest $request
     * @return bool
     */
    protected function allowFetch(SS_HTTPRequest $request)
    {
        $vars = array(
            'request' => $request,
            'member' => \Member::currentUser(),
            'session' => \Session::get_all()
        ) + $this->extraExpressionVars;

        if (count($this->fetchExcludeRules)) {
            foreach ($this->fetchExcludeRules as $rule) {
                if ($this->expressionLanguage->evaluate($rule, $vars)) {
                    return false;
                }
            }
        }

        if (count($this->fetchIncludeRules)) {
            foreach ($this->fetchIncludeRules as $rule) {
                if (!$this->expressionLanguage->evaluate($rule, $vars)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param SS_HTTPRequest $request
     * @param SS_HTTPResponse $response
     * @return bool
     */
    protected function allowSave(SS_HTTPRequest $request, SS_HTTPResponse $response)
    {   
        $vars = array(
            'request' => $request,
            'response' => $response,
            'member' => \Member::currentUser(),
            'session' => \Session::get_all()
        ) + $this->extraExpressionVars;

        if (count($this->saveExcludeRules)) {
            foreach ($this->saveExcludeRules as $rule) {
                if ($this->expressionLanguage->evaluate($rule, $vars)) {
                    return false;
                }
            }
        }

        if (count($this->saveIncludeRules)) {
            foreach ($this->saveIncludeRules as $rule) {
                if (!$this->expressionLanguage->evaluate($rule, $vars)) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
