<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\FormGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\FormGenerator;
use Drupal\AppConsole\Test\DataProvider\FormDataProviderTrait;

class FormGeneratorTest extends GeneratorTest
{
    use FormDataProviderTrait;

    /**
     * Form generator test
     *
     * @param $module
     * @param $class_name
     * @param $services
     * @param $inputs
     * @param $form_id
     * @param $update_routing
     *
     * @dataProvider commandData
     */
    public function testGenerateForm(
        $module,
        $class_name,
        $services,
        $inputs,
        $form_id,
        $update_routing
    ) {
        $generator = new FormGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $services,
            $inputs,
            $form_id,
            $update_routing
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getFormPath($module).'/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );

        if ($update_routing) {
            $this->assertTrue(
                file_exists($generator->getSite()->getModulePath($module).'/'.$module.'.routing.yml'),
                sprintf('%s does not exist', $class_name.'.php')
            );
        }
    }
}
