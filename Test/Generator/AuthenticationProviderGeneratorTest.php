<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\AuthenticationProviderGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Test\DataProvider\AuthenticationProviderDataProviderTrait;

class AuthenticationProviderGeneratorTest extends GeneratorTest
{
    use AuthenticationProviderDataProviderTrait;

    /**
     * AuthenticationProvider generator test
     *
     * @param $module
     * @param $class_name
     *
     * @dataProvider commandData
     */
    public function testGenerateAuthenticationProvider(
        $module,
        $class_name,
        $provider_id
    ) {
        $generator = new AuthenticationProviderGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $provider_id
        );

        $files = [
          $generator->getSite()->getAuthenticationPath($module, 'Provider').'/'.$class_name.'.php',
          $generator->getSite()->getModulePath($module).'/'.$module.'.services.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
