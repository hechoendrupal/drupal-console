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
     * @param  $primary_key
     * @param  $indexes
     */
    public function generate($module, $table_name, $table_description, $columns, $primary_key, $indexes)
    {
        $parameters = array(
          'module_name' => $module,
          'table_name' => $table_name,
          'table_description' => $table_description,
          'columns' => $columns,
          'primary_key' => $primary_key,
          'indexes' => $indexes,
        );

        $this->renderFile(
          'module/install.twig',
          $this->getModulePath($module) . '/' . $module . '.install',
          $parameters
        );
    }
}
