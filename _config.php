<?php

if (file_exists(__DIR__ . '../vendor/autoload.php')) {
    require_once __DIR__ . '../vendor/autoload.php';
}

if (isset($_GET['flush']) && $_GET['flush']) {
    define('CACHEINCLUDE_FORCE_EXPIRE', true);
}
