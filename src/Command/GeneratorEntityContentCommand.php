<?php

namespace Drupal\AppConsole\Command;

class GeneratorEntityContentCommand extends GeneratorEntityCommand {

  protected function configure()
  {
    parent::configure('EntityContent', 'generate:entity:content');
  }
}
