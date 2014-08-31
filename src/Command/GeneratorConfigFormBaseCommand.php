<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\GeneratorFormBaseCommand.
 */

namespace Drupal\AppConsole\Command;

class GeneratorConfigFormBaseCommand extends GeneratorFormCommand {

  protected function getFormType ()
  {
   return 'ConfigFormBase';
  }

  protected function getCommandName ()
  {
    return 'generate:form:config';
  }

  protected function configure()
  {
    parent::configure();
  }

}
