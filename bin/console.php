<?php

use Drupal\AppConsole\Console\Shell;
use Drupal\AppConsole\Console\Application;
use Drupal\AppConsole\Command\Helper\ShellHelper;
use Drupal\AppConsole\Command\Helper\KernelHelper;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Helper\RegisterCommandsHelper;
use Drupal\AppConsole\Utils\StringUtils;
use Drupal\AppConsole\Utils\Validators;
use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Drupal\AppConsole\Config;
use Drupal\AppConsole\Command\Helper\DrupalAutoloadHelper;
use Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper;
use Drupal\AppConsole\EventSubscriber\ShowGeneratedFiles;
use Drupal\AppConsole\EventSubscriber\ShowWelcomeMessage;

set_time_limit(0);

$consoleRoot = __DIR__ . '/../';

if (file_exists($consoleRoot . '/vendor/autoload.php')) {
    require_once $consoleRoot . '/vendor/autoload.php';
} else if (file_exists($consoleRoot . '/../../vendor/autoload.php')) {
    require_once $consoleRoot . '/../../vendor/autoload.php';
} else {
    echo 'Something goes wrong with your archive' . PHP_EOL .
        'Try downloading again' . PHP_EOL;
    exit(1);
}

$consoleConfig = new Config(new Parser(), $consoleRoot);
$config = $consoleConfig->getConfig();

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config['application']['language'], $consoleRoot);

$application = new Application($config);
$application->setDirectoryRoot($consoleRoot);

$helpers = [
    'bootstrap' => new DrupalBootstrapHelper(),
    'kernel' => new KernelHelper(),
    'shell' => new ShellHelper(new Shell($application)),
    'dialog' => new DialogHelper(),
    'register_commands' => new RegisterCommandsHelper($application),
    'stringUtils' => new StringUtils(),
    'validators' => new Validators(),
    'translator' => $translatorHelper,
    'drupal-autoload' => new DrupalAutoloadHelper(),
];

$application->addHelpers($helpers);

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new ShowGeneratedFiles($translatorHelper));
$dispatcher->addSubscriber(new ShowWelcomeMessage($translatorHelper));

$application->setDispatcher($dispatcher);
$application->setDefaultCommand('list');
$application->run();
