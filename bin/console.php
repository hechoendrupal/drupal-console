<?php

use Drupal\Console\Console\Shell;
use Drupal\Console\Console\Application;
use Drupal\Console\Helper\ShellHelper;
use Drupal\Console\Helper\KernelHelper;
use Drupal\Console\Helper\DialogHelper;
use Drupal\Console\Helper\RegisterCommandsHelper;
use Drupal\Console\Utils\StringUtils;
use Drupal\Console\Utils\Validators;
use Drupal\Console\Helper\TranslatorHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Console\UserConfig;
use Drupal\Console\Helper\SiteHelper;
use Drupal\Console\EventSubscriber\ShowGeneratedFilesListener;
use Drupal\Console\EventSubscriber\ShowWelcomeMessageListener;
use Drupal\Console\Helper\MessageHelper;
use Drupal\Console\Helper\ChainCommandHelper;
use Drupal\Console\EventSubscriber\CallCommandListener;
use Drupal\Console\EventSubscriber\ShowGenerateChainListener;
use Drupal\Console\EventSubscriber\ShowGenerateInlineListener;
use Drupal\Console\EventSubscriber\ShowCompletedMessageListener;
use Drupal\Console\EventSubscriber\ValidateDependenciesListener;
use Drupal\Console\EventSubscriber\DefaultValueEventListener;
use Drupal\Console\Helper\NestedArrayHelper;
use Drupal\Console\Helper\TwigRendererHelper;
use Drupal\Console\EventSubscriber\ShowGenerateDocListener;
use Drupal\Console\Helper\DrupalHelper;

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
    'nested-array' => new NestedArrayHelper(),
    'kernel' => new KernelHelper(),
    'shell' => new ShellHelper(new Shell($application)),
    'dialog' => new DialogHelper(),
    'register_commands' => new RegisterCommandsHelper($application),
    'stringUtils' => new StringUtils(),
    'validators' => new Validators(),
    'translator' => $translatorHelper,
    'site' => new SiteHelper(),
    'renderer' => new TwigRendererHelper(),
    'message' => new MessageHelper($translatorHelper),
    'chain' => new ChainCommandHelper(),
    'drupal' => new DrupalHelper(),
];

$application->addHelpers($helpers);

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new ValidateDependenciesListener());
$dispatcher->addSubscriber(new ShowWelcomeMessageListener());
$dispatcher->addSubscriber(new ShowGenerateDocListener());
$dispatcher->addSubscriber(new DefaultValueEventListener());
$dispatcher->addSubscriber(new ShowGeneratedFilesListener());
$dispatcher->addSubscriber(new CallCommandListener());
$dispatcher->addSubscriber(new ShowGenerateChainListener());
$dispatcher->addSubscriber(new ShowGenerateInlineListener());
$dispatcher->addSubscriber(new ShowCompletedMessageListener());

$application->setDispatcher($dispatcher);
$application->setDefaultCommand('about');
$application->run();
