<?php

use Drupal\AppConsole\Console\Shell;
use Drupal\AppConsole\Console\Application;
use Drupal\AppConsole\Command\Helper\ShellHelper;
use Drupal\AppConsole\Command\Helper\KernelHelper;
use Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper;
use Drupal\AppConsole\Command\Helper\BootstrapFinderHelper;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Helper\RegisterCommandsHelper;
use Symfony\Component\Finder\Finder;
use Drupal\AppConsole\Utils\StringUtils;
use Drupal\AppConsole\Utils\Validators;
use Symfony\Component\Yaml\Parser;
use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

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

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config['application']['language'], $directoryRoot);

$application = new Application($config);
$application->setDirectoryRoot($directoryRoot);

$errorMessages = [];
$class_loader = null;
// Try to find the Drupal autoloader.
if (file_exists(getcwd() . '/core/vendor/autoload.php')) {
  if (!file_exists(getcwd() . '/sites/default/settings.php')) {
    $errorMessages[] = $translatorHelper->trans('application.site.errors.settings');
  }
  else {
    $class_loader = require getcwd() . '/core/vendor/autoload.php';
    $application->setBooted(true);
  }
} else {
  $errorMessages[] = $translatorHelper->trans('application.site.errors.directory');
}

$application->addErrorMessages($errorMessages);

$helpers = [
  'bootstrap' => new DrupalBootstrapHelper(),
  'finder' => new BootstrapFinderHelper(new Finder()),
  'kernel' => new KernelHelper(),
  'shell' => new ShellHelper(new Shell($application)),
  'dialog' => new DialogHelper(),
  'register_commands' => new RegisterCommandsHelper($application),
  'stringUtils' => new StringUtils(),
  'validators' => new Validators(),
  'translator' => $translatorHelper
];

$application->addHelpers($helpers);

$dispatcher = new EventDispatcher();
$dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($translatorHelper) {
  $output = $event->getOutput();
  $command = $event->getCommand();

  $welcomeMessageKey = 'commands.'. str_replace(':', '.', $command->getName()). '.welcome';
  $welcomeMessage = $translatorHelper->trans($welcomeMessageKey);

  if ($welcomeMessage != $welcomeMessageKey){
    $command->showMessage($output, $welcomeMessage);
  }
});
$dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) use ($translatorHelper) {
  $output = $event->getOutput();
  $command = $event->getCommand();

  if (method_exists($command,'getMessages')) {
    $messages = $command->getMessages();
    foreach ($messages as $message) {
      $command->showMessage($output, $translatorHelper->trans($message));
    }
  }
});

$application->setDispatcher($dispatcher);
$application->setDefaultCommand('list');
$application->run();
