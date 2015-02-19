<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\InstallTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait InstallTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     * @return mixed
     */
    public function installQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        if ($dialog->askConfirmation(
          $output,
          $dialog->getQuestion($this->trans('commands.common.questions.columns.confirm'), 'yes', '?'),
          true
        )
        ) {
            $column_types = [
              'serial',
              'int',
              'float',
              'numeric',
              'varchar',
              'char',
              'text',
              'blob',
            ];
            $column_size = [
              'tiny',
              'small',
              'medium',
              'big',
              'normal',
            ];
            $column_length = [
              '3',
              '4',
              '10',
              '11',
              '12',
              '32',
              '64',
              '128',
              '255',
              '2048',
            ];

            $columns = [];
            while (true) {
                // Column name
                $column_name = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_name'), '', ':'),
                  null
                );
                $column_name = $this->getStringUtils()->camelCaseToMachineName($column_name);

                if (empty($column_name)) {
                    break;
                }

                // Column type input
                $column_type = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_type'), 'varchar', ':'),
                  function ($column_options) use ($column_types) {
                      if (!in_array($column_options, $column_types)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_invalid'), $column_options)
                          );
                      }

                      return $column_options;
                  },
                  false,
                  'varchar',
                  $column_types
                );

                // Description for input
                $column_description = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_description'), '', ':'),
                  null
                );

                array_push($columns, array(
                  'column_name' => $column_name,
                  'column_type' => $column_type,
                  'column_description' => $column_description,
                ));
            }

            return $columns;
        }
        return null;
    }
}
