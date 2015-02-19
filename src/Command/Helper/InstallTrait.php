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
            $column_true_false = [
              'TRUE',
              'FALSE',
              'None',
            ];
            $column_default_choices = [
              '\'\'',
              '0',
              '1',
              'None',
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

                // Column type
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

                // Unsigned
                $column_unsigned = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_unsigned'), 'TRUE or FALSE. Type None to exclude', ':'),
                  function ($column_unsigned_options) use ($column_true_false) {
                      if (!in_array($column_unsigned_options, $column_true_false)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_unsigned_invalid'), $column_unsigned_options)
                          );
                      }

                      return $column_unsigned_options;
                  },
                  false,
                  'TRUE',
                  $column_true_false
                );

                // Not null
                $column_not_null = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_not_null'), 'TRUE or FALSE. Type None to exclude', ':'),
                  function ($column_not_null_options) use ($column_true_false) {
                      if (!in_array($column_not_null_options, $column_true_false)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_not_null_invalid'), $column_not_null_options)
                          );
                      }

                      return $column_not_null_options;
                  },
                  false,
                  'TRUE',
                  $column_true_false
                );

                // Default
                $column_default = $dialog->askAndValidate(
                  $output,
//                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_default'), '\'\'', ':'),
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_default'), '0, 1, or \'\'. Type None to exclude', ':'),
                  function ($column_default_options) use ($column_default_choices) {
                      if (!in_array($column_default_options, $column_default_choices)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_default_invalid'), $column_default_options)
                          );
                      }

                      return $column_default_options;
                  },
                  false,
                  '\'\'',
                  $column_default_choices
                );


//                'unsigned' => TRUE,
//                'not null' => TRUE,
//                'default' => 0,

                // Column Description
                $column_description = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_description'), '', ':'),
                  null
                );

                array_push($columns, array(
                  'column_name' => $column_name,
                  'column_type' => $column_type,
                  'column_unsigned' => $column_unsigned,
                  'column_not_null' => $column_not_null,
                  'column_default' => $column_default,
                  'column_description' => $column_description,
                ));
            }

            return $columns;
        }
        return null;
    }
}
