<?php

namespace Heyday\CacheInclude\KeyCreators;

interface KeyInformationProviderInterface
{
    /**
     * @return array
     */
    public function getKeyInformation();
}