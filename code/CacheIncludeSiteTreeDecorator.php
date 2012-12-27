<?php

class CacheIncludeSiteTreeDecorator extends DataObjectDecorator
{

    public function extraStatics()
    {
        return array(
            'db' => array(
                'FullLink' => 'Varchar(255)'
            )
        );
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->owner->FullLink = $this->owner->RelativeLink();
    }

}
