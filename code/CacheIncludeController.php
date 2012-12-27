<?php
/**
 *
 * Provides ability to clear cache from sake
 *
 * @name     cache-include
 * @author   Cam Spiers (cameron [at] heyday [dot] co [dot] nz)
 *
 */
class CacheIncludeController extends Controller
{

    public function init()
    {

        if (!defined('STDIN')) {

            exit;

        }

        parent::init();

    }

    public function clearAll()
    {

        CacheIncludeExtension::clearAll();

    }

    public function clearTemplate()
    {

        if (isset($_GET['args'][0])) {

            CacheIncludeExtension::clearTemplate($_GET['args'][0], true);

        }

    }

    public function clearFolder()
    {

        if (isset($_GET['args'][0])) {

            CacheIncludeExtension::clearFolder($_GET['args'][0]);

        }

    }

}
