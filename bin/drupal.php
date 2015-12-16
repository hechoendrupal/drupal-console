<?php

use Drupal\Console\Application;
use Drupal\Console\Helper\KernelHelper;
use Drupal\Console\Helper\StringHelper;
use Drupal\Console\Helper\ValidatorHelper;
use Drupal\Console\Helper\TranslatorHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Console\Config;
use Drupal\Console\Helper\SiteHelper;
use Drupal\Console\EventSubscriber\ShowGeneratedFilesListener;
use Drupal\Console\EventSubscriber\ShowWelcomeMessageListener;
use Drupal\Console\Helper\MessageHelper;
use Drupal\Console\Helper\ChainCommandHelper;
use Drupal\Console\EventSubscriber\CallCommandListener;
use Drupal\Console\EventSubscriber\ShowGenerateChainListener;
use Drupal\Console\EventSubscriber\ShowGenerateInlineListener;
use Drupal\Console\EventSubscriber\ShowTerminateMessageListener;
use Drupal\Console\EventSubscriber\ValidateDependenciesListener;
use Drupal\Console\EventSubscriber\DefaultValueEventListener;
use Drupal\Console\Helper\NestedArrayHelper;
use Drupal\Console\Helper\TwigRendererHelper;
use Drupal\Console\EventSubscriber\ShowGenerateDocListener;
use Drupal\Console\Helper\DrupalHelper;
use Drupal\Console\Helper\CommandDiscoveryHelper;
use Drupal\Console\Helper\RemoteHelper;
use Drupal\Console\Helper\HttpClientHelper;

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

$config = new Config();

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource($config->get('application.language'), $consoleRoot);

$application = new Application($config, $translatorHelper);
$application->setDirectoryRoot($consoleRoot);

$helpers = [
    'nested-array' => new NestedArrayHelper(),
    'kernel' => new KernelHelper(),
    'string' => new StringHelper(),
    'validator' => new ValidatorHelper(),
    'translator' => $translatorHelper,
    'site' => new SiteHelper(),
    'renderer' => new TwigRendererHelper(),
    'message' => new MessageHelper(),
    'chain' => new ChainCommandHelper(),
    'drupal' => new DrupalHelper(),
    'commandDiscovery' => new CommandDiscoveryHelper($config->get('application.develop')),
    'remote' => new RemoteHelper(),
    'httpClient' => new HttpClientHelper(),
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
$dispatcher->addSubscriber(new ShowTerminateMessageListener());
$application->setDispatcher($dispatcher);

$defaultCommand = 'about';
if ($config->get('application.command')
    && $application->has($config->get('application.command'))
) {
    $defaultCommand = $config->get('application.command');
}

$application->setDefaultCommand($defaultCommand);
$application->run();
