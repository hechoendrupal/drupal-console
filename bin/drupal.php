<?php

use Drupal\Console\Application;
use Drupal\Console\Bootstrap\Drupal as DrupalConsole;
use Drupal\Console\ConsoleApplication;
use Drupal\Console\ConsolePaths;

set_time_limit(0);
$autoLoadFile = __DIR__ . '/../../../autoload.php';

if (file_exists($autoLoadFile)) {
    $autoload = require_once $autoLoadFile;
} else {
    echo PHP_EOL .
        ' Something goes wrong with your package.'.PHP_EOL.
        ' Try downloading again.'. PHP_EOL .
        ' Executing:'. PHP_EOL .
        ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader'. PHP_EOL;

    exit(1);
}

$reflector = new ReflectionClass(Drupal::class);
$drupalRoot = dirname(dirname(dirname($reflector->getFileName())));
chdir($drupalRoot);

$drupal = new DrupalConsole($autoload, $drupalRoot, ConsolePaths::consoleCore());
$container = $drupal->boot();

if (!$container) {
    echo 'In order to list all of the available commands,' . PHP_EOL .
         'you should install drupal first.' . PHP_EOL;

    exit(1);
}

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
