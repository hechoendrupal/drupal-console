<?php

namespace Drupal\AppConsole\Command;

class GeneratorEntityConfigCommand extends GeneratorEntityCommand {

  protected function configure()
  {
    parent::configure('EntityConfig', 'generate:entity:config');
  }
}
