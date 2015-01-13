<?php

use Drupal\AppConsole\Console\Shell;
use Drupal\AppConsole\Console\Application;
use Drupal\AppConsole\Command\Helper\ShellHelper;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Helper\KernelHelper;
use Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper;
use Drupal\AppConsole\Command\Helper\BootstrapFinderHelper;
use Drupal\AppConsole\Command\Helper\DrupalCommonHelper;
use Drupal\AppConsole\Command\Helper\RegisterCommandsHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\TableHelper;
use Drupal\AppConsole\Utils\StringUtils;
use Drupal\AppConsole\Utils\Validators;
use Symfony\Component\Yaml\Parser;
use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

set_time_limit(0);

// Try to find the Console autoloader.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
  require __DIR__ . '/../vendor/autoload.php';
}
else if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
  require __DIR__ . '/../../../vendor/autoload.php';
}
else {
  echo 'Something goes wrong with your archive'.PHP_EOL.
    'Try downloading again'.PHP_EOL;
  exit(1);
}

$directoryRoot = __DIR__ . '/../';

$yaml = new Parser();
$config = $yaml->parse(file_get_contents($directoryRoot.'config.yml'));

$homeDirectory = trim(getenv('HOME') ?: getenv('USERPROFILE'));
if (file_exists($homeDirectory.'/.console/config.yml')){
  $userConfig = $yaml->parse(file_get_contents($homeDirectory.'/.console/config.yml'));
  unset($userConfig['application']['name']);
  unset($userConfig['application']['version']);
  $config = array_replace_recursive($config, $userConfig);
}

$application = new Application($config);
$application->setDirectoryRoot($directoryRoot);

// Try to find the Drupal autoloader.
if (file_exists(getcwd() . '/core/vendor/autoload.php')) {
  $class_loader = require getcwd() . '/core/vendor/autoload.php';
  $application->setBooted(true);
} else {
  $class_loader = null;
}

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config['application']['language'], $directoryRoot);

$helpers = [
  'bootstrap' => new DrupalBootstrapHelper(),
  'finder' => new BootstrapFinderHelper(new Finder()),
  'kernel' => new KernelHelper(),
  'shell' => new ShellHelper(new Shell($application)),
  'dialog' => new DialogHelper(),
  'formatter' => new FormatterHelper(),
  'drupal_common' => new DrupalCommonHelper(),
  'register_commands' => new RegisterCommandsHelper($application),
  'table' => new TableHelper(),
  'stringUtils' => new StringUtils(),
  'validators' => new Validators(),
  'translator' => $translatorHelper
];

$application->setHelperSet(new HelperSet($helpers));

$application->run();
