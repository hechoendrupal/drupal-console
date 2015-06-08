<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */
namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorPluginBlockCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getDataInteractive
     */
    public function testInteractiveCommand($options, $expected, $input)
    {
        list($module, $class_name, $plugin_label, $plugin_id, $services, $inputs) = $expected;

        $generator = $this->getGenerator();

        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $class_name, $plugin_label, $plugin_id, $services, $inputs);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    public function getDataInteractive()
    {
        $service ['twig'] = [
          'name' => 'twig',
          'machine_name' => 'twig',
          'class' => 'Twig_Environment',
          'short' => 'Twig_Environment',
        ];

        $inputs = [
          [
            'name' => 'text_field',
            'type' => 'textfield',
            'label' => 'Text Field',
            'options' => '',
            'description' => 'Description Field',
          ],
        ];

        return [
            // case base
          [
            [],
            ['Foo', 'FooBlock', 'Foo label', 'foo_id', null, []],
            "Foo\nFooBlock\nFoo label\nfoo_id\nno\nno",
          ],
            //case two services
          [
            [],
            ['Foo', 'FooBlock', 'Foo label', 'foo_id', $service, []],
            "Foo\nFooBlock\nFoo label\nfoo_id\nyes\nyes\ntwig\n\nno\n",
          ],
            // case three inputs
          [
            ['--module' => 'Foo'],
            ['Foo', 'FooBlock', 'Foo label', 'foo_id', null, $inputs],
            "FooBlock\nFoo label\nfoo_id\nno\nyes\nText Field\ntext_field\n\nDescription Field\n",
          ],
            //case four services and inputs
          [
            ['--module' => 'Foo'],
            ['Foo', 'FooBlock', 'Foo label', 'foo_id', $service, $inputs],
            "FooBlock\nFoo label\nfoo_id\nyes\ntwig\n\nyes\nText Field\ntext_field\n\nDescription Field\n",
          ],
        ];
    }

    public function getCommand($generator, $input)
    {
        $command = $this->getMockBuilder('Drupal\AppConsole\Command\GeneratorPluginBlockCommand')
          ->setMethods(['getModules', 'getServices', '__construct'])
          ->setConstructorArgs([$this->getTranslationHelper()])
          ->getMock();

        $command->expects($this->any())
          ->method('getModules')
          ->will($this->returnValue(['Foo']));

        $command->expects($this->any())
          ->method('getServices')
          ->will($this->returnValue(['twig', 'database']));

        $command->setGenerator($generator);
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));

        return $command;
    }

    private function getGenerator()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Generator\PluginBlockGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
