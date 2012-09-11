<?php

class CacheIncludeContext implements CacheIncludeContextInterface
{

    public function context($controller, $config)
    {

        $keyParts = array(
            SSViewer::current_theme()
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
            case 0: //No Context
                break;
            case 1: //Page Context
                $keyParts[] = $controller->URLSegment;
                break;
            case 2: //Action Context
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                break;
            case 3: //Full Page Context
                $data = $controller->getRequest()->requestVars();

                if (array_key_exists('flush', $data)) {

                    unset($data['flush']);

                }

                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));

                $keyParts[] = md5(http_build_query($data));
                break;
            case 4: //Controller Context
                $keyParts[] = $controller->class;
                break;
            case 5: //Full Controller Context
                $keyParts[] = $controller->class;
                $keyParts = array_merge($keyParts, array_filter($controller->getURLParams()));
                break;
            case 6: //Custom Controller Context
                $keyParts = $controller->CacheContext($keyParts);
                break;
        }

        return $keyParts;

    }

}
