<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ServiceGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\ServiceGenerator;
use Drupal\AppConsole\Test\DataProvider\ServiceDataProviderTrait;

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
     *
     * @dataProvider commandData
     */
    public function testGenerateService(
        $module,
        $name,
        $class,
        $interface,
        $services
    ) {
        $generator = new ServiceGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->translator);
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $name,
            $class,
            $interface,
            $services
        );

        $files = [
          $generator->getSite()->getModulePath($module).'/'.$module.'.services.yml',
          $generator->getSite()->getModulePath($module).'/src/'.$class.'.php'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
