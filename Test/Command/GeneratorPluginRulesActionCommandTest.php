<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginRulesActionCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginRulesActionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginRulesActionDataProviderTrait;

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
     * @param $type
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginRulesAction(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $category,
        $context,
        $type
    ) {
        $command = new PluginRulesActionCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'        => $module,
              '--class'    => $class_name,
              '--label'         => $label,
              '--plugin-id'     => $plugin_id,
              '--category'      => $category,
              '--context'       => $context,
              '--type'          => $type
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginRulesActionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
