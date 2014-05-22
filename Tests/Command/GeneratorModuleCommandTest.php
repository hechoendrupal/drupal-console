<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorModuleCommandTest extends GenerateCommandTest {

  /**
   * @dataProvider getInteractiveData
   */
  public function testInteractive($options, $expected, $input){

    list($module, $dir, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root) = $expected;

    $generator = $this->getGenerator();

    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module, $dir, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root)
    ;

    $command = $this->getCommand($generator, $input);

    $cmd = new CommandTester($command);
    $cmd->execute($options);
  }

  public function getInteractiveData(){
    $dir = sys_get_temp_dir();

    return [
      // case one basic options
      [
        [],
        ['foo', $dir, 'My Awesome Module', '8.x', 'Other', false, true, false, true, false],
        "foo\n$dir\n"
      ],
      // case two skip-root
      [
        ['--skip-root'=> true,'--module-path'=> $dir,'--description'=>'My old module','--package'=>'Other'],
        ['foo', $dir, "My old module", '8.x', 'Other', false, true, false, true, true],
        "foo"
      ],
    ];
  }

  /**
   * @dataProvider  getNoInteractiveData
   */
  public function testNoInteractive($options, $expected){

    list($module, $dir, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root) = $expected;

    $generator = $this->getGenerator();

    $generator
      ->expects($this->once())
      ->method('generate')
      ->with($module, $dir, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root)
    ;

    $cmd = new CommandTester($this->getCommand($generator,''));
    $cmd->execute($options,['interactive' => false]);
  }

  public function getNoInteractiveData(){

    $dir = sys_get_temp_dir();
    return [
      // case one
      [
        ['--module'=>'bar','--module-path'=>$dir, '--description'=>'My Awesome Module','--core'=>'8.x','--package'=>'Other', '--controller'=>true,'--tests'=>true,'--setting'=>true,'--structure'=>true],
        ['bar', $dir, "My Awesome Module", '8.x', 'Other', true, true, true, true, false],
      ],
      [
        ['--module'=>'bar','--module-path'=>$dir,'--description'=>'My Awesome Module','--core'=>'8.x','--package'=>'Other', '--controller'=>true,'--tests'=>true,'--setting'=>true,'--structure'=>true,'--skip-root'=>true],
        ['bar', $dir, "My Awesome Module", '8.x', 'Other', true, true, true, true, true],
      ]
    ];
  }

  protected function getCommand($generator, $input){

    /** @var \Drupal\AppConsole\Command\GeneratorModuleCommand $command */
    $command = $this
      ->getMockBuilder('Drupal\AppConsole\Command\GeneratorModuleCommand')
      ->setMethods(null)
      ->getMock()
    ;

    $command->setContainer($this->getContainer());
    $command->setHelperSet($this->getHelperSet($input));
    $command->setGenerator($generator);

    return $command;
  }

  private function getGenerator(){

    return $this
      ->getMockBuilder('Drupal\AppConsole\Generator\ModuleGenerator')
      ->disableOriginalConstructor()
      ->setMethods(['generate'])
      ->getMock()
    ;
  }

}
