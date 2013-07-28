<?php

namespace Heyday\CacheInclude\KeyCreators;

use Config;
use ContentController;
use Controller;
use Member;
use SSViewer;
use Versioned;
use Director;

/**
 * Class KeyCreator
 * @package Heyday\CacheInclude\KeyCreators
 */
class KeyCreator implements KeyCreatorInterface
{
    /**
     * @var \Config
     */
    protected $config;
    /**
     * Get a config instance
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = Config::inst();
        }
        return $this->config;
    }
    /**
     * @param              $name
     * @param  \Controller $controller
     * @param              $config
     * @return string
     */
    public function getKey($name, Controller $controller, $config)
    {
        $keyParts = array(
            $this->getConfig()->get('SSViewer', 'theme'),
            Versioned::current_stage()
        );
        
        if (Director::is_https()) {
            $keyParts[] = 'ssl';
        }
        
        if (Director::is_ajax()) {
            $keyParts[] = 'ajax';
        }

        //If member context matters get the members id
        if (isset($config['member']) && $config['member']) {
            $memberID = Member::currentUserID();
            if ($memberID) {
                $keyParts[] = 'Members';
                $keyParts[] = $memberID;
            }
        }

        //Determine the context
        switch ($config['context']) {
            case 'no':
                break;
            case 'page':
                if ($controller instanceof ContentController && $controller->data()->db('FullLink')) {
                    $keyParts = array_merge($keyParts, explode('/', $controller->FullLink));
                } else {
                    $params = $controller->getURLParams();
                    if (isset($params['URLSegment'])) {
                        $keyParts[] = $params['URLSegment'];
                    }
                }
                break;
            //Action Context
            case 'url-params':
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                break;
            //Full Page Context
            case 'full':
                $data = $controller->getRequest()->requestVars();
                if (array_key_exists('flush', $data)) {
                    unset($data['flush']);
                }
                $keyParts[] = md5(http_build_query($data));
                break;
            //Controller Context
            case 'controller':
                $keyParts[] = $controller->class;
                break;
        }

        if (isset($config['versions'])) {
            $keyParts[] = mt_rand(1, $config['versions']);
        }

        $keyParts[] = $name;

        return implode(
            '.',
            (array) $keyParts
        );

    }
}
