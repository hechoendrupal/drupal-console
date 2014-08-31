<?php

namespace Drupal\AppConsole\Command;

class GeneratorEntityContentCommand extends GeneratorEntityCommand {

  protected function getEntityType()
  {
    return 'EntityContent';
  }

  protected function getCommandName()
  {
    return 'generate:entity:content';
  }

  protected function configure()
  {
    parent::configure();
  }
}
