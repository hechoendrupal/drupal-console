<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Console\Application;
use Drupal\Console\Utils\DrupalKernel;
use Drupal\Console\Utils\DrupalServiceModifier;

set_time_limit(0);
$consoleRoot = realpath(__DIR__.'/../') . '/';
$root = getcwd() . '/';
$siteRoot = realpath(__DIR__.'/../../../../') . '/';

$autoLoadFile = $root.'/autoload.php';

if (file_exists($autoLoadFile)) {
    $autoload = include_once $autoLoadFile;
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

$drupalKernel->addServiceModifier(
    new DrupalServiceModifier(
        $consoleRoot,
        $siteRoot,
        'console.command'
    )
);

$drupalKernel->invalidateContainer();
$drupalKernel->rebuildContainer();
$drupalKernel->boot();
/* DrupalKernel */

$container = $drupalKernel->getContainer();

AnnotationRegistry::registerLoader([$autoload, "loadClass"]);

$configuration = $container->get('console.configuration_manager')
    ->loadConfiguration($siteRoot)
    ->getConfiguration();

$translator = $container->get('console.translator_manager')
    ->loadCoreLanguage(
        $configuration->get('application.language'),
        $siteRoot
    );

$container->get('console.renderer')
    ->setSkeletonDirs(
        [
            $consoleRoot.'/templates/',
            $siteRoot.DRUPAL_CONSOLE_CORE.'/templates/'
        ]
    );

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
