<?php

namespace Drupal\Console\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;

class Drupal
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * Drupal constructor.
     * @param $autoload
     * @param $root
     */
    public function __construct($autoload, $root, $appRoot)
    {
        $this->autoload = $autoload;
        $this->root = $root;
        $this->appRoot = $appRoot;
    }

    public function boot()
    {
        $request = Request::createFromGlobals();

        try {
            $drupalKernel = DrupalKernel::createFromRequest(
                $request,
                $this->autoload,
                'prod',
                false
            );
        } catch (\Exception $e) {
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            return $drupal->boot();
        }

        $drupalKernel->addServiceModifier(
            new DrupalServiceModifier(
                $this->root,
                'drupal.command',
                'console.generator'
            )
        );

        $drupalKernel->invalidateContainer();
        $drupalKernel->rebuildContainer();
        $drupalKernel->boot();

        $container = $drupalKernel->getContainer();

        $container->set('console.root', $this->root);

        AnnotationRegistry::registerLoader([$this->autoload, "loadClass"]);

        $configuration = $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        $container->get('console.translator_manager')
            ->loadCoreLanguage(
                $configuration->get('application.language'),
                $this->root
            );

        $container->get('console.renderer')
            ->setSkeletonDirs(
                [
                    $this->root.DRUPAL_CONSOLE.'/templates/',
                    $this->root.DRUPAL_CONSOLE_CORE.'/templates/'
                ]
            );

        return $container;
    }
}
