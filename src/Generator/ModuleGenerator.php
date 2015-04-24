<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Generator\ModuleGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ModuleGenerator extends Generator
{

    public function generate(
      $module,
      $machine_name,
      $dir,
      $description,
      $core,
      $package,
      $controller,
      $dependencies,
      $tests
    ) {
        $dir .= '/' . $machine_name;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.',
                  realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.',
                  realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.',
                  realpath($dir)));
            }
        }

        $parameters = array(
          'module' => $module,
          'machine_name' => $machine_name,
          'type' => 'module',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'dependencies' => $dependencies
        );

        $this->renderFile(
          'module/info.yml.twig',
          $dir . '/' . $machine_name . '.info.yml',
          $parameters
        );

        $this->renderFile(
          'module/module.twig',
          $dir . '/' . $machine_name . '.module',
          $parameters
        );

        $this->renderFile(
          'module/composer.json.twig',
          $dir . '/' . 'composer.json',
          $parameters
        );

        if ($controller) {
            $class_name = 'DefaultController';
            $parameters = array(
              'class_name' => $class_name,
              'module' => $machine_name,
              'method_name' => 'hello',
              'class_machine_name' => 'default_controller',
              'route' => '/' . $machine_name . '/hello/{name}',
              'services' => []
            );

            $this->renderFile(
              'module/src/Controller/controller.php.twig',
              $dir . '/src/Controller/' . $class_name . '.php',
              $parameters
            );

            $this->renderFile(
              'module/routing-controller.yml.twig',
              $dir . '/' . $machine_name . '.routing.yml',
              $parameters
            );

            if ($tests) {
                $this->renderFile(
                  'module/Tests/Controller/controller.php.twig',
                  $dir . '/Tests/Controller/' . $class_name . 'Test.php',
                  $parameters
                );
            }
        }
    }
}
