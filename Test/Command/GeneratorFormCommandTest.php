<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorFormCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveData
     */
    public function testInteractive($options, $expected, $input)
    {
        list($module, $class_name, $form_id, $services, $inputs, $routing_update) = $expected;

        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $class_name, $form_id, $services, $inputs, $routing_update);

        $command = $this->getCommand($generator, $input);
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
            'short' => 'Twig_Environment',
          ]
        ];

        $inputs = [
          [
            'name' => 'bar',
            'type' => 'textfield',
            'label' => 'Bar',
            'options' => '',
            'description' => 'Baz',
          ]
        ];

        return [
            // case one
          [
              // Inline options
            [],
              // Expected options
            ['foo', 'DefaultForm', 'default_form', $services, $inputs, true],
              // User input options
            "foo\nDefaultForm\ndefault_form\nyes\ntwig\n\nyes\nBar\nbar\ntextfield\nBaz\n",
          ],
            // case two
          [
              // Inline options
            ['--module' => 'foo'],
              // Expected options
            ['foo', 'DefaultForm', 'default_form', null, null, true],
              // User input options
            "DefaultForm\ndefault_form\nno\nno\n",
          ],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
          ->getMockBuilder('Drupal\AppConsole\Command\GeneratorConfigFormBaseCommand')
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
          ->getMockBuilder('Drupal\AppConsole\Generator\FormGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
