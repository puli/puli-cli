<?php

require_once __DIR__.'/../vendor/autoload.php';

use Humbug\FileGetContents;

var_dump(FileGetContents::getSystemCaRootBundlePath());
