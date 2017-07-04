<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Example.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class ExampleCommand extends Command
{

    use CommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:twig:filter')
            ->setDescription($this->trans('commands.generate.twig.filter.description'))
            ->setHelp($this->trans('commands.twig.filter.update.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.twig.filter.class')
            )
            ->addOption(
                'name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.twig.filter.name')
            );
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
        // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
        $module = $this->moduleQuestion($output);
        $input->setOption('module', $module);
      }

      // --class option
      $class = $input->getOption('class');
      if (!$class) {
        $class = $io->ask(
          $this->trans('commands.generate.controller.questions.class'),
          'DefaultController',
          function ($class) {
            return $this->validateClassName($class);
          }
        );
        $input->setOption('class', $class);
      }

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

    }
}