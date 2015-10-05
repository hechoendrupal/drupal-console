<?php

namespace Drupal\Console\Console;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

/**
 * Class Application
 * @package Drupal\Console\Console
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';
    /**
     * @var string
     */
    const VERSION = '0.9.1';
    /**
     * @var bool
     */
    protected $booted = false;
    /**
     * @var Drupal\Console\UserConfig
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

    /**
     * @var TranslatorHelper
     */
    protected $translator;

    /**
     * Create a new application extended from \Symfony\Component\Console\Application.
     *
     * @param $config
     * @param $translator
     */
    public function __construct($config, $translator)
    {
        $this->config = $config;
        $this->translator = $translator;
        $this->env = $config->get('application.environment');

        parent::__construct($this::NAME, sprintf('%s', $this::VERSION));

        $this->getDefinition()->addOption(
            new InputOption('--root', null, InputOption::VALUE_OPTIONAL, $this->trans('application.console.arguments.root'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--shell', '-s', InputOption::VALUE_NONE, $this->trans('application.console.arguments.shell'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, $this->trans('application.console.arguments.env'), $this->env)
        );
        $this->getDefinition()->addOption(
            new InputOption('--no-debug', null, InputOption::VALUE_NONE, $this->trans('application.console.arguments.no-debug'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--learning', null, InputOption::VALUE_NONE, $this->trans('application.console.arguments.learning'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-chain', '--gc', InputOption::VALUE_NONE, $this->trans('application.console.arguments.generate-chain'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-inline', '--gi', InputOption::VALUE_NONE, $this->trans('application.console.arguments.generate-inline'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-doc', '--gd', InputOption::VALUE_NONE, $this->trans('application.console.arguments.generate-doc'))
        );
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(
            array(
            new InputArgument('command', InputArgument::REQUIRED, $this->trans('application.console.input.definition.command')),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.help')),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.quiet')),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.verbose')),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.version')),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.ansi')),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.no-ansi')),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, $this->trans('application.console.input.definition.no-interaction')),
            )
        );
    }

    /**
     * Returns the long version of the application.
     *
     * @return string The long application version
     *
     * @api
     */
    public function getLongVersion()
    {
        if ('UNKNOWN' !== $this->getName() && 'UNKNOWN' !== $this->getVersion()) {
            return sprintf($this->trans('application.console.options.version'), $this->getName(), $this->getVersion());
        }

        return '<info>Drupal Console</info>';
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $root = $input->getParameterOption(['--root'], null);

        $env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');

        $debug = getenv('DRUPAL_DEBUG') !== '0'
            && !$input->hasParameterOption(array('--no-debug', ''))
            && $env !== 'prod';

        $message = $this->getHelperSet()->get('message');
        $drupal = $this->getHelperSet()->get('drupal');
        $site = $this->getHelperSet()->get('site');
        $commandDiscovery = $this->getHelperSet()->get('commandDiscovery');
        $commandDiscovery->setApplicationRoot($this->getDirectoryRoot());

        $commands = [];
        $recursive = false;
        if (!$root) {
            $root = getcwd();
            $recursive = true;
        }

        if (!$drupal->isValidRoot($root, $recursive)) {
            $commands = $commandDiscovery->getConsoleCommands();
            $message->addWarningMessage(
                $this->trans('application.site.errors.directory')
            );
        } else {
            $site->setSitePath($drupal->getRoot());

            if ($drupal->isInstalled()) {
                $this->bootDrupal($env, $debug, $drupal);
                $disabledModules = $this->config->get('application.disable.modules');
                $commandDiscovery->setDisabledModules($disabledModules);

                $commands = $commandDiscovery->getCommands();
            } else {
                $commands = $commandDiscovery->getConsoleCommands();
                $message->addWarningMessage(
                    $this->trans('application.site.errors.settings')
                );
            }
        }

        $this->registerCommands($commands, $drupal);

        if (true === $input->hasParameterOption(['--shell', '-s'])) {
            $this->runShell($input);
            return;
        }

        if ($input) {
            $commandName = $this->getCommandName($input);
        }

        if (true === $input->hasParameterOption(array('--generate-doc', '--gd'))) {
            $command = $this->get($commandName);
            $command->addOption(
                'generate-doc',
                '--gd',
                InputOption::VALUE_NONE, $this->trans('application.console.arguments.generate-doc')
            );
        }

        parent::doRun($input, $output);
    }

    /**
     * @param $commands
     */
    private function registerCommands($commands)
    {
        if (!$commands) {
            return;
        }
        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    /**
     * @param string     $env
     * @param bool|false $debug
     * @param $drupal
     */
    private function bootDrupal($env = 'prod', $debug = false, $drupal)
    {
        if ($debug) {
            Debug::enable();
        }

        /**
         * @var \Drupal\Console\Helper\KernelHelper $kernelHelper
         */
        $kernelHelper = $this->getHelperSet()->get('kernel');

        $kernelHelper->setDebug($debug);
        $kernelHelper->setEnvironment($env);
        $kernelHelper->setClassLoader($drupal->getAutoLoadClass());
        if ($drupal->isInstalled()) {
            $kernelHelper->bootKernel();
        }
    }

    /**
     * @param InputInterface $input
     */
    protected function runShell(InputInterface $input)
    {
        /**
         * @var \Drupal\Console\Helper\ShellHelper $shell
         */
        $shell = $this->getHelperSet()->get('shell')->getShell();

        $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
        $shell->run();
    }

    /**
     * @return \Drupal\Console\UserConfig
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
        return realpath($this->directoryRoot);
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
        $defaultHelperSet = $this->getHelperSet();
        foreach ($helpers as $alias => $helper) {
            $defaultHelperSet->set($helper, is_int($alias) ? null : $alias);
        }
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
    }
}
