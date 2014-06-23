<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\Helper\ModuleTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

trait ModuleTrait
{
  /**
   * @return string
   */
  public function moduleQuestion($input, $output, $dialog)
  {
    $modules = $this->getModules();

    return $dialog->askAndValidate(
      $output,
      $dialog->getQuestion('Enter your module'),
      function ($module) {
        return $this->validateModuleExist($module);
      },
      false,
      '',
      $modules
    );
  }
}
