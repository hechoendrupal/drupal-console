<?php

use Drupal\Console\Utils\ArgvInputReader;
use Drupal\Console\Application;
use Drupal\Console\Bootstrap\Drupal;

set_time_limit(0);
$appRoot = getcwd() . '/';
$root = $appRoot;

$globalAutoLoadFile = $appRoot.'/autoload.php';
$projectAutoLoadFile = $appRoot.'/vendor/autoload.php';

if (file_exists($globalAutoLoadFile)) {
    $autoload = include_once $globalAutoLoadFile;
} elseif (file_exists($projectAutoLoadFile)) {
    $autoload = include_once $projectAutoLoadFile;
} else {
    echo PHP_EOL .
        ' DrupalConsole must be executed within a Drupal Site.'.PHP_EOL.
        ' Try changing to a Drupal site directory and download it by executing:'. PHP_EOL .
        ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader'. PHP_EOL .
        ' composer update drupal/console --with-dependencies'. PHP_EOL .
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

$argvInputReader = new ArgvInputReader();
if ($root === $appRoot && $argvInputReader->get('root')) {
    $appRoot = $argvInputReader->get('root');
    if (is_dir($appRoot)) {
        chdir($appRoot);
    }
    else {
        $appRoot = $root;
    }
}
$argvInputReader->setOptionsAsArgv();

$drupal = new Drupal($autoload, $root, $appRoot);
$container = $drupal->boot();

if (!$container) {
    echo ' In order to list all of the available commands you should try: ' . PHP_EOL .
         ' Copy config files: drupal init ' . PHP_EOL .
         ' Install Drupal site: drupal site:install ' . PHP_EOL;

    exit(1);
}

$configuration = $container->get('console.configuration_manager')
    ->getConfiguration();

$translator = $container->get('console.translator_manager');

if ($options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
