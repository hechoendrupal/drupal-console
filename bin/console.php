<?php
use Drupal\Core\DrupalKernel;
use Drupal\AppConsole\Console\Shell;
use Drupal\AppConsole\Console\Application;
use Drupal\AppConsole\Command\Helper\ShellHelper;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Helper\KernelHelper;
use Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper;
use Drupal\AppConsole\Command\Helper\BootstrapFinderHelper;
use Drupal\AppConsole\Command\GeneratorModuleCommand;
use Drupal\AppConsole\Command\GeneratorControllerCommand;
use Drupal\AppConsole\Command\GeneratorFormCommand;
use Drupal\AppConsole\Command\ServicesCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Finder\Finder;

if (file_exists(__DIR__ . '/../../../core/vendor/autoload.php')) {
  require __DIR__ . '/../../../core/vendor/autoload.php';
} else {
  require __DIR__ . '/../vendor/autoload.php';
}
set_time_limit(0);

$application = new Application();

$application->setHelperSet(new HelperSet(array(
  'bootstrap' => new DrupalBootstrapHelper(),
  'finder' => new BootstrapFinderHelper(new Finder()),
  'kernel' => new KernelHelper(),
  'shell' => new ShellHelper(new Shell($application)),
  'dialog' => new DialogHelper(),
  'formatter' => new FormatterHelper(),
)));

$application->addCommands(array(
  new GeneratorModuleCommand(),
  new GeneratorControllerCommand(),
  new GeneratorFormCommand(),
  new ServicesCommand(),
));

$application->run();
