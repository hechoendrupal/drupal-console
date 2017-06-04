<?php

use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Utils\ArgvInputReader;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Console\Application;
use Drupal\Console\Core\Utils\ConfigurationManager;

set_time_limit(0);

$autoloaders = [];

if (file_exists(__DIR__ . '/../autoload.local.php')) {
    include_once __DIR__ . '/../autoload.local.php';
} else {
    $autoloaders = [
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php'
    ];
}

foreach ($autoloaders as $file) {
    if (file_exists($file)) {
        $autoloader = $file;
        break;
    }
}

if (isset($autoloader)) {
    $autoload = include_once $autoloader;
} else {
    echo ' You must set up the project dependencies using `composer install`' . PHP_EOL;
    exit(1);
}

$drupalFinder = new DrupalFinder();
if (!$drupalFinder->locateRoot(getcwd())) {
    echo ' DrupalConsole must be executed within a Drupal Site.'.PHP_EOL;

    exit(1);
}

chdir($drupalFinder->getDrupalRoot());

$configurationManager = new ConfigurationManager();
$configuration = $configurationManager
    ->loadConfigurationFromDirectory($drupalFinder->getComposerRoot());

$argvInputReader = new ArgvInputReader();
if ($configuration && $options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();

$drupal = new Drupal($autoload, $drupalFinder);
$container = $drupal->boot();

if (!$container) {
    echo ' Something was wrong. Drupal can not be bootstrap.';

    exit(1);
}

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
