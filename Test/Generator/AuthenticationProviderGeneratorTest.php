<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\AuthenticationProviderGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\AuthenticationProviderGenerator;
use Drupal\AppConsole\Test\DataProvider\AuthenticationProviderDataProviderTrait;

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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

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
