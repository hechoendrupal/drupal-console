<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginRulesActionCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginRulesActionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginRulesActionDataProviderTrait;

class GeneratorPluginRulesActionCommandTest extends GenerateCommandTest
{
    use PluginRulesActionDataProviderTrait;

    /**
     * Plugin rules action generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $category
     * @param $context
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginRulesAction(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $category,
        $context
    ) {
        $command = new GeneratorPluginRulesActionCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'        => $module,
              '--class-name'    => $class_name,
              '--label'         => $label,
              '--plugin-id'     => $plugin_id,
              '--category'      => $category,
              '--context'       => $context,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginRulesActionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}