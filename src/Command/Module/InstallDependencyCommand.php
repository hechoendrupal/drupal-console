<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallDependencyCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class InstallCommand
 * @package Drupal\Console\Command\Module
 */
class InstallDependencyCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ProjectDownloadTrait;
    use ModuleTrait;
  
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:dependency:install')
            ->setDescription($this->trans('commands.module.install.dependencies.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.install.dependencies.arguments.module')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setArgument('module', $module);
        }

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module'); 
        $unInstalledDependencies = $this->calculateDependencies((array)$module);
        
        if (!$unInstalledDependencies) {
            $io->warning($this->trans('commands.module.install.dependencies.messages.no-depencies'));
             return 0;

        }
        
        try {
            $io->comment(
                sprintf(
                    $this->trans('commands.module.install.dependencies.messages.installing'),
                    implode(', ', $unInstalledModules)
                )
            );

            $moduleInstaller = $this->getDrupalService('module_installer');
            drupal_static_reset('system_rebuild_module_data');

            $moduleInstaller->install($unInstalledDependencies, true);
            $io->success(
                sprintf(
                    $this->trans('commands.module.install.dependencies.messages.success'),
                    implode(', ', $unInstalledDependencies)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
