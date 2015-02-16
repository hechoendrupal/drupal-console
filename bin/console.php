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
use Drupal\AppConsole\Config;

set_time_limit(0);

$consoleRoot = __DIR__ . '/../';
require $consoleRoot . '/vendor/autoload.php';

$consoleConfig  = new Config(new Parser(), $consoleRoot);
$config = $consoleConfig->getConfig();

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config['application']['language'], $consoleRoot);

$application = new Application($config);
$application->setDirectoryRoot($consoleRoot);

$errorMessages = [];
$class_loader = null;

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

  if (method_exists($command,'getDependencies')) {
    $dependencies = $command->getDependencies();
    foreach ($dependencies as $dependency) {
      if (\Drupal::moduleHandler()->moduleExists($dependency) === false) {
        $errorMessage = sprintf(
          $translatorHelper->trans('commands.common.errors.module-dependency'),
          $dependency
        );
        $command->showMessage($output, $errorMessage, 'error');
        $event->disableCommand();
      }
    }
  }

  $welcomeMessageKey = 'commands.'. str_replace(':', '.', $command->getName()). '.welcome';
  $welcomeMessage = $translatorHelper->trans($welcomeMessageKey);

  if ($welcomeMessage != $welcomeMessageKey){
    $command->showMessage($output, $welcomeMessage);
  }
});

$dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) use ($translatorHelper) {
  $output = $event->getOutput();
  $command = $event->getCommand();

  if ($event->getExitCode()!=0) {
    return;
  }

  $completedMessageKey = 'application.console.messages.completed';

  if ('self-update' == $command->getName()) {
    return;
  }

  if (method_exists($command,'getMessages')) {
    $messages = $command->getMessages();
    foreach ($messages as $message) {
      $command->showMessage($output, $translatorHelper->trans($message));
    }
  }

  if (method_exists($command,'getGenerator') && method_exists($command,'showGeneratedFiles')) {
    $files = $command->getGenerator()->getFiles();
    if ($files) {
      $command->showGeneratedFiles($output, $files);
    }
    $completedMessageKey = 'application.console.messages.generated.completed';
  }

  $completedMessage = $translatorHelper->trans($completedMessageKey);

  if ($completedMessage != $completedMessageKey) {
    if (method_exists($command,'showMessage')) {
      $command->showMessage($output, $completedMessage);
    }
  }
});

$application->setDispatcher($dispatcher);
$application->setDefaultCommand('list');
$application->run();
