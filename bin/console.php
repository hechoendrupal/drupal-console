<?php
use Drupal\AppConsole\Console\Application;
use Drupal\Core\DrupalKernel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\AppConsole\Command\Helper\ShellHelper;
use Drupal\AppConsole\Console\Shell;
use Drupal\AppConsole\Command\GeneratorModuleCommand;
use Drupal\AppConsole\Command\GeneratorControllerCommand;
use Drupal\AppConsole\Command\GeneratorFormCommand;
use Drupal\AppConsole\Command\ServicesCommand;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;

set_time_limit(0);
require_once __DIR__ . '/../../../../includes/bootstrap.inc';

$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');
$debug = getenv('DRUPAL_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';
if ($debug) {
    Debug::enable();
}

drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
$kernel = new DrupalKernel($env, drupal_classloader(), !$debug);
$application = new Application($kernel);

$helperSet = new HelperSet();
$helperSet->set(new ShellHelper(new Shell($application)), 'shell');
$helperSet->set(new DialogHelper(), 'dialog');
$helperSet->set(new FormatterHelper(), 'formatter');

$application->setHelperSet($helperSet);
$application->addCommands(array(
    new GeneratorModuleCommand(),
    new GeneratorControllerCommand(),
    new GeneratorFormCommand(),
    new ServicesCommand(),
));
$application->run($input);
