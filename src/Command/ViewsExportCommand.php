<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RestDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Yaml\Dumper;

class ViewsExportCommand extends ContainerAwareCommand
{
    use ModuleTrait;

    protected $entity_manager;
    protected $configStorage;
    protected $config_export;

    protected function configure()
    {
        $this
            ->setName('views:export')
            ->setDescription($this->trans('commands.views.export.description'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);


        // --module option
        $view_id = $input->getArgument('view-id');
        if (!$view_id) {
            $entity_manager = $this->getEntityManager();
            $views = $entity_manager->getStorage('view')->loadMultiple();

            $views_list = [];
            foreach ($views as $view) {
                $views_list[$view->get('id')] = $view->get('label');
            }

            $view_id = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.views.export.questions.view'), ''),
                function ($view) use ($views_list) {
                    if (!in_array($view, array_values($views_list))) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'View "%s" is invalid.',
                                $view
                            )
                        );
                    }

                    return array_search($view, $views_list);
                },
                false,
                '',
                $views_list
            );
        }
        $input->setArgument('view-id', $view_id);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->entity_manager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $view_id = $input->getArgument('view-id');

        $view_type_definition = $this->entity_manager->getDefinition('view');
        $view_type_name = $view_type_definition->getConfigPrefix() . '.' . $view_id;

        $view_name_config = $this->getConfiguration($view_type_name);

        $this->config_export[$view_type_name] = $view_name_config;

        $this->exportConfig($module, $output);
    }

    protected function exportConfig($module, OutputInterface $output)
    {
        $dumper = new Dumper();

        $module_path =  $this->getSite()->getModulePath($module);
        if (!file_exists($module_path .'/config')) {
            mkdir($module_path .'/config', 0755, true);
        }

        if (!file_exists($module_path .'/config/install')) {
            mkdir($module_path .'/config/install', 0755, true);
        }

        $output->writeln(
            '[+] <info>' .
            $this->trans('commands.views.export.messages.view_exported') .
            '</info>'
        );

        foreach ($this->config_export as $file_name => $config) {
            $yaml_config = $dumper->dump($config, 10);
            $output->writeln(
                '- <info>' .
                str_replace(DRUPAL_ROOT, '', $module_path)  . '/config/install/' . $file_name . '.yml' .
                '</info>'
            );
            file_put_contents($module_path . '/config/install/' . $file_name . '.yml', $yaml_config);
        }
    }

    protected function getConfiguration($config_name)
    {
        // Unset uuid, maybe is not necessary to export
        $config = $this->configStorage->read($config_name);
        unset($config['uuid']);
        return $config;
    }


}
