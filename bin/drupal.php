<?php

use Drupal\Console\Application;
use Drupal\Console\Utils\Bootstrap\Drupal;

set_time_limit(0);
$consoleRoot = realpath(__DIR__.'/../') . '/';
$root = getcwd() . '/';
$siteRoot = realpath(__DIR__.'/../../../../') . '/';

$autoLoadFile = $root.'/autoload.php';

if (file_exists($autoLoadFile)) {
    $autoload = include_once $autoLoadFile;
} else {
    echo PHP_EOL .
        ' Something goes wrong with your package.'.PHP_EOL.
        ' Try downloading again.'. PHP_EOL .
        ' Executing:'. PHP_EOL .
        ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader'. PHP_EOL;

    exit(1);
}

$drupal = new Drupal($autoload, $consoleRoot, $siteRoot);
$container = $drupal->boot();

if (!$container) {
    echo 'Drupal not installed.' . PHP_EOL;
}

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
