<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorPluginBlockCommandTest extends GenerateCommandTest {

  /**
   * @dataProvider getDataInteractive
   */
  public function testInteractiveCommand($options, $expected, $input){

    list($module, $name, $services) = $expected;

    $generator = $this->getGenerator();

    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module,$name,$services)
    ;

    $command = $this->getCommand($generator,$input);
    $command->expects($this->any())
      ->method('getModules')
      ->will($this->returnValue(['Foo']));
    ;

    $command->expects($this->any())
      ->method('getServices')
      ->will($this->returnValue(['twig','database']));
    ;

    $cmd = new CommandTester($command);
    $cmd->execute($options);
  }

  public function getDataInteractive(){

    return[
      // case base
      [
        [],
        ['Foo','FooBlock',[]],
        "Foo\nFooBlock\nno"
      ],
      //case two services
      [
        [],
        ['Foo','FooBlock',['twig'=>['name'=>'twig','machine_name'=>'twig','class'=>'Twig_Environment','short'=>'Twig_Environment']]],
        "Foo\nFooBlock\nyes\ntwig\n"
      ],
      // case three module name in arguments
      [
        ['--module'=>'Foo'],
        ['Foo','FooBlock',['twig'=>['name'=>'twig','machine_name'=>'twig','class'=>'Twig_Environment','short'=>'Twig_Environment']]],
        "FooBlock\nyes\ntwig\n"
      ],
      //case four default values and not services
      [
        ['--module'=>'Foo'],
        ['Foo','DefaultBlock',[]],
        "\nno\n"
      ],
      // case five default values and clean services
      [
        ['--module'=>'Foo'],
        ['Foo','DefaultBlock',[]],
        "\nyes\n\n"
      ]

    ];
  }

  public function getCommand($generator, $input){
    $command = $this->getMockBuilder('Drupal\AppConsole\Command\GeneratorPluginBlockCommand')
      ->setMethods(['getModules','getServices'])
      ->getMock()
    ;

    $command->setGenerator($generator);
    $command->setContainer($this->getContainer());
    $command->setHelperSet($this->getHelperSet($input));

    return $command;
  }

  private function getGenerator(){

    return $this
      ->getMockBuilder('Drupal\AppConsole\Generator\PluginBlockGenerator')
      ->disableOriginalConstructor()
      ->setMethods(['generate'])
      ->getMock()
    ;
  }

}
