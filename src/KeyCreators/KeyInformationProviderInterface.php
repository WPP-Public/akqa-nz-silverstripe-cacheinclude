<?php

namespace Heyday\CacheInclude\KeyCreators;

/**
 * @package Heyday\CacheInclude\KeyInformationProvider
 */
interface KeyInformationProviderInterface
{
    /**
     * @return array
     */
    public function getKeyInformation();
}