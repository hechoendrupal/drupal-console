<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\ConfigFormBaseGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Test\DataProvider\ConfigFormBaseDataProviderTrait;

class ConfigFormBaseGeneratorTest extends GeneratorTest
{
    use ConfigFormBaseDataProviderTrait;

    /**
     * Form generator test
     *
     * @param $module
     * @param $class_name
     * @param $services
     * @param $inputs
     * @param $form_id
     * @param $form_type
     * @param $update_routing
     *
     * @dataProvider commandData
     */
    public function testGenerateConfigFormBase(
        $module,
        $class_name,
        $services,
        $inputs,
        $form_id,
        $form_type,
        $update_routing
    ) {
        $generator = new FormGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $services,
            $inputs,
            $form_id,
            $form_type,
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
