<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorInstallCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorInstallCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveData
     *
     * @param $options
     * @param $expected
     * @param $input
     */
    public function testInteractive($options, $expected, $input)
    {
        list($module, $table_name, $table_description, $columns, $primary_key, $indexes) = $expected;

        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $table_name, $table_description, $columns, $primary_key, $indexes);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    public function getInteractiveData()
    {
        $columns = [
          [
            'column_name' => 'bar',
            'column_type' => 'int',
            'column_type_options' => '',
            'column_unsigned' => TRUE,
            'column_not_null' => TRUE,
            'column_default' => 0,
            'column_size' => 'tiny',
            'column_description' => 'Baz',
//            'primary_key' => 'bar',
//            'indexes' => 'bar',
          ]
        ];
        $primary_key = [
          [
            'primary_key' => 'bar',
          ]
        ];
        $indexes = [
          [
            'index_name_key' => 'bar',
            'index_name_value' => 'bar',
          ]
        ];

        return [
            // case one
          [
              // Inline options
            [],
              // Expected options
            ['foo', 'Description', $columns, $primary_key, $indexes],
              // User input options
            "foo\nDescription\nyes\nbar\nint\nTRUE\nTRUE\n0\ntiny\nBaz\nyes\nbar\nyes\nbar\nbar\n",
          ],
//            // case two
//          [
//              // Inline options
//            ['--module' => 'foo'],
//              // Expected options
//            ['foo', $inputs, true],
//              // User input options
//            "foo\nyes\nMy Permission\n",
//          ],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
          ->getMockBuilder('Drupal\AppConsole\Command\GeneratorInstallCommand')
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
          ->getMockBuilder('Drupal\AppConsole\Generator\InstallGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
