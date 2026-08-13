<?php

$indexPath = __DIR__;
while (!file_exists($indexPath . '/index.php') && dirname($indexPath) !== $indexPath) {
    $indexPath = dirname($indexPath);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $indexPath . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require_once $indexPath . '/index.php';
