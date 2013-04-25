<?php

/**
 * Class CacheIncludeSiteTreeDecorator
 */
class CacheIncludeSiteTreeDecorator extends DataObjectDecorator
{
    /**
     * @return array
     */
    public function extraStatics()
    {
        return array(
            'db' => array(
                'FullLink' => 'Varchar(255)'
            )
        );
    }
    /**
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->owner->FullLink = $this->owner->RelativeLink();
    }
}
