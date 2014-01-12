<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class ModuleGenerator extends Generator {

  public function __construct() {}

  public function generate($module, $dir, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root) {

        $dir .= '/' . $module;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..') && !$skip_root) {
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

        // help to port module
        if ($skip_root) {
            $dot_info = $dir . '/' . $module . '.info';

            if (!file_exists($dot_info)) {
                throw new \RuntimeException(sprintf('Don\'t exist info file in "%s".', $dot_info ));
            }

            $info = file($dot_info);
            foreach ($info as $id => $line) {
                $data = explode('=',$line);
                switch (str_replace(' ','', $data[0])) {
                    case 'name':
                        $parameters['module'] = trim($data[1]);
                    break;
                    case 'description':
                        $parameters['description'] = trim($data[1]);
                    break;
                    case 'package':
                        $parameters['package'] = trim($data[1]);
                    break;
                }
            }
            $this->renderFile('module/module.info.yml.twig', $dir.'/'.$module.'.info.yml', $parameters);
            unlink($dot_info);
        }
        else {
            $this->renderFile('module/module.info.yml.twig', $dir.'/'.$module.'.info.yml', $parameters);
            $this->renderFile('module/module.module.twig', $dir.'/'.$module.'.module', $parameters);
        }

        if ($setting) {
            $this->renderFile('module/module.settings.yml.twig', $dir.'/config/'.$module.'.settings.yml',$parameters);
        }

        if ($controller){
          $name = 'DefaultController';
          $parameters['name'] = $name;
          $this->renderFile(
              'module/module.DefaultController.php.twig',
              $dir.'/lib/Drupal/'.$module.'/Controller/'. $name .'.php',
              $parameters
          );

          $this->renderFile('module/controller-routing.yml.twig', $dir.'/'.$module.'.routing.yml', $parameters);
        }

        if ($tests){
          $this->renderFile(
              'module/module.tests.twig',
              $dir.'/lib/Drupal/'.$module.'/Tests/'. $module .'Test.php',
              $parameters
          );
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
