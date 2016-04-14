<?php

namespace Drupal\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Drupal\Console\Helper\HelperTrait;
use Drupal\Console\Helper\DrupalHelper;
use Drupal\Console\Style\DrupalStyle;

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
    const VERSION = '0.11.3';

    /**
     * @var string
     */
    const DRUPAL_SUPPORTED_VERSION = '8.0.x';

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
     * @var \Drupal\Console\Helper\TranslatorHelper
     */
    protected $translator;

    /**
     * @var string
     */
    protected $commandName;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * Create a new application.
     *
     * @param $helpers
     */
    public function __construct($helpers)
    {
        parent::__construct($this::NAME, $this::VERSION);
        $this->addHelpers($helpers);

        $this->env = $this->getConfig()->get('application.environment');
        $this->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, $this->trans('application.options.env'), $this->env)
        );
        $this->getDefinition()->addOption(
            new InputOption('--root', null, InputOption::VALUE_OPTIONAL, $this->trans('application.options.root'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--no-debug', null, InputOption::VALUE_NONE, $this->trans('application.options.no-debug'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--learning', null, InputOption::VALUE_NONE, $this->trans('application.options.learning'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-chain', '-c', InputOption::VALUE_NONE, $this->trans('application.options.generate-chain'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-inline', '-i', InputOption::VALUE_NONE, $this->trans('application.options.generate-inline'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-doc', '-d', InputOption::VALUE_NONE, $this->trans('application.options.generate-doc'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--target', '-t', InputOption::VALUE_OPTIONAL, $this->trans('application.options.target'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--uri', '-l', InputOption::VALUE_REQUIRED, $this->trans('application.options.uri'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--yes', '-y', InputOption::VALUE_NONE, $this->trans('application.options.yes'))
        );

        $options = $this->getConfig()->get('application.default.global.options')?:[];
        foreach ($options as $key => $option) {
            if ($this->getDefinition()->hasOption($key)) {
                $_SERVER['argv'][] = sprintf('--%s', $key);
            }
        }

        if (count($_SERVER['argv'])>1 && stripos($_SERVER['argv'][1], '@')===0) {
            $_SERVER['argv'][1] = sprintf(
                '--target=%s',
                substr($_SERVER['argv'][1], 1)
            );
        }
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(
            [
                new InputArgument('command', InputArgument::REQUIRED, $this->trans('application.arguments.command')),
                new InputOption('--help', '-h', InputOption::VALUE_NONE, $this->trans('application.options.help')),
                new InputOption('--quiet', '-q', InputOption::VALUE_NONE, $this->trans('application.options.quiet')),
                new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, $this->trans('application.options.verbose')),
                new InputOption('--version', '-V', InputOption::VALUE_NONE, $this->trans('application.options.version')),
                new InputOption('--ansi', '', InputOption::VALUE_NONE, $this->trans('application.options.ansi')),
                new InputOption('--no-ansi', '', InputOption::VALUE_NONE, $this->trans('application.options.no-ansi')),
                new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, $this->trans('application.options.no-interaction')),
            ]
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
            return sprintf($this->trans('application.messages.version'), $this->getName(), $this->getVersion());
        }

        return '<info>Drupal Console</info>';
    }
    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $root = null;
        $commandName = null;
        $recursive = false;
        $config = $this->getConfig();
        $target = $input->getParameterOption(['--target'], null);

        if ($input && $commandName = $this->getCommandName($input)) {
            $this->commandName = $commandName;
        }

        $targetConfig = [];
        if ($target && $config->loadTarget($target)) {
            $targetConfig = $config->getTarget($target);
            $root = $targetConfig['root'];
        }

        if ($targetConfig && $targetConfig['remote']) {
            $remoteResult = $this->getRemoteHelper()->executeCommand(
                $commandName,
                $target,
                $targetConfig,
                $input->__toString(),
                $config->getUserHomeDir()
            );
            $output->writeln($remoteResult);
            return 0;
        }

        if (!$target && $input->hasParameterOption(['--root'])) {
            $root = $input->getParameterOption(['--root']);
            $root = (strpos($root, '/')===0)?$root:sprintf('%s/%s', getcwd(), $root);
        }

        $uri = $input->getParameterOption(['--uri', '-l']);
        $env = $input->getParameterOption(['--env', '-e'], getenv('DRUPAL_ENV') ?: 'prod');

        if ($env) {
            $this->env = $env;
        }

        $debug = getenv('DRUPAL_DEBUG') !== '0'
          && !$input->hasParameterOption(['--no-debug', ''])
          && $env !== 'prod';

        if ($debug) {
            Debug::enable();
        }

        $drupal = $this->getDrupalHelper();
        $this->getCommandDiscoveryHelper()->setApplicationRoot($this->getDirectoryRoot());

        if (!$root) {
            $root = getcwd();
            $recursive = true;
        }

        if (!$drupal->isValidRoot($root, $recursive)) {
            $commands = $this->getCommandDiscoveryHelper()->getConsoleCommands();
            if ($commandName == 'list') {
                $this->errorMessage = $this->trans('application.site.errors.directory');
            }
            $this->registerCommands($commands);
        } else {
            $this->getKernelHelper()->setRequestUri($uri);
            $this->getKernelHelper()->setDebug($debug);
            $this->getKernelHelper()->setEnvironment($this->env);

            $this->prepare($drupal, $commandName);
        }

        if ($commandName && $this->has($commandName)) {
            $command = $this->get($commandName);
            $parameterOptions = $this->getDefinition()->getOptions();
            foreach ($parameterOptions as $optionName => $parameterOption) {
                $parameterOption = [
                    sprintf('--%s', $parameterOption->getName()),
                    sprintf('-%s', $parameterOption->getShortcut())
                ];
                if (true === $input->hasParameterOption($parameterOption)) {
                    $option = $this->getDefinition()->getOption($optionName);
                    $command->getDefinition()->addOption($option);
                }
            }
        }

        $skipCheck = [
          'check',
          'settings:check',
          'init',
          'settings:check'
        ];
        if (!in_array($commandName, $skipCheck) && $config->get('application.checked') != 'true') {
            $requirementChecker = $this->getContainerHelper()->get('requirement_checker');
            $phpCheckFile = $this->getConfig()->getUserHomeDir().'/.console/phpcheck.yml';
            if (!file_exists($phpCheckFile)) {
                $phpCheckFile = $this->getDirectoryRoot().'config/dist/phpcheck.yml';
            }
            $requirementChecker->validate($phpCheckFile);
            if (!$requirementChecker->isValid()) {
                $command = $this->find('settings:check');
                return $this->doRunCommand($command, $input, $output);
            }
            if ($requirementChecker->isOverwritten()) {
                $this->getChain()->addCommand('settings:check');
            } else {
                $this->getChain()->addCommand(
                    'settings:set',
                    [
                        'setting-name' => 'checked',
                        'setting-value' => 'true',
                        '--quiet'
                    ]
                );
            }
        }

        return parent::doRun($input, $output);
    }

    /**
     * Prepare drupal.
     *
     * @param DrupalHelper $drupal
     * @param string       $commandName
     */
    public function prepare(DrupalHelper $drupal, $commandName = null)
    {
        if ($drupal->isValidInstance()) {
            chdir($drupal->getRoot());
            $this->getSite()->setSiteRoot($drupal->getRoot());
            $this->bootDrupal($drupal);
        }

        if ($drupal->isInstalled()) {
            $disabledModules = $this->getConfig()->get('application.disable.modules');
            $this->getCommandDiscoveryHelper()->setDisabledModules($disabledModules);
            $commands = $this->getCommandDiscoveryHelper()->getCommands();
        } else {
            $commands = $this->getCommandDiscoveryHelper()->getConsoleCommands();
            if ($commandName == 'list') {
                $this->errorMessage = $this->trans(
                    'application.site.errors.settings'
                );
            }
        }

        $this->registerCommands($commands);
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
            $aliases = $this->getCommandAliases($command);
            if ($aliases) {
                $command->setAliases($aliases);
            }

            $this->add($command);
        }

        $autoWireForcedCommands = $this->getConfig()->get(
            sprintf(
                'application.autowire.commands.forced'
            )
        );

        foreach ($autoWireForcedCommands as $autoWireForcedCommand) {
            $command = new $autoWireForcedCommand['class'](
                $autoWireForcedCommand['helperset']?$this->getHelperSet():null
            );
            $this->add($command);
        }

        $autoWireNameCommand = $this->getConfig()->get(
            sprintf(
                'application.autowire.commands.name.%s',
                $this->commandName
            )
        );

        if ($autoWireNameCommand) {
            $command = new $autoWireNameCommand['class'](
                $autoWireNameCommand['helperset']?$this->getHelperSet():null
            );
            $this->add($command);
        }
    }

    /**
     * @param $command
     * @return array|null
     */
    private function getCommandAliases($command)
    {
        $aliasKey = sprintf(
            'application.default.commands.%s.aliases',
            str_replace(':', '.', $command->getName())
        );

        return $this->getConfig()->get($aliasKey);
    }

    /**
     * @param DrupalHelper $drupal
     */
    public function bootDrupal(DrupalHelper $drupal)
    {
        $this->getKernelHelper()->setClassLoader($drupal->getAutoLoadClass());
        $drupal->setInstalled($this->getKernelHelper()->bootKernel());
    }

    /**
     * @return \Drupal\Console\Config
     */
    public function getConfig()
    {
        if ($this->getContainerHelper()) {
            return $this->getContainerHelper()->get('config');
        }

        return null;
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
        $defaultHelperSet = $this->getHelperSet()?:$this->getDefaultHelperSet();
        foreach ($helpers as $alias => $helper) {
            $defaultHelperSet->set($helper, is_int($alias) ? null : $alias);
        }
    }

    /**
     * Remove dispatcher.
     */
    public function removeDispatcher()
    {
        $dispatcher = new EventDispatcher();
        $this->setDispatcher($dispatcher);
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if ($translator = $this->getTranslator()) {
            return $translator->trans($key);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
