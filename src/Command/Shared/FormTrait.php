<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\FormTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class FormTrait
 *
 * @package Drupal\Console\Command
 */
trait FormTrait
{
    /**
     * @return mixed
     */
    public function formQuestion()
    {
        if ($this->getIo()->confirm(
            $this->trans('commands.common.questions.inputs.confirm'),
            true
        )
        ) {
            $input_types = [
                'fieldset',
                'text_format'
            ];

            foreach ($this->elementInfoManager->getDefinitions() as $definition) {
                $type = $definition['id'];
                $elementInfo = $this->elementInfoManager->getInfo($type);
                if (isset($elementInfo['#input']) && $elementInfo['#input']) {
                    if (!in_array($type, $input_types)) {
                        $input_types[] = $type;
                    }
                }
            }
            sort($input_types);

            $this->getIo()->writeln(sprintf(
              $this->trans('commands.common.messages.available-field-types'), implode(', ', $input_types)
            ));
            $this->getIo()->newLine();

            $inputs = [];
            $fieldSets = [];
            while (true) {
                $this->getIo()->comment($this->trans('commands.common.questions.inputs.new-field'));
                $this->getIo()->newLine();
                $input_type = $this->getIo()->choiceNoList(
                    $this->trans('commands.common.questions.inputs.type'),
                    $input_types,
                    '',
                    true
                );

                if (empty($input_type) || is_numeric($input_type)) {
                    break;
                }

                // Label for input
                $inputLabelMessage = $input_type == 'fieldset'?$this->trans('commands.common.questions.inputs.title'):$this->trans('commands.common.questions.inputs.label');
                $input_label = $this->getIo()->ask(
                    $inputLabelMessage,
                    null
                );

                // Machine name
                $input_machine_name = $this->stringConverter->createMachineName($input_label);

                $input_name = $this->getIo()->ask(
                    $this->trans('commands.common.questions.inputs.machine-name'),
                    $input_machine_name
                );

                if ($input_type == 'fieldset') {
                    $fieldSets[$input_machine_name] = $input_label;
                }

                $inputFieldSet = '';
                if ($input_type != 'fieldset' && !empty($fieldSets)) {
                    $inputFieldSet = $this->getIo()->choiceNoList(
                        $this->trans('commands.common.questions.inputs.fieldset'),
                        $fieldSets,
                        '',
                        true
                    );

                    $inputFieldSet = array_search($inputFieldSet, $fieldSets);
                }

                $maxlength = null;
                $size = null;
                if (in_array($input_type, ['textfield', 'password', 'password_confirm'])) {
                    $maxlength = $this->getIo()->ask(
                        $this->trans('commands.generate.form.questions.max-amount-characters'),
                        '64'
                    );

                    $size = $this->getIo()->ask(
                        $this->trans('commands.generate.form.questions.textfield-width-in-chars'),
                        '64'
                    );
                }

                if ($input_type == 'select') {
                    $size = $this->getIo()->ask(
                        $this->trans('commands.generate.form.questions.multiselect-size-in-lines'),
                        '5'
                    );
                }

                $input_options = '';
                if (in_array($input_type, ['checkboxes', 'radios', 'select'])) {
                    $input_options = $this->getIo()->ask(
                        $this->trans('commands.generate.form.questions.input-options')
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

                    $input_options = '['.implode(', ', $input_options_output).']';
                }

                // Description for input
                $input_description = $this->getIo()->askEmpty(
                    $this->trans('commands.common.questions.inputs.description')
                );

                // Default value for input
                switch ($input_type) {
                case 'checkboxes':
                    $question = 'commands.common.questions.inputs.default-value.checkboxes';
                    break;
                default:
                    $question = 'commands.common.questions.inputs.default-value.default-value';
                    break;
                }
                if ($input_type != 'fieldset') {
                    $default_value = $this->getIo()->askEmpty(
                        $this->trans($question)
                    );
                }
                if ($input_type == 'checkboxes') {
                    // Prepare options as an array
                    if (strlen(trim($default_value))) {
                        // remove spaces in options and empty options
                        $default_options = array_filter(array_map('trim', explode(',', $default_value)));
                        $default_value = $default_options;
                    }
                }

                // Weight for input
                $weight = $this->getIo()->ask(
                    $this->trans('commands.common.questions.inputs.weight'),
                    '0'
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

                $this->getIo()->newLine();
            }

            return $inputs;
        }

        return null;
    }
}
