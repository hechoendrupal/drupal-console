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
use Drupal\AppConsole\Command\GeneratorModuleCommand;
use Drupal\AppConsole\Command\GeneratorControllerCommand;
use Drupal\AppConsole\Command\GeneratorFormCommand;
use Drupal\AppConsole\Command\GeneratorPluginBlockCommand;
use Drupal\AppConsole\Command\GeneratorCommandCommand;
use Drupal\AppConsole\Command\DrushCommand;
use Drupal\AppConsole\Command\GeneratorServiceCommand;
use Drupal\AppConsole\Command\GeneratorPluginImageEffectCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\TableHelper;
use Drupal\AppConsole\Command\ContainerDebugCommand;
use Drupal\AppConsole\Command\RouterDebugCommand;

set_time_limit(0);

$application = new Application();

$application->setHelperSet(new HelperSet(array(
  'bootstrap' => new DrupalBootstrapHelper(),
  'finder' => new BootstrapFinderHelper(new Finder()),
  'kernel' => new KernelHelper(),
  'shell' => new ShellHelper(new Shell($application)),
  'dialog' => new DialogHelper(),
  'formatter' => new FormatterHelper(),
  'drupal_common' => new DrupalCommonHelper(),
  'register_commands' => new RegisterCommandsHelper($application),
  'table' => new TableHelper(),
)));

$application->addCommands([
  new GeneratorModuleCommand(),
  new GeneratorControllerCommand(),
  new GeneratorFormCommand(),
  new GeneratorPluginBlockCommand(),
  new GeneratorCommandCommand(),
  new DrushCommand(),
  new GeneratorServiceCommand(),
  new GeneratorPluginImageEffectCommand(),
  new ContainerDebugCommand(),
  new RouterDebugCommand(),
]);

$application->run();
