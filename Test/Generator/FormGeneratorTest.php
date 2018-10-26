<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\FormGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Test\DataProvider\FormDataProviderTrait;

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
     * @param $form_type
     * @param $path
     * @param $menu_link_gen
     * @param $menu_link_title
     * @param $menu_parent
     * @param $menu_link_desc
     *
     * @dataProvider commandData
     */
    public function testGenerateForm(
        $module,
        $class_name,
        $services,
        $inputs,
        $form_id,
        $form_type,
        $path,
        $menu_link_gen,
        $menu_link_title,
        $menu_parent,
        $menu_link_desc
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
            $path,
            $menu_link_gen,
            $menu_link_title,
            $menu_parent,
            $menu_link_desc
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getFormPath($module).'/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );

        if ($path) {
            $this->assertTrue(
                file_exists($generator->getSite()->getModulePath($module).'/'.$module.'.routing.yml'),
                sprintf('%s does not exist', $module.'.routing.yml')
            );
        }
    }
}
