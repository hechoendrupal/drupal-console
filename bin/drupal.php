<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Console\App;
use Drupal\Console\EventSubscriber\ShowGeneratedFilesListener;
use Drupal\Console\EventSubscriber\ShowWelcomeMessageListener;
use Drupal\Console\EventSubscriber\CallCommandListener;
use Drupal\Console\EventSubscriber\ShowGenerateChainListener;
use Drupal\Console\EventSubscriber\ShowGenerateInlineListener;
use Drupal\Console\EventSubscriber\ShowTerminateMessageListener;
use Drupal\Console\EventSubscriber\ShowTipsListener;
use Drupal\Console\EventSubscriber\ValidateDependenciesListener;
use Drupal\Console\EventSubscriber\DefaultValueEventListener;
use Drupal\Console\EventSubscriber\ValidateExecutionListener;
use Drupal\Console\Utils\DrupalKernel;
use Drupal\Console\Utils\DrupalServiceModifier;

set_time_limit(0);
$consoleRoot = realpath(__DIR__.'/../') . '/';
$root = getcwd() . '/';
$siteRoot = realpath(__DIR__.'/../../../../') . '/';

$autoloadFile = $root.'/autoload.php';

if (file_exists($autoloadFile)) {
    $autoload = include_once $autoloadFile;
} else {
    echo PHP_EOL .
         ' Something goes wrong with your package.'.PHP_EOL.
         ' Try downloading again.'. PHP_EOL .
         ' Executing:'. PHP_EOL .
         ' composer require drupal/console:~1.0 --prefer-dist --optimize-autoloader'. PHP_EOL;

    exit(1);
}

/* DrupalKernel */
$request = Request::createFromGlobals();
$drupalKernel = DrupalKernel::createFromRequest(
    $request,
    $autoload,
    'prod',
    true
);

$drupalKernel->addServiceModifier(new DrupalServiceModifier(
    $consoleRoot,
    'console.command'
));
$drupalKernel->invalidateContainer();
$drupalKernel->boot();
/* DrupalKernel */

$container = $drupalKernel->getContainer();
AnnotationRegistry::registerLoader([$autoload, "loadClass"]);

//$config = $container->get('config');

$configuration = $container->get('console.configuration_manager')
    ->loadConfiguration(__DIR__)
    ->getConfiguration();

$translator = $container->get('console.translator_manager')
    ->loadCoreLanguage(
        $configuration->get('application.language'),
        $consoleRoot
    );

//$container->get('translator')->loadResource(
//    $config->get('application.language'),
//    $consoleRoot
//);
//
//$translatorHelper = new TranslatorHelper();
//$translatorHelper->loadResource(
//    $config->get('application.language'),
//    $consoleRoot
//);

//$helpers = [
//    'nested-array' => new NestedArrayHelper(),
//    'kernel' => new KernelHelper(),
//    'string' => new StringHelper(),
//    'validator' => new ValidatorHelper(),
//    'translator' => $translatorHelper, /* registered as a service */
//    'site' => new SiteHelper(),
//    'renderer' => new TwigRendererHelper(),
//    'showFile' => new ShowFileHelper(), /* registered as a service */
//    'chain' => new ChainCommandHelper(), /* registered as a service */
//    'drupal' => new DrupalHelper(), /* registered as a service "site" */
//    'commandDiscovery' => new CommandDiscoveryHelper(
//        $config->get('application.develop'),
//        $container->get("command_dependency_resolver")
//    ),
//    'remote' => new RemoteHelper(),
//    'httpClient' => new HttpClientHelper(),
//    'api' => new DrupalApiHelper(),
//    'container' => new ContainerHelper($container),
//];

$application = new App($container);
//$application->addHelpers($helpers);
//$application->setDirectoryRoot($consoleRoot);

//$dispatcher = new EventDispatcher();
//$dispatcher->addSubscriber(new ValidateExecutionListener());
//$dispatcher->addSubscriber(new ValidateDependenciesListener());
//$dispatcher->addSubscriber(new ShowWelcomeMessageListener());
//$dispatcher->addSubscriber(new DefaultValueEventListener());
//$dispatcher->addSubscriber(new ShowGeneratedFilesListener());
//$dispatcher->addSubscriber(new ShowTipsListener());
//$dispatcher->addSubscriber(new CallCommandListener());
//$dispatcher->addSubscriber(new ShowGenerateChainListener());
//$dispatcher->addSubscriber(new ShowGenerateInlineListener());
//$dispatcher->addSubscriber(new ShowTerminateMessageListener());
//$application->setDispatcher($dispatcher);

//$defaultCommand = 'about';
//if ($config->get('application.command')
//    && $application->has($config->get('application.command'))
//) {
//    $defaultCommand = $config->get('application.command');
//}
//$application->setDefaultCommand($defaultCommand);

//$application->setDefaultCommand('about');
$application->run();
