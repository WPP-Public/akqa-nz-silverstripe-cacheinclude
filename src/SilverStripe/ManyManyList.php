<?php

namespace Heyday\CacheInclude\SilverStripe;

use ManyManyList as SilverStripeManyManyList;

/**
 * @package Heyday\CacheInclude\SilverStripe
 */
class ManyManyList extends SilverStripeManyManyList
{
    /**
     * @param mixed $item
     * @param null $extraFields
     * @throws \Exception
     */
    public function add($item, $extraFields = null)
    {
        parent::add($item, $extraFields);

        if ($item instanceof \DataObject) {
            $item->extend('onAfterManyManyRelationAdd');
        }
    }

    /**
     * @param int $itemID
     */
    public function removeByID($itemID)
    {
        $result = parent::removeByID($itemID);

        $item = \DataList::create($this->dataClass)->byId($itemID);

        if ($item instanceof $this->dataClass) {
            $item->extend('onAfterManyManyRelationRemove');
        }

        return $result;
    }
}