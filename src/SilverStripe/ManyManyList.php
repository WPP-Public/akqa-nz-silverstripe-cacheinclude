<?php

namespace Heyday\CacheInclude\SilverStripe;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList as SilverStripeManyManyList;

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

        if ($item instanceof DataObject) {
            $item->extend('onAfterManyManyRelationAdd');
        }
    }

    /**
     * @param int $itemID
     */
    public function removeByID($itemID)
    {
        $result = parent::removeByID($itemID);
        $item = DataList::create($this->dataClass)->byID($itemID);

        if ($item instanceof $this->dataClass) {
            $item->extend('onAfterManyManyRelationRemove');
        }

        return $result;
    }
}