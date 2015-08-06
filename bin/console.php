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
use Drupal\AppConsole\UserConfig;
use Drupal\AppConsole\Command\Helper\DrupalAutoloadHelper;
use Drupal\AppConsole\EventSubscriber\ShowGeneratedFilesListener;
use Drupal\AppConsole\EventSubscriber\ShowWelcomeMessageListener;
use Drupal\AppConsole\Command\Helper\MessageHelper;
use Drupal\AppConsole\Command\Helper\ChainCommandHelper;
use Drupal\AppConsole\EventSubscriber\CallCommandListener;
use Drupal\AppConsole\EventSubscriber\ShowGenerateChainListener;
use Drupal\AppConsole\EventSubscriber\ShowGenerateInlineListener;
use Drupal\AppConsole\EventSubscriber\ShowCompletedMessageListener;
use Drupal\AppConsole\EventSubscriber\ValidateDependenciesListener;
use Drupal\AppConsole\EventSubscriber\DefaultValueEventListener;

set_time_limit(0);

$consoleRoot = __DIR__.'/../';

if (file_exists($consoleRoot.'/vendor/autoload.php')) {
    include_once $consoleRoot.'/vendor/autoload.php';
} elseif (file_exists($consoleRoot.'/../../autoload.php')) {
    include_once $consoleRoot.'/../../autoload.php';
} else {
    echo 'Something goes wrong with your archive'.PHP_EOL.
        'Try downloading again'.PHP_EOL;
    exit(1);
}

$config = new UserConfig();

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config->get('application.language'), $consoleRoot);

$application = new Application($config, $translatorHelper);
$application->setDirectoryRoot($consoleRoot);

$helpers = [
    'kernel' => new KernelHelper(),
    'shell' => new ShellHelper(new Shell($application)),
    'dialog' => new DialogHelper(),
    'register_commands' => new RegisterCommandsHelper($application),
    'stringUtils' => new StringUtils(),
    'validators' => new Validators(),
    'translator' => $translatorHelper,
    'drupal-autoload' => new DrupalAutoloadHelper(),
    'message' => new MessageHelper($translatorHelper),
    'chain' => new ChainCommandHelper(),
];

$application->addHelpers($helpers);

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new ValidateDependenciesListener());
$dispatcher->addSubscriber(new ShowWelcomeMessageListener());
$dispatcher->addSubscriber(new DefaultValueEventListener());
$dispatcher->addSubscriber(new ShowGeneratedFilesListener());
$dispatcher->addSubscriber(new CallCommandListener());
$dispatcher->addSubscriber(new ShowGenerateChainListener());
$dispatcher->addSubscriber(new ShowGenerateInlineListener());
$dispatcher->addSubscriber(new ShowCompletedMessageListener());

$application->setDispatcher($dispatcher);
$application->setDefaultCommand('list');
$application->run();
