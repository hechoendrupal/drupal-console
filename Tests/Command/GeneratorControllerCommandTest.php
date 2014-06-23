<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorControllerCommandTest extends GenerateCommandTest
{
  /**
   * @dataProvider getInteractiveData
   */
  public function testInteractive($options, $expected, $input)
  {
    list($module, $class_name, $test, $services, $routing_update) = $expected;

    $generator = $this->getGenerator();
    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module, $class_name, $test, $services, $routing_update)
    ;

    $command = $this->getCommand($generator,$input);
    $cmd = new CommandTester($command);
    $cmd->execute($options);
  }

  public function getInteractiveData()
  {
    $services = [
      'twig' => [
        'name' => 'twig',
        'machine_name' => 'twig',
        'class' => 'Twig_Environment',
        'short'=>'Twig_Environment',
      ]
    ];

    return [
      // case one
      [
        // Inline options
        [],
        // Expected options
        ['foo', 'FooController', true, $services, true],
        // User input options
        "foo\nFooController\nyes\nyes\ntwig\n\nyes\n",
      ],
      // case two
      [
        // Inline options
        ['--module'=>'foo'],
        // Expected options
        ['foo', 'FooController', true, null, true],
        // User input options
        "FooController\nyes\nno\nyes\n",
      ],
      // case three
      [
        // Inline options
        ['--module'=>'foo'],
        // Expected options
        ['foo', 'FooController', false, null, false],
        // User input options
        "FooController\nno\nno\nno\n",
      ],
    ];
  }

  protected function getCommand($generator, $input)
  {
    $command = $this
      ->getMockBuilder('Drupal\AppConsole\Command\GeneratorControllerCommand')
      ->setMethods(['getModules','getServices'])
      ->getMock()
    ;

    $command->expects($this->any())
      ->method('getModules')
      ->will($this->returnValue(['foo']));
    ;

    $command->expects($this->any())
      ->method('getServices')
      ->will($this->returnValue(['twig','database']));
    ;

    $command->setContainer($this->getContainer());
    $command->setHelperSet($this->getHelperSet($input));
    $command->setGenerator($generator);

    return $command;
  }

  private function getGenerator()
  {
    return $this
      ->getMockBuilder('Drupal\AppConsole\Generator\ControllerGenerator')
      ->disableOriginalConstructor()
      ->setMethods(['generate'])
      ->getMock()
    ;
  }
}
