<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class ModuleGenerator extends Generator {

  private $filesystem;

  public function __construct() {}

  public function generate($module, $dir, $settings, $controller, $form, $plugin, $services, $structure) {

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

        $basename = substr($bundle, 0, -6);
        $parameters = array(
            'module' => $module,
            'type'    => 'module',
            'description'    => 'wawawa cupi cupi',
            'package' => 'Sample',
            'settings' => $settings,
            'controller' => $controller,
            'form' => $form,
            'plugin' => $plugin,
            'services' => $services
        );

        $this->renderFile('module/module.info.yml.twig', $dir.'/'.$module.'.info.yml', $parameters);
        $this->renderFile('module/module.module.twig', $dir.'/'.$module.'.module', $parameters);
        $this->renderFile('module/module.routing.yml.twig', $dir.'/'.$module.'.routing.yml', $parameters);

        if ($settings) {
            $this->renderFile('module/module.settings.yml.twig', $dir.'/config/'.$module.'.settings.yml',$parameters);
        }

        if ($controller) {
            $this->renderFile('module/module.DefaultController.php.twig', $dir.'/lib/Drupal/'.$module.'/Controller/DefaultController.php',$parameters);
        }

        if ($form) {
            $this->renderFile('module/module.Form.php.twig', $dir.'/lib/Drupal/'.$module.'/Form/FormSettings.php',$parameters);
        }

        if ($plugin) {
            $this->renderFile('module/module.Block.php.twig', $dir.'/lib/Drupal/'.$module.'/Plugin/Block/DefaultController.php',$parameters);
        }

        if ($services) {
            $this->renderFile('module/services.php.twig', $dir.'/lib/Drupal/'.$module.'/Servoces/'.ucfirst($module).'.php',$parameters);
            $this->renderFile('module/module.services.yml.twig', $dir.'/'.$module.'.services.yml', $parameters);
        }

        if ($structure) {
            drupal_mkdir($dir.'/templates/');
        }
    }
}
