<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UpdateCommand.
 */

namespace Drupal\Console\Command\Module;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\ProjectDownloadTrait;
use Drupal\Console\Command\PHPProcessTrait;

class UpdateCommand extends Command
{
    use ProjectDownloadTrait;
    use PHPProcessTrait;

    protected $stable = true;

    protected function configure()
    {
        $this
            ->setName('module:update')
            ->setDescription($this->trans('commands.module.update.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.update.arguments.module')
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.update.options.composer')
            )
            ->addOption(
                'simulate',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.update.options.simulate')
            );
    }

    /**
     * {@inheritdoc}
     *
     * without --composer it does nothing
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        if (!$composer) {
            $io->error($this->trans('commands.module.update.messages.only-composer'));
            return false;
        }

        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $modules = $input->getArgument('module');
        $composer = $input->getOption('composer');
        $simulate = $input->getOption('simulate');

        if (empty($modules)) $modules = "";
        else {
          if (count($modules) > 1) $modules = " drupal/" . implode(" drupal/", $modules);
          else $modules = " drupal/" . current($modules);
        }

        if ($composer) {

                $this->setComposerRepositories($io);

                $cmd = "cd " . $this->getApplication()->getSite()->getSiteRoot() . "; ";
                $cmd .= 'composer update ' . $modules . ' -o ';

                if ($simulate) {
                  $cmd .= " --dry-run";
                }


                if ($this->execProcess($cmd)) {
                    $io->success(
                        sprintf(
                            $this->trans('commands.module.update.messages.composer'),
                            $version
                        )
                    );
                }

        }

        return true;
    }
}
