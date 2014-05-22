<?php
function autoload($className) {
  $className = ltrim($className, '\\');
  $fileName  = '';
  if ($lastNsPos = strrpos($className, '\\')) {
    $namespace = substr($className, 0, $lastNsPos);
    $className = substr($className, $lastNsPos + 1);
    $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
  }
  $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

  require 'phar://' . __FILE__ . DIRECTORY_SEPARATOR .$fileName;
}

spl_autoload_register('autoload');

require __DIR__ . '/core/vendor/autoload.php';

require 'phar://' . __FILE__ . '/console.php';

__HALT_COMPILER();
