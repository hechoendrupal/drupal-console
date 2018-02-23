<?php

namespace Drupal\Console\Bootstrap;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Database\Database;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ArgvInputReader;
use Drupal\Console\Core\Bootstrap\DrupalConsoleCore;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Bootstrap\DrupalInterface;
use Drupal\Console\Core\Utils\ConfigurationManager;

class Drupal implements DrupalInterface
{
    protected $autoload;

    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * Drupal constructor.
     *
     * @param $autoload
     * @param $drupalFinder
     * @param $configurationManager
     */
    public function __construct(
        $autoload,
        DrupalFinder $drupalFinder,
        ConfigurationManager $configurationManager
    ) {
        $this->autoload = $autoload;
        $this->drupalFinder = $drupalFinder;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Boot the Drupal object
     *
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function boot()
    {
        $output = new ConsoleOutput();
        $input = new ArrayInput([]);
        $io = new DrupalStyle($input, $output);
        $argvInputReader = new ArgvInputReader();
        $command = $argvInputReader->get('command');
        $uri = $argvInputReader->get('uri');
        $debug = $argvInputReader->get('debug', false);

        if ($debug) {
            $binaryPath = $this->drupalFinder->getVendorDir() .
                '/drupal/console/bin/drupal';
            $io->writeln("<info>Per-Site path:</info> <comment>$binaryPath</comment>");
            $io->newLine();
        }

        if (!class_exists('Drupal\Core\DrupalKernel')) {
            $io->error('Class Drupal\Core\DrupalKernel does not exist.');

            return $this->bootDrupalConsoleCore();
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
            $_SERVER['SCRIPT_FILENAME'] = $this->drupalFinder->getDrupalRoot() . '/index.php';
            $request = Request::createFromGlobals();

            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Creating Drupal kernel');
            }

            $updateCommands = [
                'update:execute',
                'upex',
                'updb',
                'update:entities',
                'upe'
            ];

            if (!in_array($command, $updateCommands)) {
                $drupalKernel = DrupalKernel::createFromRequest(
                    $request,
                    $this->autoload,
                    'prod',
                    false,
                    $this->drupalFinder->getDrupalRoot()
                );
            } else {
                $drupalKernel = DrupalUpdateKernel::createFromRequest(
                    $request,
                    $this->autoload,
                    'prod',
                    false,
                    $this->drupalFinder->getDrupalRoot()
                );
            }

            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Registering dynamic services');
            }

            $configuration = $this->configurationManager->getConfiguration();

            $drupalKernel->addServiceModifier(
                new DrupalServiceModifier(
                    $this->drupalFinder->getComposerRoot(),
                    'drupal.command',
                    'drupal.generator',
                    $configuration
                )
            );

            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
                $io->writeln('➤ Rebuilding container');
            }

            // Fix an exception of FileCacheFactory not prefix not set when
            // container is build and looks that as we depend on cache for
            // AddServicesCompilerPass but container is not ready this prefix
            // needs to be set manually to allow use of the cache files.
            FileCacheFactory::setPrefix($this->drupalFinder->getDrupalRoot());

            // Invalidate container to ensure rebuild of any cached state
            // when boot is processed.
            $drupalKernel->invalidateContainer();

            // Load legacy libraries, modules, register stream wrapper, and push
            // request to request stack but without trigger processing of '/'
            // request that invokes hooks like hook_page_attachments().
            $drupalKernel->boot();
            $drupalKernel->preHandle($request);
            if ($debug) {
                $io->writeln("\r\033[K\033[1A\r<info>✔</info>");
            }

            $container = $drupalKernel->getContainer();

            if ($this->shouldRedirectToDrupalCore($container)) {
                $container = $this->bootDrupalConsoleCore();
                $container->set('class_loader', $this->autoload);

                return $container;
            }

            $container->set(
                'console.root',
                $this->drupalFinder->getComposerRoot()
            );

            AnnotationRegistry::registerLoader([$this->autoload, "loadClass"]);

            $container->set(
                'console.configuration_manager',
                $this->configurationManager
            );

            $container->get('console.translator_manager')
                ->loadCoreLanguage(
                    $configuration->get('application.language'),
                    $this->drupalFinder->getComposerRoot()
                );

            $container->get('console.renderer')
                ->setSkeletonDirs(
                    [
                        $this->drupalFinder->getComposerRoot().DRUPAL_CONSOLE.'/templates/',
                        $this->drupalFinder->getComposerRoot().DRUPAL_CONSOLE_CORE.'/templates/'
                    ]
                );

            $container->set(
                'console.drupal_finder',
                $this->drupalFinder
            );

            $container->set(
                'console.cache_key',
                $drupalKernel->getContainerKey()
            );

            return $container;
        } catch (\Exception $e) {
            $container = $this->bootDrupalConsoleCore();
            $container->set('class_loader', $this->autoload);

            $notifyErrorCodes = [
                0,
                1045,
                1049,
                2002,
            ];

            if (in_array($e->getCode(), $notifyErrorCodes)) {
                /**
                 * @var \Drupal\Console\Core\Utils\MessageManager $messageManager
                 */
                $messageManager = $container->get('console.message_manager');
                $messageManager->error(
                    $e->getMessage(),
                    $e->getCode(),
                    'list',
                    'site:install'
                );
            }

            return $container;
        }
    }

    /**
     * Builds and boot a DrupalConsoleCore object
     *
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function bootDrupalConsoleCore()
    {
        $drupal = new DrupalConsoleCore(
            $this->drupalFinder->getComposerRoot(),
            $this->drupalFinder->getDrupalRoot(),
            $this->drupalFinder
        );

        return $drupal->boot();
    }

    /**
     * Validate if flow should redirect to DrupalCore
     *
     * @param  $container
     * @return bool
     */
    protected function shouldRedirectToDrupalCore($container)
    {
        if (!Database::getConnectionInfo()) {
            return true;
        }

        if (!$container->has('database')) {
            return true;
        }


        return !$container->get('database')->schema()->tableExists('sessions');
    }
}
