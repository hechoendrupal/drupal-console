<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorConfigFormBaseCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Utils\StringConverter;
use Symfony\Component\Console\Application;
use Drupal\Console\Command\Generate\ConfigFormBaseCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ConfigFormBaseDataProviderTrait;

class ConfigFormBaseCommandTest extends GenerateCommandTest
{
    use ConfigFormBaseDataProviderTrait;

    /**
     * ConfigFormBase generator test
     *
     * @param $module
     * @param $class_name
     * @param $form_id
     * @param $services
     * @param $inputs
     * @param $routing
     *
     * @dataProvider commandData
     */
    public function testGenerateConfigFormBase(
        $module,
        $class_name,
        $form_id,
        $services,
        $inputs,
        $routing
    ) {
        $manager = an::extensionManager();
        $stringConverter = an::stringConverter()->reveal();
        $generator = an::formGenerator();
        $elementInfoManager = an::elementInfoManager();
        $app = new Application();
        $chainQueue = an::chainQueue();
        $router = an::routeProvider();
        $command = new ConfigFormBaseCommand(
            $manager, 
            $generator->reveal(),
            $chainQueue->reveal(),
            $stringConverter,
            $elementInfoManager->reveal(),
            $router->reveal());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         =>   $module,
              '--class'          =>   $class_name,
              '--form-id'        =>   $form_id,
              '--services'       =>   $services,
              '--inputs'         =>   $inputs
            ],
            ['interactive' => false]
        );
        $generator->generate($module, $class_name, $form_id, 'ConfigFormBase', $services, 
            $inputs, '/', false, '', null, '');
        $this->assertEquals(0, $code);
    }
}
