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
    const VERSION = '0.7.5';
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
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->isRuningOnDrupalInstance($input);

        if ($this->isBooted()) {
            if ($this->drupalAutoload) {
                $this->initDebug($input);
                $this->doKernelConfiguration();
            }

            if (!$this->commandsRegistered) {
                $this->commandsRegistered = $this->registerCommands();
            }

            if (true === $input->hasParameterOption(array('--shell', '-s'))) {
                $this->runShell($input);
                return 0;
            }
        }

        parent::doRun($input, $output);
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function isRuningOnDrupalInstance(InputInterface $input)
    {
        $drupal_root = $input->getParameterOption(['--drupal', '-d'], false);

        $auto_load = $this
          ->getHelperSet()
          ->get('drupal-autoload')
          ->findAutoload($drupal_root);

        if (!$this->isSettingsFile()) {
            return false;
        }

        if ($auto_load && !$this->isBooted()) {
            $this->drupalAutoload = require_once $auto_load;
            if ($this->drupalAutoload instanceof ClassLoader) {
                $this->setBooted(true);
                return true;
            }
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
            $messageHelper->addErrorMessage($translatorHelper->trans('application.site.errors.directory'));
            return false;
        }

        if (!file_exists($drupalRoot . '/sites/default/settings.php')) {
            $messageHelper->addErrorMessage($translatorHelper->trans('application.site.errors.settings'));
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
    protected function initDebug(InputInterface $input)
    {
        $env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');

        $debug = getenv('DRUPAL_DEBUG') !== '0'
          && !$input->hasParameterOption(array('--no-debug', ''))
          && $env !== 'prod';

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
        $kernelHelper->bootKernel();
        $kernelHelper->initCommands($this->all());
    }

    /**
     * Register the console commands.
     */
    protected function registerCommands()
    {
        /** @var \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper $rc */
        $rc = $this->getHelperSet()->get('register_commands');

        $drupalModules = $this->drupalAutoload;
        $rc->register($drupalModules);
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
