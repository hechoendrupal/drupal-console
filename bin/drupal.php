<?php

use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Utils\ArgvInputReader;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Console\Application;


set_time_limit(0);

$autoload = FALSE;

if (strpos(__DIR__, '/vendor/') !== FALSE) {
  $composerPath = substr(__DIR__, 0, strpos(__DIR__, '/vendor/'));
  $projectAutoLoadFile = $composerPath . '/vendor/autoload.php';
  if (file_exists($projectAutoLoadFile)) {
    $autoload = include_once $projectAutoLoadFile;
  }
}

if (!$autoload) {
  echo PHP_EOL .
    ' Unable to discover composer autoload file.' . PHP_EOL .
    ' Try changing to a Drupal site directory and download it by executing:' . PHP_EOL .
    ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader' . PHP_EOL .
    ' composer update drupal/console --with-dependencies' . PHP_EOL .
    PHP_EOL;

  exit(1);
}

$argvInputReader = new ArgvInputReader();
if ($argvInputReader->get('root')) {
  $appRoot = $argvInputReader->get('root');
  if (is_dir($appRoot)) {
    chdir($appRoot);
  }
  if (substr($appRoot, -1) != '/') {
    $appRoot .= '/';
  }
}

$root = $appRoot;

if (!file_exists($appRoot . 'composer.json')) {
  $root = realpath($appRoot . '../') . '/';
}

if (!file_exists($root . 'composer.json')) {
  echo ' No composer.json file found at:' . PHP_EOL .
    ' ' . $root . PHP_EOL .
    ' you should try run this command,' . PHP_EOL .
    ' from the Drupal root directory.' . PHP_EOL;

  exit(1);
}

/* relocate to a class */
$today = date('Y-m-d');
$loggerFile = $root.'console/log/' . $today . '.log';
$handle = null;

if (!is_file($loggerFile)) {
    try {
        $directoryName = dirname($loggerFile);
        if (!is_dir($directoryName )) {
            mkdir($directoryName, 0777, TRUE);
        }
        touch($loggerFile);
    } catch (\Exception $e) {
        $loggerFile = null;
        $loggerOutput = new ConsoleOutput();
    }
}
if ($loggerFile && is_writable($loggerFile)) {
    try {
        $handle = fopen($loggerFile, 'a+');
        $loggerOutput = new StreamOutput($handle);
    } catch (\Exception $e) {
        $loggerOutput = new ConsoleOutput();
    }
} else {
    $loggerOutput = new ConsoleOutput();
}
/* relocate to a class */

$argvInputReader = new ArgvInputReader();
$configurationManager = new ConfigurationManager();
$configuration = $configurationManager->loadConfiguration($root)
    ->getConfiguration();
if ($options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();

if ($root === $appRoot && $argvInputReader->get('root')) {
    $appRoot = $argvInputReader->get('root');
    if (is_dir($appRoot)) {
        chdir($appRoot);
    }
    else {
        $appRoot = $root;
    }
}

$drupal = new Drupal($autoload, $root, $appRoot, $loggerOutput);
$container = $drupal->boot();

/* relocate to a class */
if ($handle) {
    fclose($handle);
}
/* relocate to a class */

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
