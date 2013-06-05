<?php

namespace Heyday\CacheInclude\KeyCreators;

use Member;
use SSViewer;
use Versioned;

/**
 * Class KeyCreator
 * @package Heyday\CacheInclude\KeyCreators
 */
class KeyCreator implements KeyCreatorInterface
{
    /**
     * @param              $name
     * @param  \Controller $controller
     * @param              $config
     * @return string
     */
    public function getKey($name, \Controller $controller, $config)
    {
        $keyParts = array(
            SSViewer::current_theme(),
            Versioned::current_stage()
        );

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
                if ($controller->FullLink) {
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
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                $keyParts[] = md5(http_build_query($data));
                break;
            //Controller Context
            case 'controller':
                $keyParts[] = $controller->class;
                break;
            //Custom Controller Context
            case 'custom':
                $keyParts = $controller->CacheContext($keyParts);
                break;
        }

        $keyParts[] = $name;

        return implode(
            '.',
            (array) $keyParts
        );

    }
}
