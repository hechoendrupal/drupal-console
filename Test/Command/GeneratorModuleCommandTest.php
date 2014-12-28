<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorModuleCommandTest extends GenerateCommandTest
{
  /**
   * @dataProvider getInteractiveData
   */
  public function testInteractive($options, $expected, $input)
  {
    list($module, $machine_name, $dir, $description, $core, $package, $controller, $tests, $structure) = $expected;

    $generator = $this->getGenerator();

    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module, $machine_name, $dir, $description, $core, $package, $controller, $tests, $structure)
    ;

    $command = $this->getCommand($generator, $input);

    $cmd = new CommandTester($command);
    $cmd->execute($options);
  }

  public function getInteractiveData()
  {
    $dir = sys_get_temp_dir() . "/modules";

    return [
      // case one basic options
      [
        [],
        ['foo', 'foo', $dir, 'My Awesome Module', '8.x', 'Other', false, false, true],
        "foo\nfoo\n$dir\n"
      ],
    ];
  }

  /**
   * @dataProvider  getNoInteractiveData
   */
  public function testNoInteractive($options, $expected)
  {
    list($module, $machine_name, $dir, $description, $core, $package, $controller, $tests, $structure) = $expected;

    $generator = $this->getGenerator();

    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module, $machine_name, $dir, $description, $core, $package, $controller, $tests, $structure)
    ;

    $cmd = new CommandTester($this->getCommand($generator,''));
    $cmd->execute($options,['interactive' => false]);
  }

  public function getNoInteractiveData()
  {
    $dir = sys_get_temp_dir();

    return [
      [
        ['--module'=>'foo', '--machine-name'=>'foo', '--module-path'=>$dir, '--description'=>'My Awesome Module','--core'=>'8.x','--package'=>'Other', '--controller'=>true,'--tests'=>true,'--structure'=>true],
        ["foo", "foo", $dir, "My Awesome Module", '8.x', 'Other', true, true, true],
      ],
      [
        ['--module'=>'foo', '--machine-name'=>'foo', '--module-path'=>$dir,'--description'=>'My Awesome Module','--core'=>'8.x','--package'=>'Other', '--controller'=>true,'--tests'=>true,'--structure'=>true],
        ["foo", 'foo', $dir, "My Awesome Module", '8.x', 'Other', true, true, true],
      ]
    ];
  }

  protected function getCommand($generator, $input)
  {
    $command = $this
      ->getMockBuilder('Drupal\AppConsole\Command\GeneratorModuleCommand')
      ->setMethods(['validateModuleName', 'validateModule'])
      ->getMock()
    ;

    $command->expects($this->any())
      ->method('validateModule')
      ->will($this->returnValue('foo'));
    ;

    $command->setContainer($this->getContainer());
    $command->setHelperSet($this->getHelperSet($input));
    $command->setGenerator($generator);

    return $command;
  }

  private function getGenerator()
  {
    return $this
      ->getMockBuilder('Drupal\AppConsole\Generator\ModuleGenerator')
      ->disableOriginalConstructor()
      ->setMethods(['generate'])
      ->getMock()
    ;
  }

}
