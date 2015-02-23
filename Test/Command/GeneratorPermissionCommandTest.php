<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPermissionCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorPermissionCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveData
     */
    public function testInteractive($options, $expected, $input)
    {

        list($module, $permissions) = $expected;
        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $permissions);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    public function getInteractiveData()
    {
        $permissions = [
          [
            'permission' => 'my permission',
            'permission_title' => 'My permission',
          ]
        ];

        return [
            // case one
          [
              // Inline options
            [],
              // Expected options
              // Expected options
            ['foo', $permissions, true],
              // User input options
            "foo\nyes\nMy Permission\n",
          ],
            // case two
          [
              // Inline options
            ['--module' => 'foo'],
              // Expected options
            ['foo', $permissions, true],
              // User input options
            "foo\nyes\nMy Permission\n",
          ],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
          ->getMockBuilder('Drupal\AppConsole\Command\GeneratorPermissionCommand')
          ->setMethods(['getModules', '__construct'])
          ->setConstructorArgs([$this->getTranslationHelper()])
          ->getMock();

        $command->expects($this->any())
          ->method('getModules')
          ->will($this->returnValue(['foo']));;

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    private function getGenerator()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Generator\PermissionGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
