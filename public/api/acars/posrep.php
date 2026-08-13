<?php

$_SERVER['SCRIPT_NAME'] = '/index.php';

$indexPath = __DIR__;
while (!file_exists($indexPath . '/index.php') && dirname($indexPath) !== $indexPath) {
    $indexPath = dirname($indexPath);
}

require_once $indexPath . '/index.php';
