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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Console\Helper\HelperTrait;
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
    const VERSION = '1.0.0-beta3';

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
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Create a new application.
     *
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        parent::__construct($this::NAME, $this::VERSION);

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

        $options = $this->getConfig()->get('application.options')?:[];
        foreach ($options as $key => $option) {
            if ($this->getDefinition()->hasOption($key)) {
                if (is_bool($option) && $option === true) {
                    $_SERVER['argv'][] = sprintf('--%s', $key);
                }
                if (!is_bool($option) && $option) {
                    $_SERVER['argv'][] = sprintf('--%s=%s', $key, $option);
                }
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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

        /*Checking if the URI has http of not in begenning*/
        if ($uri && !preg_match('/^(http|https):\/\//', $uri)) {
            $uri = sprintf(
                'http://%s',
                $uri
            );
        }

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

        /* validate drupal site */
        $this->container->get('site')->isValidRoot($root, $recursive);

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
          'init',
        ];
        if (!in_array($commandName, $skipCheck) && $config->get('application.checked') != 'true') {
            $requirementChecker = $this->getContainerHelper()->get('requirement_checker');
            $phpCheckFile = $this->getConfig()->getUserHomeDir().'/.console/phpcheck.yml';
            if (!file_exists($phpCheckFile)) {
                $phpCheckFile = $this->getDirectoryRoot().'config/dist/phpcheck.yml';
            }
            $requirementChecker->validate($phpCheckFile);
            if (!$requirementChecker->isValid()) {
                $command = $this->find('check');
                return $this->doRunCommand($command, $input, $output);
            }
            if ($requirementChecker->isOverwritten()) {
                $this->getChain()->addCommand('check');
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
     * @param $drupal
     * @param $commandName
     */
    public function prepare($drupal, $commandName = null)
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
            $command->setAliases($this->getCommandAliases($command));
            $this->add($command);
        }

        $autoWireForcedCommands = $this->getConfig()->get(
            sprintf(
                'application.autowire.commands.forced'
            )
        );

        foreach ($autoWireForcedCommands as $autoWireForcedCommand) {
            $command = new $autoWireForcedCommand['class'];
            $this->add($command);
        }

        $autoWireNameCommand = $this->getConfig()->get(
            sprintf(
                'application.autowire.commands.name.%s',
                $this->commandName
            )
        );

        if ($autoWireNameCommand) {
            $command = new $autoWireNameCommand['class'];

            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator($this->container->get('translator'));
            }

            $this->add($command);
        }

        $tags = $this->container->findTaggedServiceIds('console.command');
        foreach ($tags as $name => $tags) {
            /* Add interface(s) for commands:
             * DrupalConsoleCommandInterface &
             * DrupalConsoleContainerAwareCommandInterface
             * and use implements for validation
             */
            $command = $this->getContainerHelper()->get($name);
            if (!$this->getDrupalHelper()->isInstalled()) {
                $traits = class_uses($command);
                if (in_array('Drupal\\Console\\Command\\Shared\\ContainerAwareCommandTrait', $traits)) {
                    continue;
                }
            }

            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator($this->container->get('translator'));
            }

            $command->setAliases($this->getCommandAliases($command));
            $this->add($command);
        }
    }

    public function getData()
    {
        $singleCommands = [
            'about',
            'chain',
            'check',
            'help',
            'init',
            'list',
            'self-update',
            'server'
        ];
        $languages = $this->getConfig()->get('application.languages');

        $data = [];
        foreach ($singleCommands as $singleCommand) {
            $data['commands']['none'][] = $this->commandData($singleCommand);
        }

        $namespaces = array_filter(
            $this->getNamespaces(), function ($item) {
                return (strpos($item, ':')<=0);
            }
        );
        sort($namespaces);
        array_unshift($namespaces, 'none');

        foreach ($namespaces as $namespace) {
            $commands = $this->all($namespace);
            usort(
                $commands, function ($cmd1, $cmd2) {
                    return strcmp($cmd1->getName(), $cmd2->getName());
                }
            );
            foreach ($commands as $command) {
                if ($command->getModule()=='Console') {
                    $data['commands'][$namespace][] = $this->commandData($command->getName());
                }
            }
        }

        $input = $this->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[] = [
                'name' => $option->getName(),
                'description' => $this->trans('application.options.'.$option->getName())
            ];
        }
        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[] = [
                'name' => $argument->getName(),
                'description' => $this->trans('application.arguments.'.$argument->getName())
            ];
        }

        $data['application'] = [
            'namespaces' => $namespaces,
            'options' => $options,
            'arguments' => $arguments,
            'languages' => $languages,
            'messages' => [
                'title' =>  $this->trans('commands.generate.doc.gitbook.messages.title'),
                'note' =>  $this->trans('commands.generate.doc.gitbook.messages.note'),
                'note_description' =>  $this->trans('commands.generate.doc.gitbook.messages.note-description'),
                'command' =>  $this->trans('commands.generate.doc.gitbook.messages.command'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
            'examples' => []
        ];

        return $data;
    }

    private function commandData($commandName)
    {
        $command = $this->find($commandName);

        $input = $command->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[$option->getName()] = [
                'name' => $option->getName(),
                'description' => $option->getDescription(),
            ];
        }

        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[$argument->getName()] = [
                'name' => $argument->getName(),
                'description' => $argument->getDescription(),
            ];
        }

        $commandKey = str_replace(':', '.', $command->getName());

        $examples = [];
        for ($i = 0; $i < 5; $i++) {
            $description = sprintf(
                'commands.%s.examples.%s.description',
                $commandKey,
                $i
            );
            $execution = sprintf(
                'commands.%s.examples.%s.execution',
                $commandKey,
                $i
            );

            if ($description != $this->trans($description)) {
                $examples[] = [
                    'description' => $this->trans($description),
                    'execution' => $this->trans($execution)
                ];
            } else {
                break;
            }
        }

        $data = [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'options' => $options,
            'arguments' => $arguments,
            'examples' => $examples,
            'aliases' => $command->getAliases(),
            'key' => $commandKey,
            'dashed' => str_replace(':', '-', $command->getName()),
            'messages' => [
                'usage' =>  $this->trans('commands.generate.doc.gitbook.messages.usage'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
        ];

        return $data;
    }

    /**
     * @param $command
     * @return array
     */
    private function getCommandAliases($command)
    {
        $aliases = $this->getConfig()
            ->get('commands.aliases.'. $command->getName());

        return $aliases?[$aliases]:[];
    }

    /**
     * @param $drupal
     */
    public function bootDrupal($drupal)
    {
        $this->getKernelHelper()->setClassLoader($drupal->getAutoLoadClass());
        $drupal->setInstalled($this->getKernelHelper()->bootKernel());
        $this->container->get('site')->setInstalled($this->getKernelHelper()->bootKernel());
    }

    /**
     * @return \Drupal\Console\Config
     */
    public function getConfig()
    {
        if ($this->container) {
            return $this->container->get('config');
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
        if ($this->container && $this->container->has('translator')) {
            return $this->container->get('translator')->trans($key);
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

    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->container;
    }
}
