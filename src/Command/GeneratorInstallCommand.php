<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorInstallCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\InstallTrait;
use Drupal\AppConsole\Generator\InstallGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorInstallCommand extends GeneratorCommand
{

    use ModuleTrait;
    use InstallTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
          ->setName('generate:install')
          ->setDescription($this->trans('commands.generate.column.description'))
          ->setHelp($this->trans('commands.generate.column.help'))
          ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
          ->addOption('table-name', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.column.options.table-name'))
          ->addOption('column_inputs', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.column_inputs'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $column_inputs = $input->getOption('column_inputs');

        $this
          ->getGenerator()
          ->generate($module, $column_inputs);
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
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --table-name option
        $default_table_name = $input->getOption('module');
        $table_name = $input->getOption('table-name');
        if (!$table_name) {
            $table_name = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.column.questions.table-name'), $default_table_name),
              $default_table_name
            );
        }
        $input->setOption('table-name', $table_name);

        // --column_inputs option
        $inputs = $input->getOption('column_inputs');
        if (!$inputs) {
            // @see \Drupal\AppConsole\Command\Helper\InstallTrait::installQuestion
            $inputs = $this->installQuestion($output, $dialog);
        }
        $input->setOption('column_inputs', $inputs);
    }

    /**
     * @return \Drupal\AppConsole\Generator\InstallGenerator.
     */
    protected function createGenerator()
    {
        return new InstallGenerator();
    }
}
