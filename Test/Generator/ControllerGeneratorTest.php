<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ControllerGeneratorTest.
 *
 */

namespace Drupal\AppConsole\Test\Generator;

class ControllerGeneratorTest extends GeneratorTest
{

    /**
     * @dataProvider commandData
     */
    public function testGenerateController($parameters)
    {
        list($module, $class_name, $method_name, $route, $test, $services, $class_machine_name) = $parameters;

        $generator = $this->getGenerator();

        $generator->expects($this->once())
          ->method('getControllerPath')
          ->will(
            $this->returnValue(
              $this->getModulePath($module) . '/src/Controller'
            )
          );

        $generator->expects($this->once())
          ->method('getModulePath')
          ->will(
            $this->returnValue(
              $this->getModulePath($module)
            )
          );

        $generator->expects($this->any())
          ->method('getTestPath')
          ->will(
            $this->returnValue(
              $this->getModulePath($module) . '/Tests/Controller'
            )
          );

        $generator->generate($module, $class_name, $method_name, $route, $test, $services, $class_machine_name);

        $this->assertTrue(
          is_file($this->getModulePath($module) . '/src/Controller/' . $class_name . '.php'),
          'Generate controller class'
        );

        $this->assertTrue(
          is_file($this->getModulePath($module) . '/' . $module . '.routing.yml'),
          'Generate routing file'
        );

        if ($test) {
            $this->assertTrue(
              is_file($this->getModulePath($module) . '/Tests/Controller/' . $class_name . 'Test.php'),
              'Generate test class'
            );
        }
    }

    public function commandData()
    {
        $services = [
          'twig' => [
            'name' => 'twig',
            'machine_name' => 'twig',
            'class' => 'Twig_Environment',
            'short' => 'Twig_Environment',
          ]
        ];

        return [
          [
            ['controller_' . rand(), 'DefaultController', 'index', '/index', false, null, 'default_controller']
          ],
          [
            ['controller_' . rand(), 'DefaultController', 'index', '/index', true, $services, 'default_controller']
          ],
        ];
    }

    protected function getGenerator()
    {
        $generator = $this->getMockBuilder('\Drupal\AppConsole\Generator\ControllerGenerator')
          ->setMethods(['getControllerPath', 'getModulePath', 'getTestPath'])
          ->getMock();

        $generator->setSkeletonDirs($this->getSkeletonDirs());

        return $generator;
    }
}
