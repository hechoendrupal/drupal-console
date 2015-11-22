<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorEntityConfigCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\EntityConfigCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\EntityConfigDataProviderTrait;

class GeneratorEntityConfigCommandTest extends GenerateCommandTest
{
    use EntityConfigDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityConfig(
        $module,
        $entity_name,
        $entity_class,
        $label
    ) {
        $command = new EntityConfigCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--entity-name'    => $entity_name,
              '--entity-class'   => $entity_class,
              '--label'          => $label
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\EntityConfigGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
