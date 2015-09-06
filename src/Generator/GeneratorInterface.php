<?php

namespace Drupal\AppConsole\Generator;

interface GeneratorInterface
{
    public function generate($module, $class_name, $routes, $test, $services, $class_machine_name);
}
