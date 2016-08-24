<?php

namespace Drupal\Console\Extension;

use Drupal\Core\Extension\Extension as BaseExtension;

class Extension extends BaseExtension
{
    public function getControllerPath() {
        return $this->getSourcePath() . '/Controller/';
    }

    private function getSourcePath() {
        return $this->getPath() . '/src';
    }
}