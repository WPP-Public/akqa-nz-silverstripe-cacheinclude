<?php

require_once __DIR__.'/../framework/core/Core.php';

// Create mock Controller to for Versioned::choose_site_stage()
/** @var Controller $controllerObj */
$controllerObj = Injector::inst()->create('Controller');
$controllerObj->pushCurrent();

/**
 * Figure out the request URL - taken from framework/main.php
 */
$url;

// Helper to safely parse and load a querystring fragment
$parseQuery = function($query) {
    parse_str($query, $_GET);
    if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
};

// Apache rewrite rules and IIS use this
if (isset($_GET['url']) && php_sapi_name() !== 'cli-server') {

    // Prevent injection of url= querystring argument by prioritising any leading url argument
    if(isset($_SERVER['QUERY_STRING']) &&
        preg_match('/^(?<url>url=[^&?]*)(?<query>.*[&?]url=.*)$/', $_SERVER['QUERY_STRING'], $results)
    ) {
        $queryString = $results['query'].'&'.$results['url'];
        $parseQuery($queryString);
    }

    $url = $_GET['url'];

    // IIS includes get variables in url
    $i = strpos($url, '?');
    if($i !== false) {
        $url = substr($url, 0, $i);
    }

    // Lighttpd and PHP 5.4's built-in webserver use this
} else {
    // Get raw URL -- still needs to be decoded below (after parsing out query string).
    $url = $_SERVER['REQUEST_URI'];

    // Querystring args need to be explicitly parsed
    if(strpos($url,'?') !== false) {
        list($url, $query) = explode('?',$url,2);
        $parseQuery($query);
    }

    // Decode URL now that it has been separated from query string.
    $url = urldecode($url);

    // Pass back to the webserver for files that exist
    if(php_sapi_name() === 'cli-server' && file_exists(BASE_PATH . $url) && is_file(BASE_PATH . $url)) {
        return false;
    }
}

// Remove base folders from the URL if webroot is hosted in a subfolder
if (substr(strtolower($url), 0, strlen(BASE_URL)) == strtolower(BASE_URL)) $url = substr($url, strlen(BASE_URL));

// Create a SS_HTTPRequest option - taken from Director::direct()
$req = new SS_HTTPRequest(
    (isset($_SERVER['X-HTTP-Method-Override']))
        ? $_SERVER['X-HTTP-Method-Override']
        : $_SERVER['REQUEST_METHOD'],
    $url,
    $_GET,
    ArrayLib::array_merge_recursive((array) $_POST, (array) $_FILES),
    @file_get_contents('php://input')
);

$headers = \Director::extract_request_headers($_SERVER);
foreach ($headers as $header => $value) {
    $req->addHeader($header, $value);
}
$controllerObj->setRequest($req);

\Versioned::choose_site_stage();

// Only skip framework/main.php if live stage
if (\Versioned::current_stage() === \Versioned::get_live_stage()) {

    $request = new SS_HTTPRequest(
        $_SERVER['REQUEST_METHOD'],
        isset($_GET['url']) ? $_GET['url'] : '',
        $_GET
    );

    $headers = Director::extract_request_headers($_SERVER);

    foreach ($headers as $header => $value) {
        $request->addHeader($header, $value);
    }

    $container = Injector::inst();

    $session = $container->create('Session', array());
    if (Session::request_contains_session_id()) {
        $session->inst_start();
    }

    $container->get('RequestProcessor')->preRequest($request, $session, DataModel::inst());
}

// Remove mock Controller
$controllerObj->popCurrent();

require_once __DIR__.'/../framework/main.php';
