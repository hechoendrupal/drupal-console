<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ModuleGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\ModuleGenerator;

class ModuleGeneratorTest extends GeneratorTest
{
    /**
     * Module generator test
     *
     * @dataProvider commandData
     */
    public function testGenerateModule($parameters)
    {
        list(
          $module,
          $machine_name,
          $dir,
          $description,
          $core,
          $package,
          $controller,
          $composer,
          $dependencies,
          $tests
          ) = $parameters;

        $this->getGenerator()->generate(
          $module,
          $machine_name,
          $dir,
          $description,
          $core,
          $package,
          $controller,
          $composer,
          $dependencies,
          $tests
        );

        $files = [
          $machine_name . '.info.yml',
          $machine_name . '.module',
        ];

        foreach ($files as $file) {
            $this->assertTrue(
              file_exists($dir . '/' . $machine_name . '/' . $file),
              sprintf('%s has been generated', $dir . '/' . $machine_name . '/' . $file)
            );
        }

        if ($controller) {
            $this->assertTrue(
              file_exists($dir . '/' . $machine_name . '/src/Controller/DefaultController.php'),
              sprintf('%s has been generated',
                $dir . $machine_name . '/src/Controller/DefaultController.php'
              )
            );
            $this->assertTrue(
              file_exists($dir . '/' . $machine_name . "/$machine_name.routing.yml"),
              sprintf('%s has been generated',
                $dir . '/' . $machine_name . "/$machine_name.routing.yml"
              )
            );

            if ($tests) {
                $this->assertTrue(
                  file_exists($dir . '/' . $machine_name . '/Tests/Controller/DefaultControllerTest.php'),
                  sprintf('%s has been generated',
                    $dir . '/' . $machine_name . '/Tests/Controller/DefaultControllerTest.php'
                  )
                );
            }
        }
    }

    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          [
            ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, false, null, false],
          ],
          [
            ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, false, null, true],
          ],
          [
            ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, false, null, false],
          ],
          [
            ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', true, true, null, true],
          ],
        ];
    }

    protected function getGenerator()
    {
        $generator = new ModuleGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');
        return $generator;
    }
}
