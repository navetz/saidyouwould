#!/usr/bin/env php
<?php

use Symfony\Component\Console\Input\ArrayInput;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

exit($app->handleCommand(new ArrayInput(['command' => 'oyl:deliver-due'])));
