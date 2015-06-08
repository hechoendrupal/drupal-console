<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Test\Generator\CommandGeneratorTest.
 */
namespace Drupal\AppConsole\Test\Generator;

class CommandGeneratorTest extends GeneratorTest
{
    /**
     * @dataProvider commandData
     */
    public function testCommandGenerator($parameters)
    {
        list($module, $command, $class_name, $container) = $parameters;

        $generator = $this->getGenerator();

        $dir_module = $this->dir.'/'.$module;

        $generator->expects($this->once())
          ->method('getCommandPath')
          ->will(
            $this->returnValue(
              $dir_module.'/src/Command'
            )
          );

        // Generate command
        $generator->generate($module, $command, $class_name, $container);

        $this->assertTrue(
          is_file($dir_module.'/src/Command/'.$class_name.'.php'),
          'Command class generated'
        );
    }

    public function commandData()
    {
        return [
          [
            ['command_'.rand(), 'command:default', 'CommandDefault', false],
          ],
          [
            ['command_'.rand(), 'command:default', 'CommandDefault', true],
          ],
        ];
    }

    protected function getGenerator()
    {
        $generator = $this->getMockBuilder('\Drupal\AppConsole\Generator\CommandGenerator')
          ->setMethods(['getCommandPath'])
          ->getMock();

        $generator->setSkeletonDirs($this->getSkeletonDirs());
        $generator->setTranslator($this->getTranslationHelper());

        return $generator;
    }

    protected function getTranslationHelper()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Command\Helper\TranslatorHelper')
          ->disableOriginalConstructor()
          ->setMethods(['loadResource', 'trans', 'writeTranslationsByModule'])
          ->getMock();
    }
}
