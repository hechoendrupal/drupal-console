<?php

namespace Drupal\Console\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ArgvInputReader;
use Drupal\Console\Core\Bootstrap\DrupalConsoleCore;
use Drupal\Console\Utils\ExtendExtensionManager;

class Drupal
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * Drupal constructor.
     *
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

    public function boot($debug)
    {
        $output = new ConsoleOutput();
        $input = new ArrayInput([]);
        $io = new DrupalStyle($input, $output);
        $argvInputReader = new ArgvInputReader();

        if (!class_exists('Drupal\Core\DrupalKernel')) {
            $io->error('Class Drupal\Core\DrupalKernel do not exists.');
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            return $drupal->boot();
        }

        try {
            // Add support for Acquia Dev Desktop sites.
            // Try both Mac and Windows home locations.
            $home = getenv('HOME');
            if (empty($home)) {
                $home = getenv('USERPROFILE');
            }
            if (!empty($home)) {
                $devDesktopSettingsDir = $home . "/.acquia/DevDesktop/DrupalSettings";
                if (file_exists($devDesktopSettingsDir)) {
                    $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] = $devDesktopSettingsDir;
                }
            }
            $argvInputReader = new ArgvInputReader();
            $command = $argvInputReader->get('command');
            $rebuildServicesFile = false;
            if ($command=='cache:rebuild' || $command=='cr') {
                $rebuildServicesFile = true;
            }

            if ($debug) {
                $io->writeln('➤ Creating request');
            }
            $uri = $argvInputReader->get('uri');
            if ($uri && $uri != 'http://default') {
                if (substr($uri, -1) != '/') {
                    $uri .= '/';
                }
                $uri .= 'index.php';
                $request = Request::create($uri, 'GET', [], [], [], ['SCRIPT_NAME' => $this->appRoot . '/index.php']);
            } else {
                $request = Request::createFromGlobals();
            }

            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Creating Drupal kernel');
            }
            $drupalKernel = DrupalKernel::createFromRequest(
                $request,
                $this->autoload,
                'prod',
                false,
                $this->appRoot
            );
            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Registering dynamic services');
            }

            $drupalKernel->addServiceModifier(
                new DrupalServiceModifier(
                    $this->root,
                    $this->appRoot,
                    'drupal.command',
                    'drupal.generator',
                    $rebuildServicesFile
                )
            );
            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Rebuilding container');
            }
            $drupalKernel->invalidateContainer();
            $drupalKernel->rebuildContainer();
            $drupalKernel->boot();

            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
            }

            $container = $drupalKernel->getContainer();
            $container->set('console.root', $this->root);

            AnnotationRegistry::registerLoader([$this->autoload, "loadClass"]);

            $configuration = $container->get('console.configuration_manager')
                ->getConfiguration();

            $container->get('console.translator_manager')
                ->loadCoreLanguage(
                    $configuration->get('application.language'),
                    $this->root
                );

            $consoleExtendConfigFile = $this->root . DRUPAL_CONSOLE .'/extend.console.config.yml';
            if (file_exists($consoleExtendConfigFile)) {
                $container->get('console.configuration_manager')
                    ->importConfigurationFile($consoleExtendConfigFile);
            }

            $container->get('console.renderer')
                ->setSkeletonDirs(
                    [
                        $this->root.DRUPAL_CONSOLE.'/templates/',
                        $this->root.DRUPAL_CONSOLE_CORE.'/templates/'
                    ]
                );

            return $container;
        } catch (\Exception $e) {
            if ($argvInputReader->get('command') == 'list') {
                $io->error($e->getMessage());
            }
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            $container = $drupal->boot();
            $container->set('class_loader', $this->autoload);
            return $container;
        }
    }
}
