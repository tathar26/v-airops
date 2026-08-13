<?php

// Physical bridge file to allow Nginx (with try_files $uri =404) to load Laravel
$indexPath = __DIR__;
while (!file_exists($indexPath . '/index.php') && dirname($indexPath) !== $indexPath) {
    $indexPath = dirname($indexPath);
}

require_once $indexPath . '/index.php';
