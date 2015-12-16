<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginBlockCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginBlockGenerator;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginBlockCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:block')
            ->setDescription($this->trans('commands.generate.plugin.block.description'))
            ->setHelp($this->trans('commands.generate.plugin.block.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.plugin-id')
            )
            ->addOption(
                'theme-region',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.theme-region')
            )
            ->addOption(
                'inputs',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            )
            ->addOption('services', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.services'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $services = $input->getOption('services');
        $theme_region = $input->getOption('theme-region');
        $inputs = $input->getOption('inputs');

        $configFactory = $this->getConfigFactory();
        $theme = $configFactory->get('system.theme')->get('default');
        $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);

        if (!empty($theme_region) && !isset($themeRegions[$theme_region])) {
            $output->error(
                sprintf(
                    $this->trans('commands.generate.plugin.block.messages.invalid-theme-region'),
                    $theme_region
                )
            );

            return 1;
        }

        // @see use Drupal\Console\Command\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $build_services, $inputs);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);

        if ($theme_region) {
            // Load block to set theme region
            $block = $this->getEntityManager()->getStorage('block')->create(array('id'=> $plugin_id, 'plugin' => $plugin_id, 'theme' => $theme));
            $block->setRegion($theme_region);
            $block->save();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $configFactory = $this->getConfigFactory();
        $theme = $configFactory->get('system.theme')->get('default');
        $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);
        
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
            $class = $output->ask(
                $this->trans('commands.generate.plugin.block.options.class'),
                'DefaultBlock',
                function ($class) {
                    return $this->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.generate.plugin.block.options.label'),
                $this->getStringHelper()->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $output->ask(
                $this->trans('commands.generate.plugin.block.options.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        // --theme-region option
        $themeRegion = $input->getOption('theme-region');
        if (!$themeRegion) {
            $themeRegion =  $output->choiceNoList(
                $this->trans('commands.generate.plugin.block.options.theme-region'),
                array_values($themeRegions),
                null,
                true
            );
            $themeRegion = array_search($themeRegion, $themeRegions);
            $input->setOption('theme-region', $themeRegion);
        }

        // --services option
        // @see Drupal\Console\Command\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion($output);
        $input->setOption('services', $services);

        $output->writeln($this->trans('commands.generate.plugin.block.messages.inputs'));

        // @see Drupal\Console\Command\FormTrait::formQuestion
        $inputs = $this->formQuestion($output);
        $input->setOption('inputs', $inputs);
    }

    protected function createGenerator()
    {
        return new PluginBlockGenerator();
    }
}
