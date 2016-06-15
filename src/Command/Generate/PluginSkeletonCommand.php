<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginSkeletonCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginSkeletonGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\Console\Generator\HelpGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;


class PluginSkeletonCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:skeleton')
            ->setDescription($this->trans('commands.generate.plugin.skeleton.description'))
            ->setHelp($this->trans('commands.generate.plugin.skeleton.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.description')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');

        $pluginId = $input->getOption('plugin-id');

        //$drupalContainer = $this->getContainer();

        // Confirm that plugin.manager exist before to
        if (!$this->validatePluginManagerServiceExist('plugin.manager.' . $pluginId)) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.plugin.skeleton.messages.plugin-dont-exist'),
                    $module
                )
            );
        }

        $pluginMetaData = $this->getPluginMetadata($pluginId);

        exit();

        $this
            ->getGenerator()
            ->generate($module, $pluginId, $pluginMetaData);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $plugins = $this->getPlugins();
            $pluginId = $io->choiceNoList(
                $this->trans('commands.generate.plugin.skeleton.questions.plugin'),
                $plugins
            );
            $input->setOption('plugin-id', $pluginId);
        }
    }


    protected function createGenerator()
    {
        return new PluginSkeletonGenerator();
    }

    protected function getPluginMetadata($pluginId) {
        $metaData = [];
        $pluginTypes = [];

        if($pluginId) {
            $drupalContainer = $this->getContainer();
            if($drupalContainer->hasService('plugin.plugin_type_manager')) {
                $pluginTypes = $this->getPluginTypeManager()->getPluginTypes();
            }

            print_r($pluginTypes);
        }
        return $metaData;
    }

    protected function getPlugins(){

        $plugins = [];

        $drupalContainer = $this->getContainer();
        foreach ($drupalContainer->getServiceIds() as $serviceId) {
            if (strpos($serviceId, 'plugin.manager.') === 0) {
                $plugins[] = substr($serviceId, 15);
            }
        }

        return $plugins;

    }
}
