<?php

namespace Heyday\CacheInclude\SilverStripe;

use Heyday\CacheInclude\CacheInclude;
use Heyday\CacheInclude\ExpressionLanguage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Startup\ErrorDirector;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;

/**
 * Class RequestCache
 * @package Heyday\CacheInclude
 */
class RequestCacheMiddleware implements HTTPMiddleware
{
    /**
     * The constant used when replacing tokens in an response
     */
    const REPLACED_TOKEN_PREFIX = '!!ReplacedToken.';

    /**
     * @var boolean
     */
    protected $sessionHasFormErrors = null;

    /**
     * @var CacheInclude
     */
    protected $cache;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $saveExcludeRules = [];

    /**
     * @var array
     */
    protected $saveIncludeRules = [];

    /**
     * @var array
     */
    protected $fetchExcludeRules = [];

    /**
     * @var array
     */
    protected $fetchIncludeRules = [];

    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var array
     */
    protected $extraExpressionVars = [];

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
        $tokens = []
    )
    {
        $this->cache = $cache;
        $this->expressionLanguage = $expressionLanguage;
        $this->name = $name;
        $this->setTokens($tokens);
    }

    /**
     * @param CacheInclude $cache
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
     * Checks whether the middleware has been called by ErrorDirector instead of Director,
     * indicating that we're in an unstable state unsuitable for caching (e.g. dev/build)
     *
     * @return bool
     */
    protected function getIsInErrorDirector()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && $frame['class'] === ErrorDirector::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return bool|HTTPResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->getIsInErrorDirector()) {
            return $delegate($request);
        }

        if ($this->allowFetch($request) && $response = $this->getCachedResponse($request)) {
            header("X-HeydayCache: hit at " . @date('r'));
            return $response;
        }

        $response = $delegate($request);

        if ($this->allowSave($request, $response)) {
            $this->saveResponseToCache($response, $request);
        }

        return $response;
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getCachedResponse(HTTPRequest $request)
    {
        Versioned::choose_site_stage($request);
        if ($request->getURL() == '') {
            $request = clone $request;
            $request->setUrl('home');
        }
        $response = $this->cache->get(
            $this->name,
            Injector::inst()->create('CacheIncludeKeyCreator', $this->getController($request))
        );

        if (!$response instanceof HTTPResponse) {
            return null;
        }

        $response = $this->replaceTokensInResponseBody(
            $response,
            function($token) { return self::REPLACED_TOKEN_PREFIX . $token->getName(); },
            function($token) { return $token->getValue(); }
        );

        return $response;
    }

    /**
     * @param HTTPResponse $response
     * @param HTTPRequest $request
     */
    protected function saveResponseToCache(HTTPResponse $response, HTTPRequest $request)
    {
        $response = $this->replaceTokensInResponseBody(
            clone $response,
            function($token) { return $token->getValue(); },
            function($token) { return self::REPLACED_TOKEN_PREFIX . $token->getName(); }
        );

        $this->cache->set(
            $this->name,
            $response,
            Injector::inst()->create('CacheIncludeKeyCreator', $this->getController($request))
        );
    }

    /**
     * @param HTTPResponse $response
     * @param callable $find
     * @param callable $replace
     * @return HTTPResponse
     */
    protected function replaceTokensInResponseBody(HTTPResponse $response, callable $find, callable $replace) {
        if (!$this->hasTokens()) {
            return $response;
        }

        $body = $response->getBody();
        foreach ($this->tokens as $token) {
            $body = str_replace($find($token), $replace($token), $body);
        }

        return $response->setBody($body);
    }

    /**
     * @param  HTTPRequest $request
     * @return Controller
     */
    protected function getController(HTTPRequest $request)
    {
        $controller = new Controller();
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
        $vars = [
            'request' => $request,
            'member' => Security::getCurrentUser(),
            'session' => $request->getSession()->getAll()
        ] + $this->extraExpressionVars;

        if ($this->getSessionHasFormErrors($request->getSession())) {
            return false;
        }

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
        $vars = [
            'request' => $request,
            'response' => $response,
            'member' => Security::getCurrentUser(),
            'session' => $request->getSession()->getAll()
        ] + $this->extraExpressionVars;

        if ($this->getSessionHasFormErrors($request->getSession())) {
            return false;
        }

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
     * Store this value on the instance as the session gets cleared between
     * allowFetch and allowSave when the request is processed.
     * @param
     * @param Session $session
     * @return boolean
     */
    protected function getSessionHasFormErrors(Session $session)
    {
        if (isset($this->sessionHasFormErrors)) {
            return $this->sessionHasFormErrors;
        } else {
            return $this->sessionHasFormErrors = $this->checkIfSessionHasFormErrors($session);
        }
    }

    /**
     * Are there any form errors in session?
     * @param
     * @param Session $session
     * @return boolean
     */
    protected function checkIfSessionHasFormErrors(Session $session)
    {
        if ($session->getAll()) {
            foreach ($session->getAll() as $field => $data) {
                // Check for session details in the form FormInfo.{$FormName}.errors/FormInfo.{$FormName}.formError
                if ($field === 'FormInfo') {
                    foreach ($data as $formData) {
                        if (isset($formData['result'])) {
                            $resultData = unserialize($formData['result']);
                            if (!$resultData->isValid()) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}
