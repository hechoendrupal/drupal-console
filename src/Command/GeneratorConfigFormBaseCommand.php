<?php

namespace Drupal\AppConsole\Command;

class GeneratorConfigFormBaseCommand extends GeneratorFormCommand {

  protected function configure()
  {
    parent::configure('ConfigFormBase', 'generate:form:config');
  }
}