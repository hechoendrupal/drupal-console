<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Drupal\Console\Application;
use Drupal\Console\Helper\KernelHelper;
use Drupal\Console\Helper\StringHelper;
use Drupal\Console\Helper\ValidatorHelper;
use Drupal\Console\Helper\TranslatorHelper;
use Drupal\Console\Helper\SiteHelper;
use Drupal\Console\EventSubscriber\ShowGeneratedFilesListener;
use Drupal\Console\EventSubscriber\ShowWelcomeMessageListener;
use Drupal\Console\Helper\ShowFileHelper;
use Drupal\Console\Helper\ChainCommandHelper;
use Drupal\Console\EventSubscriber\CallCommandListener;
use Drupal\Console\EventSubscriber\ShowGenerateChainListener;
use Drupal\Console\EventSubscriber\ShowGenerateInlineListener;
use Drupal\Console\EventSubscriber\ShowTerminateMessageListener;
use Drupal\Console\EventSubscriber\ShowTipsListener;
use Drupal\Console\EventSubscriber\ValidateDependenciesListener;
use Drupal\Console\EventSubscriber\DefaultValueEventListener;
use Drupal\Console\Helper\NestedArrayHelper;
use Drupal\Console\Helper\TwigRendererHelper;
use Drupal\Console\Helper\DrupalHelper;
use Drupal\Console\Helper\CommandDiscoveryHelper;
use Drupal\Console\Helper\RemoteHelper;
use Drupal\Console\Helper\HttpClientHelper;
use Drupal\Console\Helper\DrupalApiHelper;
use Drupal\Console\Helper\ContainerHelper;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Console\Utils\DrupalKernel;
use Drupal\Console\Utils\DrupalServiceModifier;

use Symfony\Component\Console\Input\ArgvInput;

set_time_limit(0);
$consoleRoot = realpath(__DIR__.'/../') . '/';
$root = getcwd() . '/';
$siteRoot = realpath(__DIR__.'/../../../../') . '/';

$autoloadFile = $root.'/autoload.php';

if (file_exists($autoloadFile)) {
    $autoload = include_once $autoloadFile;
} else {
    echo PHP_EOL .
         ' Something goes wrong with your binary'.PHP_EOL.
         ' Try downloading again'. PHP_EOL . PHP_EOL;
    exit(1);
}

echo 'dir: ' .  __DIR__ . '/'. PHP_EOL;
echo 'consoleRoot: ' .  $consoleRoot . PHP_EOL;
echo 'siteRoot ' .  $siteRoot . PHP_EOL;
echo 'root: ' .  $root . PHP_EOL;

//$input = new ArgvInput();
//if ($input->getParameterOption(['--pre-launch'], null)) {
//    $root = getcwd();
//}

/* DrupalKernel */
$request = Request::createFromGlobals();
$drupalKernel = DrupalKernel::createFromRequest(
    $request,
    $autoload,
    'prod',
    true
);
\Drupal::getContainer()->setParameter('console.root', $consoleRoot);
$drupalKernel->addServiceModifier(new DrupalServiceModifier());
$drupalKernel->invalidateContainer();

//$drupalKernel->loadServices($consoleRoot);

echo 'AppRoot : ' . $drupalKernel->getAppRoot() . PHP_EOL;

$drupalKernel->boot();
/* DrupalKernel */

$container = $drupalKernel->getContainer();
////$container = new ContainerBuilder();
//$loader = new YamlFileLoader($container, new FileLocator($consoleRoot));
//$loader->load('services.yml');
//
//$finder = new Finder();
//$finder->files()
//    ->name('*.yml')
//    ->in(sprintf('%s/config/services/', $consoleRoot));
//foreach ($finder as $file) {
//    $loader->load($file->getPathName());
//}

AnnotationRegistry::registerLoader([$autoload, "loadClass"]);

$config = $container->get('config');
$container->get('translator')->loadResource(
    $config->get('application.language'),
    $consoleRoot
);

$translatorHelper = new TranslatorHelper();
$translatorHelper->loadResource(
    $config->get('application.language'),
    $consoleRoot
);

$helpers = [
    'nested-array' => new NestedArrayHelper(),
    'kernel' => new KernelHelper(),
    'string' => new StringHelper(),
    'validator' => new ValidatorHelper(),
    'translator' => $translatorHelper, /* registered as a service */
    'site' => new SiteHelper(),
    'renderer' => new TwigRendererHelper(),
    'showFile' => new ShowFileHelper(), /* registered as a service */
    'chain' => new ChainCommandHelper(), /* registered as a service */
    'drupal' => new DrupalHelper(), /* registered as a service "site" */
    'commandDiscovery' => new CommandDiscoveryHelper(
        $config->get('application.develop'),
        $container->get("command_dependency_resolver")
    ),
    'remote' => new RemoteHelper(),
    'httpClient' => new HttpClientHelper(),
    'api' => new DrupalApiHelper(),
    'container' => new ContainerHelper($container),
];

$application = new Application($container);
$application->addHelpers($helpers);
$application->setDirectoryRoot($consoleRoot);

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new ValidateDependenciesListener());
$dispatcher->addSubscriber(new ShowWelcomeMessageListener());
$dispatcher->addSubscriber(new DefaultValueEventListener());
$dispatcher->addSubscriber(new ShowGeneratedFilesListener());
$dispatcher->addSubscriber(new ShowTipsListener());
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
