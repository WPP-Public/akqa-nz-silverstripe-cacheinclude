<?php

namespace Heyday\CacheInclude\KeyCreators;

use Config;
use Controller;
use Member;
use Versioned;
use Director;

/**
 * Class ControllerBased
 * @package Heyday\CacheInclude\KeyCreators
 */
class ControllerBased implements KeyCreatorInterface
{
    /**
     * @var \Controller
     */
    protected $controller;

    /**
     * @var \Config
     */
    protected $config;

    /**
     * @param Controller $controlller
     */
    public function __construct(Controller $controlller)
    {
        $this->controller = $controlller;
        $this->config = Config::inst();
    }

    /**
     * @param              $name
     * @param              $config
     * @return mixed
     */
    public function getKey($name, $config)
    {
        $keyParts = array(
            $this->config->get('SSViewer', 'theme'),
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
                if ($config['member'] !== 'any') {
                    $keyParts[] = $memberID;
                }
            }
        }

        //Determine the context
        switch ($config['context']) {
            case 'no':
                break;
            case 'page':
            case 'url-params':
            case 'controller':
                $keyParts[] = md5($this->controller->getRequest()->getURL());
                break;
            //Full Page Context
            case 'full':
                $keyParts[] = md5($this->controller->getRequest()->getURL(true));
                break;
        }

        if (isset($config['versions'])) {
            $keyParts[] = mt_rand(1, $config['versions']);
        }

        $keyParts[] = $name;

        return $keyParts;
    }
}
