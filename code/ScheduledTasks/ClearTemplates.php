<?php

class ClearTemplates extends ScheduledTask
{

    public function process()
    {

        $processList = CacheIncludeQueueItem::getAll();

        if ($processList) {

            foreach ($processList as $record) {

                CacheIncludeExtension::clearTemplate($record['Template'], true);

                CacheIncludeQueueItem::remove($record['Template']);

                echo 'Removed: ', $record['Template'], PHP_EOL;

            }

        }

        echo 'Completed', PHP_EOL;

    }

}
