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
class ControllerBased implements KeyCreatorInterface, KeyInformationProviderInterface
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
     * @var string
     */
    protected $environmentType;

    /**
     * @var string
     */
    protected $currentStage;

    /**
     * @var string
     */
    protected $theme;

    /**
     * @var bool
     */
    protected $ssl;

    /**
     * @var int
     */
    protected $memberID;

    /**
     * @param  \Controller|void $controller
     * @throws \Exception
     */
    public function __construct(Controller $controller = null)
    {
        if (!$controller && !Controller::has_curr()) {
            throw new \Exception("Controller based key creators must have a current controller");
        }
        $this->controller = $controller ?: Controller::curr();
        $this->config = Config::inst();
        $this->environmentType = Director::get_environment_type();
        $this->currentStage = Versioned::current_stage();
        $this->theme = $this->config->get('SSViewer', 'theme');
        $this->ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->memberID = Member::currentUserID();
    }

    /**
     * @param        $name
     * @param        $config
     * @return mixed
     */
    public function getKey($name, $config)
    {
        $request = $this->controller->getRequest();

        $keyParts = array(
            $this->environmentType,
            $this->currentStage,
            $this->theme,
        );

        if ($this->ssl) {
            $keyParts[] = 'ssl';
        }

        if ($request->isAjax()) {
            $keyParts[] = 'ajax';
        }

        // If member context matters get the members id
        if (isset($config['member']) && $config['member'] && $this->memberID) {
            $keyParts[] = 'Members';
            if ($config['member'] !== 'any') {
                $keyParts[] = $this->memberID;
            }
        }

        // Determine the context
        if (isset($config['context'])) {
            switch ($config['context']) {
                case 'no':
                    break;
                case 'page':
                    $keyParts[] = md5($request->getURL());
                    break;
                case 'full':
                    $keyParts[] = md5($request->getURL(true));
                    break;
            }
        }

        if (isset($config['versions'])) {
            $keyParts[] = mt_rand(1, (int) $config['versions']);
        }

        $keyParts[] = $name;

        return $keyParts;
    }

    /**
     * @return array
     */
    public function getKeyInformation()
    {
        $request = $this->controller->getRequest();

        return array(
            'url' => sprintf(
                "/%s/",
                trim($request->getURL(true), '/')
            )
        );
    }

    /**
     * @param \Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param \Controller $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param string $currentStage
     */
    public function setCurrentStage($currentStage)
    {
        $this->currentStage = $currentStage;
    }

    /**
     * @param string $environmentType
     */
    public function setEnvironmentType($environmentType)
    {
        $this->environmentType = $environmentType;
    }

    /**
     * @param int $memberID
     */
    public function setMemberID($memberID)
    {
        $this->memberID = $memberID;
    }

    /**
     * @param boolean $ssl
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;
    }

    /**
     * @param string $theme
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }
}
