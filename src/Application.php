<?php

namespace Drupal\Console;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Drupal\Console\Helper\HelperTrait;

/**
 * Class Application
 * @package Drupal\Console\Console
 */
class Application extends BaseApplication
{
    use HelperTrait;

    /**
     * @var string
     */
    const NAME = 'Drupal Console';
    /**
     * @var string
     */
    const VERSION = '0.9.4';
    /**
     * @var string
     */
    const DRUPAL_VERSION = 'Drupal 8 RC-1';
    /**
     * @var Drupal\Console\Config
     */
    protected $config;
    /**
     * @var string
     */
    protected $directoryRoot;
    /**
     * @var string
     * The Drupal environment.
     */
    protected $env;
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

        parent::__construct($this::NAME, $this::VERSION);

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
        $this->getDefinition()->addOption(
            new InputOption('--target', '--t', InputOption::VALUE_OPTIONAL, $this->trans('application.console.arguments.target'))
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
        $root = null;
        $config = $this->getConfig();
        $target = $input->getParameterOption(['--target'], null);

        if ($input) {
            $commandName = $this->getCommandName($input);
        }

        $targetConfig = [];
        if ($target && $config->loadTarget($target)) {
            $targetConfig = $config->getTarget($target);
            $root = $targetConfig['root'];
        }

        if ($targetConfig && $targetConfig['remote']) {
            $remoteHelper = $this->getRemoteHelper();
            $remoteResult = $remoteHelper->executeCommand(
                $commandName,
                $target,
                $targetConfig,
                $input->__toString(),
                $config->getUserHomeDir()
            );
            $output->writeln($remoteResult);
            return 0;
        }

        if (!$target) {
            $root = $input->getParameterOption(['--root'], null);
        }

        $env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');

        $debug = getenv('DRUPAL_DEBUG') !== '0'
          && !$input->hasParameterOption(array('--no-debug', ''))
          && $env !== 'prod';

        $message = $this->getMessageHelper();
        $drupal = $this->getDrupalHelper();
        $site = $this->getSite();
        $commandDiscovery = $this->getCommandDiscoveryHelper();
        $commandDiscovery->setApplicationRoot($this->getDirectoryRoot());
        $recursive = false;

        if (!$root) {
            $root = getcwd();
            $recursive = true;
        }

        if (!$drupal->isValidRoot($root, $recursive)) {
            $commands = $commandDiscovery->getConsoleCommands();
            if (!$commandName) {
                $message->addWarningMessage(
                    $this->trans('application.site.errors.directory')
                );
            }
        } else {
            chdir($drupal->getRoot());
            $site->setSitePath($drupal->getRoot());

            if ($drupal->isValidInstance()) {
                $this->bootDrupal($env, $debug, $drupal);
            }

            if ($drupal->isInstalled()) {
                $disabledModules = $this->config->get('application.disable.modules');
                $commandDiscovery->setDisabledModules($disabledModules);

                $commands = $commandDiscovery->getCommands();
            } else {
                $commands = $commandDiscovery->getConsoleCommands();
                if (!$commandName) {
                    $message->addWarningMessage(
                        $this->trans('application.site.errors.settings')
                    );
                }
            }
        }

        $this->registerCommands($commands, $drupal);

        if (true === $input->hasParameterOption(['--shell', '-s'])) {
            $this->runShell($input);
            return;
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

        $kernelHelper = $this->getKernelHelper();

        $kernelHelper->setDebug($debug);
        $kernelHelper->setEnvironment($env);
        $kernelHelper->setClassLoader($drupal->getAutoLoadClass());
        $kernelHelper->bootKernel();
    }

    /**
     * @param InputInterface $input
     */
    protected function runShell(InputInterface $input)
    {
        /**
         * @var \Drupal\Console\Helper\ShellHelper $shell
         */
        $shell = $this->getShellHelper()->getShell();

        $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
        $shell->run();
    }

    /**
     * @return \Drupal\Console\Config
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

    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new \Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand();
        return $commands;
    }
}
