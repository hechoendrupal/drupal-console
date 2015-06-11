<?php
namespace Drupal\AppConsole\Console;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

class Application extends BaseApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';
    /**
     * @var string
     */
    const VERSION = '0.7.13';
    /**
     * @var bool
     */
    protected $booted = false;
    /**
     * @var Drupal\AppConsole\UserConfig
     */
    protected $config;
    /**
     * @var string
     */
    protected $directoryRoot;
    /**
     * @var \Composer\Autoload\ClassLoader
     * The Drupal autoload file.
     */
    protected $drupalAutoload;
    /**
     * @var string
     * The Drupal environment.
     */
    protected $env;
    /**
     * @var bool
     */
    private $commandsRegistered = false;

    private $searchSettingsFile = true;

    /**
     * Create a new application extended from \Symfony\Component\Console\Application.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->env = $config->get('application.environment');

        parent::__construct($this::NAME, sprintf('%s', $this::VERSION));

        $this->getDefinition()->addOption(
          new InputOption('--drupal', '-d', InputOption::VALUE_OPTIONAL, 'Path to Drupal root.')
        );
        $this->getDefinition()->addOption(
          new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.')
        );
        $this->getDefinition()->addOption(
          new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, 'The Environment name.', $this->env)
        );
        $this->getDefinition()->addOption(
          new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.')
        );
        $this->getDefinition()->addOption(
          new InputOption('--learning', null, InputOption::VALUE_NONE, 'Generate a verbose code output.')
        );
    }

    /**
     * Prepare Drupal Console to run, and bootstrap Drupal.
     *
     * @param string $env
     * @param bool $debug
     */
    public function setup($env = 'prod', $debug = false)
    {
        if ($this->isBooted()) {
            if ($this->drupalAutoload) {
                $this->initDebug($env, $debug);
                $this->doKernelConfiguration();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $drupal_root = $input->getParameterOption(['--drupal', '-d'], false);

        $env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');

        $debug = getenv('DRUPAL_DEBUG') !== '0'
            && !$input->hasParameterOption(array('--no-debug', ''))
            && $env !== 'prod';

        if ($this->isBooted()) {
            if (true === $input->hasParameterOption(array('--shell', '-s'))) {
                $this->runShell($input);
                return 0;
            }
        }

        if (!$this->commandsRegistered) {
            $this->commandsRegistered = $this->registerCommands();
        }

        if ($input) {
            $commandName = $this->getCommandName($input);
        }

        if ($commandName && $this->has($commandName)){
            $this->searchSettingsFile = false;
        }

        if ($drupal_root && $this->isRunningOnDrupalInstance($drupal_root)) {
            $this->setup($env, $debug);
            $this->bootstrap();
        }

        parent::doRun($input, $output);

        if ($this->isBooted()) {
            $kernelHelper = $this->getHelperSet()->get('kernel');
            if ($kernelHelper) {
                $kernelHelper->terminate();
            }
        }
    }

    /**
     * @param $drupal_root
     * @return bool
     */
    protected function isRunningOnDrupalInstance($drupal_root)
    {
        $auto_load = $this
            ->getHelperSet()
            ->get('drupal-autoload')
            ->findAutoload($drupal_root);

        if (!$this->isSettingsFile()) {
            return false;
        }

        if ($auto_load && !$this->isBooted()) {
            $drupalLoader = require $auto_load;

            return $this->setDrupalAutoload($drupalLoader);
        }
        return false;
    }

    public function setDrupalAutoLoad($drupalLoader)
    {
        if ($drupalLoader instanceof ClassLoader) {
            $this->drupalAutoload = $drupalLoader;
            $this->setBooted(true);
            return true;
        }

        return false;
    }

    public function setSearchSettingsFile($searchSettingsFile)
    {
        $this->searchSettingsFile = $searchSettingsFile;
    }

    public function isSettingsFile()
    {
        if (!$this->searchSettingsFile) {
            return true;
        }

        $drupalRoot = $this
          ->getHelperSet()
          ->get('drupal-autoload')
          ->getDrupalRoot();

        $messageHelper = $this
          ->getHelperSet()
          ->get('message');

        $translatorHelper = $this
          ->getHelperSet()
          ->get('translator');

        if (!file_exists($drupalRoot . '/core/vendor/autoload.php')) {
            $messageHelper->addWarningMessage($translatorHelper->trans('application.site.errors.directory'));
            return false;
        }

        if (!file_exists($drupalRoot . '/sites/default/settings.php')) {
            $messageHelper->addWarningMessage($translatorHelper->trans('application.site.errors.settings'));
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @param boolean $booted
     */
    public function setBooted($booted)
    {
        $this->booted = $booted;
    }

    /**
     * @param InputInterface $input
     */
    protected function initDebug($env, $debug)
    {
        if ($debug) {
            Debug::enable();
        }

        /** @var \Drupal\AppConsole\Command\Helper\KernelHelper $kernelHelper */
        $kernelHelper = $this->getHelperSet()->get('kernel');

        $kernelHelper->setDebug($debug);
        $kernelHelper->setEnvironment($env);
    }

    protected function doKernelConfiguration()
    {
        /** @var \Drupal\AppConsole\Command\Helper\KernelHelper $kernelHelper */
        $kernelHelper = $this->getHelperSet()->get('kernel');

        $kernelHelper->setClassLoader($this->drupalAutoload);
        $kernelHelper->setEnvironment($this->env);
    }

    public function bootstrap()
    {
        $kernelHelper = $this->getHelperSet()->get('kernel');
        if ($kernelHelper) {
            $kernelHelper->bootKernel();
            $kernelHelper->initCommands($this->all());
        }

        if (!$this->commandsRegistered) {
            $this->commandsRegistered = $this->registerCommands();
        }
    }

    /**
     * Register the console commands.
     */
    protected function registerCommands()
    {
        /** @var \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper $rc */
        $registerCommands = $this->getHelperSet()->get('register_commands');
        if ($registerCommands) {
            $registerCommands->register();
        }
    }

    /**
     * @param InputInterface $input
     */
    protected function runShell(InputInterface $input)
    {
        /** @var \Drupal\AppConsole\Command\Helper\ShellHelper $shell */
        $shell = $this->getHelperSet()->get('shell')->getShell();

        $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
        $shell->run();
    }

    /**
     * @return \Drupal\Core\DrupalKernel | null
     */
    public function getKernel()
    {
        return $this->drupalAutoload ? $this->getHelperSet()->get('kernel')->getKernel() : null;
    }

    /**
     * @return \Drupal\AppConsole\UserConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getDirectoryRoot()
    {
        return $this->directoryRoot;
    }

    /**
     * @param string $directoryRoot
     */
    public function setDirectoryRoot($directoryRoot)
    {
        $this->directoryRoot = $directoryRoot;
    }

    /**
     * @param array $helpers
     */
    public function addHelpers(array $helpers)
    {
        $defaultHelperset = $this->getHelperSet();
        foreach ($helpers as $alias => $helper) {
            $defaultHelperset->set($helper, is_int($alias) ? null : $alias);
        }
    }
}
