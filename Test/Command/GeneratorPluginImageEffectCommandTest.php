<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginImageEffectTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorPluginImageEffectCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveData
     */
    public function testInteractive($options, $expected, $input)
    {
        list($module, $class_name, $plugin_label, $plugin_id, $description) = $expected;

        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $class_name, $plugin_label, $plugin_id, $description);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    public function getInteractiveData()
    {
        return [
            // case one
          [
              // Inline options
            [],
              // Expected options
            ['foo', 'FooImagePlugin', 'Foo label', 'foo_id', 'Foo Description'],
              // User input options
            "foo\nFooImagePlugin\nFoo label\nfoo_id\nFoo Description\n",
          ],
            // case two
          [
              // Inline options
            [
              '--module' => 'foo',
              '--class-name' => 'FooImagePlugin',
              '--label' => 'Foo label',
              '--plugin-id' => 'foo_id',
              '--description' => 'Foo Description'
            ],
              // Expected options
            ['foo', 'FooImagePlugin', 'Foo label', 'foo_id', 'Foo Description'],
              // User input options
            "",
          ],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
          ->getMockBuilder('Drupal\AppConsole\Command\GeneratorPluginImageEffectCommand')
          ->setMethods(['getModules', 'getServices', '__construct'])
          ->setConstructorArgs([$this->getTranslationHelper()])
          ->getMock();

        $command->expects($this->any())
          ->method('getModules')
          ->will($this->returnValue(['foo']));;

        $command->expects($this->any())
          ->method('getServices')
          ->will($this->returnValue(['twig', 'database']));;

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    private function getGenerator()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Generator\PluginImageEffectGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
