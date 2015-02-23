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
          ->addOption('module', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.common.options.module'))
          ->addOption('table-name', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.column.options.table-name'))
          ->addOption('table-description', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.column.options.table-description'))
          ->addOption('columns', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.common.options.columns'))
          ->addOption('primary-key', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.common.options.primary-key'))
          ->addOption('indexes', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.common.options.indexes'));
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
        $primary_key = $input->getOption('primary-key');
        $indexes = $input->getOption('indexes');

        $this
          ->getGenerator()
          ->generate($module, $table_name, $table_description, $columns,
            $primary_key, $indexes);
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
            $table_name = $this->getStringUtils()
              ->camelCaseToMachineName($module);
            $table_name = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.column.questions.table-name'),
                $table_name),
              $table_name
            );
        }
        $input->setOption('table-name', $table_name);

        // --table-description option
        $table_description = $input->getOption('table-description');
        if (!$table_description) {
            $table_description = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.column.questions.table-description'),
                'Hit enter to exclude'),
              null
            );
        }
        $table_description = $this->getStringUtils()
          ->anyCaseToUcFirst($table_description);
        $input->setOption('table-description', $table_description);

        // --column options
        $columns = $input->getOption('columns');
        if (!$columns) {
            // @see \Drupal\AppConsole\Command\Helper\InstallTrait::installQuestion
            $columns = $this->installQuestion($output, $dialog);
        }
        $input->setOption('columns', $columns);

        $column_names = array();
        foreach ($columns as $item) {
            $column_names[] = $item['column_name'];
        }

        $column_names_string = implode(', ', $column_names);

        // --primary key options
        $primary_key = $input->getOption('primary-key');
        if (!$primary_key) {
            if ($dialog->askConfirmation(
              $output,
              $dialog->getQuestion($this->trans('commands.common.questions.columns.confirm_primary_key'),
                'yes', '?'),
              true
            )
            ) {
                $primary_key_options = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.table_primary_key'),
                    $column_names_string, ':'),
                  function ($primary_key_choices) use ($column_names) {
                      $primary_key_choices = $this->getStringUtils()
                        ->camelCaseToCommaSeparated($primary_key_choices);
                      if (empty($primary_key_choices)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.table_primary_key_invalid'),
                              $primary_key_choices)
                          );
                      }

                      return $primary_key_choices;
                  },
                  false,
                  null,
                  $column_names
                );
            }
        }

        $input->setOption('primary-key', $primary_key_options);




        // --indexes options
        $indexes = $input->getOption('indexes');
        if (!$indexes) {
            // @see \Drupal\AppConsole\Command\Helper\InstallTrait::installIndex
            $indexes = $this->installIndex($output, $dialog);
        }
        $input->setOption('indexes', $indexes);





    }

    /**
     * @return \Drupal\AppConsole\Generator\InstallGenerator.
     */
    protected function createGenerator()
    {
        return new InstallGenerator();
    }
}
