<?php

declare(strict_types=1);

const TESTS_PATH = __DIR__;
define('TESTS_TEMP_DIR', sys_get_temp_dir().'/doctrine-extension-tests');
define('VENDOR_PATH', realpath(dirname(__DIR__).'/vendor'));

require dirname(__DIR__).'/vendor/autoload.php';
