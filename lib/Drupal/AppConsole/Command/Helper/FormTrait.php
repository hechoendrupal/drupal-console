<?php
/**
 * @file
 * Containt Drupa\AppConsole\Command\Helper\FormTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Drupal\AppConsole\Utils\Utils;

trait FormTrait
{
	public function formQuestion($input, $output, $dialog)
	{
    if ($dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Do you like generate a form structure?', 'yes', '?'),
      true
    )) {
      $input_types = [
        'textfield',
        'color',
        'date',
        'datetime',
        'email',
        'number',
        'range',
        'tel'
      ];

      $inputs = [];
      while (true) {
        // Label for input
        $input_label = $dialog->ask(
          $output,
          $dialog->getQuestion(' Input label','',':'),
          null
        );

        // break if is blank
        if ($input_label == null) {
          break;
        }

        // Machine name
        $input_machine_name = Utils::createMachineName($input_label);

        $input_name = $dialog->ask(
          $output,
          $dialog->getQuestion('  Input machine name', $input_machine_name, ':'),
          $input_machine_name
        );

        // Type input
        // TODO: validate
        $input_type = $dialog->askAndValidate(
          $output,
          $dialog->getQuestion('  Type', 'textfield',':'),
          function ($input) use ($input_types) {
            return $input;
          },
          false,
          'textfield',
          $input_types
        );

        array_push($inputs, array(
          'name'  => $input_name,
          'type'  => $input_type,
          'label' => $input_label
        ));
      }
      
      return $inputs;
    }
  }
}
