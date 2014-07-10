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
      $tests,
      $structure
    ) = $parameters;

    $this->getGenerator()->generate(
      $module,
      $machine_name,
      $dir,
      $description,
      $core,
      $package,
      $controller,
      $tests,
      $structure
    );

    $files = [
      'foo/foo.info.yml',
      'foo/foo.module',
    ];

    foreach ($files as $file) {
      $this->assertTrue(
        file_exists($this->dir . '/' . $file),
        sprintf('%s has been generated', $this->dir . '/' .$dir)
      );
    }

    if ($controller) {
      $this->assertTrue(
        file_exists($this->dir . '/foo/src/Controller/DefaultController.php'),
        sprintf('%s has been generated',
          $this->dir . '/foo/src/Controller/DefaultController.php'
        )
      );
      $this->assertTrue(
        file_exists($this->dir . '/foo/foo.routing.yml'),
        sprintf('%s has been generated',
          $this->dir . '/foo/foo.routing.yml'
        )
      );

      if ($tests) {
        $this->assertTrue(
          file_exists($this->dir . '/foo/Tests/Controller/DefaultControllerTest.php'),
          sprintf('%s has been generated',
            $this->dir . '/foo/Tests/Controller/DefaultControllerTest.php'
          )
        );
      }
    }

    $dirs = [
      'foo',
      'foo/src',
      'foo/Tests',
      'foo/templates',
      'foo/src/Controller',
      'foo/src/Form',
      'foo/src/Plugin',
    ];

    if ($structure) {
      foreach ($dirs as $dir) {
        $this->assertTrue(
          is_dir($this->dir . '/' . $dir),
          sprintf('%s has been generated', $this->dir . '/' .$dir)
        );
      }
    }
  }

  public function commandData()
  {
    $this->dir = sys_get_temp_dir() . "/module";

    return [
      [
        ['Foo', 'foo', $this->dir, 'Description', '8.x', 'Other', true, true, true],
      ],
      [
        ['Foo', 'foo', $this->dir, 'Description', '8.x', 'Other', false, true, true],
      ],
      [
        ['Foo', 'foo', $this->dir, 'Description', '8.x', 'Other', false, false, true],
      ],
      [
        ['Foo', 'foo', $this->dir, 'Description', '8.x', 'Other', false, false, false],
      ],
    ];
  }

  protected function getGenerator()
  {
    $generator = new ModuleGenerator();
    $generator->setSkeletonDirs(__DIR__.'/../../src/Resources/skeleton');
    return $generator;
  }
}