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
    public function installQuestion(
      OutputInterface $output,
      HelperInterface $dialog
    ) {
        if ($dialog->askConfirmation(
          $output,
          $dialog->getQuestion($this->trans('commands.common.questions.columns.confirm'),
            'yes', '?'),
          true
        )
        ) {
            $column_types = [
              'blob',
              'char',
              'float',
              'int',
              'numeric',
              'serial',
              'text',
              'varchar',
            ];
            $column_true_false = [
              'TRUE',
              'FALSE',
              null,
            ];
            $column_default_choices = [
              '\'\'',
              '0',
              '1',
              null,
            ];
            $column_size_choices = [
              'tiny',
              'small',
              'medium',
              'big',
              'normal',
              null,
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
              null,
            ];

            $columns = [];
            while (true) {
                // Column name
                $column_name = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.column_name'),
                    '', ':'),
                  null
                );
                $column_name = $this->getStringUtils()
                  ->camelCaseToMachineName($column_name);

                if (empty($column_name)) {
                    break;
                }

                // Column type
                $column_type = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_type'),
                    'blob, char, float, int, numeric, serial, text, varchar',
                    ':'),
                  function ($column_options) use ($column_types) {
                      if (!in_array($column_options, $column_types)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_type_invalid'),
                              $column_options)
                          );
                      }

                      return $column_options;
                  },
                  false,
                  'varchar',
                  $column_types
                );

                // varchar gets length
                $column_type_options = '';
                if (in_array($column_type, array('varchar'))) {

                    $column_type_options = $dialog->askAndValidate(
                      $output,
                      $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_length'),
                        '255', ':'),
                      function ($column_length_choices) use ($column_length) {
                          if (!in_array($column_length_choices, $column_length)
                          ) {
                              throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.common.questions.columns.column_length_invalid'),
                                  $column_length_choices)
                              );
                          }

                          return $column_length_choices;
                      },
                      false,
                      '255',
                      $column_length
                    );
                }

                // Unsigned
                $column_unsigned = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_unsigned'),
                    'TRUE or FALSE. Hit enter to exclude', ':'),
                  function ($column_unsigned_options) use ($column_true_false) {
                      if (!in_array($column_unsigned_options,
                        $column_true_false)
                      ) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_unsigned_invalid'),
                              $column_unsigned_options)
                          );
                      }

                      return $column_unsigned_options;
                  },
                  false,
                  null,
                  $column_true_false
                );

                // Not null
                $column_not_null = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_not_null'),
                    'TRUE or FALSE. Hit enter to exclude', ':'),
                  function ($column_not_null_options) use ($column_true_false) {
                      if (!in_array($column_not_null_options,
                        $column_true_false)
                      ) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_not_null_invalid'),
                              $column_not_null_options)
                          );
                      }

                      return $column_not_null_options;
                  },
                  false,
                  null,
                  $column_true_false
                );

                // Default
                $column_default = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_default'),
                    '0, 1, or \'\'. Hit enter to exclude', ':'),
                  function ($column_default_options) use (
                    $column_default_choices
                  ) {
                      if (!in_array($column_default_options,
                        $column_default_choices)
                      ) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_default_invalid'),
                              $column_default_options)
                          );
                      }

                      return $column_default_options;
                  },
                  false,
                  null,
                  $column_default_choices
                );

                // Size
                $column_size = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_size'),
                    'tiny, small, medium, big, normal. Hit enter to exclude',
                    ':'),
                  function ($column_size_options) use ($column_size_choices) {
                      if (!in_array($column_size_options, $column_size_choices)
                      ) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.columns.column_size_invalid'),
                              $column_size_options)
                          );
                      }

                      return $column_size_options;
                  },
                  false,
                  null,
                  $column_size_choices
                );

                // Description
                $column_description = $dialog->ask(
                  $output,
                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_description'),
                    'Hit enter to exclude', ':'),
                  null
                );

                array_push($columns, array(
                  'column_name' => $column_name,
                  'column_type' => $column_type,
                  'column_type_options' => $column_type_options,
                  'column_unsigned' => $column_unsigned,
                  'column_not_null' => $column_not_null,
                  'column_default' => $column_default,
                  'column_size' => $column_size,
                  'column_description' => $column_description,
                ));
            }

            return $columns;
        }

        return null;
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     * @return mixed
     */
    public function installIndex(
      OutputInterface $output,
      HelperInterface $dialog
    ) {
        if ($dialog->askConfirmation(
          $output,
          $dialog->getQuestion($this->trans('commands.common.questions.columns.confirm_index'),
            'yes', '?'),
          true
        )
        ) {
            $indexes = [];
            while (true) {
                // Column name
                $index_name = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.index_name'),
                    '', ':'),
                  null
                );
                $index_name_key = $this->getStringUtils()->camelCaseToMachineName($index_name);
                $index_name_value = $this->getStringUtils()->camelCaseToCommaSeparated($index_name);

                if (empty($index_name)) {
                    break;
                }

                array_push($indexes, array(
                  'index_name_key' => $index_name_key,
                  'index_name_value' => $index_name_value,
                ));
            }

            return $indexes;
        }

        return null;
    }
}

//                // Not null
//                $column_not_null = $dialog->askAndValidate(
//                  $output,
//                  $dialog->getQuestion('    ' . $this->trans('commands.common.questions.columns.column_not_null'), 'TRUE or FALSE. Hit enter to exclude', ':'),
//                  function ($column_not_null_options) use ($column_true_false) {
//                      if (!in_array($column_not_null_options, $column_true_false)) {
//                          throw new \InvalidArgumentException(
//                            sprintf($this->trans('commands.common.questions.columns.column_not_null_invalid'), $column_not_null_options)
//                          );
//                      }
//
//                      return $column_not_null_options;
//                  },
//                  false,
//                  null,
//                  $column_true_false
//                );


//                $input->setOption('table-name', $table_name);


//                $index_options = $dialog->askAndValidate(
//                  $output,
//                  $dialog->getQuestion('  ' . $this->trans('commands.common.questions.columns.table_index'),
//                    $column_names_string, ':'),
//                  function ($primary_key_choices) use ($column_names) {
//                      $primary_key_choices = $this->getStringUtils()
//                        ->camelCaseToCommaSeparated($primary_key_choices);
//                      if (empty($primary_key_choices)) {
//                          throw new \InvalidArgumentException(
//                            sprintf($this->trans('commands.common.questions.columns.table_index_invalid'),
//                              $primary_key_choices)
//                          );
//                      }
//
//                      return $primary_key_choices;
//                  },
//                  false,
//                  null,
//                  $column_names
//                );
//
//                if (empty($index)) {
//                    break;
//                }

//            }
//
//        }
//
//        $input->setOption('index', $index_options);

//        }

//    public function getColumns()
//    {
//        return $this->columns;
//    }
//
//
//    /**
//     * @param OutputInterface $output
//     * @param HelperInterface $dialog
//     * @return mixed
//     */
//    public function installPrimaryKey(OutputInterface $output, HelperInterface $dialog)
//    {
//        if ($dialog->askConfirmation(
//          $output,
//          $dialog->getQuestion($this->trans('commands.common.questions.columns.confirm_primary_key'),
//            'yes', '?'),
//          true
//        )
//        ) {
//
////            $columns = $this->installQuestion($output, $dialog);
//
//
////            var_dump($this->columns);
//            var_dump('eric');
//        }
//    }
//    }

