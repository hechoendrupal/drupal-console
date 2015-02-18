<?php
/**
 * @file
 * Contains Drupal\AppConsole\Generator\InstallGenerator.
 */
namespace Drupal\AppConsole\Generator;

class InstallGenerator extends Generator
{
    /**
     * @param  $module
     * @param  $column_name
     * @param  $column_type
     * @param  $column_description
     */
    public function generate($module, $column_name, $column_type, $column_description)
    {
        $parameters = array(
          'module_name' => $module,
          'column_name' => $column_name,
          'column_type' => $column_type,
          'column_description' => $column_description,
        );

        $this->renderFile(
          'module/install.twig',
          $this->getModulePath($module) . '/' . $module . '.install',
          $parameters
        );
    }
}
