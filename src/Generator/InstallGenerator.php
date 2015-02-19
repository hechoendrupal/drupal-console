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
     * @param  $table_name
     * @param  $table_description
     * @param  $columns
     */
    public function generate($module, $table_name, $table_description, $columns)
    {
        $parameters = array(
          'module_name' => $module,
          'table_name' => $table_name,
          'table_description' => $table_description,
          'columns' => $columns,
        );

        $this->renderFile(
          'module/install.twig',
          $this->getModulePath($module) . '/' . $module . '.install',
          $parameters
        );
    }
}
