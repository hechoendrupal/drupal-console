<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorEntityCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\EntityConfigCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\EntityDataProviderTrait;

class GeneratorEntityCommandTest extends GenerateCommandTest
{
    use EntityDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_class
     * @param $entity_name
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityConfig(
        $module,
        $entity_class,
        $entity_name,
        $label
    ) {
        $command = new EntityConfigCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--entity-class'   => $entity_class,
              '--entity-name'    => $entity_name,
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
