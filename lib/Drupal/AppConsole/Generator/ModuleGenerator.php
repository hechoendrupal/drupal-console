<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class ModuleGenerator extends Generator {

  public function __construct() {}

  public function generate($module, $dir, $description, $core, $package, $routing, $setting, $structure) {

        $dir .= '/' . $module;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $parameters = array(
            'module' => $module,
            'type'    => 'module',
            'core'    => $core,
            'description'    => $description,
            'package' => $package,
        );

        $this->renderFile('module/module.info.yml.twig', $dir.'/'.$module.'.info.yml', $parameters);
        $this->renderFile('module/module.module.twig', $dir.'/'.$module.'.module', $parameters);

        if ($routing){
            $this->renderFile('module/form-routing.yml.twig', $dir.'/'.$module.'.routing.yml', $parameters);
        }

        if ($setting) {
            $this->renderFile('module/module.settings.yml.twig', $dir.'/config/'.$module.'.settings.yml',$parameters);
        }

        if ($structure) {
            drupal_mkdir($dir.'/templates');
            drupal_mkdir($dir.'/config');
            drupal_mkdir($dir.'/tests');
            drupal_mkdir($dir.'/lib');
            drupal_mkdir($dir.'/lib/Drupal');
            drupal_mkdir($dir.'/lib/Drupal/'.$module);
            drupal_mkdir($dir.'/lib/Drupal/'.$module.'/Controller');
            drupal_mkdir($dir.'/lib/Drupal/'.$module.'/Form');
            drupal_mkdir($dir.'/lib/Drupal/'.$module.'/Plugin');
            drupal_mkdir($dir.'/lib/Drupal/'.$module.'/Plugin/Block');
            drupal_mkdir($dir.'/lib/Drupal/'.$module.'/Tests');
        }
    }
}
