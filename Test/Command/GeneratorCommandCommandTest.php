<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorCommandCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorCommandCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveData
     */
    public function testInteractive($options, $expected, $input)
    {
        list($module, $class_name, $command, $container) = $expected;

        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $class_name, $command, $container);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    private function getGenerator()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Generator\CommandGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
          ->getMockBuilder('Drupal\AppConsole\Command\GeneratorCommandCommand')
          ->setMethods(['getModules', 'getServices', '__construct'])
          ->setConstructorArgs([$this->getTranslationHelper()])
          ->getMock();

        $command->expects($this->any())
          ->method('getModules')
          ->will($this->returnValue(['foo']));

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    public function getInteractiveData()
    {
        return [
            // case one
          [
              // Inline options
            [],
              // Expected options
            ['foo', 'FooCommand', 'foo:command', true],
              // User input options
            "foo\nFooCommand\nfoo:command\nyes",
          ],
            // case two
          [
              // Inline options
            ['--module' => 'foo'],
              // Expected options
            ['foo', 'FooCommand', 'foo:command', true],
              // User input options
            "FooCommand\nfoo:command\nyes",
          ],
        ];
    }
}
