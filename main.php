<?php

require_once __DIR__.'/../framework/core/Core.php';

SilverStripe\Versioned\Versioned::choose_site_stage();

// Only skip framework/main.php if live stage
if (\Versioned::current_stage() == \Versioned::get_live_stage()) {

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

require_once __DIR__.'/../framework/main.php';
