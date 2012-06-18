<?php

class CacheIncludeQueueItem extends DataObject
{

    static $db = array(
        'Template' => 'Varchar(255)'
    );

    public static function canAdd($template)
    {

        $record = DB::query("SELECT COUNT(ID) AS Num FROM `CacheIncludeQueueItem` WHERE `Template` = '$template'")->nextRecord();

        return $record['Num'] == 0;

    }

    public static function add($template)
    {

        if (self::canAdd($template)) {

            DB::query("INSERT INTO `CacheIncludeQueueItem` SET `Template` = '$template'");

        }

    }

    public static function remove($template)
    {

         DB::query("DELETE FROM `CacheIncludeQueueItem` WHERE `Template` = '$template'");

    }

    public static function getAll()
    {

         return DB::query("SELECT `Template` FROM `CacheIncludeQueueItem`");

    }

}