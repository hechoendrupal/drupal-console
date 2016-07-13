<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\PathCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Helper\HelperTrait;
use Drupal\Console\Style\DrupalStyle;

class PathCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;
    use HelperTrait;

    protected function configure()
    {
        $this
            ->setName('theme:path')
            ->setDescription($this->trans('commands.theme.path.description'))
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                $this->trans('commands.theme.path.arguments.module')
            )
            ->addOption(
                'absolute',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.path.options.absolute')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');

        $absolute = $input->getOption('absolute');

        $modulePath = $this->getSite()->getThemePath($module, $absolute);

        $io->info(
            $modulePath
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module argument
        $module = $input->getArgument('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setArgument('module', $module);
        }
    }
}
