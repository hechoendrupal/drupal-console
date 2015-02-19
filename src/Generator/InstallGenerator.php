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
     * @param  $columns
     * @param  $table_name
     */
    public function generate($module, $table_name, $columns)
    {
        $parameters = array(
          'module_name' => $module,
          'table_name' => $table_name,
          'columns' => $columns,
        );

        $this->renderFile(
          'module/install.twig',
          $this->getModulePath($module) . '/' . $module . '.install',
          $parameters
        );
    }
}
