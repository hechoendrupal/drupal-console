<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorModuleCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\ModuleCommand;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ModuleDataProviderTrait;
use GuzzleHttp;

class ModuleCommandTest extends GenerateCommandTest
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
     * @param $featuresBundle
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
        $featuresBundle,
        $composer,
        $dependencies
    ) {
        $generator = an::moduleGenerator();
        $manager = an::extensionManager();
        $command = new ModuleCommand(
              $generator->reveal(),
              new Validator($manager),
              '/tmp',
              new StringConverter(),
              an::drupalApi()->reveal(),
              an::guzzleHttpClient()->reveal(),
              an::siteDrupal()->reveal()
          );

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--machine-name'   => $machine_name,
              '--module-path'    => $module_path,
              '--description'    => $description,
              '--core'           => $core,
              '--package'        => $package,
              '--features-bundle'=> $featuresBundle,
              '--composer'       => $composer,
              '--dependencies'   => $dependencies
            ],
            ['interactive' => false]
        );
        $generator
            ->generate($module, $machine_name, $module_path, $description, $core, $package, $featuresBundle, $composer, $dependencies);
        $this->assertEquals(0, $code);
    }
}
