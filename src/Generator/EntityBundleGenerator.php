<?php

/**
 * @file
 * Contains Drupal\Console\Generator\EntityBundleGenerator.
 */

namespace Drupal\Console\Generator;

class EntityBundleGenerator extends Generator
{
    public function generate($module, $bundleName, $bundleTitle)
    {
        $parameters = [
            'module' => $module,
            'bundle_name' => $bundleName,
            'bundle_title' => $bundleTitle,
            'learning' => $this->isLearning(),
        ];

        /**
         * Generate core.entity_form_display.node.{ bundle_name }.default.yml
         */
        $this->renderFile(
            'module/src/Entity/Bundle/core.entity_form_display.node.default.yml.twig',
            $this->getSite()->getModulePath($module) . '/config/install/core.entity_form_display.node.' . $bundleName . '.default.yml',
            $parameters
        );

        /**
         * Generate core.entity_view_display.node.{ bundle_name }.default.yml
         */
        $this->renderFile(
            'module/src/Entity/Bundle/core.entity_view_display.node.default.yml.twig',
            $this->getSite()->getModulePath($module) . '/config/install/core.entity_view_display.node.' . $bundleName . '.default.yml',
            $parameters
        );

        /**
         * Generate core.entity_view_display.node.{ bundle_name }.teaser.yml
         */
        $this->renderFile(
            'module/src/Entity/Bundle/core.entity_view_display.node.teaser.yml.twig',
            $this->getSite()->getModulePath($module) . '/config/install/core.entity_view_display.node.' . $bundleName . '.teaser.yml',
            $parameters
        );

        /**
         * Generate field.field.node.{ bundle_name }.body.yml
         */
        $this->renderFile(
            'module/src/Entity/Bundle/field.field.node.body.yml.twig',
            $this->getSite()->getModulePath($module) . '/config/install/field.field.node.' . $bundleName . '.body.yml',
            $parameters
        );

        /**
         * Generate node.type.{ bundle_name }.yml
         */
        $this->renderFile(
            'module/src/Entity/Bundle/node.type.yml.twig',
            $this->getSite()->getModulePath($module) . '/config/install/node.type.' . $bundleName . '.yml',
            $parameters
        );
    }
}
