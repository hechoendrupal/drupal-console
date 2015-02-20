<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorInstallCommand.
 *
 * Todo: Permit the creation of more than one table.
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
          ->addOption('table-description', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.column.options.table-description'))
          ->addOption('columns', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.columns'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $table_name = $input->getOption('table-name');
        $table_description = $input->getOption('table-description');
        $columns = $input->getOption('columns');

        $this
          ->getGenerator()
          ->generate($module, $table_name, $table_description, $columns);
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
        $table_name = $input->getOption('table-name');
        if (!$table_name) {
            $table_name = $this->getStringUtils()->camelCaseToMachineName($module);
            $table_name = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.column.questions.table-name'), $table_name),
              $table_name
            );
        }
        $input->setOption('table-name', $table_name);

        // --table-description option
        $table_description = $input->getOption('table-description');
        if (!$table_description) {
            $table_description = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.column.questions.table-description'), 'Hit enter to exclude'),
              null
            );
        }
        $table_description = $this->getStringUtils()->anyCaseToUcFirst($table_description);
        $input->setOption('table-description', $table_description);

        // --column options
        $columns = $input->getOption('columns');
        if (!$columns) {
            // @see \Drupal\AppConsole\Command\Helper\InstallTrait::installQuestion
            $columns = $this->installQuestion($output, $dialog);
        }
        $input->setOption('columns', $columns);
    }

    /**
     * @return \Drupal\AppConsole\Generator\InstallGenerator.
     */
    protected function createGenerator()
    {
        return new InstallGenerator();
    }
}
