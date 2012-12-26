<?php

class CacheIncludeKeyCreator implements CacheIncludeKeyCreatorInterface
{
    public function getKey($name, Controller $controller, $config)
    {

        $keyParts = array(
            SSViewer::current_theme(),
            Versioned::current_stage()
        );

        //If member context matters get the members id
        if ($config['member']) {
            $memberID = Member::currentUserID();
            if ($memberID) {
                $keyParts[] = 'Members';
                $keyParts[] = $memberID;
            }
        }

        //Determine the context
        switch ($config['context']) {
            //No Context
            case 0:
            case 'no':
                break;
            //Page Context
            case 1:  
            case 'page':           
                $keyParts = $controller->FullLink 
                    ? array_merge($keyParts, explode('/', $controller->FullLink))
                    : array_merge($keyParts, array($controller->URLSegment));
                break;
            //Action Context
            case 2:
            case 'action':
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                break;
            //Full Page Context
            case 3:
            case 'full-page':
                $data = $controller->getRequest()->requestVars();
                if (array_key_exists('flush', $data)) {
                    unset($data['flush']);
                }
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                $keyParts[] = md5(http_build_query($data));
                break;
            //Controller Context
            case 4:
            case 'controller':
                $keyParts[] = $controller->class;
                break;
            //Full Controller Context
            case 5:
            case 'full-controller':
                $keyParts[] = $controller->class;
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                break;
            //Custom Controller Context
            case 6:
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
