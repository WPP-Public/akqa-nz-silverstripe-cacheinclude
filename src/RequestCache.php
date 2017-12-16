<?php

namespace Heyday\CacheInclude;

use Heyday\CacheInclude\SilverStripe\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\RequestFilter;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;

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
     * @param CacheInclude       $cache
     * @param ExpressionLanguage $expressionLanguage
     * @param string             $name
     * @param array              $tokens
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
     * If this url allows caching and there is a cached response then send it
     *
     * @param HTTPRequest $request
     * @return bool
     */
    public function preRequest(HTTPRequest $request)
    {
        if ($this->allowFetch($request)) {
            Versioned::choose_site_stage($request);
            if ($request->getURL() == '') {
                $request = clone $request;
                $request->setUrl('home');
            }
            $response = $this->cache->get(
                $this->name,
                Injector::inst()->create(
                    'CacheIncludeKeyCreator',
                    $this->getController($request)
                )
            );
            if ($response instanceof HTTPResponse) {
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
//                $session->save();
                $response->output();
                exit;
            }
        }

        return true;
    }

    /**
     * If this request allows caching then cache it
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @return bool
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        if ($response instanceof HTTPResponse && $this->allowSave($request, $response)) {
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
                Injector::inst()->create(
                    'CacheIncludeKeyCreator',
                    $this->getController($request)
                )
            );
        }

        return true;
    }

    /**
     * @param  HTTPRequest $request
     * @return Controller
     */
    protected function getController(HTTPRequest $request)
    {
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->setURLParams($request->allParams());

        return $controller;
    }

    /**
     * @param  HTTPRequest $request
     * @return bool
     */
    protected function allowFetch(HTTPRequest $request)
    {
        $vars = array(
            'request' => $request,
            'member' => function () { return $this->getMember(); },
            'session' => function () { return Session::get_all(); }
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
     * @param  HTTPRequest  $request
     * @param  HTTPResponse $response
     * @return bool
     */
    protected function allowSave(HTTPRequest $request, HTTPResponse $response)
    {
        $vars = array(
            'request' => $request,
            'response' => $response,
            'member' => Security::getCurrentUser(),
            'session' => Session::get_all()
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

    /**
     * @return Member
     */
    public function getMember()
    {
        if (!DB::get_conn()) {
            global $databaseConfig;
            if ($databaseConfig) {
                DB::connect($databaseConfig);
            }
        }
        return Security::getCurrentUser();
    }
}
