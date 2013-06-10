<?php

/**
 * Class CacheIncludeSiteTreeExtension
 */
class CacheIncludeSiteTreeExtension extends DataExtension
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

        // Ensure that the method exists
        if (method_exists($this->owner, 'RelativeLink')) {
            $this->owner->FullLink = $this->owner->RelativeLink();
        }
    }
}
