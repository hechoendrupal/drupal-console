<?php

namespace Drupal\Console\Utils\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;

class Drupal
{
    protected $autoload;
    protected $root;

    /**
     * Drupal constructor.
     * @param $autoload
     * @param $root
     */
    public function __construct($autoload, $root)
    {
        $this->autoload = $autoload;
        $this->root = $root;
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
            $drupal = new DrupalConsoleCore($this->root);
            return $drupal->boot();
        }

        $drupalKernel->addServiceModifier(
            new DrupalServiceModifier(
                $this->root,
                'console.command'
            )
        );

        $drupalKernel->invalidateContainer();
        $drupalKernel->rebuildContainer();
        $drupalKernel->boot();

        $container = $drupalKernel->getContainer();

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
