<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorModuleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\ModuleDataProviderTrait;

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
        $composer,
        $dependencies
    ) {
        $command = new GeneratorModuleCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
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
          ->getMockBuilder('Drupal\AppConsole\Generator\ModuleGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
