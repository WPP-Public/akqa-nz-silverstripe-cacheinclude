<?php

/**
 * Class CacheIncludeSiteTreeExtension
 */
class CacheIncludeSiteTreeExtension extends DataExtension
{
    /**
     * @param  null  $class
     * @param  null  $extension
     * @return array
     */
    public function extraStatics($class = null, $extension = null)
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
