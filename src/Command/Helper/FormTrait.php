<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\FormTrait.
 */
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait FormTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function formQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        if ($dialog->askConfirmation(
          $output,
          $dialog->getQuestion($this->trans('commands.common.questions.inputs.confirm'), 'yes', '?'),
          true
        )
        ) {
            $input_types = [
              'color',
              'checkbox',
              'checkboxes',
              'date',
              'datetime',
              'email',
              'number',
              'range',
              'radios',
              'select',
              'tel',
              'textarea',
              'textfield',
            ];

            $inputs = [];
            while (true) {
                // Label for input
                $input_label = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.label'), '', ':'),
                  null
                );

                if (empty($input_label)) {
                    break;
                }

                // Machine name
                $input_machine_name = $this->getStringUtils()->createMachineName($input_label);

                $input_name = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.machine_name'),
                    $input_machine_name, ':'),
                  $input_machine_name
                );

                // Type input
                $input_type = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.type'), 'textfield', ':'),
                  function ($input) use ($input_types) {
                      if (!in_array($input, $input_types)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.common.questions.inputs.invalid'), $input)
                          );
                      }

                      return $input;
                  },
                  false,
                  'textfield',
                  $input_types
                );

                $input_options = '';
                if (in_array($input_type, array('checkboxes', 'radios', 'select'))) {
                    $input_options = $dialog->ask(
                      $output,
                      $dialog->getQuestion(' Input options separated by comma', '', ':'),
                      null
                    );
                }

                // Prepare options as an array
                if (strlen(trim($input_options))) {
                    // remove spaces in options and empty options
                    $input_options = array_filter(array_map('trim', explode(',', $input_options)));
                    // Create array format for options
                    foreach ($input_options as $key => $value) {
                        $input_options_output[$key] = "\$this->t('".$value."') => \$this->t('".$value."')";
                    }

                    $input_options = 'array('.implode(', ', $input_options_output).')';
                }

                // Description for input
                $input_description = $dialog->ask(
                  $output,
                  $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.description'), '', ':'),
                  null
                );

                array_push($inputs, array(
                  'name' => $input_name,
                  'type' => $input_type,
                  'label' => $input_label,
                  'options' => $input_options,
                  'description' => $input_description,
                ));
            }

            return $inputs;
        }

        return;
    }
}
