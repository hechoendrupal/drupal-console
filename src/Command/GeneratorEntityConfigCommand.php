<?php

namespace Drupal\AppConsole\Command;

class GeneratorEntityConfigCommand extends GeneratorEntityCommand {

  protected function getEntityType()
  {
    return 'EntityConfig';
  }

  protected function getCommandName()
  {
    return 'generate:entity:config';
  }

  protected function configure()
  {
    parent::configure();
  }
}
