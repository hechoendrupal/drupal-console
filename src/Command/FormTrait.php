<?php

/**
 * @file
 * Contains Drupal\Console\Command\FormTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait FormTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function formQuestion(DrupalStyle $output)
    {
        if ($output->confirm(
            $this->trans('commands.common.questions.inputs.confirm'),
            true
        )) {
            $input_types = [
              'color',
              'checkbox',
              'checkboxes',
              'date',
              'datetime',
              'fieldset',
              'email',
              'number',
              'password',
              'password_confirm',
              'range',
              'radios',
              'select',
              'tel',
              'textarea',
              'textfield',
            ];

            $inputs = [];
            $fieldSets = [];
            while (true) {
                $input_type = $output->choiceNoList(
                    $this->trans('commands.common.questions.inputs.type'),
                    $input_types,
                    'textfield'
                );

                // Label for input
                $inputLabelMessage = $input_type == 'fieldset'?$this->trans('commands.common.questions.inputs.title'):$this->trans('commands.common.questions.inputs.label');
                $input_label = $output->ask(
                    $inputLabelMessage,
                    null
                );

                if (empty($input_label)) {
                    break;
                }

                // Machine name
                $input_machine_name = $this->getStringHelper()->createMachineName($input_label);

                $input_name = $output->ask(
                    $this->trans('commands.common.questions.inputs.machine_name'),
                    $input_machine_name
                );

                if ($input_type == 'fieldset') {
                    $fieldSets[$input_machine_name] = $input_label;
                }

                $inputFieldSet = '';
                if ($input_type != 'fieldset' && !empty($fieldSets)) {
                    $inputFieldSet = $output->choiceNoList(
                        $this->trans('commands.common.questions.inputs.fieldset'),
                        $fieldSets
                    );

                    $inputFieldSet = array_search($inputFieldSet, $fieldSets);
                }

                $maxlength = null;
                $size = null;
                if (in_array($input_type, array('textfield', 'password', 'password_confirm'))) {
                    $maxlength = $output->ask(
                        'Maximum amount of characters'
                    );

                    $size = $output->ask(
                        'Width of the textfield (in characters)',
                        null
                    );
                }

                if ($input_type == 'select') {
                    $size = $output->ask(
                        'Size of multiselect box (in lines)'
                    );
                }

                $input_options = '';
                if (in_array($input_type, array('checkboxes', 'radios', 'select'))) {
                    $input_options = $output->ask(
                        'Input options separated by comma'
                    );
                }

                // Prepare options as an array
                if (strlen(trim($input_options))) {
                    // remove spaces in options and empty options
                    $input_options = array_filter(array_map('trim', explode(',', $input_options)));
                    // Create array format for options
                    foreach ($input_options as $key => $value) {
                        $input_options_output[$key] = "'$value' => \$this->t('".$value."')";
                    }

                    $input_options = 'array('.implode(', ', $input_options_output).')';
                }

                // Description for input
                $input_description = $output->ask(
                    $this->trans('commands.common.questions.inputs.description')
                );

                if ($input_type != 'fieldset') {
                    // Default value for input
                    $default_value = $output->ask(
                        $this->trans('commands.common.questions.inputs.default-value')
                    );
                }

                // Weight for input
                $weight = $output->ask(
                    $this->trans('commands.common.questions.inputs.weight')
                );

                array_push(
                    $inputs,
                    [
                        'name' => $input_name,
                        'type' => $input_type,
                        'label' => $input_label,
                        'options' => $input_options,
                        'description' => $input_description,
                        'maxlength' => $maxlength,
                        'size' => $size,
                        'default_value' => $default_value,
                        'weight' => $weight,
                        'fieldset' => $inputFieldSet,
                    ]
                );
            }

            return $inputs;
        }

        return;
    }
}
