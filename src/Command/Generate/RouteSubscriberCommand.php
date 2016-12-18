<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\RouteSubscriber.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\RouteSubscriberGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Command\Shared\CommandTrait;

/**
 * Class RouteSubscriberCommand
 * @package Drupal\Console\Command\Generate
 */
class RouteSubscriberCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager  
*/
    protected $extensionManager;

    /**
 * @var RouteSubscriberGenerator  
*/
    protected $generator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * RouteSubscriberCommand constructor.
     * @param Manager                  $extensionManager
     * @param RouteSubscriberGenerator $generator
     * @param ChainQueue               $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        RouteSubscriberGenerator $generator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:routesubscriber')
            ->setDescription($this->trans('commands.generate.routesubscriber.description'))
            ->setHelp($this->trans('commands.generate.routesubscriber.description'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.routesubscriber.options.name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.routesubscriber.options.class')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $class = $input->getOption('class');

        $this->generator->generate($module, $name, $class);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.generate.routesubscriber.questions.name'),
                $module.'.route_subscriber'
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.routesubscriber.questions.class'),
                'RouteSubscriber'
            );
            $input->setOption('class', $class);
        }
    }

    protected function createGenerator()
    {
        return new RouteSubscriberGenerator();
    }
}
