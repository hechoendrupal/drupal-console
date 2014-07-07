<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\ModuleGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ModuleGenerator extends Generator
{

  private $defaultDirectoryStructure = ['Tests', 'src', 'src/Controller', 'src/Form', 'src/Plugin', 'templates'];

  public function generate($module, $dir, $description, $core, $package, $controller, $tests, $structure )
  {
    $dir .= '/' . $module;
    if (file_exists($dir)) {
      if (!is_dir($dir)) {
        throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
      }
      $files = scandir($dir);
      if ($files != array('.', '..')) {
        throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
      }
      if (!is_writable($dir)) {
        throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
      }
    }

    $parameters = array(
      'module' => $module,
      'type'    => 'module',
      'core'    => $core,
      'description'    => $description,
      'package' => $package,
    );

    $this->renderFile(
      'module/info.yml.twig',
      $dir.'/'.$module.'.info.yml',
      $parameters
    );

    $this->renderFile(
      'module/module.twig',
      $dir.'/'.$module.'.module',
      $parameters
    );

    if ($controller) {
      $class_name = 'DefaultController';
      $parameters = array(
        'class_name' => $class_name,
        'module' => $module,
        'method_name' => 'hello',
        'class_machine_name' => 'default_controller',
        'route' => $module . '/hello/{name}',
      );

      $this->renderFile(
          'module/Controller/controller.php.twig',
          $dir.'/src/Controller/'.$class_name.'.php',
          $parameters
      );

      $this->renderFile(
        'module/routing-controller.yml.twig',
        $dir.'/'.$module.'.routing.yml',
        $parameters
      );
    }

    if ($tests) {
      $this->renderFile(
        'module/Tests/Controller/controller.php.twig',
        $dir.'/Tests/Controller/'. $class_name .'Test.php',
        $parameters
      );
    }

    if ($structure) {
      foreach ($this->defaultDirectoryStructure as $directory) {
        if (!file_exists($dir.'/'.$directory)) {
          drupal_mkdir($dir.'/'.$directory);
        }
      }
    }
  }
}
