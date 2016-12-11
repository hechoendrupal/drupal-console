<?php

use DrupalFinder\DrupalFinder;
use Drupal\Console\Utils\ArgvInputReader;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Console\Application;

set_time_limit(0);

if(file_exists(__DIR__ . '/../autoload.local.php')) {
    require_once __DIR__ . '/../autoload.local.php';
}
else {
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
    $autoload = require_once $autoloader;
}
else {
    echo ' You must set up the project dependencies using `composer install`' . PHP_EOL;
    exit(1);
}

$drupalFinder = new DrupalFinder();
$drupalFinder->locateRoot(getcwd());
$composerRoot = $drupalFinder->getComposerRoot();
$drupalRoot = $drupalFinder->getDrupalRoot();

if (!$drupalRoot || !$composerRoot) {
    echo ' DrupalConsole must be executed within a Drupal Site.'.PHP_EOL;
    exit(1);
}

chdir($drupalRoot);

$drupal = new Drupal($autoload, $composerRoot, $drupalRoot);
$container = $drupal->boot();

if (!$container) {
    echo ' Something goes wrong. Drupal can not be bootstrapped.';

    exit(1);
}

$configuration = $container->get('console.configuration_manager')
    ->getConfiguration();

$translator = $container->get('console.translator_manager');

$argvInputReader = new ArgvInputReader();
if ($options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
