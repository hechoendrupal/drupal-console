<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\Helper\ModuleTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ModuleTrait
{
  /**
   * @param OutputInterface $output
   * @param HelperInterface $dialog
   * @return mixed
   */
  public function moduleQuestion(OutputInterface $output, HelperInterface $dialog)
  {
    $modules = $this->getModules();

    return $dialog->askAndValidate(
      $output,
      $dialog->getQuestion($this->trans('common.questions.module'),''),
      function ($module) {
        return $this->validateModuleExist($module);
      },
      false,
      '',
      $modules
    );
  }
}
