<?php

if (isset($_GET['flush']) && $_GET['flush']) {
    define('CACHEINCLUDE_FORCE_EXPIRE', true);
}
