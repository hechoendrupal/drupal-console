<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\ModuleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ModuleDataProviderTrait;

class GeneratorModuleCommandTest extends GenerateCommandTest
{
    use ModuleDataProviderTrait;

    /**
     * Module generator test
     *
     * @param $module
     * @param $machine_name
     * @param $module_path
     * @param $description
     * @param $core
     * @param $package
     * @param $feature
     * @param $composer
     * @param $dependencies
     *
     * @dataProvider commandData
     */
    public function testGenerateModule(
        $module,
        $machine_name,
        $module_path,
        $description,
        $core,
        $package,
        $feature,
        $composer,
        $dependencies
    ) {
        $command = new ModuleCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--machine-name'   => $machine_name,
              '--module-path'    => $module_path,
              '--description'    => $description,
              '--core'           => $core,
              '--package'        => $package,
              '--feature'        => $feature,
              '--composer'       => $composer,
              '--dependencies'   => $dependencies
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\ModuleGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
