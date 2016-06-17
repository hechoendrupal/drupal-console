<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\ServiceGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Test\DataProvider\ServiceDataProviderTrait;

class ServiceGeneratorTest extends GeneratorTest
{
    use ServiceDataProviderTrait;

    /**
     * Service generator test
     *
     * @param $module
     * @param $name
     * @param $class
     * @param $interface
     * @param $services
     * @param $path_service
     *
     * @dataProvider commandData
     */
    public function testGenerateService(
        $module,
        $name,
        $class,
        $interface,
        $services,
        $path_service
    ) {
        $generator = new ServiceGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $name,
            $class,
            $interface,
            $services,
            $path_service
        );

        $files = [
          $generator->getSite()->getModulePath($module).'/'.$module.'.services.yml',
          $generator->getSite()->getModulePath($module).'/'.$path_service .'/'.$class.'.php'
        ];
     
        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
