<?php

namespace Heyday\CacheInclude\KeyCreators;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;

class ControllerBased implements KeyCreatorInterface, KeyInformationProviderInterface
{
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $environmentType;

    /**
     * @var string
     */
    protected $themes;

    /**
     * @var int
     */
    protected $memberID;

    /**
     * @param  Controller|void $controller
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

        $this->themes = $this->config->get(SSViewer::class, 'themes');
        $this->memberID = Security::getCurrentUser() ? Security::getCurrentUser()->ID : 0;
    }

    /**
     * @param        $name
     * @param        $config
     * @return mixed
     */
    public function getKey($name, $config)
    {
        $request = $this->controller->getRequest();

        $keyParts = [
            $this->environmentType,
            md5(json_encode($this->themes)),
            $request->getScheme()
        ];

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
                case 'host':
                    $keyParts[] = md5(Director::absoluteBaseURL());
                    break;
                case 'page':
                    $keyParts[] = md5(Director::absoluteBaseURL().$request->getURL());
                    break;
                case 'full':
                    $keyParts[] = md5(Director::absoluteBaseURL().$request->getURL(true));
                    break;
            }
        }

        if (isset($config['subsite']) && $config['subsite']) {
            $keyParts[] = 'subsite-' . $request->param('SubsiteID');
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

        return [
            'url' => sprintf('/%s/', trim($request->getURL(true), '/'))
        ];
    }

    /**
     * @param Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param Controller $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
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
     * @param array $themes
     */
    public function setThemes(array $themes)
    {
        $this->themes = $themes;
    }
}
