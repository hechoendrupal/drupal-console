<?php

use Drupal\Console\Application;
use Drupal\Console\Bootstrap\Drupal;

set_time_limit(0);
$appRoot = getcwd() . '/';
$root = $appRoot;

$autoLoadFile = $appRoot.'/autoload.php';

if (file_exists($autoLoadFile)) {
    $autoload = include_once $autoLoadFile;
} else {
    echo PHP_EOL .
        ' DrupalConsole must be executed within a Drupal Site.'.PHP_EOL.
        ' Try changing to a Drupal site directory and download it by executing:'. PHP_EOL .
        ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader'. PHP_EOL .
        PHP_EOL;

    exit(1);
}

if (!file_exists($appRoot.'composer.json')) {
    $root = realpath($appRoot . '../') . '/';
}

if (!file_exists($root.'composer.json')) {
    echo ' No composer.json file found at:' . PHP_EOL .
         ' '. $root . PHP_EOL .
         ' you should try run this command,' . PHP_EOL .
         ' from the Drupal root directory.' . PHP_EOL;

    exit(1);
}

$drupal = new Drupal($autoload, $root, $appRoot);
$container = $drupal->boot();

if (!$container) {
    echo ' In order to list all of the available commands you should try: ' . PHP_EOL .
         ' Copy config files: drupal init ' . PHP_EOL .
         ' Install Drupal site: drupal site:install ' . PHP_EOL;

    exit(1);
}

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
