<?php

namespace Drupal\Console\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Console\Utils\ArgvInputReader;

class Drupal
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * Drupal constructor.
     * @param $autoload
     * @param $root
     * @param $appRoot
     */
    public function __construct($autoload, $root, $appRoot)
    {
        $this->autoload = $autoload;
        $this->root = $root;
        $this->appRoot = $appRoot;
    }

    public function boot()
    {
        if (!class_exists('Drupal\Core\DrupalKernel')) {
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            return $drupal->boot();
        }

        try {
            // Add support for Acquia Dev Desktop sites on Mac OS X
            // @TODO: Check if this condition works in Windows
            $devDesktopSettingsDir = getenv('HOME') . "/.acquia/DevDesktop/DrupalSettings";
            if (file_exists($devDesktopSettingsDir)) {
                $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] = $devDesktopSettingsDir;
            }

            $argvInputReader = new ArgvInputReader();
            if ($argvInputReader->get('uri')) {
                $uri = $argvInputReader->get('uri');
                if (substr($uri, -1) != '/') {
                    $uri .= '/';
                }
                $uri .= 'index.php';
                $request = Request::create($uri, 'GET', array(), array(), array(), array('SCRIPT_NAME' => $this->appRoot . '/index.php'));
            } else {
                $request = Request::createFromGlobals();
            }

            $drupalKernel = DrupalKernel::createFromRequest(
                $request,
                $this->autoload,
                'prod',
                false
            );

            $drupalKernel->addServiceModifier(
                new DrupalServiceModifier(
                    $this->root,
                    'drupal.command',
                    'drupal.generator'
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
        } catch (\Exception $e) {
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            $container = $drupal->boot();
            $container->set('class_loader', $this->autoload);
            return $container;
        }
    }
}
