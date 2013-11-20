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
            case 'url-params':
            case 'controller':
                $keyParts[] = md5($controller->getRequest()->getURL());
                break;
            //Full Page Context
            case 'full':
                $keyParts[] = md5($controller->getRequest()->getURL(true));
                break;
        }

        if (isset($config['versions'])) {
            $keyParts[] = mt_rand(1, $config['versions']);
        }

        $keyParts[] = $name;

        return implode('.', $keyParts);

    }
}
