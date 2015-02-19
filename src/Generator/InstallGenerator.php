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
     */
    public function generate($module, $columns)
    {
        $parameters = array(
          'module_name' => $module,
          'columns' => $columns,
        );

        $this->renderFile(
          'module/install.twig',
          $this->getModulePath($module) . '/' . $module . '.install',
          $parameters
        );
    }
}
