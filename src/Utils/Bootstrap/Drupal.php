<?php

namespace Drupal\Console\Utils\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;

class Drupal {

    protected $autoload;
    protected $consoleRoot;
    protected $siteRoot;

    /**
     * Drupal constructor.
     * @param $autoload
     * @param $consoleRoot
     * @param $siteRoot
     */
    public function __construct($autoload, $consoleRoot, $siteRoot) {
        $this->autoload = $autoload;
        $this->consoleRoot = $consoleRoot;
        $this->siteRoot = $siteRoot;
    }

    public function boot() {
        $request = Request::createFromGlobals();

        try {
            $drupalKernel = DrupalKernel::createFromRequest(
                $request,
                $this->autoload,
                'prod',
                FALSE
            );
        }
        catch (\Exception $e) {
            return $this->bootDrupalConsole();
        }

        $drupalKernel->addServiceModifier(
            new DrupalServiceModifier(
                $this->consoleRoot,
                $this->siteRoot,
                'console.command'
            )
        );

        $drupalKernel->invalidateContainer();
        $drupalKernel->rebuildContainer();
        $drupalKernel->boot();

        $container = $drupalKernel->getContainer();

        AnnotationRegistry::registerLoader([$this->autoload, "loadClass"]);

        $configuration = $container->get('console.configuration_manager')
            ->loadConfiguration($this->siteRoot)
            ->getConfiguration();

        $container->get('console.translator_manager')
            ->loadCoreLanguage(
                $configuration->get('application.language'),
                $this->siteRoot
            );

        $container->get('console.renderer')
            ->setSkeletonDirs(
                [
                    $this->consoleRoot.'/templates/',
                    $this->siteRoot.DRUPAL_CONSOLE_CORE.'/templates/'
                ]
            );

        return $container;
    }

    private function bootDrupalConsole() {
        return null;
    }
}