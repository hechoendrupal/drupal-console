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

    public function boot()
    {
        $output = new ConsoleOutput();
        $input = new ArrayInput([]);
        $io = new DrupalStyle($input, $output);
        $argvInputReader = new ArgvInputReader();
        $command = $argvInputReader->get('command');
        $uri = $argvInputReader->get('uri');
        $debug = $argvInputReader->get('debug', false);

        if (!class_exists('Drupal\Core\DrupalKernel')) {
            $io->error('Class Drupal\Core\DrupalKernel does not exist.');
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

            $rebuildServicesFile = false;
            if ($command=='cache:rebuild' || $command=='cr') {
                $rebuildServicesFile = true;
            }

            if ($debug) {
                $io->writeln('➤ Creating request');
            }

            $_SERVER['HTTP_HOST'] = parse_url($uri, PHP_URL_HOST);
            $_SERVER['SERVER_PORT'] = null;
            $_SERVER['REQUEST_URI'] = '/';
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['SERVER_SOFTWARE'] = null;
            $_SERVER['HTTP_USER_AGENT'] = null;
            $_SERVER['PHP_SELF'] = $_SERVER['REQUEST_URI'] . 'index.php';
            $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
            $_SERVER['SCRIPT_FILENAME'] = $this->appRoot . '/index.php';
            $request = Request::createFromGlobals();

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
            if ($command == 'list') {
                $io->error($e->getMessage());
            }
            $drupal = new DrupalConsoleCore($this->root, $this->appRoot);
            $container = $drupal->boot();
            $container->set('class_loader', $this->autoload);
            return $container;
        }
    }
}
